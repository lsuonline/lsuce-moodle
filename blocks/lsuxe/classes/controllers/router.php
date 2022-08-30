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

use block_lsuxe\models;

class router {

    /**
     * Construct the form to work with the persistents.
     *
     * @param string  the name of the object/persistent we are working with
     */
    // public function __construct() {
    // }

    /**
     * Retrieve basic info about the course and it's group information.
     * @param  object containing the course id and name
     * @return array
     */
    public function getGroupData($params) {

        $fuzzy = new \block_lsuxe\models\mixed();
        $dbresult = $fuzzy->getCourseGroupData($params);
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
     * This returns the token to the calling server.
     * @param  array list of params being sent, but should only have url.
     * @return array
     */
    public function getToken($params) {

        $url = isset($params->url) ? $params->url : null;
        $fuzzy = new \block_lsuxe\models\mixed();
        $results = array();
        $dbresult = $fuzzy->getTokenData($url);

        if ($dbresult->success == true) {
            $results["success"] = true;
            $results["data"] = $dbresult->data;

        } else {
            $results["success"] = false;
            $results["msg"] = $dbresult->msg;
        }
        return $results;
    }

    /**
     * Verify if the course and group exists
     * @param  array containing course name and group name
     * @return array
     */
    public function verifyCourse($params) {
        $results = array();

        $fuzzy = new \block_lsuxe\models\mixed();
        $dbresult = $fuzzy->verifyCourseGroup($params);
        $dbcount = count($dbresult);

        if ($dbcount == 0) {
            $return_obj->success = false;
            $return_obj->msg = "Either the course shortname and/or group name do not exist.";
        } else if ($dbcount > 1) {
            $return_obj->success = false;
            $return_obj->msg = "There are multiple records.";
        } else {
            $return_obj->success = true;
            $dbresult = array_values($dbresult);
            $return_obj->data = $dbresult[0];
        }

        return $return_obj;
    }

    public function testService($params) {
        return array("success" => true);
    }
}
