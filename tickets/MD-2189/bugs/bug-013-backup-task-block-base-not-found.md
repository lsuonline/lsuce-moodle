# Bug 013 — backup_task: "Class block_base not found" in cron context

**Status:** FIXED
**File:** `blocks/backadel/classes/task/backup_task.php`
**Discovered:** 2026-05-12 (rrusso/staging, admin/tasklogs.php)

---

## Error

```
Exception - Class "block_base" not found
File: blocks/backadel/block_backadel.php
Line: 35
Stack trace:
  line 35 of /blocks/backadel/block_backadel.php   ← class block_backadel extends block_base
  line 27 of /blocks/backadel/classes/task/backup_task.php
  line 217 of /lib/classes/component.php
  ... (reportbuilder task log rendering)
```

The error surfaces when the scheduled/adhoc backup task runs (or when the task log is rendered in `admin/tasklogs.php`).

---

## Root Cause

`backup_task.php` contained this line at the top of the file (before the class declaration):

```php
require_once($CFG->dirroot . '/blocks/backadel/block_backadel.php');
```

`block_backadel.php` declares `class block_backadel extends block_base`. In a normal Moodle page request, the block subsystem loads `block_base` (via `lib/blocklib.php`) before any block class file is touched. In a **scheduled/adhoc task (cron) context**, `block_base` is never loaded, so requiring `block_backadel.php` directly triggers a fatal "Class block_base not found" error.

`block_backadel.php` is not needed by `backup_task.php`. The task's `execute()` method calls only `begin_backup_task()`, which is defined in the same file and depends on `backadel_backup_course()`, `generate_suffix()`, and `backadel_email_admins()` — all of which live in `lib.php`.

---

## Fix Applied

Removed the unnecessary `require_once` of `block_backadel.php`.

**Before:**
```php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/backadel/block_backadel.php');
require_once($CFG->dirroot . '/blocks/backadel/lib.php');
```

**After:**
```php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/backadel/lib.php');
```

Only `lib.php` is needed; the block class file is not referenced anywhere in the task.

---

## Test Gap

No PHPUnit tests currently cover any of the three task classes in `blocks/backadel/classes/task/`:

- `backup_task.php` — no test
- `migrate_filesystem.php` — no test
- `migrate_filesystem_adhoc.php` — no test

The existing test suite under `blocks/backadel/tests/` covers forms, the filename parser, and catalogue/table logic, but does **not** exercise `begin_backup_task()`, the scheduled task's `execute()` method, or either of the filesystem migration tasks.

This exact bug (cron-context bootstrap assumptions) would have been caught by a unit test that instantiates `backup_task` and calls `execute()` in an isolated Moodle PHPUnit environment.

**Recommendation:** Add a `blocks/backadel/tests/task/backup_task_test.php` that bootstraps the task without a full block subsystem load and verifies `get_name()` plus a no-op path through `execute()` with mocked DB records returning an empty set.
