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
$PAGE->requires->js_call_amd('report_content2fix/previewmodal-lazy', 'init');
$PAGE->requires->js_call_amd('report_content2fix/format_tinymce_modal-lazy', 'init');

if ($canqueuefilteredformat) {
    $filtervaluesforjs = [];
    foreach ($filtervalues as $k => $v) {
        $filtervaluesforjs[] = [
            'name' => $k,
            'value' => is_array($v) ? json_encode($v) : (string) $v,
        ];
    }
    $editorwaitseconds = (float) get_config('report_content2fix', 'editorwaitseconds');
    if ($editorwaitseconds <= 0) {
        $editorwaitseconds = 3.5;
    }
    $maxentriesperrun = (int) get_config('report_content2fix', 'maxentriesperrun');
    if ($maxentriesperrun <= 0) {
        $maxentriesperrun = 100;
    }
    $taskqueuedmodaltitle = get_string('task_queued_modal_title', 'report_content2fix');

    $PAGE->requires->js_call_amd('report_content2fix/bulk_format_tinymce-lazy', 'init', [
        'reportId' => (int) $reportid,
        'filterValues' => $filtervaluesforjs,
        'entryCount' => (int) $filteredsummary->entrycount,
        'editorWaitMs' => (int) round($editorwaitseconds * 1000),
        'maxEntriesPerRun' => $maxentriesperrun,
        'taskQueuedModalTitle' => $taskqueuedmodaltitle,
    ]);
}

echo $OUTPUT->header();

if ($canqueuefilteredformat) {
    $helpicon = $OUTPUT->help_icon('fixformatall', 'report_content2fix');

    $dropdownid = html_writer::random_id('content2fix-format-dropdown-');
    $dropdown = html_writer::start_div('dropdown me-2');
    $dropdown .= html_writer::tag('button', get_string('fixformatall', 'report_content2fix') . ' ' .
        html_writer::span('', 'dropdown-toggle-caret', ['aria-hidden' => 'true']),
        [
            'class' => 'btn btn-secondary dropdown-toggle',
            'type' => 'button',
            'id' => $dropdownid,
            'data-toggle' => 'dropdown',
            'aria-haspopup' => 'true',
            'aria-expanded' => 'false',
        ]
    );
    $dropdown .= html_writer::start_div('dropdown-menu', ['aria-labelledby' => $dropdownid]);
    $dropdown .= html_writer::tag('a', get_string('fixformatall_backend', 'report_content2fix'), [
        'class' => 'dropdown-item',
        'href' => '#',
        'data-action' => 'format-backend-bulk',
    ]);
    $dropdown .= html_writer::tag('a', get_string('fixformatall_tinymce', 'report_content2fix'), [
        'class' => 'dropdown-item',
        'href' => '#',
        'data-action' => 'format-tinymce-bulk',
    ]);
    $dropdown .= html_writer::end_div();
    $dropdown .= html_writer::end_div();

    echo html_writer::div(
        html_writer::span($dropdown, 'd-inline-block') . $helpicon,
        'mb-3 d-flex align-items-center'
    );
}

echo $report->output();

echo $OUTPUT->footer();
