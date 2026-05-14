# Bug MD-2189 / bug-031 — Snap sidebar conflicts with Backadel/simple_restore filter offcanvas

## Symptom

On pages that load the Bootstrap-style filter offcanvas (for example Backadel catalogue or Simple restore list when the filter panel renders), Snap’s persistent right-edge navigation chrome — the **`#snap-sidebar-menu` rail**, its **peek tab**, and the **`.snap-sidebar-menu-trigger`** control — stops responding reliably: taps/clicks appear to do nothing, or the sidebar looks obscured (“hidden/broken”). The course TOC / settings flows that Snap wires through **`#page`** and **`.drawer`** may feel similarly impaired while the filter layer is visually active.

## Where the panels come from

| Area | Implementation |
|------|----------------|
| **Backadel** | `blocks/backadel/catalogue.php` queues `block_backadel/filter_panel` init and renders `block_backadel\output\filter_panel` (lines 205–236). Mustache: `blocks/backadel/templates/local/filter_panel.mustache` — **`offcanvas offcanvas-end`**, **`data-bs-toggle="offcanvas"`** on the trigger. |
| **Simple restore** | `blocks/simple_restore/list.php` uses the **same AMD module and shared Mustache** (`$PAGE->requires->js_call_amd('block_backadel/filter_panel', 'init');` at lines 241–242 and `render_from_template('block_backadel/local/filter_panel', …)` at lines 322–327). |
| **Snap sidebar** | `theme/snap/amd/src/sidebar_menu.js` (**`#snap-sidebar-menu`**, **`.snap-sidebar-menu-trigger`**). Layout/CSS: **`theme/snap/scss/sidebarmenu.scss`** (`$sidebar-z-index: 1050`). Snap also uses **`#page.offcanvas`** for legacy layout shift when toggling settings / drawers — **`theme/snap/scss/_core.scss`** lines 1615–1617 and **`theme/snap/amd/src/snap.js`** lines 558–559, 591 — this is **`#page`**, not **`body.offcanvas-open`**, and is not toggled by the block filter code. |

*Note:* Snap ships no single `theme/snap/styles.css`; compiled rules live under `theme/snap/scss/` (built into Moodle’s aggregated CSS).

## Root cause (specific)

### 1. Z-index stacking — full-screen overlay above Snap’s rail (**primary**)

`blocks/backadel/amd/src/filter_panel.js` injects positioning and backdrop CSS:

```22:71:blocks/backadel/amd/src/filter_panel.js
/* block_backadel: offcanvas positioning — Snap does not ship Bootstrap 5 offcanvas CSS */
.offcanvas {
    position: fixed !important;
    …
    z-index: 1055;
    …
}
…
body.offcanvas-open::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1054;
}
```

When `openOffcanvas()` runs (**lines 81–94**), it adds **`show`** on the panel and **`offcanvas-open`** on **`document.body`**.

Snap intentionally keeps the sidebar **below** that layer:

```7:23:theme/snap/scss/sidebarmenu.scss
$sidebar-z-index: 1050;

.snap-sidebar-menu {
    …
    z-index: $sidebar-z-index;
```

The trigger peaks slightly above the bar but stays at **`$sidebar-z-index + 1` (1051)**:

```96:109:theme/snap/scss/sidebarmenu.scss
.snap-sidebar-menu-trigger {
    …
    z-index: $sidebar-z-index + 1; // Ensure button is above sidebar
```

**Effect:** Whenever the filter offcanvas is open, the **`body::before` backdrop at 1054** and the **`.offcanvas` panel at 1055** paint **above** the Snap rail (1050–1051). Pointer events intended for Snap hit the opaque overlay instead. The global **`click`** listener in **`filter_panel.js` (lines 159–167)** then treats outside clicks as dismiss — so the **first** user click often **closes the filter** rather than toggling Snap, which reads as “the sidebar doesn’t work.” With the drawer closed (`body` without `offcanvas-open`), stacking returns to normal unless another bug leaves state dirty.

There is **no ID collision**: filter instances use scoped ids such as **`backadel-catalogue-filters`** / **`sr-list-filters`** from the Mustache `collapseid` variable.

### 2. Not the same as `#page.offcanvas` (**clarification only**)

`_core.scss` applies **`left: -315px`** when **`#page`** has class **`offcanvas`** — unrelated to **`body.offcanvas-open`**. Naming overlap is misleading but not the mechanical cause of Snap rail blocking.

### 3. `simple_restore` “AMD module” attribution

Simple restore **does not** ship its own filter offcanvas AMD in `blocks/simple_restore/amd/src/`; it **requires `block_backadel/filter_panel`** and the shared **`block_backadel/local/filter_panel`** template. Any fix belongs in **`block_backadel`** and/or **`theme_snap`**, optionally with layout scoping.

## Proposed fix options

1. **Raise Snap sidebar stacking above the block backdrop (theme change)**  
   Increase **`$sidebar-z-index`** (and matching trigger/rule offsets) in **`theme/snap/scss/sidebarmenu.scss`** — e.g. to **≥ 1056** — so the course navigation rail stays interactive when an admin filter drawer is open, *or* add a narrower scope (wrapper class on affected admin pages only) if raising globally conflicts with Moodle modals.  
   *Files:* `theme/snap/scss/sidebarmenu.scss`; possibly **`theme_snap` JS** if anything assumes stacking order elsewhere.

2. **Lower / reshape the injected overlay (block/plugin change)**  
   In **`blocks/backadel/amd/src/filter_panel.js`**, reduce **`z-index`** values for **`.offcanvas`** and **`body.offcanvas-open::before`** below **1050**, or replace the full-viewport `body::before` pseudo-backdrop with a sibling backdrop `div` limited to the main content column (excluding the Snap gutter), or tune **`pointer-events`** so only the dimmed region blocks clicks.  
   *Files:* `blocks/backadel/amd/src/filter_panel.js` (and **`amd/build/filter_panel.min.js`** after grunt).

3. **Use core Bootstrap offcanvas stacking consistently**  
   If Moodle’s aggregated CSS exposes Bootstrap 5 `offcanvas` / `offcanvas-backdrop`, prefer core’s Bootstrap layering (for example **`core/offcanvas`**) instead of injecting parallel z-index semantics, aligning backdrop and rail with SCSS **`$zindex`** tokens so Snap and overlays don’t silently disagree. Higher effort but fewer one-off clashes.  
   *Files:* `blocks/backadel/amd/src/filter_panel.js`, `blocks/backadel/templates/local/filter_panel.mustache`, **`config.php`** / theme SCSS imports as needed.

## Files to touch (for implementers)

- **`blocks/backadel/amd/src/filter_panel.js`** — injected CSS, **`openOffcanvas` / `closeOffcanvas`**, global click handling.  
- **`theme/snap/scss/sidebarmenu.scss`** — **`$sidebar-z-index`** and `.snap-sidebar-menu-trigger` layering.  
- **`blocks/backadel/templates/local/filter_panel.mustache`** — only if HTML structure/backdrop markup changes (e.g. dedicated backdrop element).  
- **`blocks/simple_restore/list.php`** / **`blocks/backadel/catalogue.php`** — only if page-specific requirements or **`PAGE->requires`** change.  
- Rebuild **`blocks/backadel/amd/build/filter_panel.min.js`** after AMD edits.

## References (code)

| File | Lines / notes |
|------|----------------|
| `blocks/backadel/amd/src/filter_panel.js` | 15–167 — inject CSS (**z-index 1054–1055**), **`body.offcanvas-open`**, listeners |
| `theme/snap/scss/sidebarmenu.scss` | 7, 23, 109 — **1050 / 1051** |
| `theme/snap/amd/src/sidebar_menu.js` | 28–39 — **`#snap-sidebar-menu`** selectors |
| `theme/snap/scss/_core.scss` | 1615–1617 — **`#page.offcanvas`** layout (separate concern) |
| `theme/snap/amd/src/snap.js` | 552–591 — toggles **`#page.offcanvas`** for admin/message/feeds triggers |
| `blocks/backadel/templates/local/filter_panel.mustache` | **`.offcanvas.offcanvas-end`**, **`data-bs-toggle/target`** |
