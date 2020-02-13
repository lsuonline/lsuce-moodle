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

require_once('../../../config.php');
require_once($CFG->dirroot.'/lib/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/quick_edit/lib.php');

// If $CFG->removeevent2triggers is true, call event handlers directly w/o using Event 2.
$CFG->removeevent2triggers = true;

$courseid = required_param('id', PARAM_INT);
$groupid = optional_param('group', null, PARAM_INT);

// Making this work with profile reports.
$userid = optional_param('userid', null, PARAM_INT);

$defaulttype = $userid ? 'user' : 'select';

$itemid = optional_param('itemid', $userid, PARAM_INT);
$itemtype = optional_param('item', $defaulttype, PARAM_TEXT);

$courseparams = array('id' => $courseid);

$PAGE->set_url(new moodle_url('/grade/report/quick_edit/index.php', $courseparams));

if (!$course = $DB->get_record('course', $courseparams)) {
    print_error('nocourseid');
}

if (!in_array($itemtype, grade_report_quick_edit::valid_screens())) {
    print_error('notvalid', 'gradereport_quick_edit', '', $itemtype);
}

require_login($course);

$context = context_course::instance($course->id);

// This is the normal requirements.
require_capability('gradereport/quick_edit:view', $context);
require_capability('moodle/grade:viewall', $context);
require_capability('moodle/grade:edit', $context);
// End permission.

$gpr = new grade_plugin_return(array(
    'type' => 'report',
    'plugin' => 'quick_edit',
    'courseid' => $courseid
));

// Last selected report session tracking.
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'quick_edit';

grade_regrade_final_grades($courseid);

$report = new grade_report_quick_edit(
    $courseid, $gpr, $context,
    $itemtype, $itemid, $groupid
);

$reportname = $report->screen->heading();

$pluginname = get_string('pluginname', 'gradereport_quick_edit');

$reporturl = new moodle_url('/grade/report/grader/index.php', $courseparams);
$editurl = new moodle_url('/grade/report/quick_edit/index.php', $courseparams);

$PAGE->navbar->ignore_active(true);

$PAGE->navbar->add(get_string('courses'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', $courseparams));

$PAGE->navbar->add(get_string('gradeadministration', 'grades'));
$PAGE->navbar->add(get_string('pluginname', 'gradereport_grader'), $reporturl);

if ($reportname != $pluginname) {
    $PAGE->navbar->add($pluginname, $editurl);
    $PAGE->navbar->add($reportname);
} else {
    $PAGE->navbar->add($pluginname);
}

if ($data = data_submitted()) {
    $warnings = $report->process_data($data);

    if (empty($warnings)) {
        redirect($reporturl);
    }
}

print_grade_page_head($course->id, 'report', 'quick_edit', $reportname);

if ($report->screen->supports_paging()) {
    echo $report->screen->pager();
}

if ($report->screen->display_group_selector()) {
    echo $report->group_selector;
}

if (!empty($warnings)) {
    foreach ($warnings as $warning) {
        echo $OUTPUT->notification($warning);
    }
}

echo $report->output();

echo $OUTPUT->footer();