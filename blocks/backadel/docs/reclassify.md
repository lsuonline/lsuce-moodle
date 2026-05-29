## Re-classify Catalogue Entries

This tool re-parses filenames for rows that already exist in the catalogue. Use it after updating filename-recognition patterns or fixing classification bugs — it lets you correct stale rows without re-scanning the entire filesystem.

**What gets updated:**
- Pattern (semester_legacy, backadel_instructor, etc.)
- Year, semester, department, course number
- Course type (teaching / blueprint / other)
- Instructor list (stale teacher rows are removed and replaced)
- File availability — rows whose files can no longer be found on disk are marked *missing*

**What is preserved:**
- Manual course-type overrides (`coursetype_override` column)
- Rows with `status = archived` keep that status even if the file still exists

**Dry run:** When checked, only counts how many rows match your selection — no rows are modified. Use this to preview impact before committing.
