<?php

/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */
/**
 * This page handles the creation of new evaluations.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('forms/eval_form.php');
// ----- Parameters ----- //
$courseid = optional_param('cid', null, PARAM_INT);
$eval_id = optional_param('eval_id', 0, PARAM_INT);
$dept = required_param('dept', PARAM_TEXT);
$eval_db = $DB->get_record('evaluations', array('id' => $eval_id));
$context = context_system::instance();
// error_log("evaluation.php => START");
//Check for department permission.
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid));
} elseif ($eval_id) {
    $course = $DB->get_record('course', array('id' => $eval_db->course));
}

$department_list = get_departments();

$course_context = context_course::instance($course->id);
$is_instructor = has_capability('local/evaluations:instructor', $course_context);

// ----- Security ----- //
require_login();

// ----- Navigation ----- //
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluation.php');

// ----- Breadcrumbs ----- //
// $PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
// $PAGE->navbar->add(get_string('nav_ev_course', 'local_evaluations'), new moodle_url('evaluations.php'));
// $PAGE->navbar->add(get_string('evaluation', 'local_evaluations'), new moodle_url(''));

$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('evaluations.php'));
//If a department was selected the create a link the the department selection page
if ($dept) {
    $PAGE->navbar->add(get_string('nav_ev_course', 'local_evaluations'), new moodle_url('evaluations.php?dept='.$dept));
    $PAGE->navbar->add(get_string('nav_create_eval', 'local_evaluations'), new moodle_url('evaluation.php?dept='.$dept));
}

// ----- Stuff ----- //
$eval_name = get_string('new_evaluation', 'local_evaluations');
$PAGE->set_context($context);
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_title(get_string('evaluation', 'local_evaluations'));
$PAGE->set_heading($eval_name);
$PAGE->set_pagelayout('standard');

// ----- Output ----- //
//CREATE FORM
if (isset($courseid)) {

    // error_log("evaluation.php => creating form and have course id: ".$courseid);
    $eval_form = new eval_form($dept, $eval_id, $courseid);
    // error_log("evaluation.php => have eval form now");
} else {

    // error_log("evaluation.php => FAIL, don't have course id, do we have eval->course???");
    if (isset($eval_db->course)) {
        // error_log("evaluation.php => YES, we have eval->course, what is the course id: ". $eval_db->course);
        $eval_form = new eval_form($dept, $eval_id, $eval_db->course);
        // error_log("evaluation.php => what is the eval_form: ". print_r($eval_form, 1));

    } else {
        // error_log("evaluation.php => NO, we don't have eval->course so creating a new one.");
        $eval_form = new eval_form($dept, $eval_id);
    }
}

// error_log("evaluation.php => do we have access to eval_form->_form??");
// $funky_chunky = $eval_form->_form;

$eval_form->expandQuestions();

$PAGE->requires->js('/local/evaluations/evaluation.js');

//DEAL WITH SUBMISSION OF FORM
$fromform = $eval_form->get_data();
// error_log("\n");
// error_log("\nWhat is from from: ". print_r($fromform, 1));
if ($fromform) {//subbmitted
    // error_log("evaluation.php => fromform ");
    process_submission($fromform);
} elseif ($eval_form->no_submit_button_pressed()) {//Occurs with delete swapup and swapdown
    // error_log("evaluation.php => doing else if ");
    //Handle delete, swapups and swapdowns.
    $q_returns[] = question_button_event('delete_question_x', 'delete',
            'question', $eval_id);
    $q_returns[] = question_button_event('swapup_question_x', 'order_swapup',
            'question', $eval_id);
    $q_returns[] = question_button_event('swapdown_question_x',
            'order_swapdown', 'question', $eval_id);


    //This will create a (#) link to the changed element on the page.
    $achor = '';
    foreach ($q_returns as $q_return) {
        //Only one should not be false.
        if ($q_return != false) {
            $achor = $q_return;
            break;
        }
    }

    $additional = '';
    if (isset($_REQUEST['option_add_fields'])) {
        $additional = '&option_repeats=' . ($_REQUEST['option_repeats'] + 1);
    }

    if (isset($_REQUEST['cid'])) {
        redirect($CFG->wwwroot . '/local/evaluations/evaluation.php?dept=' . $dept . '&cid=' . $courseid . '&eval_id=' . $eval_id . $additional . $achor);
    } else {
        redirect($CFG->wwwroot . '/local/evaluations/evaluation.php?dept=' . $dept . '&eval_id=' . $eval_id . $additional . $achor);
    }
//} else {
//    error_log("evaluation.php => Using standard form, no additional questions added yet.");

}

//Display Form
echo $OUTPUT->header();

$eval_form->display();

echo $OUTPUT->footer();

// ----- Functions ----- //
function process_submission($fromform) {
    global $CFG, $dept;

    //get the question objects from the form data.
    $questions = process_question_postdata($fromform);
    $start = $fromform->eval_time_start;
    $end = $fromform->eval_time_end;

    //Create a new evaluation with the posted data.
    $evaluation = new evaluation($dept, $fromform->eval_id, $questions, $fromform->eval_course_id, $start,
                    $end, $fromform->eval_name, $fromform->student_email_toogle, $fromform->eval_complete, $db_load = false);

    //Save the evaluation.
    $evaluation->save();

    //Redirect to list of evaluations for this department.
    redirect($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . $dept);
}
