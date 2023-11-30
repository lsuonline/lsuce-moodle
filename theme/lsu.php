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
 * General LSU Theme Functions.
 *
 * @package   theme_lsu
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// namespace theme_lsu;

defined('MOODLE_INTERNAL') || die();

// use moodle_url;
// use stdClass;

global $CFG;

/**
 * General LSU Class with various functions.
 *
 */
class lsu_theme_snippets {
    
    public function show_course_size($isadmin = 0) {
        global $OUTPUT, $COURSE, $CFG, $USER;

        $coursesize = $this->get_file_size();

        $sizesetting = (int)get_config('theme_snap', 'course_size_limit');

        $percentage = number_format(((($coursesize / 1048576) * 100) / $sizesetting), 0);
        $percent = round((($coursesize / 1048576) * 100) / $sizesetting, 0);

        // number_format( $myNumber, 2, '.', '' );
        // Let's format this number so it's readable.
        $size = $this->formatBytes($coursesize);
        
        // What is the percentage of it being full.
        $displayclass = $this->get_bootstrap_barlevel($percent);
        $show_course_size_link = "";
        if ($isadmin) {
            $show_course_size_link = ' <a href="' . $CFG->wwwroot .
                '/report/coursesize/course.php?id='
                . $COURSE->id .
                '" target="_blank">' .
                '<i class="fa fa-question-circle-o" aria-hidden="true"></i>' .
                '</a>';
        }

        $coursesnippet = 'Course File Size: '
            . $size
            . $show_course_size_link .
            '<div class="progress" ' .
            'role="progressbar" ' .
            'aria-label="Success example" ' .
            'aria-valuenow="' . $percent . '" ' .
            'aria-valuemin="0" ' .
            'aria-valuemax="100"> ' .
            '<div class="progress-bar bg-' . $displayclass .
            '" style="width: ' . $percent . '%">' .
            '<span class="fg-' . $displayclass . '">' . $percentage . '%</span>' .
            '</div></div>';
      
        return $coursesnippet;
    }

    /**
     * Based on the percentage show the type of bar to use. 
     * @param  [int]        $percentage number ranging from 0-100
     * @return [string]     partial string used in the div-class.
     */
    private function get_bootstrap_barlevel($percentage) {

        if ($percentage > 0 && $percentage < 25) {
            return "success";
        } else if ($percentage >= 25 && $percentage < 50) {
            return "info";
        } else if ($percentage >= 50 && $percentage < 75) {
            return "warning";
        } else if ($percentage >= 75) {
            return "danger";
        }
        
    }

    private function get_file_size($courseid = 0) {
        
        global $COURSE, $DB;
        if ($courseid == 0) {
            $courseid = $COURSE->id;            
        }

        // Search the report_coursesize table first.
        $found = $DB->get_record_sql(
            "SELECT filesize, timestamp
            FROM {report_coursesize}
            WHERE course = ?
            AND timestamp = (SELECT MAX(timestamp) FROM {report_coursesize} WHERE course = ?)",
            array($courseid, $courseid)
        );

        if ($found) {
            return $found->filesize;
        }

        // No records found for this course so let's find the size.
        $sql = "SELECT c.id, c.shortname, c.category, ca.name, rc.filesize
            FROM mdl_course c
            JOIN (
                SELECT id AS course, SUM(filesize) AS filesize
                    FROM (
                        SELECT c.id, f.filesize
                        FROM mdl_course c
                        JOIN mdl_context cx ON cx.contextlevel = 50 AND cx.instanceid = c.id
                        JOIN mdl_files f ON f.contextid = cx.id

                        UNION ALL

                        SELECT c.id, f.filesize
                        FROM mdl_block_instances bi
                        JOIN mdl_context cx1 ON cx1.contextlevel = 80 AND cx1.instanceid = bi.id
                        JOIN mdl_context cx2 ON cx2.contextlevel = 50 AND cx2.id = bi.parentcontextid
                        JOIN mdl_course c ON c.id = cx2.instanceid
                        JOIN mdl_files f ON f.contextid = cx1.id

                        UNION ALL

                        SELECT c.id, f.filesize
                        FROM mdl_course_modules cm
                        JOIN mdl_context cx ON cx.contextlevel = 70 AND cx.instanceid = cm.id
                        JOIN mdl_course c ON c.id = cm.course
                        JOIN mdl_files f ON f.contextid = cx.id
                    ) x
                    GROUP BY id
            ) rc on rc.course = c.id JOIN mdl_course_categories ca on c.category = ca.id AND c.id=". $courseid. "
            ORDER BY rc.filesize DESC";

        $csize = $DB->get_record_sql($sql);

        // Make sure we are returning something regardless of data returned.
        if ($csize == false) {
            return '0';
        } else {
            return $csize->filesize;
        }
    }

    private function formatBytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $bytes = $bytes / pow(1024, $pow);
        // $bytes2 = $bytes / (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    }

    /*
    function is_user_with_role($rolename, $courseid = 0, $userid = 0) {
        $result = false;
        global $DB, $USER, $COURSE;
        if ($courseid == 0) {
            $courseid = $COURSE->id;
        }
        if ($userid == 0) {
            $userid = $USER->id;
        }
        
        $roles = get_user_roles(context_course::instance($courseid), $userid, false);
        foreach ($roles as $role) {
            if ($role->shortname == $rolename) {
                $result = true;
                break;
            }
        }
        return $result;
    }
    */
   
    /**
     * Check to see if this user is the role you are searching for in current course.
     * @param  integer $courseid The course id, will get from GLOBAL if not passed in.
     * @param  integer $userid   The user id, will get from GLOBAL if not passed in.
     * @param  string  $role     What role to search for.
     * @return bool              
     */
    function are_you_student() {

        global $DB, $COURSE, $USER, $SESSION;
        
        if (isset($SESSION->snapstudent)) {
            return $SESSION->snapstudent;
        }
        
        $role = 'student';
        if ($COURSE->id == 0) {
            return;
        }

        // $context = context_course::instance($COURSE->id);

        // Test 0 ----------------------------------------
        // $time_start = microtime(true);
        // $isStudent0 = has_capability('moodle/grade:edit', $context, $USER);
        // $time_end = microtime(true);
        // $execution_time0 = ($time_end - $time_start);

        // Test 1 ----------------------------------------
        // $time_start = microtime(true);
        // $isStudent1 = current(get_user_roles($context, $USER->id))->shortname == 'student' ? true : false; // instead of shortname you can also use roleid
        // $time_end = microtime(true);
        // $execution_time1 = ($time_end - $time_start);
        
        // Test 2 ----------------------------------------
        // $time_start = microtime(true);
        // $isStudent2 = !has_capability ('moodle/course:update', $context) ? true : false;
        // $time_end = microtime(true);
        // $execution_time2 = ($time_end - $time_start);
        
        // Test 3 ----------------------------------------
        // $time_start = microtime(true);
        
        $sql = "SELECT * FROM mdl_role_assignments AS ra 
            LEFT JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid 
            LEFT JOIN mdl_role AS r ON ra.roleid = r.id 
            LEFT JOIN mdl_context AS c ON c.id = ra.contextid 
            LEFT JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
            WHERE r.shortname = ? AND ue.userid = ? AND e.courseid = ?";
        
        $result = $DB->get_record_sql($sql, array($role, $USER->id, $COURSE->id));
        // $time_end = microtime(true);
        // $execution_time3 = ($time_end - $time_start);
        $isStudent3 = false;
        if (isset($result->roleid)) {
            $isStudent3 = $result->roleid == 5 ? true : false;
        }
        
        // error_log("\n TEST 0 -> Are you the father: ". $isStudent0 ." - Total Execution Time1: ".$execution_time0." Mins\n");
        // error_log("\n TEST 1 -> Are you the father: ". $isStudent1 ." - Total Execution Time1: ".$execution_time1." Mins\n");
        // error_log("\n TEST 2 -> Are you the father: ". $isStudent2 ." - Total Execution Time2: ".$execution_time2." Mins\n");
        // error_log("\n TEST 3 -> Are you the father: ". $isStudent3 ." - Total Execution Time3: ".$execution_time3." Mins\n");
        if ($isStudent3 == true) {
            $SESSION->snapstudent = true;
        } else {
            $SESSION->snapstudent = false;
        }
    }
}
