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


$string['results'] = 'Results';
//$string['select_all_compare'] = 'Select all items on this page';
//$string['select_all_compare'] = 'Select all items ';

// config words 
$string['main_title'] = 'Course Evaluation Settings';

$string['school_name'] = 'Name of your school';
$string['school_name_desc'] = 'This shows in the school name in the default preamble.';

$string['preamble_title'] = 'Default Preamble';
$string['general_settings'] = 'General Settings';
$string['current_term'] = 'Current Term';
$string['current_term_desc'] = 'Term is used to find historical reports, year (2024) followed by term (02), for example: 201402 (01,02,03 - winter, summer, fall)';

$string['eventCourseEvaluations'] = 'Course Evaluation Event Handler';

// Tasks.
$string['send_emails_task'] = 'Send course evaluation email reminders.';


$string['denied_viewing'] = 'Only envigilator\'s can view, sorry!';
$string['already_started'] = 'The evaluation has already started. You cannot make the start time before the current time, since its started.';
$string['already_started_invig'] = 'The evaluation has already started.';
$string['average'] = 'Average: ';
$string['beforestart'] = 'Pre-Start';
$string['cannot_started'] = 'This would automatically start the evaluation. You can only do this by "Force Starting" in the Evaluation Manager.';
$string['choices'] = 'Choices';
$string['complete'] = 'Complete';
$string['saveq'] = 'Save Questions';
$string['course_c'] = 'Course: ';
$string['current_ingiv'] = 'Current Invigilator Users';
$string['days'] = 'Days ';
$string['delete_header'] = 'Delete(Only Inactive)';
$string['duration'] = 'Duration';
$string['end_LE_now'] = 'The end time selected is less than current time, and will cause it to complete. To forcefully complete, click "Force Complete" from the evaluation list page.';
$string['end_header'] = 'End Time';
$string['eval_id_invalid'] = 'Invaid Evaluation ID';
$string['eval_name_c'] = 'Evaluation Name: ';
$string['evaluation'] = 'Evaluation';
$string['evaluationinvigilator'] = 'A person that supervises an evaluation.';
$string['evaluationinvigilatordescription'] = 'A person that supervises an evaluation.';
$string['evaluations:admin'] = 'The admin access of Course Evaluations local plugin.';
$string['evaluations:course_eval_admin'] = 'The needed security to access all admin functions of course admin';
$string['evaluations:instructor'] = 'The security needed to access the instructor section of the course evaluations';
$string['evaluations:invigilator'] = 'Course Evaluations';
$string['excellent'] = 'Excellent';
$string['force_c_header'] = 'Force Complete';
$string['force_e_header'] = 'Force End';
$string['force_s_header'] = 'Force Start';
$string['form_error'] = 'An error occured when attempting to display this form.';
$string['form_restricted'] = 'The evaluation had reached a state of completion. It cannot be edited.';
$string['general'] = 'General';
$string['good'] = 'Good';
$string['hours'] = 'Hours ';
$string['inprogress'] = 'In Progress';
$string['invalid_course'] = 'Invalid Course';
$string['invalid_eval_type'] = 'Incorrect Evaluation Type';
$string['invalid_evalid_save'] = 'Tried to save questions to eval with id 1';
$string['invig'] = 'Face to Face With Invigilator';
$string['invigilators'] = 'Invigilators';
$string['matched_ingiv_users'] = 'Matched Invigilator Users';
$string['median'] = 'Median: ';
$string['minutes'] = 'Minutes ';
$string['mode'] = 'Mode: ';
$string['months'] = 'Months ';
$string['name_header'] = 'Name';
$string['nav_reports'] = 'Evaluation Reports By Course';
$string['admin_add'] = 'Add Administrator';
$string['new_evaluation'] = 'New Evaluation (for your courses)';
$string['new_single_evaluation'] = "New Evaluation (for this course)";
$string['no_instructing_courses'] = 'You current have no courses with editing access.';
$string['no_name'] = 'No Name';
$string['none'] = 'None';
$string['not_completed'] = 'Evaluation hasn\'t been completed';
$string['online'] = 'Online';
$string['pluginname'] = 'Course Evaluations';
$string['poor'] = 'Poor';
$string['poor'] = 'Poor';
$string['preamble'] = 'Preamble';
$string['question'] = 'Question';
$string['question_c'] = 'Question: ';
$string['question_count'] = 'Number Of Questions: ';
$string['question_header'] = 'Questions';
$string['question_id_LEZ'] = 'Question Id cannot be less than zero.';
$string['question_id_invalid'] = 'Invalid Question Id';
$string['range'] = 'Range: ';
$string['response_count'] = 'Response Count';
$string['response_rate'] = 'Response Rate: ';
$string['restricted'] = 'Ooops, sorry! but you don\'t have access to this.';
$string['scale4_1'] = 'Poor';
$string['scale4_2'] = 'Unsatisfactory';
$string['scale4_3'] = 'Good';
$string['scale4_4'] = 'Excellent';
$string['scale5_1'] = "  Poor  ";
$string['scale5_2'] = "  Fair  ";
$string['scale5_3'] = "  Good  ";
$string['scale5_4'] = "  Excellent  ";
$string['scale5_5'] = "  Superior  ";
$string['seconds'] = 'Seconds ';
$string['selected_count'] = 'Selected Count(Option => Total): ';
$string['startLEend'] = 'Start time cannot be less(or equal) than End time.';
$string['start_header'] = 'Start Time';
$string['status_header'] = 'Status';
$string['student'] = 'Student';
$string['student_email'] = 'Student Email Reminders';
$string['tb_t_adde'] = "Add Eval";
$string['tb_t_qok'] = "Question set";
$string['times_chosen'] = 'Times Chosen';
$string['type_c'] = 'Question Type: ';
$string['type_c'] = 'Type: ';
$string['unsatisfactory'] = 'Unsatisfactory';
$string['year_number'] = 'Year Number';
$string['years'] = 'Years ';

$string['complete'] = 'Complete';
$string['delete'] = 'Delete';
$string['start'] = 'Start';

$string['confirm_complete'] = 'Are you sure you want to complete this evaluation. The evaluation will be considered final and will lose its ability to change, or respond to the evaluation.';
$string['confirm_delete'] = 'Are you sure you want to delete this evaluation. It will be unrecoverable.';
$string['confirm_start'] = 'Are you sure you want to start the evaluation. This will restrict what changes can be made.';

$string['add_invig'] = 'Add Invigilator(s)';
$string['already_responded'] = 'You have already responded to this Evaluation.';
$string['average'] = 'Average: ';
$string['comments'] = 'Comments';
$string['comments_c'] = 'Comments: ';
$string['error_question_type'] = 'Question Type Doesn\'t Exist';
$string['eval_preamble_desc'] = 'The preamble displayed above every single course evaluation.';
$string['eval_preamble_header'] = 'Evaluation Preamble';
$string['eval_response'] = 'Evaluation Response';
$string['evaluations'] = 'Evaluations';
$string['invalid_evaluation'] = 'Invalid Evaluation ID';
$string['invalid_responseid'] = 'Invalid Response Id.';
$string['nav_admin'] = 'Department Administration';
$string['nav_cs_mx'] = 'Course Compare Report';
$string['nav_ev_course'] = 'Evaluations By Course';
$string['nav_ev_mn'] = 'Course Evaluations';
$string['nav_course_setings'] = 'Evaluation Settings';
$string['nav_reports'] = 'Reports';
$string['nav_st_qe'] = 'Standard Questions';
$string['nav_create_eval'] = 'New Evaluation';
$string['new_eval_delete_question'] = 'Cannot delete question from a new evaluation.';
$string['open_evaluations'] = 'Open Evaluations';
$string['question_response_header'] = 'Course Evaluation';
$string['standard_questions_info'] = 'This page contains the default questions that are included in all Evaluations. They can be edited by the instructor within an evaluation to meet unique circumstances, but editing the questions here will change the default added to each evaluation when created.';
$string['status'] = 'Status';

$string['agree'] = ' Agree ';
$string['agree_ab'] = 'A';
$string['disagree'] = ' Disagree ';
$string['disagree_ab'] = 'D';
$string['neutral'] = ' Neutral ';
$string['neutral_ab'] = 'N';

//QUESTION TYPES
//1-5 Rate
$string['question_5_rate'] = 'Rate 1-5';
$string['question_5_rate_help'] = 'Strongly Disagree, Disagree, Neutral, Agree, Strongly Agree  (EXEC 29 MAR 1999)';
$string['question_5_rate_response'] = '/5';
$string['strongly_agree'] = ' Strongly Agree ';
$string['strongly_agree_ab'] = 'SA';
$string['strongly_disagree'] = ' Strongly Disagree ';
$string['strongly_disagree_ab'] = 'SD';
$string['not_applicable'] = 'Not Applicable';

//QUESTION TYPES
//1-10 Rate
$string['question_10_rate'] = 'Rate 1-10';
$string['question_10_rate_help'] = 'Rate from 1 being Strongly Disagree, to 5 being Neutral, and 10 to be Strongly Agree';
$string['question_10_rate_response'] = '/10';

$string['email_body'] = 'This is a reminder that you need to complete a course evaluation for';
$string['email_body_html'] = 'This is a reminder that you need to complete a course evaluation for';
$string['email_new_evaluation'] = 'Course Evaluation Reminder: ';

$string['email_early_body'] = 'This is a reminder that you need to setup a course evaluation for';
$string['email_early_body_html'] = 'This is a reminder that you need to setup a course evaluation for';
$string['email_early_evaluation'] = 'Course Evaluation Reminder: ';

$string['email_complete_body'] = 'The email has been send to inform you that the evaluation has been completed for';
$string['email_complete_body_html'] = 'The email has been send to inform you that the evaluation has been completed for';
$string['email_complete_evaluation'] = 'Course Evaluation Complete: ';
$string['email_num_reports'] = 'The number of reponses to the evaluation was';

$string['early_message_delay'] ="Early Reminder Delay in Days"; 
$string['early_message_delay_desc'] = 'The amount of days, after a course starts, before an instructor is reminded to create an evaluation.';

$string['administration'] = 'Administration';
$string['area_admins'] = 'Area Administrators';
$string['dept_selection'] = 'Department Selection';
$string['message_que_limit'] ="Time limit in days that a message can be in queue"; 
$string['message_que_limit_desc'] = "The amount of time an email can be in queue before its disgarded. This occurs if the cron is not running for a period of time, and messages build up. Instead of spamming reminders to students, we delete the messages older than current time - X days";

$string['course_name'] = 'Course Name';
$string['professor_name'] = 'Professor Name';

$string['NA'] = ' Not Applicable';

$string['course_pace_TS'] = ' Too Slow';
$string['course_pace_S'] = ' Slow';
$string['course_pace_JR'] = ' Just Right';
$string['course_pace_F'] = ' Fast';
$string['course_pace_TF'] = ' Too Fast';

$string['required_hours_less_2'] = ' Less than 2';
$string['required_hours_2_4'] = ' 2 to 4';
$string['required_hours_5_7'] = ' 5 to 7';
$string['required_hours_8_10'] = ' 8 to 10';
$string['required_hours_great_10'] = 'Greater than 10';

$string['expected_grade_A'] = ' A- to A+';
$string['expected_grade_B'] = ' B- to B+';
$string['expected_grade_C'] = ' C- to C+';
$string['expected_grade_D'] = ' D- to D+';

$string['gpa4'] = ' 3.70 to 4.0';
$string['gpa3'] = ' 2.7 to 3.69';
$string['gpa2'] = ' 1.7 to 2.69';
$string['gpa1'] = ' 1.0 to 1.69';

$string['interest_level_mmp'] = ' Become much more positive';
$string['interest_level_mp'] = ' Become more positive';
$string['interest_level_s'] = ' Stayed the same';
$string['interest_level_mn'] = ' Become more negative';
$string['interest_level_mmn'] = ' Become much more negative';
