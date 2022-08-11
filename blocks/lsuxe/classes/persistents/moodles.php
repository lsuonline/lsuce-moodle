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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lsuxe\persistents;

class moodles extends \block_lsuxe\persistents\persistent {

    /** Table name for the persistent. */
    const TABLE = 'block_lsuxe_moodles';
    const PNAME = 'moodles';
    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [

            // TODO: Robert changed the following and need to update.....
            /*
            ADDED "teacherrole"
            ADDED "studentrole"
            DONE - ADDED "usercreated"
            DONE - ADDED "usermodified"
            DONE - ADDED "timemodified"
            DONE - ADDED "userdeleted"
            */
            
            'url' => [
                'type' => PARAM_TEXT,
            ],
            'teacherrole' => [
                'type' => PARAM_INT,
                // 'default' => null,
                // 'null' => NULL_ALLOWED
            ],
            'studentrole' => [
                'type' => PARAM_INT,
                // 'default' => null,
                // 'null' => NULL_ALLOWED
            ],
            'token' => [
                'type' => PARAM_TEXT,
            ],
            'tokenexpire' => [
                'type' => PARAM_INT,
            ],
            'updateinterval' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
            ],
            'timecreated' => [
                'type' => PARAM_INT,
            ],
            'usermodified' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'timemodified' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'userdeleted' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'timedeleted' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED
            ]
        ];
    }

    /**
     * Define the columns that need to be checked for duplicate records.
     *
     * @return array
     */
    public function column_record_check() {
        return array(
            // DB Column Name => Form Name
            'url' => 'instanceurl',
            'token' => 'instancetoken',
            'updateinterval' => 'defaultupdateinterval'
            // 'tokenexpire' => 'tokenexpiration'
            // 'enabletoken' => 'enabletokenexpiration'
        );
    }

    /**
     * When saving a new record this matches the form fields to the db columns.
     *
     * @return array
     */
    public function column_form_symetric() {
        return array(
            // DB Column Name => Form Name
            'url' => 'instanceurl',
            'token' => 'instancetoken'
        );
    }

    /**
     * The form has limited data and the rest will have to be extracted and/or
     * interpolated. This function is where we do that.
     * @param object This is the current info ready to be saved
     * @param object All form data and tidbits to be extracted and/or interpolated.
     * @return void The object is referenced.
     */
    public function column_form_custom(&$to_save, $data, $update = false) {
        global $USER;
        // This comes from the form, first 2 are done, process the rest
        // ------------------------------------------------------------------------
        // instanceurl
        // instancetoken
        // defaultupdateinterval
        // tokenexpiration
        // enabletokenexpiration
        //  ------------------------------------------------------------------------

        // Current form data ready to go
        //      instanceurl
        //      instancetoken
        // Remaining fields to store based on install.xml
        //      updateinterval
        //      {
        //          if: token expiration is enabled $data->enabletokenexpiration
        //          then: tokenexpiration 
        //          else: 0
        //      }
        //      teacherrole
        //      studentrole
        //      usercreated
        //      timecreated
        //      usermodified
        //      timemodified
        //      userdeleted
        //      timedeleted

        // If it's new then update first time fields.
        if ($update == false) {
            $to_save->timecreated = time();
            $to_save->usercreated = $USER->id;
        } else {
            // It's an update, so change the modified fields.
            $to_save->usermodified = $USER->id;
            $to_save->timemodified = time();
        }
        // The interval is a select and will be a string, need to typecast it.
        $to_save->updateinterval = (int) $data->defaultupdateinterval;
        // the token expire
        if (isset($data->enabletokenexpiration) && $data->enabletokenexpiration == 1) {
            $to_save->tokenexpire = $data->tokenexpiration;
        } else {
            $to_save->tokenexpire = 0;
        }

        // TODO: This is a placeholder in order to save/update the form.
        $to_save->teacherrole = 99;
        $to_save->studentrole = 99;
    }

    /**
     * Transform any custom data from the DB to be used in the form.
     * @param object the data object
     * @param object Helper injection
     * @return void The object is referenced.
     */
    public function transform_for_view($data, $helpers) {
        global $DB;

        $intervals = $helpers->config_to_array('block_lsuxe_interval_list');
        // // We need to show the correct interval and not the number
        foreach ($data[self::PNAME] as &$this_record) {

            // handle intervals
            if (isset($intervals[$this_record['updateinterval']]) && $this_record['updateinterval'] != 0) {
                $this_record['updateinterval'] = $intervals[$this_record['updateinterval']];
            } else {
                $this_record['updateinterval'] = "<i class='fa fa-ban'></i>";
            }
            // handle Token expire timestamp
            if ($this_record['tokenexpire'] == "0") {
                $this_record['tokenexpire'] = "Not Set";
            } else {
                $this_record['tokenexpire'] = userdate($this_record['tokenexpire']);
            }
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
        redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php',
            get_string('creatednewmoodle', 'block_lsuxe'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    /**
     * Persistent hook to redirect user back to the view after the object is updated.
     *
     * @return array
     */
    protected function after_update($result) {
        global $CFG;
        redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php',
            get_string('updatedmoodle', 'block_lsuxe'),
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
        // global $CFG;
        // redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php');
    }
}
