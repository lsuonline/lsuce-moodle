# Bug 015 — Migrate page spinner shows "Migration is running" persistently

## Symptom
The `migrate.php` page shows the blue "Migration is running" spinner persistently, even after the adhoc task has completed (or was never queued).

## Root cause analysis

### PHP / initial HTML — not the cause
The spinner div is rendered with Bootstrap's `d-none` class by default:
```php
echo html_writer::div(..., 'd-none d-flex align-items-center mb-3', [...]);
```
It cannot appear without JS removing `d-none`. So the issue is not in the PHP template.

### JS polling — correct logic
`migrate_status.js` calls `getMigrateStatus()` on page load and every 5 seconds while `queued`. It correctly adds/removes `d-none` based on `status`. JS logic is not buggy.

### Web service — the real cause (indirect)
`get_migrate_status.php` queries `task_adhoc` for a row matching the adhoc task class. If a task row is stuck (process crashed, task failed mid-run leaving a `timestarted` set but no completion), the WS returns `status: 'queued'` indefinitely and the JS keeps the spinner visible.

Additionally, on rrusso the migration task runs but scans 0 files (see Bug 014 — wrong config key), completes instantly, and the task row IS deleted normally. So the persistent spinner is caused by Bug 014 triggering fast: the task is queued → cron picks it up → scans nothing → exits → row deleted. The spinner appears briefly and disappears. If cron is not running, the task row stays in `task_adhoc` and the spinner stays.

The true fix for the persistent-spinner scenario is:
1. Bug 014 fix (correct config key) so the task actually does meaningful work
2. A staleness guard in the WS: if `timestarted` is set and older than 30 min, treat as stale/idle

## Fix applied
- Added staleness check to `get_migrate_status.php`: tasks with `timestarted` older than 1800 seconds are reported as `idle` to prevent the spinner from hanging indefinitely on crashed tasks.

## Files changed
- `blocks/backadel/classes/external/get_migrate_status.php`
