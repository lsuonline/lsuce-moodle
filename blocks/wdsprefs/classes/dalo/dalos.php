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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo, David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');

// class wds_semester extends wds_dalo {
class wds_period extends wds_dalo {
    public $sections;
    public $courses;

    public static function in_session($when = null) {
        if (empty($when)) {
            $when = time();
        }

        $filters = wds_where()->classes_start->less_equal($when)->grades_due->greater_equal($when)->is(null);

        return self::get_all($filters, true);
    }

    public function sections() {
        if (empty($this->sections)) {
            $sections = wds_section::get_all(array('academic_period_id' => $this->id));

            $this->sections = $sections;
        }

        return $this->sections;
    }

    public function get_session_key() {
        $s = empty($this->session_key) ? '' : ' (' . $this->session_key . ')';

        return $s;
    }

    public static function merge_sections(array $sections) {
        $semesters = array();

        foreach ($sections as $section) {
            $academic_period_id = $section->academic_period_id;

            // Work on different semesters.
            if (isset($semesters[$academic_period_id])) {
                continue;
            }

            $semester = $section->semester();
            $semester->courses = wds_course::merge_sections($sections, $semester);

            $semesters[$semester->id] = $semester;
        }

        return $semesters;
    }

    public function __toString() {
        $session = $this->get_session_key();
        return sprintf('%s %s%s at %s', $this->year, $this->name, $session, $this->campus);
    }
}

class wds_course extends wds_dalo {
    public $sections;
    public $teachers;
    public $student;

    public function get_department() {
        global $DB;

        $sql = "SELECT academic_unit
                    FROM {enrol_wds_units} WHERE academic_unit_id";

        $params = array('academic_unit_id' => $this->academic_unit_id);
        $unit_info = $DB->get_record('enrol_wds_units', $params);
        // $this->department = $unit_info->academic_unit;
        $this->department = $this->course_subject_abbreviation;
        return $this->course_subject_abbreviation;
    }

    public static function get_departments($filter = null) {
        global $DB;

        $safefilter = $filter ? "WHERE department = '" . addslashes($filter) . "'" : '';

        $sql = "SELECT DISTINCT(department)
                    FROM {enrol_wds_courses} $safefilter ORDER BY department";

        return array_keys($DB->get_records_sql($sql));
    }

    public static function flatten_departments($courses) {
        $departments = array();

        foreach ($courses as $course) {
            if (!isset($departments[$course->department])) {
                $departments[$course->department] = array();
            }

            $departments[$course->department][] = $course->id;
        }

        return $departments;
    }

    public static function by_department($dept) {
        return self::get_all(array('department' => $dept), true);
    }

    public static function merge_sections(array $sections, $semester = null) {
        $courses = array();

        foreach ($sections as $section) {
            $courseid = $section->moodle_status;

            // Filter on semester.
            if (is_array($semester) || is_object($semester)){
                $this_id = $semester->academic_period_id;
            } else {
                $this_id = $semester;
            }
            if ($semester and $section->academic_period_id != $this_id) {
                continue;
            }

            if (!isset($courses[$courseid])) {
                $course = $section->course();
                $course->sections = array();
                $courses[$courseid] = $course;
            }

            $courses[$courseid]->sections[$section->id] = $section;
        }

        return $courses;
    }

    public function teachers($semester = null, $isprimary = true) {
        if (empty($this->teachers)) {
            $filters = $this->section_filters($semester);

            if ($isprimary) {
                $filters->primary_flag->equal(1);
            }

            $this->teachers = wds_teacher::get_all($filters);
        }

        return $this->teachers;
    }

    public function students($semester = null) {
        if (empty($this->students)) {
            $filters = $this->section_filters($semester);

            $this->students = wds_student::get_all($filters);
        }

        return $this->students;
    }

    public function sections($semester = null) {
        if (empty($this->sections)) {
            $byparams = array('courseid' => $this->id);

            if ($semester) {
                $byparams['academic_period_id'] = $semester->id;
            }

            $this->sections = wds_section::get_all($byparams);
        }

        return $this->sections;
    }

    public function __toString() {
        return sprintf('%s %s', $this->department, $this->course_number);
    }

    private function section_filters($semester = null) {
        $sectionids = array_keys($this->sections($semester));

        $filters = wds::where()->sectionid->in($sectionids)->status->equal(wds::PROCESSED)->equal(wds::ENROLLED);

        return $filters;
    }
}

class wds_section extends wds_dalo {
    public $semester;
    public $course;

    public $moodle;
    public $group;

    public $primary;
    public $teachers;

    public $students;

    // This is very important!!!
    protected function qualified() {
        return wds::where()->section_listing_id->equal($this->section_listing_id)->status->in(wds::ENROLLED, wds::PROCESSED);
    }

    public function primary() {
        if (empty($this->primary)) {
            $teachers = $this->teachers();
            $primaries = function ($t) {
                return $t->role;
            };
            $this->primary = current(array_filter($teachers, $primaries));
        }
        if (property_exists($this->primary, 'userid')) {
            return $this->primary;
        }

        return $this->primary::get_userid($this->primary->universal_id);
    }

    public function teachers() {
        if (empty($this->teachers)) {
            $params = $this->qualified();
            $params->tablex = 'enrol_wds_teacher_enroll';
            $this->teachers = wds_teacher::get_all($params);
        }

        return $this->teachers;
    }

    public function students() {
        if (empty($this->students)) {
            $params = $this->qualified();
            $params->tablex = 'enrol_wds_student_enroll';
            $this->students = wds_student::get_all($params);
        }

        return $this->students;
    }

    public function semester() {
        if (empty($this->semester)) {
            $semester = wds_period::get(array(
                'academic_period_id' => $this->academic_period_id,
                // 'tablex' => 'enrol_wds_periods'
            ));

            $this->semester = $semester;
        }

        return $this->semester;
    }

    public function course() {
        if (empty($this->course)) {
            $course = wds_course::get(array('course_listing_id' => $this->course_listing_id));

            $this->course = $course;
        }

        return $this->course;
    }

    public function moodle() {
        if (empty($this->moodle) and !empty($this->idnumber)) {
            global $DB;

            $courseparams = array('idnumber' => $this->idnumber);
            $this->moodle = $DB->get_record('course', $courseparams);
        }

        return $this->moodle;
    }

    public function group() {
        if (!$this->is_manifested()) {
            return null;
        }

        if (empty($this->group)) {
            global $DB;

            $course = $this->course();
            $moodle = $this->moodle();
            $name = "$course->department $course->course_number $this->section_number";

            $params = array('name' => $name, 'courseid' => $moodle->id);

            $this->group = $DB->get_record('groups', $params);
        }

        return $this->group;
    }

    public function is_manifested() {
        global $DB;

        // Clearly it hasn't been manifested.
        if (empty($this->idnumber)) {
            return false;
        }

        $moodle = $this->moodle();

        return $moodle ? true : false;
    }

    public function __toString() {
        if ($this->course and $this->semester) {
            $course = $this->course;
            $semester = $this->semester;

            return sprintf('%s %s %s %s %s', $semester->period_year, $semester->period_type,
                $course->department, $course->course_number, $this->section_number);
        }

        return 'Section '. $this->section_number;
    }

    /** Expects a Moodle course, returns an optionally full wds_section */
    public static function from_course(stdClass $course, $fill = false) {
        if (empty($course->idnumber)) {
            return array();
        }

        $sections = self::get_all(array('idnumber' => $course->idnumber));

        if ($sections and $fill) {
            foreach ($sections as $section) {
                $section->course();
                $section->semester();
                $section->moodle = $course;
            }
        }

        return $sections;
    }

    public static function ids_by_course_department($semester, $department) {
        global $DB;

        $sql = 'SELECT sec.*
                FROM {enrol_wds_sections} sec,
                     {enrol_wds_courses} cou
                     WHERE sec.course_listing_id = cou.id
                       AND sec.academic_period_id = :semid
                       AND cou.department = :dept';

        $params = array('semid' => $semester->id, 'dept' => $department);

        return array_keys($DB->get_records_sql($sql, $params));
    }
}

abstract class user_handler extends wds_dalo {
    public $section;
    public $user;

    protected function qualified($bystatus = null) {
        // DALO
        $filters = wds::where()->universal_id->equal($this->universal_id);

        if (empty($bystatus)) {
            $filters->status->in(wds::ENROLLED, wds::PROCESSED);
        } else {
            $filters->status->equal($bystatus);
        }

        return $filters;
    }

    public function sections_by_status($status) {
        $params = $this->qualified($status);
        // DALO
        $class = 'enrol_'. get_called_class(). '_enroll';
        $params->tablex = $class;
        
        // if ($class == 'wds_student') {
        //     $params->tablex = 'enrol_wds_student_enroll';
        // } else if ($class == 'wds_teacher') {
        //     $params->tablex = 'enrol_wds_teacher_enroll';
        // }
        $bystatus = self::call('get_all', $params);

        $sections = array();
        foreach ($bystatus as $state) {
            $section = $state->section();
            $sections[$section->id] = $section;
        }

        return $sections;
    }

    public function section() {
        if (empty($this->section)) {
            // $section = wds_section::get(array('id' => $this->sectionid));
            // if (get_called_class() == "wds_teacher") {

            //     $section = wds_section::get(array(
            //         'section_listing_id' => $this->section_listing_id,
            //         'tablex' => 'enrol_'. get_called_class(). '_enroll'
            //     ));
            // } else {
                $section = wds_section::get(array(
                    'section_listing_id' => $this->section_listing_id
                ));
            // }
            
            $this->section = $section;
        }

        return $this->section;
    }

    public static function get_userid($obj = '') {
        global $DB;


        if (is_object($obj)) {
            $id = $obj->universal_id;
        } else if (is_array($obj)) {
            $id = $obj['universal_id'];
        } else {
            $id = $obj;
        }
        error_log("\n\nget_userid() -> what is the universal id: ". $id. "\n\n");
        $class = 'enrol_'. get_called_class(). 's';
        $params = array('universal_id' => $id);
        $user_info = $DB->get_record($class, $params);
        if ($user_info == false) {
            error_log("\n\nget_userid() -> you donkey fucker!!!!\n\n");
        }
        $user_info2 = $DB->get_records($class, $params);

        if (count($user_info2) > 1) {
            error_log("\n\nWhat is universal_id: ". $id);
            error_log("\n\nWhat is class: ". $class);
        }
        
        if (!isset($user_info->userid) || $user_info->userid == '') {
            $mdl_user = $DB->get_record('user', array(
                'email' => $user_info->email
            ));
            $user_info->userid = $mdl_user->id;
        }
        // Get the actual object.
        // get_called_class()::get(array('universal_id' => $user->universal_id));
        return $user_info;
    }

    public function user() {
        if (empty($this->user)) {

            $extrafields = \core_user\fields::for_userpic()->get_required_fields();
            $usernamefields = implode(",", $extrafields);
            // DALO CHECK - second param was true, now false (meta)
            $user = wds_user::get(array('id' => $this->userid), false,
                "{$usernamefields}, username, idnumber");
            $this->user = $user;
        }

        return $this->user;
    }

    public static function reset_status($section, $to = 'pending', $from = 'enrolled') {
        if (is_object($section)) {
            $section = $section->section_listing_id;
        }

        $class = get_called_class();
        // DALO: hack fix for now
        $params['status'] = $to;
        if ($class == 'wds_student') {
            $params['tablex'] = 'enrol_wds_student_enroll';
        } else if ($class == 'wds_teacher') {
            $params['tablex'] = 'enrol_wds_teacher_enroll';
        }
        $class::update(
            $params,
            wds::where()->section_listing_id->in($section)->status->equal($from)
        );
    }
}

class wds_teacher extends user_handler {
    public $sections;

    public function sections($isprimary = false) {
        if (empty($this->sections)) {
            $qualified = $this->qualified();

            // if ($isprimary) {
            //     $qualified->primary_flag->equal(1);
            // }
            $qualified->tablex = 'enrol_wds_teacher_enroll';
            $allteaching = self::get_all($qualified);
            $sections = array();
            foreach ($allteaching as $teacher) {
                $section = $teacher->section();
                if($section == false) {
                    // DALO: FIX THIS - have seen sections return as false.
                    // skip for now but find out why!!!!!
                    continue;
                }
                $sections[$section->id] = $section;
            }

            $this->sections = $sections;
        }

        return $this->sections;
    }
}

class wds_student extends user_handler {
    public $sections;

    public function sections() {
        if (empty($this->sections)) {
            $allstudents = self::get_all($this->qualified());

            $sections = array();
            foreach ($allstudents as $student) {
                $section = $student->section();
                $sections[$section->id] = $section;
            }

            $this->sections = $sections;
        }

        return $this->sections;
    }
}

class wds_user extends wds_dalo {

    public static function tablename($alias='', $options='') {
        return !empty($alias) ? "{user} $alias" : 'user';
    }

    private static function qualified($userid = null) {
        if (!$userid) {
            global $USER;
            $userid = $USER->id;
        }

        $filters = wds::where()->universal_id->equal($universal_id)->status->in(wds::PROCESSED, wds::ENROLLED);

        return $filters;
    }

    public static function is_teacher($userid = null) {
        $count = wds_teacher::count(self::qualified($userid));

        return !empty($count);
    }

    public static function is_teacher_in($sections, $primary = false, $userid = null) {
        $filters = self::qualified($userid);

        $filters->sectionid->in(array_keys($sections));

        if ($primary) {
            $filters->primary_flag->equal(1);
        }

        $count = wds_teacher::count($filters);
        return !empty($count);
    }

    public static function sections($primary = false) {
        if (!self::is_teacher()) {
            return array();
        }

        $teacher = current(wds_teacher::get_all(self::qualified()));

        return $teacher->sections($primary);
    }
}
