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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/wdsprefs/classes/wdsprefs.php');
require_once($CFG->dirroot . '/blocks/wdsprefs/classes/teamteach.php');

require_login();

$url = new moodle_url('/blocks/wdsprefs/teamteach.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('wdsprefs:teamteach', 'block_wdsprefs'));
$PAGE->set_heading(get_string('wdsprefs:teamteachheading', 'block_wdsprefs'));

// Action Handling Logic.
$action = optional_param('action', '', PARAM_ALPHA);
$request_id = optional_param('request_id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

if ($action && $request_id && confirm_sesskey()) {
    switch ($action) {
        case 'cancel':
             if (!$confirm) {
                 echo $OUTPUT->header();
                 echo $OUTPUT->confirm(
                     get_string('wdsprefs:teamteach_confirm_cancel', 'block_wdsprefs'),
                     new moodle_url($url, ['action' => 'cancel', 'request_id' => $request_id, 'confirm' => 1, 'sesskey' => sesskey()]),
                     $url
                 );
                 echo $OUTPUT->footer();
                 exit;
             }
             if (block_wdsprefs_teamteach::cancel_request($request_id, $USER->id)) {
                 redirect($url, get_string('wdsprefs:teamteach_cancelled', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_SUCCESS);
             } else {
                 echo $OUTPUT->header();
                 echo $OUTPUT->notification(get_string('wdsprefs:teamteach_failed_action', 'block_wdsprefs'), 'error');
                 echo $OUTPUT->continue_button($url);
                 echo $OUTPUT->footer();
                 exit;
             }
             break;

        case 'undo':
             if (!$confirm) {
                 echo $OUTPUT->header();
                 echo $OUTPUT->confirm(
                     get_string('wdsprefs:teamteach_confirm_undo', 'block_wdsprefs'),
                     new moodle_url($url, ['action' => 'undo', 'request_id' => $request_id, 'confirm' => 1, 'sesskey' => sesskey()]),
                     $url
                 );
                 echo $OUTPUT->footer();
                 exit;
             }
             if (block_wdsprefs_teamteach::undo_request($request_id, $USER->id)) {
                 redirect($url, get_string('wdsprefs:teamteach_undone_success', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_SUCCESS);
             } else {
                 echo $OUTPUT->header();
                 echo $OUTPUT->notification(get_string('wdsprefs:teamteach_failed_action', 'block_wdsprefs'), 'error');
                 echo $OUTPUT->continue_button($url);
                 echo $OUTPUT->footer();
                 exit;
             }
             break;

        case 'revoke':
             if (!$confirm) {
                 echo $OUTPUT->header();
                 echo $OUTPUT->confirm(
                     get_string('wdsprefs:teamteach_confirm_revoke', 'block_wdsprefs'),
                     new moodle_url($url, ['action' => 'revoke', 'request_id' => $request_id, 'confirm' => 1, 'sesskey' => sesskey()]),
                     $url
                 );
                 echo $OUTPUT->footer();
                 exit;
             }
             if (block_wdsprefs_teamteach::undo_request($request_id, $USER->id)) {
                 redirect($url, get_string('wdsprefs:teamteach_revoked_success', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_SUCCESS);
             } else {
                 echo $OUTPUT->header();
                 echo $OUTPUT->notification(get_string('wdsprefs:teamteach_failed_action', 'block_wdsprefs'), 'error');
                 echo $OUTPUT->continue_button($url);
                 echo $OUTPUT->footer();
                 exit;
             }
             break;

        case 'approve':
             $req = $DB->get_record('block_wdsprefs_teamteach', ['id' => $request_id]);
             if ($req && $req->requested_userid == $USER->id) {
                 if (!$confirm) {
                     echo $OUTPUT->header();
                     echo $OUTPUT->confirm(
                         get_string('wdsprefs:teamteach_confirm_approve', 'block_wdsprefs'),
                         new moodle_url($url, ['action' => 'approve', 'request_id' => $request_id, 'confirm' => 1, 'sesskey' => sesskey()]),
                         $url
                     );
                     echo $OUTPUT->footer();
                     exit;
                 }
                 if (block_wdsprefs_teamteach::approve_request($request_id)) {
                     redirect($url, get_string('wdsprefs:teamteach_approved', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_SUCCESS);
                 } else {
                     echo $OUTPUT->header();
                     echo $OUTPUT->notification(get_string('wdsprefs:teamteach_failed_action', 'block_wdsprefs'), 'error');
                     echo $OUTPUT->continue_button($url);
                     echo $OUTPUT->footer();
                     exit;
                 }
             }
             break;

        case 'decline':
             $req = $DB->get_record('block_wdsprefs_teamteach', ['id' => $request_id]);
             if ($req && $req->requested_userid == $USER->id) {
                 if (!$confirm) {
                     echo $OUTPUT->header();
                     echo $OUTPUT->confirm(
                         get_string('wdsprefs:teamteach_confirm_decline', 'block_wdsprefs'),
                         new moodle_url($url, ['action' => 'decline', 'request_id' => $request_id, 'confirm' => 1, 'sesskey' => sesskey()]),
                         $url
                     );
                     echo $OUTPUT->footer();
                     exit;
                 }
                 if (block_wdsprefs_teamteach::decline_request($request_id)) {
                     redirect($url, get_string('wdsprefs:teamteach_declined', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_SUCCESS);
                 } else {
                     echo $OUTPUT->header();
                     echo $OUTPUT->notification(get_string('wdsprefs:teamteach_failed_action', 'block_wdsprefs'), 'error');
                     echo $OUTPUT->continue_button($url);
                     echo $OUTPUT->footer();
                     exit;
                 }
             }
             break;
    }
}

if (!wdsprefs::faster_get_instructor_status($USER->id) && !is_siteadmin()) {
    print_error('wdsprefs:noinstructor', 'block_wdsprefs');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('wdsprefs:teamteach', 'block_wdsprefs'));
echo html_writer::tag('p', get_string('wdsprefs:teamteach_desc', 'block_wdsprefs'));

// Get parms.
$target_course_id = optional_param('target_course_id', 0, PARAM_INT);
$search_query = optional_param('search_query', '', PARAM_TEXT);
$selected_teacher_id = optional_param('selected_teacher_id', 0, PARAM_INT);
$section_ids = optional_param_array('section_ids', [], PARAM_INT);
$send_request = optional_param('send_request', '', PARAM_TEXT);

// Get user's WDS courses (shells).
$my_courses = wdsprefs::get_courses($USER->id);
$shells = [];
$potential_shells = [];

// Group sections by Moodle course ID first to check eligibility as a whole shell.
foreach ($my_courses as $period_id => $period_sections) {
    foreach ($period_sections as $section) {
        if (!empty($section->moodle_courseid) && is_numeric($section->moodle_courseid)) {
            if (!isset($potential_shells[$section->moodle_courseid])) {
                $course_obj = $DB->get_record('course', ['id' => $section->moodle_courseid]);
                if ($course_obj) {
                    $potential_shells[$section->moodle_courseid] = [
                        'id' => $course_obj->id,
                        'fullname' => $course_obj->fullname,
                        'period_id' => $section->academic_period_id,
                        'section_ids' => []
                    ];
                }
            }
            if (isset($potential_shells[$section->moodle_courseid])) {
                $potential_shells[$section->moodle_courseid]['section_ids'][] = $section->id;
            }
        }
    }
}

// Filter shells based on eligibility.
foreach ($potential_shells as $moodle_courseid => $shell_data) {
    if (block_wdsprefs_teamteach::check_shell_eligibility($moodle_courseid, $shell_data['section_ids'])) {
        $shells[$moodle_courseid] = [
            'id' => $shell_data['id'],
            'fullname' => $shell_data['fullname'],
            'period_id' => $shell_data['period_id']
        ];
    }
}

// Process Request.
if ($send_request && $target_course_id && $selected_teacher_id && !empty($section_ids)) {
    require_sesskey();

    $request_id = block_wdsprefs_teamteach::create_request($USER->id, $target_course_id, $selected_teacher_id, $section_ids);

    if ($request_id) {
        echo $OUTPUT->notification(get_string('wdsprefs:teamteach_request_created', 'block_wdsprefs'), 'success');
        // Link back to start.
        echo $OUTPUT->continue_button(new moodle_url('/blocks/wdsprefs/teamteach.php'));
        echo $OUTPUT->footer();
        exit;
    } else {
        echo $OUTPUT->notification(get_string('wdsprefs:teamteach_request_failed', 'block_wdsprefs'), 'error');
    }
}

// Form Start.
echo html_writer::start_tag('form', ['action' => $url, 'method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Target Course Dropdown.
$options = ['' => get_string('wdsprefs:teamteach_select_target_course', 'block_wdsprefs')];
foreach ($shells as $shell) {
    $options[$shell['id']] = $shell['fullname'];
}

echo html_writer::label(get_string('wdsprefs:teamteach_target_course', 'block_wdsprefs'), 'target_course_id');
echo html_writer::select($options, 'target_course_id', $target_course_id, null, ['class' => 'form-control', 'onchange' => 'this.form.submit()']);
echo html_writer::empty_tag('br');

// Search Teacher.
if ($target_course_id) {

    // Show search box.
    echo html_writer::tag('h4', get_string('wdsprefs:teamteach_search_teacher', 'block_wdsprefs'));
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'search_query',
        'value' => $search_query,
        'class' => 'form-control',
        'placeholder' => get_string('wdsprefs:teamteach_search_placeholder', 'block_wdsprefs')
    ]);
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search'), 'class' => 'btn btn-secondary']);
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('br');

    // Teacher Results.
    if ($search_query) {
        $academic_period_id = '';
        if (isset($shells[$target_course_id])) {
            $academic_period_id = $shells[$target_course_id]['period_id'];
        }
        $teachers = block_wdsprefs_teamteach::search_teachers($search_query, $academic_period_id);

        if (empty($teachers)) {
            echo $OUTPUT->notification(get_string('wdsprefs:teamteach_no_teacher_found', 'block_wdsprefs'), 'warning');
        } else {
            echo html_writer::tag('h4', 'Select Instructor');
            echo html_writer::start_tag('ul', ['class' => 'list-group']);
            foreach ($teachers as $teacher) {
                $name = $teacher->firstname . ' ' . $teacher->lastname . ' (' . $teacher->email . ')';
                $link = new moodle_url($url, [
                    'target_course_id' => $target_course_id,
                    'search_query' => $search_query,
                    'selected_teacher_id' => $teacher->userid
                ]);

                $active = ($selected_teacher_id == $teacher->userid) ? 'active' : '';
                echo html_writer::tag('li',
                    html_writer::link($link, $name, ['class' => 'text-decoration-none ' . ($active ? 'text-white' : '')]),
                    ['class' => 'list-group-item ' . $active]
                );
            }
            echo html_writer::end_tag('ul');
            echo html_writer::empty_tag('br');
        }
    }
}

// Select Sections.
if ($target_course_id && $selected_teacher_id) {

    // Get period of target course.
    $target_period_id = $shells[$target_course_id]['period_id'];

    // Get teacher's sections in that period.
    $sections = block_wdsprefs_teamteach::get_teacher_sections($selected_teacher_id, $target_period_id);

    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'selected_teacher_id', 'value' => $selected_teacher_id]);

    if (empty($sections)) {
        echo $OUTPUT->notification(get_string('wdsprefs:teamteach_no_sections_found', 'block_wdsprefs'), 'warning');
    } else {
        echo html_writer::tag('h4', get_string('wdsprefs:teamteach_select_sections', 'block_wdsprefs'));

        foreach ($sections as $section) {

            // Check if already in target course?
            if ($section->moodle_status == $target_course_id) {
                continue;
            }

            $label = $section->course_subject_abbreviation . ' ' . $section->course_number . ' ' . $section->section_number;

            // Check if section is available.
            $status = block_wdsprefs_teamteach::check_section_status($section->id, $selected_teacher_id);
            $disabled = '';
            $status_message = '';

            if (!$status['available']) {
                $disabled = 'disabled';
                $status_message = html_writer::tag('span', ' (' . $status['message'] . ')', ['class' => 'text-muted small']);
            }

            // Add checkbox.
            echo html_writer::start_tag('div', ['class' => 'form-check']);
            $input_attributes = [
                'type' => 'checkbox',
                'name' => 'section_ids[]',
                'value' => $section->id,
                'class' => 'form-check-input',
                'id' => 'section_' . $section->id
            ];
            if ($disabled) {
                $input_attributes['disabled'] = 'disabled';
            }

            echo html_writer::empty_tag('input', $input_attributes);
            echo html_writer::label($label . $status_message, 'section_' . $section->id, false, ['class' => 'form-check-label']);
            echo html_writer::end_tag('div');
        }

        echo html_writer::empty_tag('br');
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'name' => 'send_request',
            'value' => get_string('wdsprefs:teamteach_submit_request', 'block_wdsprefs'),
            'class' => 'btn btn-primary'
        ]);
    }
}

echo html_writer::end_tag('form');

// New Sections: Requests Tables.
echo html_writer::empty_tag('hr');

if (!function_exists('render_teamteach_table')) {
    function render_teamteach_table($requests, $is_requester) {
        global $OUTPUT, $DB;
        if (empty($requests)) {
            return '';
        }

        $table = new html_table();
        $table->head = [
            get_string('wdsprefs:teamteach_target_course', 'block_wdsprefs'),
            $is_requester ? 'Instructor' : get_string('wdsprefs:teamteach_requester', 'block_wdsprefs'),
            get_string('wdsprefs:teamteach_status', 'block_wdsprefs'),
            get_string('date'),
            get_string('wdsprefs:teamteach_action', 'block_wdsprefs')
        ];

        foreach ($requests as $r) {
            $course = $DB->get_record('course', ['id' => $r->target_course_id]);
            $target_name = $course ? $course->fullname : 'Unknown Course';

            if ($is_requester) {
                $other_user = $DB->get_record('user', ['id' => $r->requested_userid]);
            } else {
                $other_user = $DB->get_record('user', ['id' => $r->requester_userid]);
            }
            $other_name = $other_user ? fullname($other_user) : 'Unknown User';

            $actions = '';
            $url = new moodle_url('/blocks/wdsprefs/teamteach.php');

            if ($r->status == 'pending') {
                if ($is_requester) {

                    // Cancel Button.
                    $cancel_url = new moodle_url($url, ['action' => 'cancel', 'request_id' => $r->id, 'sesskey' => sesskey()]);
                    $actions .= $OUTPUT->single_button($cancel_url, get_string('wdsprefs:teamteach_cancel', 'block_wdsprefs'), 'post');
                } else {

                    // Approve / Decline Buttons.
                    $approve_url = new moodle_url($url, ['action' => 'approve', 'request_id' => $r->id, 'sesskey' => sesskey()]);
                    $decline_url = new moodle_url($url, ['action' => 'decline', 'request_id' => $r->id, 'sesskey' => sesskey()]);
                    $actions .= $OUTPUT->single_button($approve_url, get_string('wdsprefs:teamteach_approve', 'block_wdsprefs'), 'post');
                    $actions .= $OUTPUT->single_button($decline_url, get_string('wdsprefs:teamteach_decline', 'block_wdsprefs'), 'post');
                }
            } elseif ($r->status == 'approved') {
                 // View Course Button.
                 if ($course) {
                     $course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
                     $actions .= html_writer::link($course_url, get_string('wdsprefs:viewcourse', 'block_wdsprefs'), ['class' => 'btn btn-primary btn-sm mr-1', 'target' => '_blank']);
                 }

                 // View Sections Button.
                 $sections_url = new moodle_url('/blocks/wdsprefs/teamteach_sections.php', ['request_id' => $r->id]);
                 $actions .= html_writer::link($sections_url, get_string('wdsprefs:viewsections', 'block_wdsprefs'), ['class' => 'btn btn-secondary btn-sm']);
            }
            $status_string = ucfirst($r->status);
            $status_key = 'wdsprefs:teamteach_status_' . $r->status;
            if (get_string_manager()->string_exists($status_key, 'block_wdsprefs')) {
                $status_string = get_string($status_key, 'block_wdsprefs');
            }

            $table->data[] = [
                $target_name,
                $other_name,
                $status_string,
                userdate($r->timecreated),
                $actions
            ];
        }

        return html_writer::table($table);
    }
}

// Requests I have made.
$my_pending = block_wdsprefs_teamteach::get_pending_requests_by_requester($USER->id);
$my_approved = block_wdsprefs_teamteach::get_approved_requests_by_requester($USER->id);

if ($my_pending || $my_approved) {
    echo html_writer::tag('h3', get_string('wdsprefs:teamteach_my_requests', 'block_wdsprefs'));

    if ($my_pending) {
        echo html_writer::tag('h4', get_string('wdsprefs:teamteach_status_pending', 'block_wdsprefs'));
        echo render_teamteach_table($my_pending, true);
    }

    if ($my_approved) {
        echo html_writer::tag('h4', get_string('wdsprefs:teamteach_status_approved', 'block_wdsprefs'));
        echo render_teamteach_table($my_approved, true);
    }
}

// Requests for me.
$for_me_pending = block_wdsprefs_teamteach::get_pending_requests_by_requested($USER->id);
$for_me_approved = block_wdsprefs_teamteach::get_approved_requests_by_requested($USER->id);

if ($for_me_pending || $for_me_approved) {
    echo html_writer::tag('h3', get_string('wdsprefs:teamteach_requests_for_me', 'block_wdsprefs'));

    if ($for_me_pending) {
        echo html_writer::tag('h4', get_string('wdsprefs:teamteach_status_pending', 'block_wdsprefs'));
        echo render_teamteach_table($for_me_pending, false);
    }

    if ($for_me_approved) {
        echo html_writer::tag('h4', get_string('wdsprefs:teamteach_status_approved', 'block_wdsprefs'));
        echo render_teamteach_table($for_me_approved, false);
    }
}

echo $OUTPUT->footer();
