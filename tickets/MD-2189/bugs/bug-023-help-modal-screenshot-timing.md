# BUG-023 — Help modal screenshots taken before Bootstrap animation completes

**Severity:** Low  
**Found by:** PDF review pages 33–37 (help modal shots show blank/closed modal)

## Symptom
All help modal screenshots (29-help-modal-catalogue.png, 29-help-modal-migrate.png, 29-help-modal-search.png, 38-catalogue-help-modal.png, help-modal-catalogue.png) show the modal either not open or partially animated.

## Root cause
`test_help_modal_opens()` and `test_catalogue_help_modal_opens()` call `shot()` immediately after `wait_for_selector(".modal.show")`. Bootstrap 5 modal fade animation takes ~300ms after `.show` is set — the screenshot races the CSS transition.

## Fix
Add `page.wait_for_timeout(500)` after each `wait_for_selector(".modal.show")` call (before `shot()`) in all help-modal test functions.
