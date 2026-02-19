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
$string['fixformatall'] = 'Format HTML in all entries';
$string['fixformatone'] = 'Format HTML';
$string['task_queued_format_all'] = 'The task to format HTML in all entries has been queued. You can check its progress in the {$a}.';
$string['fixformat_success'] = 'HTML formatted successfully for course {$a->courseid}, {$a->component} (cmid: {$a->cmid}).';
$string['task_scan_malformed_html'] = 'Scan activity content for malformed HTML';
$string['column_component'] = 'Component';
$string['activity'] = 'Activity';
$string['column_field'] = 'Field';
$string['summary'] = 'Summary';
$string['timechecked'] = 'Last checked';
$string['link'] = 'Link';
$string['malformed_no_text'] = '(No extractable text)';
$string['unknown'] = 'Unknown';
$string['view'] = 'View';
$string['privacy:metadata'] = 'The Content to fix report does not store any personal data. It stores aggregated scan results (component, table, course, summary) produced by a scheduled task.';
