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
 * Library of interface functions and constants for module biosigid
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * BIOSIGID_MODULE_NAME - name of the module
 */
define('BIOSIGID_MODULE_NAME', 'biosigid');
/**
 * BIOSIGID_MODULE_TYPE - Moodle code for module type
 */
define('BIOSIGID_MODULE_TYPE', 'mod');
/**
 * BIOSIGID_MODULE_SOURCE - Moodle code for module source
 */
define('BIOSIGID_MODULE_SOURCE', 'mod/biosigid');
/**
 * BIOSIGID_MAX_TIMESTAMP_DIFF - maximum number of seconds time difference between Moodle and BSI servers
 */
define('BIOSIGID_MAX_TIMESTAMP_DIFF', 60000);
/**
 * BIOSIGID_DATE_FORMAT - format for generating time stamp
 */
define('BIOSIGID_DATE_FORMAT', 'Y-m-d\TH:i:s\Z');
/**
 * BIOSIGID_MAX_NAME_LENGTH - maximum length of course title to pass to BSI
 */
define('BIOSIGID_MAX_NAME_LENGTH', 36);
/**
 * BIOSIGID_KEY_SIZE - size of encryption key
 */
define('BIOSIGID_KEY_SIZE', 128);


/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function biosigid_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default: return null;
    }
}

/**
 * Saves a new instance of the biosigid into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $biosigid An object from the form in mod_form.php
 * @param mod_biosigid_mod_form $mform
 * @return int The id of the newly inserted biosigid record
 */
function biosigid_add_instance(stdClass $biosigid, mod_biosigid_mod_form $mform = null) {
    global $DB;

    $biosigid->timecreated = time();
    $biosigid->id = $DB->insert_record(BIOSIGID_MODULE_NAME, $biosigid);

    biosigid_grade_item_update($biosigid);

    return $biosigid->id;
}

/**
 * Updates an instance of the biosigid in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $biosigid An object from the form in mod_form.php
 * @param mod_biosigid_mod_form $mform
 * @return boolean Success/Fail
 */
function biosigid_update_instance(stdClass $biosigid, mod_biosigid_mod_form $mform = null) {
    global $DB;

    $biosigid->timemodified = time();
    $biosigid->id = $biosigid->instance;

    return $DB->update_record(BIOSIGID_MODULE_NAME, $biosigid);
}

/**
 * Removes an instance of the biosigid from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function biosigid_delete_instance($id) {
    global $DB;

    if (! $biosigid = $DB->get_record(BIOSIGID_MODULE_NAME, array('id' => $id))) {
        return false;
    }

    biosigid_grade_item_delete($biosigid);

    $DB->delete_records(BIOSIGID_MODULE_NAME, array('id' => $biosigid->id));

    return true;
}

/**
 * Create grade item for given biosigid
 *
 * @param object $biosigid object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function biosigid_grade_item_update($biosigid, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname'=>$biosigid->name, 'idnumber'=>$biosigid->cmidnumber);

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = 100;
    $params['grademin']  = 0;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(BIOSIGID_MODULE_SOURCE, $biosigid->course, BIOSIGID_MODULE_TYPE, BIOSIGID_MODULE_NAME, $biosigid->id, 0, $grades, $params);
}

/**
 * Delete grade item for given biosigid
 *
 * @param object $biosigid object
 * @return object biosigid
 */
function biosigid_grade_item_delete($biosigid) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update(BIOSIGID_MODULE_SOURCE, $biosigid->course, BIOSIGID_MODULE_TYPE, BIOSIGID_MODULE_NAME, $biosigid->id, 0, null, array('deleted'=>1));
}
