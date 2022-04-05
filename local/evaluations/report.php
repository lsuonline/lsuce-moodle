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
 * @author      Dustin Durrand           				                 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */
/**
 * This is the page that acctually generates the pdfs for download or viewing.
 */
require_once('../../config.php');
require_once('lib.php');
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');
require_once('classes/anonym_report_PDF.php');
require_once('locallib.php');

// ----- Parameters ----- //
$evaluation = required_param('evalid', PARAM_INT);
$download = optional_param('force', 'I', PARAM_ALPHA);
$dept = required_param('dept', PARAM_TEXT);

$evcid = required_param('evcid', PARAM_TEXT);

$eval = $DB->get_record('evaluations', array('id' => $evaluation));
$eval_date = $DB->get_record('evaluation_compare', array('courseevalid' => $eval->id, 'id' => $evcid));
$course = $DB->get_record('course', array('id' => $eval->course));
$course_context = context_course::instance($course->id);

// $eval->compare_date = $eval_date->date;
if (isset($eval_date->date)) {
    $eval->compare_date = $eval_date->date;
} else {
    $eval->compare_date = null;
}

// ----- Security ----- //
require_login();
$is_instructor = has_capability('local/evaluations:instructor', $course_context);
$is_admin = is_dept_admin($dept, $USER);



// $is_envigilator = $DB->get_record('department_administrators', array('userid'=>$USER->id, 'department'=>$dept));
$is_envigilator = $DB->get_record_select('department_administrators', "userid=:uid AND department=:dept", array('uid'=>$USER->id, 'dept'=>$dept), 'userid,department');
$am_i_enrolled = is_enrolled($course_context, $USER->id, 'moodle/course:update', true);
if (!$is_envigilator || $am_i_enrolled) {
    // print_error("No access, simply hit back....");
    print_error(get_string('restricted', 'local_evaluations'));
}

/*
$in_this_dept = is_in_department($dept, $course);
$course_context = context_course::instance($course->id);
$am_i_enrolled = is_enrolled($course_context, $USER->id, 'moodle/course:update', true); 
$is_instructor = has_capability('local/evaluations:instructor',$course_context);
$admin = has_capability('local/evaluations:admin',$course_context);
$course_eval_admin = has_capability('local/evaluations:course_eval_admin',$course_context);

if (!$in_this_dept || !$is_instructor || !$course_eval_admin) {
    print_error(get_string('restricted', 'local_evaluations'));
    // continue;
}*/

/*
if ($is_instructor || !$is_admin) {
    print_error(get_string('restricted', 'local_evaluations'));
}

if (eval_check_status($eval) != EVAL_STATUS_COMPLETE) {//check if complete
    print_error(get_string('not_completed', 'local_evaluations'));
}


if (!$eval) {
    print_error(get_string('eval_id_invalid', 'local_evaluations'));
}


if (!$course) {
    print_error(get_string('invalid_course', 'local_evaluations'));
}
*/
// ----- Output ----- //
// 
//Create a report pdf.
ob_start(); //strange output from get_String that breaks pdf output unless we dump it
$pdf = new Anonym_report_PDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, $eval, $course, $dept);
ob_end_clean(); //dump output this far
$reportName = $course->fullname . "_report.pdf";
$pdf->Output($reportName, $download);
