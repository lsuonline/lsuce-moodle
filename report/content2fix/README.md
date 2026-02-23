# Content to fix (malformed HTML)

A Moodle report plugin that scans HTML fields in activity modules (mod_*) and lists content that would be modified by Moodle's clean_text so administrators can find and fix it.

## What it does

- **Scans** intro and content fields across activity modules (Page, Assign, Forum, Book, Label, and others) where the format is HTML.
- **Detects** content that differs after Moodle clean_text: runs clean_text on each HTML value and compares with the original; if the diff is non-empty, the content is flagged.
- **Stores** results in a dedicated table (`report_content2fix`) that is populated by a scheduled task.
- **Report** under **Site administration → Reports** shows a paginated table (via the **reportbuilder** API) with component, course, activity, field, summary of the content, last checked time, and a link to the activity. The report supports **filters** by course, by activity type (component), and by time checked.

## Requirements

- Moodle 4.5+ (or compatible with the plugin’s `requires` in `version.php`).

## Installation

1. Place the plugin in `report/content2fix` in your Moodle codebase.
2. Visit **Site administration → Notifications** and complete the upgrade, or run `php admin/cli/upgrade.php`.

## How it works

1. **Scheduled task**  
   A task runs daily at **3:00 AM** (configurable in **Site administration → Server → Scheduled tasks**). It:
   - Clears the report table.
   - Iterates over known mod_* HTML sources (e.g. `mod_page` intro/content, `mod_assign` intro).
   - For each non-empty HTML value, runs Moodle clean_text and compares with the original; if they differ, inserts a row into `report_content2fix` with component, table, field, row id, course id, course module id (for the “View” link), a short summary, and the scan time.

2. **Report page**  
   **Site administration → Reports → Content to fix (malformed HTML)** displays all stored rows in a sortable, paginated reportbuilder table. Users can filter by **course**, by **activity type (component)**, and by **time checked**. Users need the capability `report/content2fix:view` (by default granted to managers).  
   The bulk action button (**Format HTML in filtered entries**) is shown when either of these is true:
   - The current user is a site admin and has capability `report/content2fix:fixfiltered`.
   - The current user is a site admin and the currently filtered result set contains entries from exactly one course.

3. **Event observers**  
   When a course module is **updated** or **deleted**, the corresponding rows in `report_content2fix` are removed automatically so the report stays in sync. The next scheduled scan will re-check updated content.

## Activities scanned

The plugin scans HTML fields for installed activity modules such as:

- Page (intro, content)
- Assign, Book, Forum, Glossary, Lesson, Label
- Resource, Folder, URL
- Wiki, Feedback, Choice, Quiz, SCORM, H5P, Data

Only modules that are installed and that have the expected table/columns (e.g. `intro`/`introformat` or `content`/`contentformat`) are included.

## Running the scan manually

To populate the report without waiting for the scheduled run:

- **Site administration → Server → Scheduled tasks** → find “Scan activity content for malformed HTML” → **Run now**, or  
- CLI: `php admin/cli/scheduled_task.php --execute="\report_content2fix\task\scan_malformed_html_task"`

---

## License

MIT License

Copyright (c) 2026 LSU Online

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
