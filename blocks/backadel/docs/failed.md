# Failed Backups

The **Failed Backups** page lists courses whose Backadel status is **`FAIL`**. These are backups that did not complete successfully and need attention before you expect a fresh `.mbz`/`.zip`.

## Why a course lands here

Common causes:

- **Disk full** or path not writable at backup time
- **Timeouts** or PHP limits on very large courses
- **Plugin or data** faults during export
- **Concurrency** issues if multiple runners touched the same job

Always read the **cron / task logs** and Moodle error output around the timestamp of the failure—Backadel flags the row, but the root cause is usually in the server log.

## What “Re-schedule” / re-queue does

From this report you select rows and reschedule; from **Course Search** you can **Re-queue** individual courses. Both paths reset the status so the next **scheduled task** pass attempts the backup again.

**Re-queue does not fix underlying problems.** If the disk or permission error persists, the course will fail again.

## Recommended workflow

1. Open the **Failed** list and note **shortname** and approximate time.
2. Check **Site administration → Reports → Logs** and web server / PHP logs.
3. Fix the cause (space, permissions, broken block, etc.).
4. **Re-queue** a single course as a test before bulk re-scheduling.
5. After success, confirm the row disappears from Failed and appears as **Complete** on Course Search.

## When not to re-queue yet

- Course is **absurdly large** — raise timeouts or use split strategies per institutional policy.
- **Third-party tool** is erroring — disable or update the offending plugin first.
- **Storage path** is wrong — correct **Global settings** before flooding cron with new failures.

## Navigation

Use the **tab strip** at the top to jump to **Course Search** or **Catalogue** without duplicate links in the content area.

## Related

- **Course Search** — Filter by status `FAIL` to reconcile failures against the full course list.
- **Cron** — Ensure `backuptask` and related tasks are actually running.

Treat every failed row as **action required** until logs show a clean retry.
