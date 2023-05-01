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
 * @package    block_pu
 * @copyright  2021 onwards LSU Online & Continuing Education
 * @copyright  2021 onwards Tim Hunt, Robert Russo, David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
// function uploadfile_supports($feature) {

//     switch($feature) {
        
//         case FEATURE_SHOW_DESCRIPTION:
//             return true;
//         case FEATURE_BACKUP_MOODLE2:
//             return true;
//         default:
//             return null;
//     }
// }

// ===============
//
//  Plugin File
//
// ===============
// I M P O R T A N T
//
/**
 * return url image for display
 */
// function print_image_uploadfile($itemid, $contextid) {

//     $fs = get_file_storage();
//     if ($files = $fs->get_area_files($contextid, 'block_pu', 'attachment', "{$itemid}", 'sortorder', false)) {              
//         foreach ($files as $file) {
//             $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());           
//             $imageurl = $fileurl->get_port() ? $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path();          
//             if (file_extension_in_typegroup($file->get_filename(), 'web_image')) {
//                 return $imageurl;
//             }
//         }
//     } 
    
//     return false;
// }


// ===============
//
//  Plugin File
//
// ===============
// I M P O R T A N T
// 
// This is the most confusing part. For each plugin using a file manager will automatically
// look for this function. It always ends with _pluginfile. Depending on where you build
// your plugin, the name will change. In case, it is a local plugin called file manager.
function block_pu_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB;
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    require_login();
    if ($filearea != 'pu_file') {
        return false;
    }
    $itemid = (int) array_shift($args);
    if (!$itemid) {
        return false;
    }
    $fs = get_file_storage();
    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }
    $file = $fs->get_file($context->id, 'block_pu', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}

/**
 * Saves a new instance of the uploadfile into the database
 *
 * Given an object containing all the necessary data,
 * (defined 
 * 
 * 
 * by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $uploadfile Submitted data from the form in mod_form.php
 * @param block_pu_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted uploadfile record
 */
function uploadfile_add_instance(stdClass $uploadfile, block_pu_mod_form $mform = null) {
    global $DB;

    $uploadfile->timecreated = time();

    // You may have to add extra stuff in here.

    $uploadfile->id = $DB->insert_record('block_pu_file', $uploadfile);

    return $uploadfile->id;
}

/**
 * Updates an instance of the uploadfile in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $uploadfile An object from the form in mod_form.php
 * @param block_pu_block_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function uploadfile_update_instance(stdClass $uploadfile, block_pu_block_form $mform = null) {
    global $DB;

    $uploadfile->timemodified = time();
    $uploadfile->id = $uploadfile->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('block_pu_file', $uploadfile);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every uploadfile event in the site is checked, else
 * only uploadfile events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function uploadfile_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$uploadfiles = $DB->get_records('block_pu_file')) {
            return true;
        }
    } else {
        if (!$uploadfiles = $DB->get_records('block_pu_file', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($uploadfiles as $uploadfile) {
        // Create a function such as the one below to deal with updating calendar events.
        // uploadfile_update_events($uploadfile);
    }

    return true;
}

/**
 * Removes an instance of the uploadfile from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function uploadfile_delete_instance($id) {
    global $DB;

    if (! $uploadfile = $DB->get_record('block_pu_file', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('block_pu_file', array('id' => $uploadfile->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $uploadfile The uploadfile instance record
 * @return stdClass|null
 */
function uploadfile_user_outline($course, $user, $mod, $uploadfile) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $uploadfile the module instance record
 */
function uploadfile_user_complete($course, $user, $mod, $uploadfile) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in uploadfile activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function uploadfile_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link uploadfile_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function uploadfile_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link uploadfile_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function uploadfile_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function uploadfile_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function uploadfile_get_extra_capabilities() {
    return array();
}
// ====================================================================
// ====================================================================
/* File API */
// ====================================================================
// ====================================================================

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function uploadfile_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for uploadfile file areas
 *
 * @package block_pu
 * @category files
 *
 * @param file_browseohr $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function uploadfile_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding uploadfile nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the uploadfile module instance
 * @param stdClass $course current course record
 * @param stdClass $module current uploadfile instance record
 * @param cm_info $cm course module information
 */
function uploadfile_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the uploadfile settings
 *
 * This function is called when the context for the page is a uploadfile module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $uploadfilenode uploadfile administration node
 */
function uploadfile_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $uploadfilenode=null) {
    // TODO Delete this function and its docblock, or implement it.
}
