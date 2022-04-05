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

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_submit.php';

$id = required_param('id', PARAM_INT); // course id
$step = optional_param('step', 1, PARAM_INT);
$reset_id = optional_param('resetFinalGrade', 0, PARAM_INT);

// error_log("\n^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
// error_log("\nJust landed on the MAIN index page, do we have the reset_id: ". $reset_id);

if (isset($reset_id) && $reset_id > 0) {

    if ($DB->delete_records('grade_grades', array('itemid' => $reset_id))) {
        $DB->delete_records_select('grade_items', "courseid = :the_course_id AND id = :final_grade_id", array('the_course_id' => $id, 'final_grade_id' => $reset_id));
    } else {
        error_log("Uh oh.........tried to delete from the grade grades table and it didn't work. This is for the Final Grades Export process.");
    }

    // now need to trigger the recalculate page (as of 3.1.2) or you get the following error
    // Stack trace:
    // line 7442 of /lib/moodlelib.php: coding_exception thrown
    // line 808 of /lib/grade/grade_item.php: call to component_callback_exists()
    // line 1918 of /lib/grade/grade_item.php: call to grade_item->adjust_raw_grade()
    // line 284 of /lib/gradelib.php: call to grade_item->update_raw_grade()
    // line 86 of /grade/export/submit/check_grades.php: call to grade_update()
    // line 125 of /grade/export/submit/index.php: call to include_once()
}

$PAGE->set_url('/grade/export/submit/index.php', array('id'=>$id));

$arguments = array();

$PAGE->requires->js_call_amd('core_grades/exportsubmit', 'init');

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/submit:view', $context);

print_grade_page_head($COURSE->id, 'export', 'submit', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_submit'));

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/submit:publish', $context);
}

//******* Create the standard form options form ******
$mform = new grade_export_form(null, array('includeseparator'=>true, 'publishing' => true, 'simpleui' => true));

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
$currentgroup = groups_get_course_group($course, true);
if ($groupmode == SEPARATEGROUPS and !$currentgroup and !has_capability('moodle/site:accessallgroups', $context)) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}



// process post information
// ************ This is the preview screen after they picked the parameters. screen #2...
if ($data = $mform->get_data()) {
    $export = new grade_export_submit($course, $currentgroup, '', false, false, $data->display, $data->decimals);

    // print the grades on screen for feedback

    $export->process_form($data);
    $export->print_continue();
    $export->display_preview();
    echo $OUTPUT->footer();
    exit;
}

//Check if grades have already been submitted.
//TODO: -move this up higher?  -make text look nicer

/*
$rec=$DB->get_record("grade_submit_lmb_submissions", array('courseid'=>$course->id, 'response_received'=>0), '*', IGNORE_MULTIPLE);
if ($rec)
{
    //These grades are already submitted and awaiting a response!
    echo '<div class="clearer"></div>';
    echo "<h2>".get_string('grades_submitted_pending_title','gradeexport_submit')."</h2>";
    echo get_string('grades_submitted_pending', 'gradeexport_submit', array('time'=>date("c",$rec->timesubmitted)));

    groups_print_course_menu($course, 'index.php?id='.$id);
    echo $OUTPUT->footer();

    exit;
}
*/


$rec = $DB->get_record("grade_submit_lmb_submissions", array('courseid' => $course->id, 'response_received' => 1, 'succeeded' => 1), '*', IGNORE_MULTIPLE);

if ($rec) {
    //These grades have already been received successfully!
    echo '<div class="clearer"></div>';
    echo "<h1>".get_string('grades_submitted_already_title', 'gradeexport_submit')."</h1>";
    echo get_string('grades_submitted_already', 'gradeexport_submit', array('time' => date("c", $rec->timesubmitted)));
    echo get_string('contact_info', 'gradeexport_submit');
    echo "<br/><br/><br/><hr>";
/*
    groups_print_course_menu($course, 'index.php?id='.$id);
    echo $OUTPUT->footer();
    exit;
*/
}

//****** ELSE, print the screen where you pick settings....
if ($step == 1 || !$step) {
    include_once('letters.php');
    exit;
} elseif ($step == 2) {
    include_once('check_grades.php');
    exit;
} elseif ($step == 3) {
    include_once('final_send.php');
    exit;
} elseif ($step == 4) {

    // this is where the xml is created and sent via cURL
    include_once('grade_submit.php');
    exit;
}
groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

$mform->display();

echo $OUTPUT->footer();
