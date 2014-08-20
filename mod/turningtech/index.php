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
require_once($CFG->dirroot . '/mod/turningtech/lib/helpers/EncryptionHelper.php');
require_once($CFG->dirroot . '/mod/turningtech/lib/helpers/HttpPostHelper.php');
global $PAGE;
$id = required_param('id', PARAM_INT); // Course.
global $DB;
if (!$course = $DB->get_record('course', array(
                'id' => $id
))) {
    error(get_string('courseidincorrect', 'turningtech'));
}
require_login($course);
$PAGE->set_url('/mod/turningtech/index.php', array(
                'id' => $id
));
$PAGE->set_course($course);
add_to_log($course->id, 'turningtech', 'view devices', "index.php?id=$course->id", '');

global $USER;
$context = context_course::instance($course->id);
$title   = get_string('pluginname', 'turningtech');
$PAGE->navbar->add($title);
$PAGE->set_heading($course->fullname);
$PAGE->requires->css('/mod/turningtech/css/style.css');

// Print the header.

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

    echo $OUTPUT->footer();
