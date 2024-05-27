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
 * Reprocess All Tool
 * @package    block_ues_reprocess
 * @copyright  Louisiana State University
 * @copyright  The guy who did stuff: David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/enrol/ues/publiclib.php');

class repall {

    public function get_semesters() {
        $chopped = get_config('moodle', "ues_reprocess_semesters");
        $cut = explode(PHP_EOL, $chopped);
        $semesters = array();
        foreach ($cut as $slice) {
            $semi = explode(',', $slice);
            // The config may have EOL's, remove them.
            $semesters[$semi[0]] = preg_replace( "/\r|\n/", "", $semi[1]);
        }
        return $semesters;
    }

    public function get_year($index = false) {

        if ($index > 1999) {
            // If using the CLI or scheduled task then the year will be passed in.
            return $index;
        }
        $years = range(2019, date("Y"));
        // Must account for the "Select Year" as first option.
        if ($index) {
            return $years[$index - 1];
        }
        return $years;
    }

    public function buildCourseName($spaced = true, $year = '', $semester = '', $department = '') {
    // public static function buildCourseName($spaced = true, $year = '', $semester = '', $department = '') {
        global $DB;

        if ($spaced) {
            $spacer = " ";
        }

        $sem = '';
        if ($semester != '' && $semester != '0') {
            $semesters = $this->get_semesters();
            $sem = $spacer. $semesters[$semester];
        }

        $dept = '';
        if ($department != '' && $department != '0' && $department != 'start') {
            if($department[0] === '_') {
                $department = ltrim($department, $department[0]); 
                $dept = $spacer. $DB->get_field('course_categories', 'name', array('id' => $department));
            } else {
                // The course cat name was passed in, use it.
                $dept = $spacer. $department;
            }
        }
        return $year.$sem.$dept;
    }
    

    public function get_departments() {
        global $DB;

        $sql = "SELECT id, name
            FROM {course_categories}
            ORDER BY name";

        $dept_list = $DB->get_records_sql($sql);
        $depts = array();
        foreach ($dept_list as $dippy) {
            $depts["_".$dippy->id] = $dippy->name;
        }

        return $depts;
    }

    public function get_courses($year = '', $semester = '', $department = '') {
        global $DB;

        $likethis = $this->buildCourseName(true, $year, $semester, $department);
        $sql = "SELECT * FROM {course} where fullname like '".$likethis. "%'";
        
        $course_list = $DB->get_records_sql($sql);
        $courses = array(0 => "Select A Course");
        foreach ($course_list as $dippy) {
            $courses[$dippy->id] = $dippy->fullname;
        }

        return $courses;
    }

    public function get_sections($year = 0, $semester = 0, $department = 0, $course = 0) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/enrol/ues/publiclib.php');

        if ($course == 0) {
            return false;
        }

        $spaced = false;
        $likethis = $this->buildCourseName($spaced, $year, $semester, $department);
        $sql = "select sec_number as section from {enrol_ues_sections} ".
            "where courseid=".$course." and idnumber like '".$likethis."%'";
        $sections = $DB->get_records_sql($sql);

        return $sections;
    }

    public function get_course_count($year = 0, $semester = '', $department = '') {
        global $DB, $CFG;

        if ($year == 0) {
            $year = date('Y');
        }

        $likethis = $this->buildCourseName(true, $year, $semester, $department);
        $sql = "select COUNT(*) AS counter from {course} where fullname like '".$likethis. "%'";
        $count = $DB->get_record_sql($sql);

        return $count->counter;        
    }

    public function run_it_all($form_data) {

        global $DB, $CFG;
        $starttime = microtime(true);

        require_once($CFG->dirroot . '/enrol/ues/publiclib.php');
        require_once(dirname(__DIR__).'/lib.php');

        ues::require_daos();
        require_login();

        // The form data is giving the index and not the year for ues_year.
        if ($form_data->ues_year == "0") {
            mtrace("\nPlease pick a year to process.\n\n\n");
            return;
        }

        $year = $this->get_year($form_data->ues_year);

        $sem = isset($form_data->ues_semesters) ? $form_data->ues_semesters : false;
        $dept = isset($form_data->ues_departments) ? $form_data->ues_departments : false;
        $cid = isset($form_data->ues_courses_h) ? $form_data->ues_courses_h : false;
        $purge = isset($form_data->ues_checkbox) ? $form_data->ues_checkbox : false;
        $stask = isset($form_data->scheduled_task) ? $form_data->scheduled_task : false;

        $breaker = "<br>";
        if ($stask) {
            $breaker = "\n";
        }

        if (debugging()) {
            mtrace("What is the data passed in: ". print_r($form_data, true). $breaker);
            mtrace("What is year: ". $year. $breaker);
            mtrace("What is sem: ". $sem. $breaker);
            mtrace("What is dept: ". $dept. $breaker);
            mtrace("What is cid: ". $cid. $breaker);
            mtrace("What is purge: ". $purge. $breaker);
            mtrace("What is stask: ". $stask. $breaker);
        }

        // Let's process just the course.
        if ($cid) {
            $course = $DB->get_record('course', array('id' => $cid));
            mtrace("Reprocessing course: ". $course->fullname. $breaker);
            if ($purge) {
                $sections = ues_section::from_course($course, true);
                ues::unenroll_users($sections, true);
                mtrace("Unenrolled from course: ". $course->fullname. $breaker);
            } else {
                ues::reprocess_course($course);
                mtrace("Reprocessed course: ". $course->fullname. $breaker);
            }
        } else {

            $likethis = $this->buildCourseName(true, $year, $sem, $dept);

            $sql = "SELECT * FROM {course} where fullname like '".$likethis. "%'";
            mtrace("SQL to run: ".$sql. $breaker);
            $courses = $DB->get_records_sql($sql);

            ues::require_daos();
            // $s = ues::gen_str('block_ues_reprocess');
            mtrace("There are ".count($courses). " to process.". $breaker);
            foreach($courses as $course) {

                if ($course->id == 1) {
                    continue;
                }

                if ($purge) {
                    $sections = ues_section::from_course($course, true);
                    ues::unenroll_users($sections, true);
                    mtrace("Unenrolled from course: ". $course->fullname. $breaker);
                } else {
                    ues::reprocess_course($course);
                    mtrace("Reprocessed course: ". $course->fullname. $breaker);
                }
            }
        }

        $endtime = microtime(true);
        $elapsed = round($starttime - $endtime, 1);

        mtrace("Total time to run reprocess_all is: ". $elapsed);
    }    
}
