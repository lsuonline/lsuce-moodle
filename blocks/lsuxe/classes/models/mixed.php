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

namespace block_lsuxe\models;

/**
 * Mixed functions to retrieve info from the DB.
 */
class mixed {

    // public function __construct() {
    // }

    /**
     * Retrieve basic info about the course and it's group information.
     * @param  object containing the course id and name
     * @return array
     */
    public function getCourseGroupData($params = false) {
        global $DB;
        
        $courseid = isset($params->courseid) ? $params->courseid : null;
        $coursename = isset($params->coursename) ? $params->coursename : null;
        $return_obj = new \stdClass();

        $coursedata = $DB->get_records_sql(
            'SELECT g.id as groupid, c.id, c.idnumber, c.shortname, g.name as groupname
            FROM mdl_course c, mdl_groups g
            WHERE c.id = g.courseid AND c.id = ?',
            array($courseid)
        );
        if (count($coursedata) == 0) {
            $return_obj->success = false;
            $return_obj->msg = "There are no groups for this course.";
            return $return_obj;
        } else {
            $return_obj->success = true;
            $return_obj->data = $coursedata;
            return $return_obj;
        }
    }

    /**
     * Retrieve basic info about the course and it's group information.
     * @param  object containing the course id and name
     * @return array
     */
    public function getTokenData($url = false) {
        global $DB;
        $return_obj = new \stdClass();

        if ($url == false ) {
            $return_obj->success = false;
            $return_obj->msg = "The token was not passed to the destination";
            return $return_obj;
        }

        $token_result = $DB->get_record_sql(
            'SELECT token from mdl_block_lsuxe_moodles where url=?',
            array($url)
        );

        if (strlen($token_result->token) < 32) {
            $return_obj->success = false;
            $return_obj->msg = "The token stored on the destination did not meet the token requirements.";

        } else {
            $return_obj->success = true;
            $return_obj->data = $token_result->token;
        }

        return $return_obj;
    }

    /**
     * Does the course and group exist?
     * @param  object containing the course shortname and group name
     * @return array
     */
    public function verifyCourseGroup($params = false) {
        global $DB;
        $coursename = isset($params->coursename) ? $params->coursename : null;
        $groupname = isset($params->groupname) ? $params->groupname : null;
        $return_obj = new \stdClass();
        
        $coursedata = $DB->get_records_sql(
            'SELECT g.id as groupid, c.id, c.idnumber, c.shortname, g.name as groupname
            FROM mdl_course c, mdl_groups g
            WHERE c.id = g.courseid AND c.shortname = ? AND g.name = ?',
            array($coursename, $groupname)
        );

        return $coursedata;
    }

    /**
     * Fetch the course and group
     * @param  array containing course name and group name
     * @return array
     */
    public function checkCourseExists($coursename = false, $use_id = false) {
        global $DB;
        if ($use_id) {
            $coursecount = $DB->count_records("course", array("id" => $coursename));
        } else {
            $coursecount = $DB->count_records("course", array("shortname" => $coursename));
        }
        return $coursecount;

    }

    public function checkGroupExists($groupname = false, $courseid = 0) {
        global $DB;
        $groupcount = $DB->count_records("groups", array("name" => $groupname));
        return $groupcount;    
    }

    /**
     * Fetch the course and group
     * @param  array containing course name and group name
     * @return array
     */
    public function getCourseGroupInfo($coursename = false, $groupname = false) {
        global $DB;
        
        $coursedata = $DB->get_record_sql(
            'SELECT g.id as groupid, c.id, c.idnumber, c.shortname, g.name as groupname
            FROM mdl_course c, mdl_groups g
            WHERE c.id = g.courseid AND c.shortname = ? AND g.name = ?',
            array($coursename, $groupname)
        );

        return $coursedata;
    }
}
