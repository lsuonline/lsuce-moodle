<?php

///////////////////////////////////////////////////////////////////////////
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once '../../../config.php';
require_once $CFG->dirroot.'/lib/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/grade_breakdown/lib.php';

$courseid = required_param('id', PARAM_INT);
$bound    = required_param('bound', PARAM_FLOAT);
$gradeid  = required_param('grade', PARAM_INT);
$groupid  = optional_param('group', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}

require_login($course);

$context = get_context_instance(CONTEXT_COURSE, $course->id);

// They MUST be able to view grades to view this page
require_capability('gradereport/grade_breakdown:view', $context);
require_capability('moodle/grade:viewall', $context);

/// Build navigation
$strgrades  = get_string('grades');
$reportname = get_string('pluginname', 'gradereport_grade_breakdown');

$PAGE->set_url(new moodle_url('/grade/report/grade_breakdown/letter_report.php',
    array(
        'id' => $courseid,
        'bound' => $bound,
        'grade' => $gradeid,
        'group' => $groupid
    )
));

$PAGE->set_context($context);

print_grade_page_head($course->id, 'report', 'grade_breakdown', $reportname, false);

// This grade report has the functionality to print the right
// group selector
$gpr = new grade_plugin_return(array(
    'type' => 'report', 'plugin' => 'grade_breakdown', 'courseid' => $courseid
));

$grade_report = new grade_report_grade_breakdown(
    $courseid, $gpr, $context, $gradeid, $groupid
);

$grade_report->baseurl = '/grade/report/grade_breakdown/letter_report.php?id='.
    $courseid . '&amp;bound='. $bound;

$grade_report->setup_groups();
$grade_report->setup_grade_items();

echo '<div class="selectors">' .
    $grade_report->group_selector .
    $grade_report->grade_selector .
    '</div>';

if (empty($gradeid)) {
    echo $OUTPUT->heading(get_string('not_supported', 'gradereport_grade_breakdown'));

    $url_params = array('id' => $courseid, 'grade' => 0, 'group' => $groupid);
    $url = new moodle_url('/grade/report/grade_breakdown/index.php', $url_params);

    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->footer();
    exit;
}

// Get all the students in this course
$roleids = explode(',', $CFG->gradebookroles);

$graded_users = get_role_users($roleids, $context,
                false, '', 'u.lastname ASC', true, $groupid);

$userids = implode(',', array_keys($graded_users));

$sql = "SELECT g.id, g.grademax, g.itemname, gc.fullname FROM
            {grade_items} g,
            {grade_categories} gc
         WHERE g.id = :gradeid
           AND (gc.id = g.categoryid
            OR (gc.id = g.iteminstance AND g.categoryid IS NULL))
           AND g.courseid = :courseid";

$grade_item = $DB->get_record_sql($sql, array(
    'gradeid' => $gradeid,
    'courseid' => $courseid
));

$letters = grade_get_letters($context);

$high = 100;
foreach($letters as $boundary => $letter) {
    // Found it!
    if ($boundary == $bound) {
        break;
    }
    $high = $boundary - (1 / (pow(10, 5)));
}

// In the event that we're looking at the max, students actually have the
// ability to go twice the max, so we must adhere to that rule
$high = ($high == 100) ? $high * 2 : $high;

$real_high = $grade_item->grademax * ($high / 100);
$real_low  = $grade_item->grademax * ($bound / 100);

$query_params = array(
    'courseid' => $courseid,
    'gradeid' => $gradeid,
    'real_high' => $real_high,
    'real_low' => $real_low
);

$group_select = $group_where = $group_name = '';

// Add group sql
if ($groupid) {
    $query_params += array('groupid' => $groupid);

    $group_select = ', {groups} grou, {groups_members} gr ';
    $group_where =' AND u.id = gr.userid
        AND grou.courseid = :courseid AND gr.groupid = grou.id
        AND gr.groupid = :groupid ';
    $group_name = ', grou.name ';
}

// Get all the grades for the users within the range specified with $real_high and $real_low
$sql = "SELECT u.id, g.id AS gradeid, g.finalgrade, u.firstname, u.lastname
                $group_name
          FROM  {grade_grades} g,
                {user} u
                $group_select
            WHERE u.id = g.userid
              $group_where
              AND g.itemid = :gradeid
              AND g.userid IN ({$userids})
              AND g.finalgrade <= :real_high
              AND g.finalgrade >= :real_low
            ORDER BY g.finalgrade DESC";

$grades = $DB->get_records_sql($sql, $query_params);

// No grades; tell them that, then die
if (!$grades) {
    echo $OUTPUT->heading(get_string('no_grades', 'gradereport_grade_breakdown'));
    echo $OUTPUT->footer();
    exit;
}

// Get the Moodle version of this grade item
$item_params = array('id' => $gradeid);
$grade_item = grade_item::fetch($item_params);

$name = $grade_item->get_name();

$g_params = array('id' => $groupid);
$groupname = ($groupid) ? ' in ' . $DB->get_field('groups', 'name', $g_params) : '';

echo $OUTPUT->heading(get_string('user_grades', 'gradereport_grade_breakdown') .
    $letters[$bound] . ' for ' . $name . $groupname);


$numusers = count($graded_users);

// Build the data
$data = array();
foreach ($grades as $userid => $gr) {
    $line = array();

    $url = new moodle_url('/grade/report/user/index.php', array(
        'id' => $courseid,
        'userid' => $userid
    ));

    $line[] = html_writer::link($url, fullname($gr));

    $line[] = grade_format_gradevalue($gr->finalgrade, $grade_item, true,
        GRADE_DISPLAY_TYPE_REAL);

    $line[] = grade_format_gradevalue($gr->finalgrade, $grade_item, true,
        GRADE_DISPLAY_TYPE_PERCENTAGE);

    $line[] = find_rank($context, $grade_item, $gr, $groupid) . '/' . $numusers;

    $line[] = print_edit_link($courseid, $grade_item, $gr->gradeid);
    $data[] = $line;
}

$table = new html_table();
$table->head = array(
    get_string('fullname'),
    get_string('real_grade', 'gradereport_grade_breakdown'),
    get_string('percent', 'grades'),
    ($groupid ? get_string('group') : get_string('course')) . ' ' . get_string('rank', 'grades'),
    get_string('edit', 'grades')
);

$table->data = $data;

echo html_writer::table($table);
echo $OUTPUT->footer();

