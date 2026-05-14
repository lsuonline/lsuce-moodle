# Bug 014 — Migration scans zero files: wrong config key in migrate_filesystem.php

## Symptom
Running the migration from `migrate.php` queues the adhoc task successfully, but the catalogue row count stays unchanged (e.g., remains at 214). No files are indexed.

## Root cause
`blocks/backadel/classes/task/migrate_filesystem.php` reads:

```php
$rootdir = get_config('block_backadel', 'backupdir');
```

But the admin settings page (`settings.php`) stores the backup path under:

```php
new backadel_path_setting('block_backadel/path', ...)
```

The key `block_backadel/backupdir` is never written by any admin settings form. It will always return `false`, causing the task to log "backupdir not configured; skipping." and exit immediately.

The correct key is `block_backadel/path`, which is a relative path (e.g. `/backadel/`) that must be joined with `$CFG->dataroot` to form the absolute scan root.

## Fix
In `migrate_filesystem.php`:
- Change `get_config('block_backadel', 'backupdir')` to use `$CFG->dataroot . get_config('block_backadel', 'path')`
- Update the variable name and error message accordingly

Also add a visible warning banner on `migrate.php` when `block_backadel/path` is empty, so admins can see at a glance that the scan directory is not configured before running the migration.

## Files changed
- `blocks/backadel/classes/task/migrate_filesystem.php`
- `blocks/backadel/migrate.php`
- `blocks/backadel/lang/en/block_backadel.php` (new warning string)
