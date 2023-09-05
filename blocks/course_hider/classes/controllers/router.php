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
 * Course Hider Tool
 *
 * @package   block_course_hider
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_course_hider\models;

class router {

    /**
     * AJAX calls will land here.
     * @param  object containing data
     * @return array
     */
    public function get_some_stuff($params) {

        $fuzzy = new \block_course_hider\models\chmixed();
        $whuzzy = $fuzzy->get_sample_data($params);
        $results = array();

        if ($dbresult->success == true) {
            $results["success"] = true;
            $results["count"] = count($dbresult->data);
            $results["data"] = $dbresult->data;

        } else {
            $results["success"] = false;
            $results["msg"] = $dbresult->msg;
        }
        return $results;
    }

    /**
     * AJAX calls will land here, this is to test the service.
     * @param  object containing data
     * @return array
     */
    public function test_service($params) {
        return array("success" => true);
    }
}
