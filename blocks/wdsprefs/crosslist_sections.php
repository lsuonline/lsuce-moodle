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
 * View sections in a crosslisted shell.
 * 
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required Moodle core.
require('../../config.php');

// Get the main wdsprefs class.
require_once("$CFG->dirroot/blocks/wdsprefs/classes/wdsprefs.php");

// Require user to be logged in.
require_login();

// Get system context for permissions.
$context = context_system::instance();

// Get the crosslist ID.
$id = required_param('id', PARAM_INT);

// Set up the page.
$url = new moodle_url('/blocks/wdsprefs/crosslist_sections.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('wdsprefs:crosslistsections', 'block_wdsprefs'));
$PAGE->set_heading(get_string('wdsprefs:crosslistsections', 'block_wdsprefs'));

// Load required CSS.
$PAGE->requires->css('/blocks/wdsprefs/styles.css');

// Add breadcrumbs.
$PAGE->navbar->add(
    get_string('home'),
    new moodle_url('/')
);
$PAGE->navbar->add(
    get_string('wdsprefs:crosslist', 'block_wdsprefs'),
    new moodle_url('/blocks/wdsprefs/crosslist.php')
);
$PAGE->navbar->add(
    get_string('wdsprefs:crosslistsections', 'block_wdsprefs'),
    new moodle_url('/blocks/wdsprefs/crosslist_sections.php', ['id' => $id])
);

// Output the header.
echo $OUTPUT->header();

// Get the crosslist info.
$crosslist = wdsprefs::get_crosslist_info($id);

// Check if crosslist exists and belongs to current user.
if (!$crosslist || $crosslist->userid != $USER->id) {
    echo $OUTPUT->notification(get_string('wdsprefs:nocrosslist', 'block_wdsprefs'), 'notifyerror');
    echo $OUTPUT->footer();
    exit;
}

// Display crosslist information.
echo html_writer::tag('h3', $crosslist->shell_name);

// Display course link if it exists.
if ($crosslist->moodle_course_id) {
    $courseurl = new moodle_url('/course/view.php', ['id' => $crosslist->moodle_course_id]);
    echo html_writer::tag('p', 
        html_writer::link(
            $courseurl, 
            get_string('wdsprefs:viewcourse', 'block_wdsprefs'),
            ['class' => 'btn btn-primary', 'target' => '_blank']
        )
    );
}

// Get sections in this crosslist.
$sections = wdsprefs::get_crosslist_sections($id);

if (empty($sections)) {
    echo html_writer::tag('p', get_string('wdsprefs:nosections', 'block_wdsprefs'));
} else {
    // Create table for sections.
    $table = new html_table();
    $table->head = [
        get_string('wdsprefs:course', 'block_wdsprefs'),
        get_string('wdsprefs:section', 'block_wdsprefs'),
        get_string('wdsprefs:status', 'block_wdsprefs')
    ];
    
    foreach ($sections as $section) {
        $row = [];
        $row[] = $section->course_subject_abbreviation . ' ' . $section->course_number;
        $row[] = $section->section_number;
        $row[] = get_string('wdsprefs:sectionstatus_' . $section->status, 'block_wdsprefs');
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
}

// Add back button.
echo html_writer::tag('div', 
    html_writer::link(
        new moodle_url('/blocks/wdsprefs/crosslist.php'), 
        get_string('back'),
        ['class' => 'btn btn-secondary']
    ),
    ['class' => 'mt-4']
);

// Output the footer.
echo $OUTPUT->footer();
