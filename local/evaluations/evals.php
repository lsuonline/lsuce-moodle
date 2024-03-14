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
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Dustin Durrand http://oohoo.biz
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * This page shows users what evaluations they have available to fill out.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Security -----//
require_login();

//Create the breadcrumbs
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('open_evaluations', 'local_evaluations'), new moodle_url('courseUsersEnrolled.php'));

// ----- Stuff ----- //
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');
$PAGE->set_title(get_string('open_evaluations', 'local_evaluations'));
$PAGE->set_heading(get_string('open_evaluations', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_pagelayout('standard');

// ----- Output ----- //
echo $OUTPUT->header();

if (isset($dept) && $dept != "") {
    $dept_list = get_departments();
    $this_course = " - ".$dept_list[$dept];
} else {
    $this_course = "";
}

echo printHeaderBar("Course Evaluations", hasAdminAccess());

echo '<table width="50%" cellpadding="1" style="text-align: center;">';


if (is_siteadmin()) {
    $courses = getAllCurrentEvals();
    $is_admin = 1;
} else {
    $courses = enrol_get_my_courses();
    $is_admin = 0;
}

foreach ($courses as $course) {
    $current = time();


    //Select all evals that are in progress
    //then strip all evals that already have responses from that user
    $sql = "SELECT * 
        FROM {evaluations} e 
        WHERE e.course = $course->id 
                AND e.start_time <= $current 
                AND e.end_time > $current AND e.complete <> 1 AND e.deleted <> 1
                AND e.id NOT IN 
                
                    (SELECT q2.evalid 
                    FROM {evaluation_questions} q2, {evaluation_response} r2 
                    WHERE r2.question_id = q2.id 
                    AND q2.evalid = e.id 
                    AND r2.user_id = $USER->id)";


    //Print the course name
    if ($is_admin) {
        echo '<tr><td colspan=4><b>' . $course->name . '</b></td></tr>';
    } else {
        echo '<tr><td colspan=4><b>' . $course->fullname . '</b></td></tr>';
    }
    //Output the table header.
    table_header();

    //Get a list of all evaluations.
    $evals = $DB->get_records_sql($sql);
    if ($evals == null || count($evals) == 0) {
        //Warn the users that there are no evaluations for this course to be taken.
        echo '<tr><td colspan=4>' . get_string('none', 'local_evaluations') . '</td></tr>';
    } else {
        foreach ($evals as $eval) {
            $href = $CFG->wwwroot . '/local/evaluations/preamble.php?eval_id=' . $eval->id; //Link to evaluation.
            //Print the evaluation info along with evaluation link.
            echo '<tr>';
            echo "  <td><a href='$href'>" . $eval->name . "</a></td>";
            echo '  <td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
            echo '  <td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
            echo '</tr>';
        }
    }
    echo '<tr><td><br></td></tr>';
}

echo '</tr></table>';

echo $OUTPUT->footer();

/**
 * Print the header for the evaluation table.
 */
function table_header() {
    echo '<tr>';
    echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
    echo '</tr>';
}
