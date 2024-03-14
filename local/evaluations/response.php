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
// Redirects to correct archives home page
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('forms/response_form.php');
require_once('classes/response.php');

// ----- Parameters ----- //
$eval_id = required_param('eval_id', PARAM_INT);
$eval_db = $DB->get_record('evaluations', array('id' => $eval_id));

// ---- Security ----- //
if ($eval_db) {
    $context = context_course::instance($eval_db->course);
    $PAGE->set_context($context);
    $eval_name = $eval_db->name;
    require_login($eval_db->course);
} else {
    print_error(get_string('invalid_evaluation', 'local_evaluations'));
}

if (eval_check_status($eval_db) != 2) {
    print_error(get_string('invalid_evaluation', 'local_evaluations'));
}

//Make sure this is the first time they've filled out a response.
$sql = "(SELECT DISTINCT q2.evalid 
                    FROM {evaluation_questions} q2, {evaluation_response} r2 
                    WHERE r2.question_id = q2.id 
                    AND q2.evalid = $eval_db->id 
                    AND r2.user_id = $USER->id)";

$default_params = null;
$count = $DB->get_field_sql($sql, $params);

//$response = $DB->count_records_sql($sql);

if ($count > 0 || $count) {
    print_error(get_string('already_responded', 'local_evaluations'));
}

// ----- Breadcrumbs ----- //
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('open_evaluations', 'local_evaluations'), new moodle_url('evals.php'));
$PAGE->navbar->add(get_string('eval_response', 'local_evaluations'), new moodle_url(''));


// ----- Stuff ----- //
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluation.php');
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_title(get_string('evaluation', 'local_evaluations'));
$PAGE->set_heading($eval_name);
$PAGE->set_pagelayout('standard');

// ----- Output ----- //
//CREATE FORM
$response_form = new response_form($eval_id);


$response_form->expandQuestions();

//DEAL WITH SUBMISSION OF FORM
if ($fromform = $response_form->get_data()) {//subbmitted
    $system_context = context_system::instance();

    //DO NOT ALLOW INSTRUCTORS OR ADMINS TO SUBMIT!
    if (!is_dept_admin($dept, $USER)
            && !has_capability('local/evaluations:instructor', $context)) {
        process_submission($fromform);
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
    
$response_form->display();

echo $OUTPUT->footer();

//Page Functions
function process_submission($fromform) {
    global $CFG, $USER;

    $responses = process_reponse_postdata($fromform);

    if (eval_check_status($fromform->eval_id) == 2) { //do not save if trying to submit after time has elapsed
        foreach ($responses as $response) {
            // print_object($response);
            $response = new response(0, $response->question_id, $response->response, $USER->id, $response->question_comment);
            $response->save();
        }
        // exit();
    }
    redirect($CFG->wwwroot . '/local/evaluations/evals.php');
}
