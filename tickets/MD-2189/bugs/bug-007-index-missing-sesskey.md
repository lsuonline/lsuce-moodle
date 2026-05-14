# Bug-007 — index.php form missing sesskey → results.php throws "missingparam sesskey"

**Date:** 2026-05-12
**Files:** `blocks/backadel/index.php` (form), `blocks/backadel/results.php:38` (require_sesskey)
**Severity:** High — "Build Search Query" button is completely broken

## Symptom

Clicking **Build Search Query** on `/blocks/backadel/index.php` redirects to
`/blocks/backadel/results.php` and immediately throws:

> core\exception\moodle_exception — A required parameter (sesskey) was missing

Stack: `require_sesskey` ← `confirm_sesskey` ← `required_param` ← `results.php:38`

## Root cause

`index.php:118-120` builds the search form:
```php
echo html_writer::tag('form', $formcontainer, [
    'id' => 'query', 'action' => 'results.php', 'method' => 'POST'
]);
```

The form contains no `sesskey` hidden input. `results.php:38` calls `require_sesskey()`, which
reads `$_POST['sesskey']` / `$_GET['sesskey']` and throws when neither is present.

## Fix

Add a `sesskey` hidden field inside the form in `index.php`. The simplest approach is to append
it inside `$formcontainer` before rendering:

```php
$formcontainer = html_writer::tag('div', $controls . $group . $button, [
    'id' => 'form_container'
]) . html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'sesskey',
    'value' => sesskey(),
]);
```

Or more cleanly, add it as a separate hidden input inside the form tag directly.
