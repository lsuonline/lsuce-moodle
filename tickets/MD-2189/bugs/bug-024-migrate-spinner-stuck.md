# BUG-024 — migrate.php "Migration is running" spinner stuck

**Severity:** Low  
**Found by:** PDF review page 34 (29-help-modal-migrate.png background)

## Symptom
When the browser visits `migrate.php` after the e2e CLI migration run, `migrate_status.js` polls the DB and finds the adhoc task still queued → sets `data-region="migrate-spinner"` visible + disables the Run button. The spinner never clears because the CLI task completed but left a record.

## Root cause
`migrate_status.js` `applyState('queued', ...)` shows the spinner div. After CLI execution, the task row may persist in `mdl_task_adhoc` with a completed state that the JS interprets as still queued.

David's decision: **remove the spinner entirely** — simpler UX, fewer moving parts.

## Fix
1. `blocks/backadel/migrate.php`: remove the `[data-region="migrate-spinner"]` div
2. `blocks/backadel/amd/src/migrate_status.js`: remove spinner show/hide from `applyState()`; keep the count-polling and button-disable while queued, but drop the spinner element references
3. Rebuild AMD: `grunt amd --root=blocks/backadel`
