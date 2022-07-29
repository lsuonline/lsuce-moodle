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

namespace block_lsuxe\controllers;

use block_lsuxe\persistents\mappings;
use block_lsuxe\persistents\moodles;
// use block_lsuxe\persistents\mappings as new_mappings;
// use block_lsuxe\persistents\moodles as new_moodle;

class form_controller {

    private $persistent_name;
    private $persistent_object;
    private $persistpath;
    /**
     * Construct the form to work with the persistents.
     *
     * @param string  the name of the object/persistent we are working with
     */
    public function __construct($this_obj) {
        $this->persistent_name = $this_obj;
        // $this->persistent_object = new $this_obj();
        $this->persist_path = "\\block_lsuxe\\persistents\\";
    }

    public function process_form($data) {


        // Check to see if there are any matching records.
        if (false == $error = $this->check_for_existing($data)) {
            // Return an error to trigger a notification.
            return $error;
        }

        // Save the record.
        if (false == $error = $this->save_record($data)) {
            // Return an error to trigger a notification.
            return $error;
        }

    }

    /**
     * Find if this record already exists
     * @param  object $data
     * @return bool
     */
    public function check_for_existing($data) {
        // global $DB;
        $pname = $this->persist_path . $this->persistent_name;
        $po = new $pname();
        $col_props = $po->column_record_check();
        $params = array();

        // Let's get the conditions for this persistent object.
        foreach ($col_props as $key => $val) {
            $params[$key] = $data->$val;
        }
        $count = $po::count_records($params);

        return $count > 1 ? false : true;
    }

    /**
     * Save this record.
     * @param  object $data
     * @return bool
     */
    public function save_record($data) {
        // global $DB;
        error_log("\n\n");
        error_log("save_record() -> START <------------------");
        error_log("What is the data: ". print_r($data, 1));
        error_log("\n\n");
        // Create object in the database.
        $pname = $this->persist_path . $this->persistent_name;
        $po = new $pname();
        $col_props = $po->column_form_symetric();
        

        $to_save = new \stdClass();

        // Let's gather the form data.
        foreach ($col_props as $key => $val) {
            $to_save->$key = $data->$val;
        }

        // Now to add any specific fields for this object.
        $po->column_form_custom($to_save, $data);
        unset($po);
        // Catch the invalid_persistent_exception.
        error_log("\n\n");
        error_log("save_record() -> what is the final object to save: " . print_r($to_save, 1));
        error_log("\n\n");
        try {
            // Check whether the object is valid.
            $po = new $pname(0, $to_save);
            if ($po->is_valid()) {
                $po->create();
            } else {
                return false;
            }

        } catch (invalid_persistent_exception $e) {
            // Whoops, something wrong happened.
            return false;
        }
    }

    /**
     * Delete the record.
     * @param  int $record
     * @return bool
     */
    public function delete_record($record) {
        // global $DB;
        error_log("\n\n");
        error_log("delete_record() -> START <------------------");
        error_log("What is the data: ". print_r($record, 1));
        error_log("\n\n");
        // Create object in the database.
        $pname = $this->persist_path . $this->persistent_name;
        $po = new $pname($record);
        

        // Check whether a record exists.
        $exists = $po->record_exists($record);
        error_log("\nDoes the record exist: ". $exists. " \n");
        // Permanently delete the object from the database.
        if ($exists) {
            $po->delete();
        }
    }

    /**
     * Fetch all records.
     * @return array
     */
    public function get_records() {
        // global $DB;
        // error_log("\n\n");
        // error_log("delete_record() -> START <------------------");
        // error_log("What is the data: ". print_r($record, 1));
        // error_log("\n\n");
        // Create object in the database.
        $pname = $this->persist_path . $this->persistent_name;
        $po = new $pname();
        $records = array();

        $persist_list = $po->get_records();
        foreach ($persist_list as $pitem) {
            // error_log("\n what is the record: ". print_r($record, 1));
            $records[] = $pitem->to_record();
            // $moodleinstances[] = $record->url;
        }
        return $records;
    }

    /**
     * Fetch all records.
     * @return array
     */
    public function get_records_by_prop($property) {
        
        // Create object in the database.
        $pname = $this->persist_path . $this->persistent_name;
        $po = new $pname();
        $records = array();

        $persist_list = $po->get_records();
        foreach ($persist_list as $pitem) {
            $temprecord = $pitem->to_record();
            $records[] = $temprecord->$property;
        }

        return $records;
    }

    
}
