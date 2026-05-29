# Backup And Delete (Backadel) — Overview

Backadel extends Moodle with scheduled and on-demand **course backups**, a searchable **catalogue** of backup files on disk, and workflows to **queue**, **re-queue**, **delete archived courses**, and **restore** from catalogue entries.

## Backup vs archive vs delete

- **Backup** — Moodle exports a course to a file (`.mbz` or `.zip`, depending on naming) in the configured storage path. Cron and the **Course Search** page drive when backups run.
- **Archive** — In day-to-day language, an *archive* is the backup file plus any catalogue row that points at it. The file may still exist on disk even after the live course is removed from Moodle.
- **Delete archive** (action on Course Search) — Removes the **course** from Moodle and clears or updates Backadel **status** for that course. It does **not** necessarily remove the `.mbz`/`.zip` from disk; the catalogue may still list the file until you clean storage separately.

Always confirm what an action removes (course only, status only, or files) before using it on production data.

## Main admin surfaces

| Area | Purpose |
|------|---------|
| Global settings | Storage path, naming suffix, roles, size limits, catalogue/migration options |
| **Course Search** | Find courses by name/category/status; queue backup, re-queue failures, delete archive |
| **Catalogue** | Browse backup files indexed from disk and imports; filter by year, pattern, source, status |
| **Failed Backups** | Courses stuck in `FAIL`; re-schedule from here |
| **Delete Archives** | Successfully backed-up courses ready for archive deletion workflow |
| **Run Migration** | Scan filesystem and refresh catalogue rows |
| **Course Backups** | Pick a year, see files, hand off to the native restore wizard |

## Who can do what

Capabilities are defined in the block’s `access.php`. Typical split:

- **Manage backups** — Course Search actions (queue / re-queue / delete archive) and restore hand-off.
- **View results** — Read-only style access to catalogue and status tables (exact mix depends on site role assignments).
- **Manage migration** — Trigger migration scan from **Run Migration**.
- **Delete archive** — Permission to confirm deletion workflows on the Delete Archives page.

Site administrators should assign these only to staff who understand course data loss risk.

## Cron and tasks

Backadel relies on **scheduled tasks** for backup execution and catalogue migration. If backups stay “Queued” forever, check Site administration → Server → Tasks and server cron.

## Getting more detail

Use the **?** help button on each page for context-specific guidance, or open the matching topic from this overview in the help modal where wired.

## Data flow (high level/backing)

1. Operators configure the **storage path** and backup naming under **Global settings**.
2. **Course Search** schedules work; **cron** produces files on disk and writes/updates **`block_backadel_statuses`**.
3. **Migrate filesystem** reads the same storage tree (plus optional extra paths) and upserts **`block_backadel_catalogue`**.
4. **Catalogue** and **Course Backups** read that index for admins; **restore** stages a file into Moodle’s temp area and defers to core **restore**.

If any step is skipped—especially migration after copying thousands of files—the UI will look “empty” even though disk is full.

## Settings worth reviewing

- **Archive suffix / roles** — Control how filenames are built (who is named in the archive).
- **Blueprint keywords** — Affects teaching vs blueprint badges on **Course Backups**.
- **Excluded categories & extra paths** — Narrow or widen what the migration scan considers.
- **Path prefix rewrite** — Critical after a dataroot or NFS move so catalogue paths still resolve.

## When something goes wrong

1. Check **Failed Backups** and server logs around the failure time.
2. Confirm the storage path exists and is writable.
3. For missing files in **Catalogue** or **Course Backups**, run migration and verify path-prefix settings if data was moved.

This tool assumes you are comfortable with Moodle’s native **backup** and **restore** concepts.
