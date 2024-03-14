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
 * This page handles the addition and removal of standard questions for each 
 * department.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('forms/standard_questions_form.php');

// ----- Parameters ---- //
$dept = required_param('dept', PARAM_TEXT);
$context = context_system::instance();

// ----- Security ----- //
require_login();
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

// ----- Breadcrumbs ----- //
// $PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
// $PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('admin.php'));
// $PAGE->navbar->add(get_string('nav_admin', 'local_evaluations'), new moodle_url('admin.php?dept='.$dept));

$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('admin.php'));
$PAGE->navbar->add(get_string('nav_course_setings', 'local_evaluations'), new moodle_url('admin.php?dept='.$dept));
$PAGE->navbar->add(get_string('nav_st_qe', 'local_evaluations'), new moodle_url('standard_questions.php?dept='.$dept));


// ----- Stuff ----- //
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/standard_questions.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_st_qe', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_st_qe', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_pagelayout('standard');


// ----- Output ----- //
$standard_questions_form = new standard_questions_form($dept);


//Handle form submission.
$fromform = new stdClass();
foreach ($_REQUEST as $key => $data) {
    $fromform->$key = $data;
}

if (property_exists($fromform, 'submitbutton')) {
    process_submission($fromform);
} elseif (property_exists($fromform, 'delete_question_x') || property_exists($fromform, 'swapup_question_x')
        || property_exists($fromform, 'swapdown_question_x') || property_exists($fromform, 'option_add_fields')) {
    
    //Handle delete, swapups and swapdowns.
    $q_returns[] = question_button_event('delete_question_x', 'delete', 'standard_question');
    $q_returns[] = question_button_event('swapup_question_x', 'order_swapup', 'standard_question');
    $q_returns[] = question_button_event('swapdown_question_x', 'order_swapdown', 'standard_question');

    //This will create a (#) link to the changed element on the page.
    $achor = '';
    foreach ($q_returns as $q_return) {
        //Only one should not be false.
        if ($q_return != false) {
            $achor = $q_return;
            break;
        }
    }

    //Overwrite repeat options on redirect
    //Hackish - should change
    $additional = '';
    if (isset($_REQUEST['option_add_fields'])) {
        if ($achor == '') {
            $additional = '&option_repeats=' . ($_REQUEST['option_repeats'] + 1);
        }
    }
    redirect($CFG->wwwroot . '/local/evaluations/standard_questions.php' . '?dept=' . $dept . $additional . $achor);
} else {
    if (isset($_REQUEST['cancel'])) {
        redirect($CFG->wwwroot . '/local/evaluations/standard_questions.php' . '?dept=' . $dept . $additional . $achor);
    }
}

//Display Form
echo $OUTPUT->header();
if (isset($dept) && $dept != "") {
    $dept_list = get_departments();
    $this_course = " - ".$dept_list[$dept];
} else {
    $this_course = "";
}
echo '<div class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="#">Course Evaluations'.$this_course.'</a>
    </div>
</div>';
    

$standard_questions_form->display();

echo $OUTPUT->footer();

//Page Functions
function process_submission($fromform) {
    global $CFG, $dept;

    $questions = process_question_postdata($fromform);

    $standard_question_set = new standard_question_set($dept, $questions);
    $standard_question_set->save();
    // error_log("What is the dirroot: ".$CFG->dirroot." and what is the dept: ".$dept);
    redirect($CFG->wwwroot . '/local/evaluations/standard_questions.php?dept=' . $dept);
}
