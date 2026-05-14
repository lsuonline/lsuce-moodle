# Bug-005 (QA Note) — "Unknown backup format" on Restore click

**Date:** 2026-05-12
**Severity:** Not a code bug — expected QA behavior with fixture data
**Reported by:** Live testing on https://lsuce-dvdcastro.ngrok.dev

---

## Symptom

After clicking **Restore** on the Course Backups page (`/blocks/backadel/restore.php`), Moodle's native restore wizard shows:

> **Unknown backup format**
> The selected file is not a valid Moodle backup file and can't be restored.

URL reached: `/backup/restore.php?contextid=1&pathnamehash=<sha1>&contenthash=<sha1>`

---

## Root cause

The fixture `.zip` files seeded into BACKUPDIR for QA (via the live-testing fixture seeder) are **dummy files** — they have realistic filenames (e.g. `2024SpringMATH1201001_jsmith_1700000001.zip`) but contain no actual Moodle backup content. Specifically, they do not contain `moodle_backup.xml`, which is what Moodle's restore wizard looks for to detect a valid backup archive.

The `restore_handoff::stage_and_redirect()` fix (bug-003) is working as designed:
- ✅ File resolved via `backadel_resolve_path()`
- ✅ File staged into user's backup file area via `file_storage::create_file_from_pathname()`
- ✅ Redirect to `/backup/restore.php?pathnamehash=...&contenthash=...` (native wizard path)
- ✅ Native wizard correctly rejects the dummy file

---

## Expected production behavior

In production, all backadel `.zip` files are real Moodle course backups produced by `mdl_backup_controller`. They contain `moodle_backup.xml` and all required inner structure. Moodle's restore wizard will accept them regardless of the `.zip` extension (it inspects internal structure, not extension).

---

## How to test with real data

To verify the full restore flow end-to-end:

1. In Moodle: go to any course → Course administration → Backup → run a manual backup → download the `.mbz` (rename to `.zip` if needed).
2. Copy the `.zip` into the BACKUPDIR path (e.g. `/var/www/moodledata/backadel/`).
3. Run the migration scan: **Backup And Delete → Run Migration** → "Run migration scan now".
4. Go to **Course Backups**, select the current year → click **Restore** on the new row.
5. Expect: Moodle's native restore wizard at step 1 (Confirm) with the course backup recognised.

---

## No code changes needed.
