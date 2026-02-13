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

$token = required_param('token', PARAM_ALPHANUM);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);

$url = new moodle_url('/blocks/wdsprefs/teamteach_action.php', ['token' => $token]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('wdsprefs:teamteach_request_details', 'block_wdsprefs'));
$PAGE->set_heading(get_string('wdsprefs:teamteachheading', 'block_wdsprefs'));

// Validate Token.
$request = block_wdsprefs_teamteach::get_request_by_token($token);

if (!$request) {
    print_error('wdsprefs:teamteach_request_not_found', 'block_wdsprefs');
}

// Check User (Requested User must be logged in).
if ($request->requested_userid != $USER->id) {

    // If admin, maybe allow viewing? But for security, stick to user only.
    if (!is_siteadmin()) {
        print_error('wdsprefs:teamteach_unauthorized', 'block_wdsprefs');
    }
}

// Check Expiry.
if ($request->expirytime < time()) {

    // Update status if pending.
    if ($request->status == 'pending') {
        $request->status = 'expired';
        $DB->update_record('block_wdsprefs_teamteach', $request);
    }
    print_error('wdsprefs:teamteach_request_expired', 'block_wdsprefs');
}

// Check Status.
if ($request->status != 'pending') {
    $a = new stdClass();
    $a->status = $request->status;

    // Just show a message, not an error.
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('wdsprefs:teamteach_already_processed', 'block_wdsprefs'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Handle Actions.
if ($action && $confirm && confirm_sesskey()) {
    if ($action == 'approve') {
        if (block_wdsprefs_teamteach::approve_request($request->id)) {
            redirect($url, get_string('wdsprefs:teamteach_approved', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            echo $OUTPUT->notification(get_string('wdsprefs:teamteach_request_failed', 'block_wdsprefs'), 'error');
        }
    } elseif ($action == 'decline') {
        if (block_wdsprefs_teamteach::decline_request($request->id)) {
            redirect($url, get_string('wdsprefs:teamteach_declined', 'block_wdsprefs'), null, \core\output\notification::NOTIFY_INFO);
        } else {
            echo $OUTPUT->notification(get_string('wdsprefs:teamteach_request_failed', 'block_wdsprefs'), 'error');
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('wdsprefs:teamteach_request_details', 'block_wdsprefs'));

// Display Details.
$requester = $DB->get_record('user', ['id' => $request->requester_userid]);
$course = $DB->get_record('course', ['id' => $request->target_course_id]);
$section_ids = json_decode($request->requested_section_ids);
$sections_str = '';
if (!empty($section_ids)) {
    list($insql, $inparams) = $DB->get_in_or_equal($section_ids);
    $sql = "SELECT s.section_number, c.course_subject_abbreviation, c.course_number
            FROM {enrol_wds_sections} s
            JOIN {enrol_wds_courses} c ON s.course_listing_id = c.course_listing_id
            WHERE s.id $insql";
    $sections = $DB->get_records_sql($sql, $inparams);
    $parts = [];
    foreach ($sections as $s) {
        $parts[] = $s->course_subject_abbreviation . ' ' . $s->course_number . ' ' . $s->section_number;
    }
    $sections_str = implode(', ', $parts);
}

$table = new html_table();
$table->data[] = [get_string('wdsprefs:teamteach_requester', 'block_wdsprefs'), fullname($requester)];
$table->data[] = [get_string('wdsprefs:teamteach_target_course', 'block_wdsprefs'), $course->fullname];
$table->data[] = [get_string('wdsprefs:teamteach_requested_sections', 'block_wdsprefs'), $sections_str];
$table->data[] = [get_string('status'), get_string('wdsprefs:teamteach_pending', 'block_wdsprefs')];

echo html_writer::table($table);

// Action Buttons.
$approve_url = new moodle_url($url, ['action' => 'approve', 'confirm' => 1, 'sesskey' => sesskey()]);
$decline_url = new moodle_url($url, ['action' => 'decline', 'confirm' => 1, 'sesskey' => sesskey()]);

// Let's show buttons first, then confirm.
if ($action == 'approve' && !$confirm) {
    echo $OUTPUT->confirm(
        get_string('wdsprefs:teamteach_confirm_approve', 'block_wdsprefs'),
        $approve_url,
        $url
    );
} elseif ($action == 'decline' && !$confirm) {
    echo $OUTPUT->confirm(
        get_string('wdsprefs:teamteach_confirm_decline', 'block_wdsprefs'),
        $decline_url,
        $url
    );
} else {

    // Show buttons.
    $approve_btn_url = new moodle_url($url, ['action' => 'approve']);
    $decline_btn_url = new moodle_url($url, ['action' => 'decline']);

    echo html_writer::start_tag('div', ['class' => 'mt-4 text-center']);
    echo $OUTPUT->single_button($approve_btn_url, get_string('wdsprefs:teamteach_approve', 'block_wdsprefs'), 'post', ['class' => 'tt-approve']);
    echo $OUTPUT->single_button($decline_btn_url, get_string('wdsprefs:teamteach_decline', 'block_wdsprefs'), 'post', ['class' => 'tt-decline']);
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
