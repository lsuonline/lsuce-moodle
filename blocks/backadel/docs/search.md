# Course Search

**Course Search** lists **courses** from your site together with their Backadel **status**. It is *not* the file browser; use **Catalogue** to inspect backup filenames on disk.

## Filters

Use the form at the top of the page:

- **Search text (`q`)** — Matches **short name** and **full name** (partial matches). You can also drive the same filter from the URL, e.g. `search.php?q=MATH`.
- **Category** — Limit rows to one course category, or leave broad.
- **Status** — Filter by Backadel status: queued, complete, failed, deleted, or courses with no status row yet. URL example: `search.php?q=a&status=SUCCESS`.

Until at least one filter is active, the page shows an instructions notice instead of a full result set—this avoids loading every course by accident.

## Actions (per row)

Depending on permissions and current status, you may see:

- **Queue backup** — Schedules a new backup for the course (picked up by cron).
- **Re-queue** — Use after a failure or when you want the job to run again; resets the workflow so cron can retry.
- **Delete archive** — Removes the **Moodle course** and associated Backadel status for that row. **Irreversible** for the course. Does not replace careful review of what should stay on disk.

Confirm every destructive action in the modal.

## Status icons / labels

Common states:

| Label | Meaning |
|-------|---------|
| Queued | Backup is lined up for the next cron pass |
| Complete | Last backup finished successfully |
| Failed | Last attempt failed — inspect Failed Backups and logs |
| Deleted | Course/archive workflow marked deleted in Backadel |

Exact wording on screen comes from the current language pack.

## Typical workflows

1. **Find courses needing backup** — Filter by category or empty status, then queue.
2. **Clear failures** — Open **Failed Backups** for detail; fix root cause, then re-queue from Course Search or reschedule from the failed list.
3. **Free space after archival** — Identify completed backups, verify files exist in **Catalogue**, then use delete-archive only when policy allows.

## URL parameters (power users)

The filter form submits with `q`, `category`, and `status`. Bookmarking or linking with those parameters is safe for repeating a search.

## Related pages

- **Catalogue** — Per-file view, year/pattern filters, restore from disk index.
- **Failed Backups** — Focused table of `FAIL` rows only.

When in doubt, run a narrow filter first (short department code or unique fragment of the full name).
