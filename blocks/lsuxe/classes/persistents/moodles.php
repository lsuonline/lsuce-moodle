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

class moodles extends \block_lsuxe\persistents\persistent {
// class moodles extends \core\persistent {

    /** Table name for the persistent. */
    const TABLE = 'block_lsuxe_moodles';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [

            'url' => [
                'type' => PARAM_TEXT,
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
            'timecreated' => [
                'type' => PARAM_INT,
            ],
            'timemodified' => [
                'type' => PARAM_INT,
            ],
            'timedeleted' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Define the columns that need to be checked when finding if record exists. 
     * These are the required fields for searching purposes to avoid duplicates.
     *
     * @return array
     */
    public function column_record_check() {
        return array(
            // db column name => form name
            'url' => 'instanceurl',
            'token' => 'instancetoken',
            // 'interval' => 'defaultupdateinterval',
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
            // db column name => form name
            'url' => 'instanceurl',
            'token' => 'instancetoken',
            // 'tokenexpire' => 'tokenexpiration',
            // 'interval' => 'defaultupdateinterval',
            // 'enabletoken' => 'enabletokenexpiration'

            // [instanceurl] => poop@poop.com
            // [instancetoken] => a;sldkfjas;ldkfjas;ldkfjas;ldkfj
            // [defaultupdateinterval] => 0
            // [tokenexpiration] => 1658296800
            // [send] => Save Moodle Instance
        );
    }

    /**
     * This function is called from the form_controller\save_record() as each
     * form will have it's own unique data points to save 
     *
     * @return array
     */
    public function column_form_custom(&$to_save, $data) {
        // $to_save->timedeleted = null;
        $to_save->timecreated = time();
        $to_save->timemodified = time();
        // The interval is a select and will be a string, need to typecast it.
        $to_save->updateinterval = (int) $data->defaultupdateinterval;
        if (isset($data->enabletokenexpiration) && $data->enabletokenexpiration == 1) {
            $to_save->tokenexpire = $data->tokenexpiration;
        } else {
            $to_save->tokenexpire = 0;
        }
    }

    /**
     * Persistent hook to redirect user back to the view after the object is saved.
     *
     * @return array
     */
    protected function after_create() {
        global $CFG;
        error_log("\n\n");
        error_log(" -------Created a new record for block_lsuxe_moodles, now redirecting --------- ");
        error_log("\n\n");
        // url, message, delay, message type
        // redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php?view=1', );
        redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php',
            get_string('creatednewmoodle', 'block_lsuxe'),
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
        error_log("after_delete() -> Successfully deleted the record for block_lsuxe_moodles");
        error_log("after_delete() -> da fook is result: ". $result);
        error_log("\n\n");
        // redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php');
    }
}
