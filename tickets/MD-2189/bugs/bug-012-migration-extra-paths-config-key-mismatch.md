# BUG-012 — migration_extra_paths admin setting has no effect on migrate_filesystem task

**Severity:** Medium (silent misconfiguration — admin sets value, nothing happens)  
**Found by:** Code review while building seed_full_corpus.py (2026-05-12)  
**Status:** Fixed  

**Resolution note:** `blocks/backadel/classes/task/migrate_filesystem.php` now reads **`get_config('block_backadel', 'migration_extra_paths')`** (see `execute()` where `$legacyraw` is loaded, ~line 50), matching `blocks/backadel/settings.php`. The mismatch described below **no longer applies** to the current tree.

---

## Symptom (historical)

The admin UI exposed a "migration extra paths" textarea at `blocks/backadel/settings.php:91`:

```php
'block_backadel/migration_extra_paths'
```

But `blocks/backadel/classes/task/migrate_filesystem.php:45` read:

```php
$legacyraw = get_config('block_backadel', 'legacy_dirs');
```

These were **different config keys**. Any directories entered in the admin UI were stored under `migration_extra_paths` but the task read `legacy_dirs`. The task would always scan only `backupdir` (plus whatever was in `legacy_dirs`, which could only be set via direct DB or PHP CLI — never via the admin UI).

---

## Files

- `blocks/backadel/settings.php:91` — stores to `migration_extra_paths`
- `blocks/backadel/classes/task/migrate_filesystem.php` — reads `migration_extra_paths` (Fixed)

---

## Fix (two options) — implemented as Option A

**Option A — Update the task to read `migration_extra_paths`:** ✅ Done in codebase.

```php
// migrate_filesystem.php
$legacyraw = get_config('block_backadel', 'migration_extra_paths');
```

**Option B — Rename the settings key to `legacy_dirs`:** Not taken; Option A preserves the admin UI as documented.

---

## Impact (historical)

Until fixed, the only way to configure additional scan directories was:

```bash
php /var/www/html/admin/cli/cfg.php --component=block_backadel --name=legacy_dirs --set=/path/to/dir
```

The `seed_full_corpus.py` tool may have used `legacy_dirs` as a workaround when this bug was open.
