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

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * General LSU Class with various functions.
 */
class lsu_theme_snippets {

    /**
     * Little snippet to display the size of the course using the bootstrap
     * progressbar.
     * @param int $isadmin - Is the current user admin or no?
     * @return string - The html to display.
     */
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

    /**
     * Get the total file size of a course.
     * @param int $courseid - The course id.
     * @return int - Total size.
     */
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

    /**
     * Format a data size number to make it human readable.
     * @param int $bytes - The size in bytes.
     * @param int $precision - The number of decimal places.
     * @return bool
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes = $bytes / pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check to see if this user is a student.
     * @return bool
     */
    function are_you_student() {

        global $DB, $COURSE, $USER, $SESSION;

        if (isset($SESSION->lsustudent) && $SESSION->lsustudent == true) {
            return $SESSION->lsustudent;
        }

        if ($COURSE->id == 0) {
            return;
        }

        $context = context_course::instance($COURSE->id);

        // If user can edit grades then let them see how big it is.
        $isStudent0 = has_capability('moodle/grade:edit', $context, $USER);

        if ($isStudent0 == true) {
            // They are NOT a student, return false.
            $SESSION->lsupstudent = false;
            return false;
        } else {
            // They ARE a student, return true.
            $SESSION->lsustudent = true;
            return true;
        }
    }
}
