# Backadel Catalogue

The **Catalogue** is a **file-centric** index: each row represents a backup archive discovered on disk (or imported from a file list), not necessarily a live Moodle course. It complements **Course Search**, which is **course-centric**.

## How rows get here

- **Migrate filesystem** task (scheduled or **Run Migration**) scans configured paths and records filenames, years, inferred semester, pattern classification, and file metadata.
- **Import** tools (e.g. FTP/Open LMS lists) can add **legacy** rows so you can correlate historical filenames with what is on disk today.

If a file is moved or renamed outside Backadel, the catalogue may show **missing** until you re-scan and fix paths.

## Filters

You can combine filters; the form posts/refreshes with query parameters:

- **`q` (free text)** — Searches **filename** and resolved course **shortname/fullname** when linkage exists. Example: `catalogue.php?q=My+Media`.
- **Year** — Parsed year from the filename or metadata. `year=2024` limits to that calendar year in the index.
- **Semester** — Spring, Fall, SecondFall, etc., when the parser extracted it.
- **Source** — `backadel_current`, legacy moodleus, legacy Open LMS, etc. Useful when merging historical imports with live scans.
- **Pattern** — Classification from `filename_parser.php` (e.g. `backadel_modern`, `semester_legacy`).
- **Status** — On-disk checks: **available**, **missing**, **archived**, or **no status row**.

**All years** clears the year filter so you can search across the whole index.

## Columns (typical)

- **Filename** — May be **truncated in the middle** with an ellipsis when the name is very long; hover or title text should still expose the full name in the UI.
- **Semester / dept / course number** — Parsed fields when the pattern supports them.
- **Source** — Where the row originated.
- **Backup date / size** — From filesystem or import metadata.
- **Pattern** — Which filename grammar matched; helps debug misclassified files.

Exports (if enabled) use the same filter set as the on-screen table.

## Catalogue vs Course Search

| Question | Use |
|----------|-----|
| “Do we have a file for 2024 Spring MATH…?” | **Catalogue** |
| “What is the backup status of course id 12345?” | **Course Search** |
| “Is this `.mbz` still on disk?” | **Catalogue** (status column) |

## Operations

Restore flows generally go through **Course Backups** (year picker) or native restore after a file is staged—see **restore** help for the hand-off.

## Troubleshooting

- **Empty after filter** — Widen year or clear pattern; confirm migration has run after new files arrived.
- **Missing status** — Path or permission issue; check `catalogue_path_prefix` rewrites in settings if servers moved.
- **Wrong pattern** — File may not match any grammar; it may fall into **unknown** until naming is normalized.

Treat the catalogue as a **discovery layer** over storage; always verify critical restores against a fresh file check.
