# Simple Restore

The **Simple Restore** list shows backup archives linked to this course from the course backup library, grouped by year.

## Admin vs teacher path

- **Admins** (users with `moodle/course:create` at system level, or accessing via the site-level admin nav entry) are **immediately redirected** to the Backadel **Catalogue** with an info notification. Any filter parameters (`q`, `year`, `semester`, `coursetype`, `status`) are forwarded to the catalogue URL. Admins do not see the per-course list at all.
- **Teachers** see the per-course backup list as described below.

## Semester Backups

Each row represents a backup archive. Backups are sorted newest-year first, then by filename.

Teaching backups appear in **year buckets** (one heading per calendar year). Blueprint and Other backups appear in collapsible sections above/below the year buckets.

### Actions

- **Restore** — Stages the archive and launches the Simple Restore confirm page. You confirm the operation before it executes.
- **Download** — Downloads the raw archive file directly.

## Filters

Use the **Filters** button (top right) to open the offcanvas filter panel:

- **Search** — Filter by filename keyword.
- **Year** — Limit results to a specific academic year.
- **Semester** — Filter by Spring, Fall, SecondFall, etc.
- **Course type** — Teaching or Blueprint classification.
- **Status** — *Available only* (default) hides missing or archived files. Switch to *All* to see unavailable entries.

## User Private Backups

The *User private backup area* section lists manual `.mbz` files uploaded to this course's private backup space. These are not from the semester archive.

## Notes

- Restoring **overwrites** the current course content. Student grades and enrollments are preserved (subject to restore settings configured by your administrator).
- If no backups appear, the course may not have been archived yet — contact your Moodle administrator.
- The **Available only** status filter is active by default; switch to *All* if you expect to see a file that is not showing.
