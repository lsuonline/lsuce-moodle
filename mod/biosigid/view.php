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
 * View.php
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/gradelib.php');

require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id(BIOSIGID_MODULE_NAME, $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$biosigid = $DB->get_record(BIOSIGID_MODULE_NAME, array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = '';
if ($CFG->branch >= 22) {
    $context = context_module::instance($cm->id);
} else {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
}
$config = get_config(BIOSIGID_MODULE_NAME);
$isstudent = !has_capability('moodle/course:manageactivities', $context);

if ($CFG->branch >= 27) {
    $event = \mod_biosigid\event\course_module_viewed::create(array(
        'objectid' => $cm->instance,
        'context' => $context,
    ));
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot(BIOSIGID_MODULE_NAME, $biosigid);
    $event->trigger();
} else {
    add_to_log($course->id, BIOSIGID_MODULE_NAME, 'view', "view.php?id={$cm->id}", $biosigid->name, $cm->id);
}

if ($isstudent) {

    $grades = grade_get_grades($biosigid->course, BIOSIGID_MODULE_TYPE, BIOSIGID_MODULE_NAME, $biosigid->id, $USER->id);

    $grade = NULL;
    $overridden = 0;
    if (array_key_exists($USER->id, $grades->items[0]->grades)) {
        $grade = $grades->items[0]->grades[$USER->id]->grade;
        $overridden = $grades->items[0]->grades[$USER->id]->overridden;
    }

    if ($overridden > 0) {

        $message = get_string('overridden', BIOSIGID_MODULE_NAME);

    } else if (is_null($grade) || (intval($grade) <= 0) || ($biosigid->attempts > 0)) {

        $error = NULL;
        $url = biosigid_inbound_sso($config, $id, $error);

        if (!is_null($url)) {
            biosigid_display_frame($biosigid, $url, $id, $course);
        } else {
            $message = sprintf(get_string('bsi_error', BIOSIGID_MODULE_NAME), $error);
        }

    } else {

        $message = get_string('already_verified', BIOSIGID_MODULE_NAME);

    }

} else {

    $sid = optional_param('sid', NULL, PARAM_INT);
    if (isset($sid)) {
        $sid = trim($sid);
        $status = biosigid_update_grade($biosigid, $sid, 0);
        if ($status == GRADE_UPDATE_OK) {
            $statusmessage = get_string('reset_ok', BIOSIGID_MODULE_NAME);
        } else {
            $statusmessage = get_string('reset_error', BIOSIGID_MODULE_NAME);
        }
    }

    $message = get_string('instructor', BIOSIGID_MODULE_NAME);

    $users = get_enrolled_users($context, '', 0, 'u.id, u.lastname, u.firstname', 'u.lastname, u.firstname', 0, 0);
    $grades = grade_get_grades($biosigid->course, BIOSIGID_MODULE_TYPE, BIOSIGID_MODULE_NAME, $biosigid->id, array_keys($users));

    $list = '';
    foreach ($users as $user) {
        $hasgrade = array_key_exists($user->id, $grades->items[0]->grades);
        if ($hasgrade) {
            $overridden = ($grades->items[0]->grades[$user->id]->overridden > 0);
            $alreadyverified = !is_null($grades->items[0]->grades[$user->id]->grade) &&
                (intval($grades->items[0]->grades[$user->id]->grade) > 0);
            if ($overridden) {
                $list .= "  <option value=\"\" disabled=\"disabled\">{$user->lastname}, {$user->firstname} [overridden]</option>\n";
            } else if ($alreadyverified) {
                $list .= "  <option value=\"{$user->id}\">{$user->lastname}, {$user->firstname}</option>\n";
            }
        }
    }

    $resettitle = get_string('reset_title', BIOSIGID_MODULE_NAME);
    $resetlabel = get_string('reset_label', BIOSIGID_MODULE_NAME);
    $resetbutton = get_string('reset_button', BIOSIGID_MODULE_NAME);
    $resetoption = get_string('reset_option', BIOSIGID_MODULE_NAME);
    $resetempty = get_string('reset_empty', BIOSIGID_MODULE_NAME);

    $form = "<div style=\"border: 1px dotted black; float: left; margin:10px; padding: 10px; text-align: center;\">\n";
    $form .= "<strong>{$resettitle}</strong><br /><br />\n";
    if ($list) {
        $form .= "<form method=\"post\" action=\"\">\n";
        $form .= "{$resetlabel}&nbsp;";
        $form .= "<select name=\"sid\" />\n  <option value=\"\">{$resetoption}</option>\n{$list}</select>\n&nbsp;";
        $form .= "<input type=\"submit\" value=\"{$resetbutton}\" /></form>\n";
    } else {
        $form .= "<p>{$resetempty}</p>\n";
    }
    $form .= "</div>\n";

}

$PAGE->set_url('/mod/biosigid/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($biosigid->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading($biosigid->name);
if (isset($statusmessage)) {
    echo "<p style=\"color: #A00; font-weight: bold;\">{$statusmessage}</p>\n";
}
echo "<p>{$message}</p>\n";
if (isset($form)) {
    echo $form;
}
echo $OUTPUT->footer();