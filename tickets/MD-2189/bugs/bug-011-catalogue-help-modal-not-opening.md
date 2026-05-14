# BUG-011 — Catalogue page help modal does not open on click

**Severity:** Medium  
**Found by:** e2e Wave 15 run (2026-05-12), assertions in Phase 4 (step "catalogue help modal") and Phase 5 ("help modal catalogue")  
**Status:** Open

---

## Symptom

On `catalogue.php`, the help button (`[data-action="show-help"]`) is present in the DOM (assertions for "help button present" pass), but clicking it does not open the modal.

```
[OK] catalogue: help button present
[FAIL] catalogue help modal: modal did not open after click
[FAIL] help modal catalogue: exception (Page.wait_for_selector: Timeout 8000ms exceeded.
  waiting for locator(".modal.show, .modal[aria-modal]") to be visible)
```

The same help modal works correctly on `search.php`, `migrate.php`, and `failed.php`.

---

## Likely root cause — `instructors_modal.init()` JS error cascades to silence help.js

`catalogue.php` initialises **two** AMD modules before `$OUTPUT->header()`:

```php
// blocks/backadel/catalogue.php:174-180
$PAGE->requires->js_call_amd('block_backadel/help', 'init');
$PAGE->requires->js_call_amd('block_backadel/instructors_modal', 'init', [[
    'modalTitle'  => get_string('catalogue_instructors_modal_title', 'block_backadel'),
    'colUsername' => get_string('catalogue_instructors_col_username', 'block_backadel'),
    'colFullname' => get_string('catalogue_instructors_col_fullname', 'block_backadel'),
    'none'        => get_string('catalogue_instructors_none', 'block_backadel'),
]]);
```

If `instructors_modal.js` throws during `init()` (e.g. the inline module is not in the AMD build, or a missing dependency), the AMD module loader may suppress subsequent callbacks — including the `help.js` `data-action="show-help"` click listener — leaving the button inert.

**Other pages** only call `js_call_amd('block_backadel/help', 'init')` (no second AMD call), so they work.

---

## How to confirm

1. Open `catalogue.php` in browser devtools → Console tab.
2. Click the `?` help button.
3. Look for any JS error (e.g. `Cannot read properties of undefined`, `Module not found`, `instructors_modal`).

If there is a console error from `instructors_modal`, that's the root cause.

Also check:
- Is `blocks/backadel/amd/build/instructors_modal.min.js` present and up to date? (`ls -la blocks/backadel/amd/build/`)
- Does `blocks/backadel/amd/src/instructors_modal.js` export an `init` function?
- After `grunt amd --root=blocks/backadel` + `purge_all_caches()`, does the modal open?

---

## Files to investigate

- `blocks/backadel/amd/src/instructors_modal.js` — does `init()` exist and what does it do?
- `blocks/backadel/amd/build/instructors_modal.min.js` — is the build current?
- `blocks/backadel/catalogue.php:174-180` — AMD init order; try swapping order or adding error handling

---

## Fix strategy

**Step 1 — Verify build is current:**
```bash
./run-docker-exec.sh bash -c 'cd /var/www/html && npx grunt amd --root=blocks/backadel'
./run-docker-exec.sh php -r "define('CLI_SCRIPT', true); require('/var/www/html/config.php'); purge_all_caches(); echo 'done';"
```
Then re-test in browser devtools.

**Step 2 — If instructors_modal.js throws, fix the JS error** (missing export, wrong dependency, etc.)

**Step 3 — If the issue is AMD load ordering**, wrap the `instructors_modal` init in a try/catch in the JS module's `init()` function so failures are isolated:

```js
// amd/src/instructors_modal.js
export const init = (opts) => {
    try {
        // ... existing init code
    } catch (err) {
        window.console.error('block_backadel/instructors_modal init failed:', err);
    }
};
```

**Step 4 — Update the e2e** to take a console screenshot / log on catalogue.php before clicking help, so future failures include JS error context.

---

## Notes

- The `[data-action="show-help"]` click handler is registered in `help.js` via event delegation on `document`. If `instructors_modal.init()` throws synchronously during AMD module execution, it can prevent the help listener from being attached.
- This failure is **specific to catalogue.php** — other admin pages only load `help.js` and work fine.
- Two separate e2e test phases both fail for this modal, confirming it's reproducible.
