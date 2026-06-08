# Backadel Catalogue

The **Catalogue** is a **file-centric** index: each row represents a backup archive discovered on disk (or imported from a file list), not necessarily a live Moodle course. It complements **Course Search**, which is **course-centric**.

**Access is admin-only** (`block/backadel:viewresults` at the system context). Teachers reach their course backups through **Simple Restore** (the block on their course page).

## How rows get here

- **Migrate filesystem** task (scheduled or **Run Migration**) scans configured paths and records filenames, years, inferred semester, pattern classification, and file metadata.
- **Import** tools (e.g. FTP/Open LMS lists) can add **legacy** rows so you can correlate historical filenames with what is on disk today.

If a file is moved or renamed outside Backadel, the catalogue may show **missing** until you re-scan and fix paths.

## Filters

Filters live in a collapsible **offcanvas panel** — click the **Filters** button (top left, with a badge showing how many filters are active) to open it. All filters combine; the page reloads with updated query parameters.

- **`q` (free text)** — Searches **filename** and resolved course **shortname/fullname** when linkage exists.
- **Year** — Parsed year from the filename or metadata. Defaults to the current calendar year. Select **All years** to search the entire index.
- **Semester** — Spring, Fall, SecondFall, etc., when the parser extracted it.
- **Instructor** — Filter by the username of an enrolled instructor linked to the course record. Opens a picker modal showing matching users; selecting one sets the `instructor` query parameter.
- **Status** — On-disk checks: **available**, **missing**, **archived**, or **no status row**.
- **Source** — `backadel_current`, legacy moodleus, legacy Open LMS, etc. Useful when merging historical imports with live scans.
- **Pattern** — Classification from `filename_parser.php` (e.g. `backadel_modern`, `semester_legacy`).
- **Course type** — **Teaching**, **Blueprint**, or **Other**.

## Columns (typical)

- **Filename** — May be **truncated in the middle** with an ellipsis when the name is very long; hover/title still exposes the full name.
- **Semester / dept / course number** — Parsed fields when the pattern supports them.
- **Source** — Where the row originated.
- **Backup date / size** — From filesystem or import metadata.
- **Pattern** — Which filename grammar matched; helps debug misclassified files.
- **Actions** — Per-row buttons (e.g. **Restore**, **Override type**) appear on each row for users with `block/backadel:managebackups`.

Exports (if enabled) use the same filter set as the on-screen table.

## Catalogue vs Course Search

| Question | Use |
|----------|-----|
| "Do we have a file for 2024 Spring MATH…?" | **Catalogue** |
| "What is the backup status of course id 12345?" | **Course Search** |
| "Is this `.mbz` still on disk?" | **Catalogue** (status column) |

## Operations

Restore flows generally go through **Course Backups** (year picker) or native restore after a file is staged — see **restore** help for the hand-off.

## Troubleshooting

- **Empty after filter** — Widen year or clear pattern; confirm migration has run after new files arrived.
- **Missing status** — Path or permission issue; check `catalogue_path_prefix` rewrites in settings if servers moved.
- **Wrong pattern** — File may not match any grammar; it may fall into **unknown** until naming is normalized.

Treat the catalogue as a **discovery layer** over storage; always verify critical restores against a fresh file check.
