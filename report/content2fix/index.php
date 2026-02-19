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
// GNU General Public License for the terms and conditions.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Content to fix report - lists content with malformed HTML from activity modules.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use report_content2fix\reportbuilder\local\systemreports\malformed_content_report;
use report_content2fix\local\html_formatter;
use report_content2fix\task\format_all_html_task;
use core_reportbuilder\system_report_factory;

admin_externalpage_setup('reportcontent2fix', '', null, '', ['pagelayout' => 'report']);

require_capability('report/content2fix:view', context_system::instance());

$canfix = has_capability('report/content2fix:fix', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$sesskey = optional_param('sesskey', '', PARAM_RAW);

$redirecturl = new moodle_url('/report/content2fix/index.php');

if ($canfix && $action === 'formatall' && confirm_sesskey($sesskey)) {
    $task = new format_all_html_task();
    \core\task\manager::queue_adhoc_task($task);

    $tasklogsurl = new moodle_url('/admin/tool/task/adhoctasks.php');
    $tasklogslink = html_writer::link(
        $tasklogsurl,
        get_string('adhoctasks', 'tool_task'),
        ['target' => '_blank']
    );
    redirect(
        $redirecturl,
        get_string('task_queued_format_all', 'report_content2fix', $tasklogslink),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($canfix && $action === 'fixone' && $id > 0 && confirm_sesskey($sesskey)) {
    global $DB;
    $entry = $DB->get_record('report_content2fix', ['id' => $id]);
    if ($entry) {
        $changed = html_formatter::format_and_persist_entry($entry);
        if ($changed) {
            $a = (object) [
                'courseid' => $entry->courseid,
                'component' => $entry->component,
                'cmid' => $entry->cmid ?? '-',
            ];
            redirect(
                $redirecturl,
                get_string('fixformat_success', 'report_content2fix', $a),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }
    redirect($redirecturl);
}

$PAGE->set_title(get_string('pluginname', 'report_content2fix'));
$PAGE->set_heading(get_string('pluginname', 'report_content2fix'));

echo $OUTPUT->header();

if ($canfix) {
    $formatallurl = new moodle_url('/report/content2fix/index.php', [
        'action' => 'formatall',
        'sesskey' => sesskey(),
    ]);
    echo html_writer::div(
        $OUTPUT->single_button($formatallurl, get_string('fixformatall', 'report_content2fix'), 'get'),
        'mb-3'
    );
}

$report = system_report_factory::create(malformed_content_report::class, context_system::instance(), '', '', 0, [
    'canfix' => $canfix,
]);
echo $report->output();

echo $OUTPUT->footer();
