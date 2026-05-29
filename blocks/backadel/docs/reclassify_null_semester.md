## Null Semester Rows

Some backup filenames — typically *backadel-modern* format — embed semester information **inside the archive**, not in the filename itself. The filename parser cannot extract a semester from these names, so their catalogue rows show no semester.

Re-classifying these rows will update all fields that **can** be extracted from the filename (course type, instructor list) but **will not** fill in the missing semester value — the semester was never in the filename.

Enable this option only if you need to refresh instructor or course-type data for these rows.
