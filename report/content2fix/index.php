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
use report_content2fix\local\format_helper;
use core_reportbuilder\system_report_factory;
use core_reportbuilder\local\helpers\user_filter_manager;

admin_externalpage_setup('reportcontent2fix', '', null, '', ['pagelayout' => 'report']);

require_capability('report/content2fix:view', context_system::instance());

$systemcontext = context_system::instance();
$canfix = has_capability('report/content2fix:fix', $systemcontext);
$canfixfiltered = has_capability('report/content2fix:fixfiltered', $systemcontext);
$isadmin = is_siteadmin();

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$sesskey = optional_param('sesskey', '', PARAM_RAW);

$redirecturl = new moodle_url('/report/content2fix/index.php');
$report = system_report_factory::create(malformed_content_report::class, $systemcontext, '', '', 0, [
    'canfix' => $canfix,
]);
$reportid = $report->get_report_persistent()->get('id');
$filtervalues = user_filter_manager::get($reportid);
$filteredsummary = $report->get_filtered_entry_summary($filtervalues);

$canqueuefilteredformat = format_helper::can_queue_filtered_format(
    $isadmin,
    $canfixfiltered,
    (int) $filteredsummary->distinctcoursecount
);

if ($action === 'formatall' && confirm_sesskey($sesskey)) {
    if (!$canqueuefilteredformat) {
        redirect(
            $redirecturl,
            get_string('fixformatall_unavailable', 'report_content2fix'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    format_helper::queue_filtered_format_task($filtervalues);

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
    $entry = format_helper::format_single_entry($id);
    if ($entry) {
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
    redirect($redirecturl);
}

$PAGE->set_title(get_string('pluginname', 'report_content2fix'));
$PAGE->set_heading(get_string('pluginname', 'report_content2fix'));
$PAGE->requires->js_call_amd('report_content2fix/reporthandler-lazy', 'init');

echo $OUTPUT->header();

if ($canqueuefilteredformat) {
    $formatallparams = ['action' => 'formatall', 'sesskey' => sesskey()];
    $formatallurl = new moodle_url('/report/content2fix/index.php', $formatallparams);
    $button = $OUTPUT->single_button($formatallurl, get_string('fixformatall', 'report_content2fix'), 'get');
    $helpicon = $OUTPUT->help_icon('fixformatall', 'report_content2fix');
    echo html_writer::div(
        html_writer::span($button, 'me-2') . $helpicon,
        'mb-3 d-flex align-items-center'
    );
}

echo $report->output();

echo $OUTPUT->footer();
