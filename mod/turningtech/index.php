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
 * This page lists all the instances of turningtech in a particular course
 * @package    mod_turningtech
 * @copyright  2012 Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('../../course/lib.php');
require_once($CFG->dirroot . '/mod/turningtech/lib.php');
global $PAGE, $DB, $COURSE;
require_login($COURSE);
$PAGE->set_url('/mod/turningtech/index.php');
$PAGE->set_course($COURSE);

global $USER;
if ($CFG->version >= '2013111800.00') {
    $context = context_course::instance($COURSE->id);
} else {
    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
}
$title   = get_string('turningtech', 'turningtech');
$PAGE->navbar->add($title);
$PAGE->set_heading($COURSE->fullname);
$PAGE->requires->css('/mod/turningtech/css/style.css');

// Print the header.

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

    echo $OUTPUT->footer();