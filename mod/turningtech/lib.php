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
// To fix maximum grades allowed.
set_config('gradeoverhundredprocentmax', 1000);
// The path to the WSDL definitions.
global $DB;
if (!defined('TURNINGTECH_WSDL_URL')) {
define('TURNINGTECH_WSDL_URL', $CFG->wwwroot . '/mod/turningtech/wsdl/wsdl.php');
// The 2 types of device ID formats.
define('TURNINGTECH_DEVICE_ID_FORMAT_HEX', 1);
// TODO: is this the correct length?
define('TURNINGTECH_DEVICE_ID_FORMAT_HEX_MIN_LENGTH', 6);
define('TURNINGTECH_DEVICE_ID_FORMAT_HEX_MAX_LENGTH', 8);
define('TURNINGTECH_DEVICE_ID_FORMAT_ALPHA', 2);
// TODO: is this the correct length?
define('TURNINGTECH_DEVICE_ID_FORMAT_ALPHA_MIN_LENGTH', 6);
define('TURNINGTECH_DEVICE_ID_FORMAT_ALPHA_MAX_LENGTH', 8);
define('TURNINGTECH_ENABLE_RESPONSEWARE', 1);
define('TURNINGTECH_DISABLE_RESPONSEWARE', 2);
define('TURNINGTECH_CUSTOM_RESPONSEWARE', 3);
define('TURNINGTECH_ENCRYPTION_FORMAT_ECB', 1);
define('TURNINGTECH_ENCRYPTION_FORMAT_CBC', 2);
// The type of gradebook items to use.
define('TURNINGTECH_GRADE_ITEM_TYPE', 'manual');
define('TURNINGTECH_GRADE_ITEM_MODULE', NULL);
// Different modes of saving scores.
define('TURNINGTECH_SAVE_NO_OVERRIDE', 1);
define('TURNINGTECH_SAVE_ONLY_OVERRIDE', 2);
define('TURNINGTECH_SAVE_ALLOW_OVERRIDE', 3);
}
// Default user roles.
$roles              = get_all_roles();
$manage_turningtech = get_roles_with_capability('mod/turningtech:manage', 1);
$manage_grades      = get_roles_with_capability('moodle/grade:manage', 1);
// Ensuring that "Manage TurningTech" is an array before it is searched as a collection.
if (!is_array($manage_turningtech)) {
    $manage_turningtech = array();
}
// Ensuring that "Manage Grades" is an array before it is searched as a collection.
if (!is_array($manage_grades)) {
    $manage_grades = array();
}
$sroles = '';
$troles = '';
foreach ($roles as $role) {
    $index = $role->id;
    if (array_key_exists($index, $manage_turningtech) || array_key_exists($index, $manage_grades)) {
        if ($troles) {
            $troles .= ',' . $index;
        } else {
            $troles .= $index;
        }
    } else {
        if ($sroles) {
            $sroles .= ',' . $index;
        } else {
            $sroles .= $index;
        }
    }
}
if (!defined('TURNINGTECH_DEFAULT_TEACHER_ROLE')) {
define('TURNINGTECH_DEFAULT_TEACHER_ROLE', $troles);
define('TURNINGTECH_DEFAULT_STUDENT_ROLE', $sroles);
// Switch for enabling/disabling WS encryption.
define('TURNINGTECH_ENABLE_ENCRYPTION', true);
// Switch for enabling/disabling WS decryption.
define('TURNINGTECH_ENABLE_DECRYPTION', true);
// Switch for enabling/disabling WS encryption EncryptionHelperException messages also being output via error_log().
define('TURNINGTECH_ENABLE_ENCRYPTION_EXCEPTIONS_IN_ERROR_LOG', true);
// Switch for enabling/disabling HttpPostHelperException and HttpPostHelperIOException messages also being output via error_log().
define('TURNINGTECH_ENABLE_POSTRW_EXCEPTIONS_IN_ERROR_LOG', true);
// Default responseware provider.
define('TURNINGTECH_DEFAULT_RESPONSEWARE_PROVIDER', 'http://www.rwpoll.com/');
}
// Require all necessary libraries.
require_once($CFG->dirroot . '/mod/turningtech/lib/IntegrationServiceProvider.php');
require_once($CFG->dirroot.'/lib/filelib.php');
/**
 * Library of functions and constants for module turningtech
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the turningtech specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

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
    return $DB->insert_record('turningtech', $turningtech);
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
    return $DB->update_record('turningtech', $turningtech);
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
    if (!$turningtech = $DB->get_record('turningtech', 'id', $id)) {
        return false;
    }
    $result = true;
    // Delete any dependent records here.
    if (!$DB->delete_records('turningtech', 'id', $turningtech->id)) {
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
 * fetches all messages and optionally clears them
 * @param bool $clear
 * @return unknown_type
 */
function turningtech_get_messages($clear = false) {
    $messages = turningtech_set_message();
    if ($clear) {
        unset($_SESSION['turning_messages']);
    }
    return $messages;
}
/**
 * Creates a directory file name, suitable for make_upload_directory()
 * @param object $course
 * @return unknown_type
 */
function turningtech_file_dir($course) {
    global $CFG;
    return "{$course->id}/{$CFG->moddata}/turningtech";
}
