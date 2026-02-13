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
 * View sections in a team teach request.
 *
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/wdsprefs/classes/wdsprefs.php');
require_once($CFG->dirroot . '/blocks/wdsprefs/classes/teamteach.php');

require_login();
$context = context_system::instance();

$request_id = required_param('request_id', PARAM_INT);
$url = new moodle_url('/blocks/wdsprefs/teamteach_sections.php', ['request_id' => $request_id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('wdsprefs:teamteachsections', 'block_wdsprefs'));
$PAGE->set_heading(get_string('wdsprefs:teamteachsections', 'block_wdsprefs'));

// Add breadcrumbs.
$PAGE->navbar->add(
    get_string('home'),
    new moodle_url('/')
);
$PAGE->navbar->add(
    get_string('wdsprefs:teamteach', 'block_wdsprefs'),
    new moodle_url('/blocks/wdsprefs/teamteach.php')
);
$PAGE->navbar->add(
    get_string('wdsprefs:teamteachsections', 'block_wdsprefs'),
    $url
);

// Get request.
$request = $DB->get_record('block_wdsprefs_teamteach', ['id' => $request_id]);

if (!$request) {
    print_error('wdsprefs:teamteach_request_not_found', 'block_wdsprefs');
}

// Check permissions.
if ($request->requester_userid != $USER->id && $request->requested_userid != $USER->id && !is_siteadmin()) {
    print_error('nopermissions', 'error');
}

echo $OUTPUT->header();

// Target Course.
$target_course = $DB->get_record('course', ['id' => $request->target_course_id]);
if ($target_course) {
    echo html_writer::tag('h3', $target_course->fullname);
    $courseurl = new moodle_url('/course/view.php', ['id' => $target_course->id]);
    echo html_writer::tag('p',
        html_writer::link($courseurl, get_string('wdsprefs:viewcourse', 'block_wdsprefs'), ['class' => 'btn btn-primary', 'target' => '_blank'])
    );
}

// Other user info.
$is_requester = ($request->requester_userid == $USER->id);
$other_userid = $is_requester ? $request->requested_userid : $request->requester_userid;
$other_user = $DB->get_record('user', ['id' => $other_userid]);
$role_label = $is_requester ? get_string('wdsprefs:instructorheading', 'block_wdsprefs') : get_string('wdsprefs:teamteach_requester', 'block_wdsprefs');

echo html_writer::tag('p', $role_label . ': ' . fullname($other_user));

// Sections Table.
$section_ids = json_decode($request->requested_section_ids);
if (!empty($section_ids)) {
    list($insql, $inparams) = $DB->get_in_or_equal($section_ids);

    // Join to get details.
    $sql = "SELECT s.*, c.course_subject_abbreviation, c.course_number
            FROM {enrol_wds_sections} s
            INNER JOIN {enrol_wds_courses} c ON s.course_listing_id = c.course_listing_id
            WHERE s.id $insql";
    $sections = $DB->get_records_sql($sql, $inparams);

    // Render table.
    $table = new html_table();
    $table->head = [
        get_string('wdsprefs:period', 'block_wdsprefs'),
        get_string('wdsprefs:coursename', 'block_wdsprefs'),
        get_string('wdsprefs:section', 'block_wdsprefs')
    ];

    foreach ($sections as $section) {

         // Period name logic.
         $periodname = wdsprefs::get_current_taught_periods($section->academic_period_id);
         $pname = is_array($periodname) ? reset($periodname) : $periodname;

         $row = [];
         $row[] = $pname;
         $row[] = $section->course_subject_abbreviation . ' ' . $section->course_number;
         $row[] = $section->section_number;
         $table->data[] = $row;
    }
    echo html_writer::table($table);
}

// Undo/Revoke Button.
$action = $is_requester ? 'undo' : 'revoke';
$label = $is_requester ? get_string('wdsprefs:teamteach_undo', 'block_wdsprefs') : get_string('wdsprefs:teamteach_revoke', 'block_wdsprefs');

$undo_url = new moodle_url('/blocks/wdsprefs/teamteach.php', ['action' => $action, 'request_id' => $request_id, 'sesskey' => sesskey()]);

echo html_writer::start_div('mt-4 buttons-container');

// Undo button (links to confirmation in teamteach.php).
echo html_writer::link($undo_url, $label, ['class' => 'btn btn-warning mr-2']);

echo ' ';
// Back button.
echo html_writer::link(new moodle_url('/blocks/wdsprefs/teamteach.php'), get_string('back'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo $OUTPUT->footer();
