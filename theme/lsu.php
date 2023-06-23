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

    
    public function show_course_size() {
        global $OUTPUT, $COURSE, $CFG;
        // $coursesize = $this->get_file_size() / 1048576;
        $coursesize = $this->get_file_size();

        $sizesetting = (int)get_config('theme_snap', 'course_size_limit');
        // $sizesetting *= 1000;

        $percentage = number_format(((($coursesize / 1048576) * 100) / $sizesetting), 2);
        // number_format( $myNumber, 2, '.', '' );
        // Let's format this number so it's readable.
        $size = $this->formatBytes($coursesize);
        
        // What is the percentage of it being full.
        // $this->formatPercentage($)
        // $OUTPUT->help_icon('course_size', 'theme_snap').
        $coursesnippet = 'Course File Size: '.$size. ' <a href="'.$CFG->wwwroot.'/report/coursesize/course.php?id='.$COURSE->id.'" target="_blank"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>'.
        '<div class="progress" style="width: 25%" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-'.$this->get_bootstrap_barlevel($percentage).'" style="width: '.$percentage.'%">'.$percentage.'%</div>
        </div>';
      

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
        return $csize->filesize;
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
    function are_you_student($courseid = 0, $userid = 0, $role = 'student') {

        global $DB, $USER, $COURSE;
        if ($courseid == 0) {
            $courseid = $COURSE->id;
        }
        if ($userid == 0) {
            $userid = $USER->id;
        }

        $sql = "SELECT * FROM mdl_role_assignments AS ra 
            LEFT JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid 
            LEFT JOIN mdl_role AS r ON ra.roleid = r.id 
            LEFT JOIN mdl_context AS c ON c.id = ra.contextid 
            LEFT JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
            WHERE r.shortname = ? AND ue.userid = ? AND e.courseid = ?";
        
        $result = $DB->get_records_sql($sql, array($role, $userid, $courseid));

        if ($result) {
            if (count($result) == 1) {
                return true;
            }
        }
        return false;
    }
}
