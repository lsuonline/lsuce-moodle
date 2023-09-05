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

namespace block_course_hider\models;

/**
 * Mixed functions to retrieve info from the DB.
 */
class chmixed {

    /**
     * A simple example where AJAX lands in classes/controller/router.php and calls this func.
     * @param  object containing the data
     * @return array
     */
    public function get_sample_data($params = false) {
        global $DB;

        $courseid = isset($params->courseid) ? $params->courseid : null;
        $coursename = isset($params->coursename) ? $params->coursename : null;
        $returnobj = new \stdClass();

        $coursedata = $DB->get_records_sql(
            'SELECT g.id as groupid, c.id, c.idnumber, c.shortname, g.name as groupname
            FROM mdl_course c, mdl_groups g
            WHERE c.id = g.courseid AND c.id = ?',
            array($courseid)
        );
        if (count($coursedata) == 0) {
            $returnobj->success = false;
            $returnobj->msg = "Ooopsies.";
            return $returnobj;
        } else {
            $returnobj->success = true;
            $returnobj->data = $coursedata;
            return $returnobj;
        }
    }

    /**
     * Fetch the course
     * @param  array containing course name and group name
     * @return array
     */
    public function check_course_exists($coursename = false, $useid = false) {
        global $DB;
        if ($useid) {
            $coursecount = $DB->count_records("course", array("id" => $coursename));
        } else {
            $coursecount = $DB->count_records("course", array("shortname" => $coursename));
        }
        return $coursecount;

    }

    /**
     * Check if group exists
     * @param  array containing course name and group name
     * @return array
     */
    public function check_group_exists($groupname = false, $courseid = 0) {
        global $DB;
        $groupcount = $DB->count_records("groups", array("name" => $groupname));
        return $groupcount;
    }
}
