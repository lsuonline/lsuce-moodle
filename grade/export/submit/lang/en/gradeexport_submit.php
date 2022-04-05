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
 * Strings for component 'gradeexport_submit', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   gradeexport_submit
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Submit Final Grades';
$string['submit:publish'] = 'Publish final grades to Banner';
$string['submit:view'] = 'Use final grade submit to Banner export';

$string['letters_title'] = 'Step 1 - Check Course Letter Grade Scale';
$string['letters_description'] = "<p>The first step in this process is to ensure that your course grade scale has been properly configured. Please check the Moodle default Letter Grade Scale below and edit accordingly to ensure that letter grades are assigned correctly for your students.</p>";
$string['letters_edit_note'] = "<br/><br/><p>If you edit the grade letters, you will need to restart the \"Submit final grades\" process when you have finished editing the Grade Scale.</p>";
$string['letters_proceed'] = "Proceed to next step";
$string['check_grades_title'] = "Step 2 - Check for Missing Student Grades and Confirm ALL Grades";
$string['check_grades_description'] = "<p>The next step in the process is to ensure that ALL of your students have been assigned a letter grade (failing to do so will cause the grade submission to fail). Please check the student grades below.</p>";

$string['check_grades_proceed'] = "Proceed to next step";

$string['final_send_title'] = "Step 3 - Ready to Submit Grades";
$string['final_send_information'] = '<p>Your final grades are ready to be submitted to the Registrars Office. Once the process has finished, you will receive an email confirmation that everything was submitted correctly. If an error occurs during the process, this error will be explained in the email and instructions on how to correct the issue wil be outlined.</p><p><strong>Please be patient. Grades may take awhile to submit. please don\'t close your browser window while the page is loading.</strong></p> ';
$string['final_send_proceed'] = "Submit Grades Now";

$string['grade_submit_title'] = "Step 4 - Submitting Grades to Banner";
$string['grade_submit_information'] = '<p>Submitting grades to banner...</p>';

$string['curusernotinlmb'] = 'Current user not found in LMB table.';
$string['coursenotinlmb'] = 'Current course not found in LMB table.';
$string['studentnotinlmb'] = 'Student not found in LMB table.';
$string['studentnotincourse'] = 'Student not found.';
$string['enrolnotinlmb'] = 'Enrolment not found in LMB table.';
$string['enrolnotincourse'] = 'Enrolment not found in Course table.';

$string['grades_submitted_pending'] = '<p>Grades have already been submitted and are currently being processed.</p><p>Submission time was {$a->time}.</p>';
$string['grades_submitted_already'] = '<p>Grades have already been successfully submitted.</p><p>Submission time was {$a->time}.</p>';

$string['grades_submitted_already_title'] = "Grades Already Successfully Submitted";
$string['grades_submitted_pending_title'] = "Grades Already Submitted";

$string['enter_grades'] = "Back to Grade Report";
$string['reset_final_grades'] = "Remove Final Grades Calculation and start again.";
$string['back_one_step'] = "Back to Step 2";

$string['missing_grades_note'] = "<p>You are missing grades for this course. Please click the <strong>".$string['enter_grades']."</strong> link below to enter the missing grades, then restart the submission process.</p>";

$string['error_teacher_noidnumber'] = "<p><strong>Error: </strong> no idnumber for logged in user.</p>";

$string['contact_info'] = "<p>For help, please contact the Teaching Centre at 403.380.1856 or <a href='mailto:teachingsupport@uleth.ca'>teachingsupport@uleth.ca</a>";

$string['contact_info_email'] = "For help, please contact the Teaching Centre at 403.380.1856 or teachingsupport@uleth.ca";


$string['grades_not_submitted'] = "<p><strong>Grades have not been submitted.</strong></p>";

$string['grade_submission_error'] = "<p><strong>There were errors in your grade submission</strong></p><p>Details are below.</p>";

$string['grade_submission_noresponse'] = "<p><strong>There was an error submitting your grades.</strong> Your grades may or may not have been successfully submitted.";

$string['grade_submission_partialsuccess'] = '<p>Moodle will send you a confirmation e-mail that your grades have been successfully submitted to Banner.</p><p>It is recommended that you double check the Bridge Web Grade system to ensure your final grades have been submitted for approval. </p>';

$string['grade_submission_success'] = '<p>Moodle will send you a confirmation e-mail that your grades have been successfully submitted to Banner.</p><p>
It is recommended that you double check the Bridge Web Grade system to ensure your final grades have been submitted for approval. </p>';

$string['grades_are_submitted'] = "<p><strong>Grades have successfully been submitted.</strong></p>";

$string['final_column_created'] = "<p>The <strong>[Final Grades]</strong> column has been created. You may now review or modify final grades by clicking the <strong>".$string['enter_grades']."</strong> link below.</p>";


$string['success_email'] = "Congratulations, your Moodle final grades have been successfully submitted to Banner.";
$string['failure_email'] = "There was an error while submitting your grades.";
$string['partialsuccess_email'] = "Congratulations, your Moodle final grades have been successfully submitted to Banner. There were some errors during submission. ";


$string['settings_webservice_url_title'] = 'Webservice URL';
$string['settings_webservice_url_description'] = 'URL of the Banner webservice.';

$string['settings_webservice_url_get_title'] = 'Webservice Results URL (GET)';
$string['settings_webservice_url_get_description'] = 'Results URL of the Banner webservice.';

$string['settings_webservice_url_get_dangler_title'] = 'Webservice Results URL (GET) end piece';
$string['settings_webservice_url_get_dangler_description'] = 'Results URL of the Banner webservice.';

$string['settings_webservice_username_title'] = 'Webservice Username';
$string['settings_webservice_username_description'] = 'Username to authenticate to webservice.';

$string['settings_webservice_password_title'] = 'Webservice Password';
$string['settings_webservice_password_description'] = 'Password to authenticate to webservice.';

$string['settings_datasource_title'] = 'Datasource';
$string['settings_datasource_description'] = 'Datasource value for XML.';

$string['settings_institution_title'] = 'Institution';
$string['settings_institution_description'] = 'Institution value for XML.';

$string['settings_final_grades_column_title'] = 'Final Grades Column Title';
$string['settings_final_grades_column_description'] = 'The title to give the final grades column.';

$string['notification_email_addresses_title'] = 'Notification Email Addresses ';
$string['notification_email_addresses_description'] = 'Comma seperated list of email addresses of people who should get notified when there is a grade submission.';

$string['success_email_subject']='Moodle grade submission successful';
$string['failure_email_subject']='Moodle grade submission failed';
$string['partialsuccess_email_subject']='Moodle grade submission successful';

$string['settings_from_email_title']="From email address";
$string['settings_from_email_description']="The 'From' email address for notification emails.";
