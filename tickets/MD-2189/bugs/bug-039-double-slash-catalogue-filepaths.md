# bug-039: double // in catalogue imported filepaths

**Status:** Fix in progress  
**Reported:** 2026-05-14 by Robert (via David) — Jira MD-2210 (can't delete, no perms)  
**Files:** `blocks/backadel/classes/local/migrator.php`,
           `blocks/backadel/classes/task/migrate_filesystem_adhoc.php`

## Symptom

Paths stored in `block_backadel_catalogue.filepath_full` contain double slashes,
e.g. `/srv/nfs/backups//LA-1203/backup-2024-Fall-LA-1203.mbz`. This can cause
restore to silently fail when `backadel_resolve_path()` tries to read the file
(most Linux filesystems tolerate `//` but NFS mounts can be strict).

## Root Cause

Three concatenation sites use `$dirpath . '/' . $entry` without normalising the
trailing slash on `$dirpath`:

| File | Line | Expression |
|---|---|---|
| `migrator.php` | 84 | `$filepathfull = $dirpath . '/' . $entry;` |
| `migrator.php` | 150 | `$this->migrate_file($dirpath . '/' . $basename, $source);` |
| `migrate_filesystem_adhoc.php` | 135 | `$migrator->migrate_file($dir . '/' . $basename, $source);` |

For the primary `backadel_current` path `build_runs_from_config()` normalises
correctly (`rtrim`/`ltrim`). For `migration_extra_paths` (legacy), it only
`trim()`s whitespace — leaving a trailing `/` in place.

## Fix

Normalise at the entry points of `list_archives()` and `migrate_directory()`:

```php
// migrator.php list_archives() line 84
$filepathfull = rtrim($dirpath, '/') . '/' . $entry;

// migrator.php migrate_directory() line 150
$this->migrate_file(rtrim($dirpath, '/') . '/' . $basename, $source);
```

Also in `build_runs_from_config()` for legacy extra paths:
```php
$runs[] = ['dir' => rtrim($line, '/'), 'source' => 'legacy_moodleus'];
```
