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

namespace block_course_hider\controllers;

// use block_course_hider\persistents\course_hider;

class form_controller {

    private $partial;
    /**
     * Let's process the form by getting the results and showing it.
     * @param  object - the form data.
     * @return array - list of courses.
     */
    public function process_form($params = false) {
        global $DB;

        // Check raw input field and use if there's stuff.
        if ($params->raw_input != "") {
            // Cleanse it.
            $stripped = preg_replace('/;/', '', $params->raw_input);
            $stripped = trim($stripped, ';');
            $stripped = trim($stripped);

            // Store the partial for later use.
            $this->partial = $stripped;
            $snippet = "SELECT * FROM {course}
                           WHERE shortname LIKE '%" . $stripped . "%'
                           OR fullname LIKE '%" . $stripped . "%'
                           OR id = '" . $stripped . "'";
        } else {

            $showhidden = (isset($params->hiddenonly) && $params->hiddenonly == 2)
                          ? ''
                          : ' visible = ' . $params->hiddenonly . ' AND ';

            $years = \course_hider_helpers::getYears()[$params->ch_years] . " ";
            $semester = \course_hider_helpers::getSemester()[$params->ch_semester];
            $semtype = "";
            $section = "";
            
            if ($params->ch_semester_type != "0") {
                $semtype = \course_hider_helpers::getSemesterType()[$params->ch_semester_type];
                $semtype .= " ";
            }
            if ($params->ch_semester_section != "0") {
                $section = " ". \course_hider_helpers::getSemesterSection()[$params->ch_semester_section];
            }

            // Store the partial for later use.
            $this->partial = $years.$semtype.$semester.$section;
            $snippet = "SELECT * FROM {course} WHERE $showhidden " .
                "shortname LIKE '" . $this->partial . " %'";
        }

        $courses = $DB->get_records_sql($snippet);
        $courses["lockme"] = $params->lockcourses;
        $courses["hideme"] = $params->hidecourses;

        return $courses;
    }

    /**
     * Execute the form to make the courses either hidden or visible.
     * @param  array - list of courses to process.
     * @param  array - the form data.
     * @return null
     */
    public function execute_hider($courses = array(), $fdata = array()) {
        global $DB, $CFG;
        $updatecount = 0;
        $time_start = microtime(true);

        if (isset($courses->hideme) && $courses->hideme == 1) {
            // Execute on the hidden courses and make them visible.
            $showhidden = '1';
            $hiddentext = "visible";
        } else {
            // Execute on the visible courses and make them hidden.
            $showhidden = '0';
            $hiddentext = "hidden";
        }
        $lockme = $courses["lockme"];
        unset($courses["lockme"]);
        $hideme = $courses["hideme"];
        unset($courses["hideme"]);

        $lockcourses = (isset($lockme) && $lockme == 2)
                           ? ''
                           : ' ctx.locked = ' . $lockme;

        foreach($courses as $course) {
            $dataobject = [
                'id' => $course->id,
                'visible' => $hideme,
            ];
            // Update the course to be hidden.
            if (isset($hideme) && $hideme < 2) {
                $result = $DB->update_record('course', $dataobject, $bulk = false);
                $hidetask = $hideme == 0 ? 'to be hidden' :  'to be visible';
            } else {
                $hidetask = '';
            }

            if (isset($lockme) && $lockme < 2) {
                $sql =  'UPDATE {course} c
                        INNER JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = "50"
                        SET ' . $lockcourses . '
                        WHERE c.id = ' . $course->id;

                $locked = $DB->execute($sql);
                $locktask = $lockme == 1 ? ' and was locked' : ' and was unlocked';
            } else {
                $locktask = '';
            }

            if ((isset($hideme) && $hideme < 2) || (isset($lockme) && $lockme < 2)) {
                $updatecount++;
                mtrace("Course (" . $course->id . "):
                    <a href='" . $CFG->wwwroot . "/course/view.php?id=" . $course->id . "' target='_blank'>" . $course->shortname . "</a>
                    was updated " . $hidetask . $locktask . ".<br>");
            } else {
                mtrace("Course (" . $course->id . "):
                    <a href='" . $CFG->wwwroot . "/course/view.php?id=" . $course->id . "' target='_blank'>" . $course->shortname . "</a>
                    has been left alone.<br>");

            }
        }
        $time_end = microtime(true);
        if ($updatecount == 0) {
            mtrace("<br><br>Ummmm......nothing was updated.<br>");
        } else {
            $execution_time = $time_end - $time_start;
            mtrace("A total of ". $updatecount. " courses have been hidden / locked and took ". number_format($execution_time, 2). " seconds.<br>");
        }
        
        mtrace("<br>--- Process Complete ---<br>");
    }    
}
