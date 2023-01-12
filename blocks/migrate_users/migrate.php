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
 * @package    block_migrate_users
 * @copyright  2019 onwards Louisiana State University
 * @copyright  2019 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/migrate_users/locallib.php');
global $CFG, $DB;

// Grab the urlparms for future use.
$page_params = [
    'userfrom' => required_param('userfrom', PARAM_TEXT),
    'userto' => required_param('userto', PARAM_TEXT),
    'courseid' => required_param('courseid', PARAM_INT)
];

$confirm = optional_param('confirm', 0, PARAM_INT);

// Require that the user is logged in and we know who they are.
require_login();

// Get the course from the course id.
$course = $DB->get_record('course', array('id' => $page_params['courseid']));

// Get the user who will serve as the data source.
$userfrom = migrate::get_user($page_params['userfrom']);

// Get the user who will serve as the data recipient.
$userto = migrate::get_user($page_params['userto']);

// Set up the page and nav links.
if ($page_params['courseid']) {
    $course_context = context_course::instance($course->id);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_context($course_context);
    $PAGE->set_url(new moodle_url('/blocks/migrate_users/migrate.php', $page_params));
    $PAGE->navbar->add($course->fullname, new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id)));
    $PAGE->navbar->add(get_string('migrate_users', 'block_migrate_users'), null);
}

// Set up the notification strings.
$handle_user_enrollments = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_user_enrollments', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_role_enrollments = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_role_enrollments', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_groups_membership = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_groups_membership', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_logs = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_logs', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_standard_logs = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_standard_logs', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_events = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_events', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_posts = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_posts', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_course_modules_completions = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_course_modules_completions', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_course_completions = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_course_completions', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_course_completion_criteria = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_course_completion_criteria', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_grades = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_grades', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_grades_history = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_grades_history', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_assign_grades = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_assign_grades', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_assign_submissions = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_assign_submissions', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_assign_user_flags = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_assign_user_flags', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_assign_user_mapping = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_assign_user_mapping', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_lesson_attempts = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_lesson_attempts', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_lesson_grades = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_lesson_grades', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_quiz_attempts = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_quiz_attempts', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_quiz_grades = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_quiz_grades', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_scorm_scoes = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_scorm_scoes', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$handle_choice_answers = html_writer::div(get_string('prefix', 'block_migrate_users') . get_string('handle_choice_answers', 'block_migrate_users') . get_string('found', 'block_migrate_users'), "alert alert-info alert-block fade in ");

// Set up success and failure strings.
$success = html_writer::div(get_string('success', 'block_migrate_users'), "alert alert-success alert-block fade in ");
$failure = html_writer::div(get_string('securityviolation', 'block_migrate_users'), "alert alert-error alert-block fade in ");
$mistake = html_writer::div(get_string('missingboth', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$tomistake = html_writer::div(get_string('missingto', 'block_migrate_users'), "alert alert-info alert-block fade in ");
$frommistake = html_writer::div(get_string('missingfrom', 'block_migrate_users'), "alert alert-info alert-block fade in ");
if(empty($userto) || empty($userfrom)) {
    echo $OUTPUT->header();
    if(empty($userto) && empty($userfrom)) {
        echo $mistake;
    } else {
    echo(empty($userto)?$tomistake:$frommistake);
    }
    $mistakebutton = html_writer::link(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id), 'coursetools'), get_string('continue'), array('class' => 'btn btn-success'));
    echo $mistakebutton;
echo $OUTPUT->footer();
exit;
}

$confirmation = html_writer::div(get_string('alldata', 'block_migrate_users') . $userfrom->firstname . ' ' . $userfrom->lastname . ' (' . $userfrom->username . ') ' . get_string('moveto', 'block_migrate_users') . $userto->firstname . ' ' . $userto->lastname . ' (' .$userto->username . ') ' . get_string('deleted', 'block_migrate_users'), "alert alert-error alert-block fade in ");

// Build the continue button.
$continuebutton = html_writer::link(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id)), get_string('continue'), array('class' => 'btn btn-success'));

// Begin page output.
echo $OUTPUT->header();

// Again, check if the user can use the tool.
if (migrate::can_use()) {

    // Check to see if they've confirmed that they REALLY want to go ahead with it.
    if (!$confirm) {

        echo $confirmation;

        // Redirect them to their course.
        $optionsno = new moodle_url('/course/view.php', array('id' => $course->id));

        // Reload the page with the confirmation.
        $optionsyes = new moodle_url('/blocks/migrate_users/migrate.php', array('userfrom' => $userfrom->username, 'userto' => $userto->username, 'courseid' => $course->id, 'confirm' => 1, 'sesskey' => sesskey()));

        // Build the mini-form.
        echo $OUTPUT->confirm(get_string('continue', 'block_migrate_users', 'migrate.php'), $optionsyes, $optionsno);
    } else {

        // Now that they've confirmed, make sure they have the correct session key.
        if (confirm_sesskey()) {

            // Run the migrate::handle_user_enrollments method and catch any issues.
            try {
                migrate::handle_user_enrollments($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_user_enrollments;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_user_enrollments.', "alert alert-error alert-block fade in ");
            }

            // Run the migrate::handle_role_enrollments method and catch any issues.
            try {
                migrate::handle_role_enrollments($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_role_enrollments;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_role_enrollments.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_groups_membership method and catch any issues.
            try {
                migrate::handle_groups_membership($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_groups_membership;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_groups_membership.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_logs method and catch any issues.
            try {
                migrate::handle_logs($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_logs;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_logs.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_standard_logs method and catch any issues.
            try {
                migrate::handle_standard_logs($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_standard_logs;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_standard_logs.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_events method and catch any issues.
            try {
                migrate::handle_events($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_events;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_events.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_posts method and catch any issues.
            try {
                migrate::handle_posts($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_posts;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_posts.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_course_modules_completions method and catch any issues.
            try {
                migrate::handle_course_modules_completions($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_course_modules_completions;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_course_modules_completions.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_course_completions method and catch any issues.
            try {
                migrate::handle_course_completions($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_course_completions;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_course_completions.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_course_completion_criteria method and catch any issues.
            try {
                migrate::handle_course_completion_criteria($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_course_completion_criteria;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_course_completion_criteria.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_grades method and catch any issues.
            try {
                migrate::handle_grades($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_grades;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_grades.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_grades_history method and catch any issues.
            try {
                migrate::handle_grades_history($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_grades_history;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_grades_history.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_assign_grades method and catch any issues.
            try {
                migrate::handle_assign_grades($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_assign_grades;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_assign_grades.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_assign_submissions method and catch any issues.
            try {
                migrate::handle_assign_submissions($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_assign_submissions;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_assign_submissions.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_assign_user_flags method and catch any issues.
            try {
                migrate::handle_assign_user_flags($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_assign_user_flags;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_assign_user_flags.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_assign_user_mapping method and catch any issues.
            try {
                migrate::handle_assign_user_mapping($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_assign_user_mapping;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_assign_user_mapping.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_lesson_attempts method and catch any issues.
            try {
                migrate::handle_lesson_attempts($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_lesson_attempts;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_lesson_attempts.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_lesson_grades method and catch any issues.
            try {
                migrate::handle_lesson_grades($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_lesson_grades;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_lesson_grades.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_quiz_attempts method and catch any issues.
            try {
                migrate::handle_quiz_attempts($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_quiz_attempts;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_quiz_attempts.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_quiz_grades method and catch any issues.
            try {
                migrate::handle_quiz_grades($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_quiz_grades;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_quiz_grades.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_scorm_scoes method and catch any issues.
            try {
                migrate::handle_scorm_scoes($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_scorm_scoes;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_scorm_scoes.', "alert alert-error alert-block fade in ");;
            }

            // Run the migrate::handle_choice_answers method and catch any issues.
            try {
                migrate::handle_choice_answers($page_params['userfrom'], $page_params['userto'], $page_params['courseid']);
                echo $handle_choice_answers;
            } catch (Exception $e) {
                echo html_writer::div(get_string('exception', 'block_migrate_users') . ' ' . $e->getMessage() . ' in migrate::handle_choice_answers.', "alert alert-error alert-block fade in ");;
            }

            // Let the user know everything worked.
            echo $success;
            echo $continuebutton;
        } else {
            echo $failure;
            echo $continuebutton;
        }
    }
} else {
    echo $failure;
    echo $continuebutton;
}

echo $OUTPUT->footer();
