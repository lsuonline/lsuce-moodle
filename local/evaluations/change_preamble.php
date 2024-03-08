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
 * This page allows users to change the preamble for their department.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('forms/preamble_form.php');
require_once('locallib.php');

// ----- Parameters ----- //
$dept = required_param('dept', PARAM_TEXT);

// ----- Security ----- //
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

// ----- Breadcrumbs ----- //
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('admin.php'));
$PAGE->navbar->add(get_string('nav_course_setings', 'local_evaluations'), new moodle_url('admin.php?dept='.$dept));
$PAGE->navbar->add(get_string('preamble', 'local_evaluations'), new moodle_url(''));


// ----- Stuff ---- //
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/change_preamble.php');
$PAGE->set_title(get_string('preamble', 'local_evaluations'));
$PAGE->set_heading(get_string('preamble', 'local_evaluations'));
$PAGE->set_pagelayout('standard');

// ----- Output ----- //
$mform = new preamble_form($dept);

//Get the data from the form if it was submitted.
if ($fromform = $mform->get_data()) {
    $record = new stdClass();
    $record->preamble = $fromform->preamble[text];
    $record->department = $fromform->dept;
//    echo("Here is the change: ".$record->preamble[text]);
//    die;
    //Check if the department already has a preamble or not.
    if ($aRecord = $DB->get_record_select(
        'department_preambles',
        "department = '$record->department'"
    )) {
        //If it has one then update the record.
        $record->id = $aRecord->id;
        $DB->update_record('department_preambles', $record);
    } else {
        //Otherwise insert a new record.
        $DB->insert_record('department_preambles', $record);
    }
    //redirect to admin page.
    header('Location: ' . $CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept);
} else {
    //Output the form.
    echo $OUTPUT->header();
    if (isset($dept) && $dept != "") {
        $dept_list = get_departments();
        $this_course = " - ".$dept_list[$dept];
    } else {
        $this_course = "";
    }
    echo '<div class="navbar">
        <div class="navbar-inner">
            <a class="brand" href="#">UofL Course Evaluations'.$this_course.'</a>
        </div>
    </div>';

    // Pre-pre-amble issued by the UofL
    echo getPreamble();

    $mform->display();
    echo $OUTPUT->footer();
}
