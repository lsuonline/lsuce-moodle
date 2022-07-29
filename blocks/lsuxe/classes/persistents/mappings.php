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
 * @package    block_lsuxe Cross Enrollment
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lsuxe\persistents;

class mappings extends \block_lsuxe\persistents\persistent {
// class mappings extends \core\persistent {

    /** Table name for the persistent. */
    const TABLE = 'block_lsuxe_mappings';
    const PNAME = 'mappings';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [

            'courseid' => [
                'type' => PARAM_INT,
            ],
            'shortname' => [
                'type' => PARAM_TEXT,
            ],
            'authmethod' => [
                'type' => PARAM_TEXT,
            ],
            'groupid' => [
                'type' => PARAM_INT,
            ],
            'groupname' => [
                'type' => PARAM_TEXT,
            ],
            'destmoodleid' => [
                'type' => PARAM_INT,
            ],
            'destcourseid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'destcourseshortname' => [
                'type' => PARAM_TEXT,
            ],
            'destgroupprefix' => [
                'type' => PARAM_TEXT,
            ],
            'destgroupid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
                // 
            ],
            'updateinterval' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
                // This is an example when using a default value (use closure)
                // 'default' => function() {
                //     return get_config('core', 'default_location');
                // },
            ],
            'starttime' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'endtime' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
            ],
            'userdeleted' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'timedeleted' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'timeprocessed' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Define the columns that need to be checked when finding if record exists. 
     *
     * @return array
     */
    public function column_record_check() {
        return array(
            // db column name => form name
            'shortname' => 'srccourseshortname',
            'groupname' => 'srccoursegroupname',
            'destcourseshortname' => 'destcourseshortname',
            'destgroupprefix' => 'destcoursegroupname'

            // This is the variable name from the form
            //  srccourseshortname
            //  srccoursegroupname
            //  destcourseshortname
            //  destcoursegroupname
            //  
            //  available_moodle_instances
            //  courseupdateinterval
            //  send
        );
    }

    /**
     * When saving a new record this matches the form fields to the db columns.
     *
     * @return array
     */
    public function column_form_symetric() {
        return array(
            // db column name => form name
            'shortname' => 'srccourseshortname',
            'groupname' => 'srccoursegroupname',
            'destcourseshortname' => 'destcourseshortname',
            'destgroupprefix' => 'destcoursegroupname',
            'destmoodleid' => 'available_moodle_instances',
            'updateinterval' => 'defaultupdateinterval'
        );
    }

    /**
     * This function is called from the form_controller\save_record() as each
     * form will have it's own unique data points to save 
     *
     * @return array
     */
    public function column_form_custom(&$to_save, $data) {
        global $DB;

        // The course shortname field is an autocomplete that returns the course id
        $courseid = $data->shortname;
        $coursedata = $DB->get_record('course', array('id' => $courseid), $fields='*');
        // The interval is a select and will be a string, need to typecast it.
        $to_save->updateinterval = (int) $data->defaultupdateinterval;
        $to_save->courseid = $coursedata['id'];
        $to_save->shortname = $coursedata['shortname'];

        // $to_save->authmethod = "manual";
        // $to_save->groupid = 99;
        // $to_save->destmoodleid = 99; // Data submitted is invalid - destcourseid:
        // $to_save->destgroupid = 99;
        // $to_save->destcourseid = 99;
        // $to_save->updateinterval // Data submitted is invalid - starttime:
        // $to_save->starttime = 99;
        // $to_save->endtime = 99;
        $to_save->usercreated = time();
        // $to_save->userdeleted = 0;
        // $to_save->usermodified = time();
        // $to_save->timedeleted = null;
        $to_save->timecreated = time();
        // $to_save->timemodified = time();
        // $to_save->timeprocessed = null;

    }

    public function transform_for_view($data, $helpers) {
        global $DB;
        
        $intervals = $helpers->config_to_array('block_lsuxe_interval_list');
        // We need to show the correct interval and not the number
        foreach ($data[self::PNAME] as &$this_record) {
            
            // handle intervals
            if (isset($intervals[$this_record['updateinterval']]) && $this_record['updateinterval'] != 0) {
                $this_record['updateinterval'] = $intervals[$this_record['updateinterval']];
            } else {
                $this_record['updateinterval'] = "<i class='fa fa-ban'></i>";
            }
            // handle URL as we are storing the id
            $dest_moodle = $DB->get_record('block_lsuxe_moodles', array('id' => $this_record['destmoodleid']), $fields='*');
            // error_log("\nWhat is dest_moodle: ". print_r($dest_moodle, 1));
            $this_record['moodleurl'] = $dest_moodle->url;

        }

        foreach ($data[self::PNAME] as $this_record) {
            error_log("\n\n");
            error_log(" what is the data BEFORE returning from TRANSFORM: ". print_r($this_record, 1));
            error_log("\n\n");
        }
        return $data;
    }

    /**
     * Persistent hook to redirect user back to the view after the object is saved.
     *
     * @return array
     */    
    protected function after_create() {
        global $CFG;
        // error_log("\n\n");
        // error_log("after_create() -> Successfully created a new record for block_lsuxe_mappings");
        // error_log("\n\n");
        redirect($CFG->wwwroot . '/blocks/lsuxe/mappings.php',
            get_string('creatednewmapping', 'block_lsuxe'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    /*
     * Persistent hook to redirect user back to the view after the object is deleted.
     *
     * @return array
     */
    protected function after_delete($result) {
        global $CFG;
        error_log("\n\n");
        error_log("after_delete() -> Successfully deleted the record for block_lsuxe_mappings");
        error_log("after_delete() -> da fook is result: ". $result);
        error_log("\n\n");
        // redirect($CFG->wwwroot . '/blocks/lsuxe/mappings.php');
    }
}
