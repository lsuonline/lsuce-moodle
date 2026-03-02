<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for report_content2fix.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Content to fix (malformed HTML)';
$string['courseid'] = 'Course ID';
$string['cmid'] = 'Course module ID';
$string['fixformatall'] = 'Format HTML in filtered entries';
$string['fixformatall_backend'] = 'Format HTML with Backend';
$string['fixformatall_backend_confirm_title'] = 'Queue format-all task for filtered entries?';
$string['fixformatall_backend_confirm_body'] = 'You are about to queue a background task to format HTML in {$a->count} entries matching the current filters. The task will run asynchronously.';
$string['fixformatall_help'] = 'Only entries currently matching the report filters will be formatted.';
$string['fixformatall_unavailable'] = 'Formatting filtered entries is not available for the current filter selection.';
$string['fixformatall_tinymce'] = 'Format with TinyMCE';
$string['fixformatall_tinymce_confirm_title'] = 'Format all filtered entries with TinyMCE?';
$string['fixformatall_tinymce_confirm_body'] = 'You are about to process {$a->count} entries. This will open the TinyMCE editor for each filtered entry and save the content after formatting. The editor loads for {$a->seconds} seconds per entry before auto-saving. You can cancel at any time.';
$string['start'] = 'Start';
$string['processing_entries'] = 'Processing entries ({$a} seconds per entry before auto-save), please wait';
$string['processing_entry_count'] = 'Processed: {$a->processed} of {$a->total}';
$string['processing_remaining'] = 'Remaining: {$a}';
$string['processing_loading'] = 'Retrieving data...';
$string['processing_pause'] = 'Pause';
$string['processing_resume'] = 'Resume';
$string['loading_filters'] = 'Loading filters...';
$string['processing_complete'] = 'Formatting complete';
$string['processing_summary'] = 'Successfully formatted {$a} entries.';
$string['fixformatbackend'] = 'Format HTML with Backend';
$string['fixformattinymce'] = 'Format with TinyMCE';
$string['editcontent'] = 'Content';
$string['task_queued_format_all'] = 'The task to format HTML in filtered entries has been queued. You can check its progress in the <a href="/admin/tool/task/adhoctasks.php" target="_blank" rel="noopener">task logs</a>.';
$string['task_queued_modal_title'] = 'Task queued';
$string['content2fix:fix'] = 'Format HTML in report entries';
$string['content2fix:fixfiltered'] = 'Format HTML in filtered entries';
$string['fixformat_success'] = 'HTML formatted successfully for course {$a->courseid}, {$a->component} (cmid: {$a->cmid}).';
$string['task_scan_malformed_html'] = 'Scan activity content for malformed HTML';
$string['column_component'] = 'Component';
$string['activity'] = 'Activity';
$string['column_field'] = 'Field';
$string['summary'] = 'Summary';
$string['preview'] = 'Preview';
$string['preview_modal_title'] = 'Malformed HTML details';
$string['htmlerrors'] = 'HTML errors found';
$string['malformedhtml'] = 'Malformed HTML';
$string['content_differs_after_clean'] = 'Content differs after Moodle clean_text.';
$string['timechecked'] = 'Last checked';
$string['link'] = 'Link';
$string['malformed_no_text'] = '(No extractable text)';
$string['unknown'] = 'Unknown';
$string['settings'] = 'Content to fix settings';
$string['setting_courseids'] = 'Course IDs';
$string['setting_courseids_desc'] = 'Optional comma-separated list of course IDs to scan. Leave empty to scan all courses.';
$string['setting_mainpageonly'] = 'Main page only';
$string['setting_mainpageonly_desc'] = 'If enabled, only scan activities in section 0 (course main page) and course intro/description. If disabled, scan all sections.';
$string['setting_editorwaitseconds'] = 'Format with TinyMCE: wait before auto-save';
$string['setting_editorwaitseconds_desc'] = 'Seconds to wait between loading the TinyMCE editor and auto-saving when using "Format with TinyMCE" for filtered entries.';
$string['setting_maxentriesperrun'] = 'Format with TinyMCE: max entries per run';
$string['setting_maxentriesperrun_desc'] = 'Maximum number of entries to process in one bulk run. Prevents timeouts; reload the page to process more.';
$string['processing_limit_reached'] = 'Limit of {$a} entries per run reached. Reload the page to process more.';
$string['coursepage'] = 'Course page';
$string['filters_applied'] = 'Filters applied';
$string['filter_label_coursefullname'] = 'Course full name';
$string['filter_label_component'] = 'Component';
$string['filter_label_lastchecked'] = 'Last checked';
$string['privacy:metadata'] = 'The Content to fix report does not store any personal data. It stores aggregated scan results (component, table, course, summary) produced by a scheduled task.';
