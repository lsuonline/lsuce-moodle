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
 * General-purpose library for use by TurningTech module/blocks.
 * @package    mod_turningtech
 * @copyright  2012 Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author jacob
 *
 * NOTE: callers which include/require this file ***MUST*** also include/require the following:
 * - [moodle root]/config.php
 */
 /* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function turningtech_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}
/**
 * Creates a directory file name, suitable for make_upload_directory()
 * @param object $course
 * @return unknown_type
 */
 global $CFG;
 global $DB;
function turningtech_file_dir($course) {
    return "{$course->id}/{$CFG->moddata}/turningtech";
}
/**
 * Create a unique temporary directory with a given prefix name,
 * inside a given directory, with given permissions. Return the
 * full path to the newly created temp directory.
 *
 * @param string $dir where to create the temp directory.
 * @param string $prefix prefix for the temp directory name (default '')
 *
 * @return string The full path to the temp directory.
 */
function ttp_mktempdir($dir, $prefix='') {
    global $CFG;

    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }

    do {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (file_exists($path));

    check_dir_exists($path);

    return $path;
}
/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in turningtech activities and print it out.
 * Return true if there was output, or false is there was none.
 * @param object $course
 * @param bool $isteacher
 * @param time $timestart
 * @return boolean
 * @todo Finish documenting this function
 */
function turningtech_print_recent_activity($course, $isteacher, $timestart) {
    return false; //  True if anything was printed, otherwise false.
}
/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of turningtech. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $turningtechid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function turningtech_get_participants($turningtechid) {
    return false;
}
/**
 * This function returns if a scale is being used by one turningtech
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $turningtechid ID of an instance of this module
 * @param int $scaleid
 * @return bool
 * @todo Finish documenting this function
 */
function turningtech_scale_used($turningtechid, $scaleid) {
    return false;
}
/**
 * Checks if scale is being used by any instance of turningtech.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean true if the scale is used by any turningtech
 */
function turningtech_scale_used_anywhere($scaleid) {
    return false;
}
/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function turningtech_install() {
    return true;
}
/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function turningtech_uninstall() {
    return true;
}
// Any other turningtech functions go here.  Each of them must have a name that
// starts with turningtech_
// Remember (see note in first lines) that, if this section grows, it's HIGHLY
// recommended to move all funcions below to a new "localib.php" file.

/**
 * generates device ID format options
 * @return unknown_type
 */
function turningtech_get_device_id_format_options() {
    return array(
        TURNINGTECH_DEVICE_ID_FORMAT_HEX => get_string('deviceidformathex', 'turningtech'),
        TURNINGTECH_DEVICE_ID_FORMAT_ALPHA => get_string('deviceidformatalpha', 'turningtech')
    );
}
/**
 * generates Responseware Display options
 * @return unknown_type
 */
function turningtech_get_device_display_options() {
    return array(
            TURNINGTECH_ENABLE_RESPONSEWARE => get_string('enableresponseware', 'turningtech'),
            TURNINGTECH_DISABLE_RESPONSEWARE => get_string('disableresponseware', 'turningtech')
    );
}
/**
 * generates cipher mode format options
 * @return unknown_type
 */
function turningtech_get_encryption_format_options() {
    return array(
        TURNINGTECH_ENCRYPTION_FORMAT_ECB => get_string('encryptionformatecb', 'turningtech'),
        TURNINGTECH_ENCRYPTION_FORMAT_CBC => get_string('encryptionformatcbc', 'turningtech')
    );
}
/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 * @param object $course The Course for user
 * @param object $user
 * @param object $mod
 * @param object $turningtech
 * @return bool
 * @todo Finish documenting this function
 */
function turningtech_user_complete($course, $user, $mod, $turningtech) {
    return true;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $turningtech An object from the form in mod_form.php
 * @return int The id of the newly inserted turningtech record
 */
function turningtech_add_instance($turningtech) {
    global $DB;

    $turningtech->timecreated = time();
    // You may have to add extra stuff in here.
    $turningtech->id = $DB->insert_record('turningtech', $turningtech);
    turningtech_grade_item_update($turningtech);
    return $turningtech->id;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $turningtech An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function turningtech_update_instance($turningtech) {
    global $DB;
    $turningtech->timemodified = time();
    $turningtech->id           = $turningtech->instance;
    // You may have to add extra stuff in here.
    turningtech_grade_item_update($turningtech);
    return $DB->update_record('turningtech', $turningtech);
}
/**
 * Creates or updates grade item for the given turningtech instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $turningtech instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function turningtech_grade_item_update(stdClass $turningtech, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    $item = array();
    $item['itemname'] = clean_param($turningtech->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $turningtech->grade = 100;
    if ($turningtech->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $turningtech->grade;
        $item['grademin']  = 0;
    } else if ($turningtech->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$turningtech->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/turningtech', $turningtech->course, 'mod', 'turningtech',
            $turningtech->id, 0, null, $item);
}
/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function turningtech_delete_instance($id) {
    global $DB;
    if (!$turningtech = $DB->get_record('turningtech', array('id' => $id))) {
        return false;
    }
    $result = true;
    // Delete any dependent records here.
    if (!$DB->delete_records('turningtech',  array('id' => $turningtech->id))) {
        $result = false;
    }
    return $result;
}
/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param object $course The Course for user
 * @param object $user
 * @param object $mod
 * @param object $turningtech
 * @return bool
 * @todo Finish and implement this function
 */
function turningtech_user_outline($course, $user, $mod, $turningtech) {
    return false;
}
