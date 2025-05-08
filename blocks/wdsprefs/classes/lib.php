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

require_once(dirname(dirname(__FILE__)) . '/publiclib.php');
require_once('event/wds_course_created.php');
wds::require_dalos();

interface verifiable {
    public static function is_valid($semesters);
}

abstract class wds_preferences extends wds_external implements verifiable {
    public static function settings() {
        $settings = array('creation', 'split', 'crosslist',
            'team_request', 'material', 'unwant', 'setting');

        $remainingsettings = array();

        foreach ($settings as $setting) {
            $class = 'wds_' . $setting;

            if (!$class::is_enabled()) {
                continue;
            }

            $remainingsettings[$setting] = $class::name();
        }

        return $remainingsettings;
    }

    public static function is_enabled() {
        global $USER;

        // Allow admins to login as instructors and by-pass disabled settings to pre-build courses for them.
        if (isset($USER->realuser) and is_siteadmin($USER->realuser)) {
            return true;
        } else {
            $setting = self::call('get_name');

            return (bool) get_config('block_wdsprefs', $setting);
        }
    }

    public static function name() {
        return get_string('wdsprefs:'.self::call('get_name'), 'block_wdsprefs');
    }

    public static function is_valid($semesters) {
        return !empty($semesters);
    }
}

interface application {
    public function apply();
}

interface undoable {
    public function unapply();
}

interface unique extends application {
    public function new_idnumber();
}

abstract class wds_section_accessor extends wds_preferences {
    public $section;

    public function section() {
        if (empty($this->section)) {
            $section = wds_section::get(array('id' => $this->sectionid));

            $this->section = $section;
        }
        return $this->section;
    }
}

abstract class wds_user_section_accessor extends wds_section_accessor {
    public $user;

    public function user() {
        if (empty($this->user)) {
            $user = wds_user::get(array('id' => $this->userid));

            $this->user = $user;
        }

        return $this->user;
    }
}

abstract class manifest_updater extends wds_user_section_accessor implements unique {
    public function save($params = '') {
        $updated = !empty($this->id);
        $updated = parent::save($params) && $updated;

        if ($updated) {
            $this->update_manifest();
        }

        return true;
    }

    public function update_manifest() {
        global $DB;
        // Only on update.
        if (empty($this->id)) {
            return false;
        }

        $section = $this->section();

        if (!$section->idnumber) {
            $wdssection = $DB->get_record('enrol_wds_sections', array(
                'id' => $section->id,
                'courseid' => $section->courseid,
                'semesterid' => $section->semesterid
            ),
            '*', MUST_EXIST);
            $section->idnumber = $wdssection->idnumber;
        }
        $course = $section->moodle();

        // Nothing to do.
        if (empty($course)) {
            return false;
        }

        $newidnumber = $this->new_idnumber();
        $context = context_course::instance($course->id);

        // Allow event to rename course.
        $event = \blocks_wdsprefs\event\wds_course_created::create(
            array(
                'context' => $context,
                'objectid' => $course->id,
                'courseid' => $course->id
            )
        );

        $event->trigger();

        // Change association if there exists no other course.
        // This would prevent an unnecessary course creation.
        $n = $DB->get_record('course', array('idnumber' => $newidnumber));
        if (empty($n) and $course->idnumber != $newidnumber) {
            $course->idnumber = $newidnumber;
        }
        return $DB->update_record('course', $course);
    }
}

// Begin Concrete classes.
class wds_unwant extends wds_user_section_accessor implements application, undoable {
    public $sectionid;
    public $userid;

    public static function active_sections_for($teacher, $isprimary = true) {
        $sections = $teacher->sections($isprimary);

        return self::active_sections($sections, $teacher->userid);
    }

    public static function active_sections(array $sections, $userid = null) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $unwants = self::get_all(array(
            'userid' => $userid,
            'tablex' => 'block_wdsprefs_unwants'
        ));

        foreach ($unwants as $unwant) {
            if (isset($sections[$unwant->sectionid])) {
                unset($sections[$unwant->sectionid]);
            }
        }

        return $sections;
    }

    public function apply() {
        $section = $this->section();

        // Severage is happening in eventslib.php.
        wds::unenroll_users(array($section));
    }

    public function unapply() {
        $section = $this->section();

        wds::enroll_users(array($section));
    }
}

class wds_material extends wds_preferences implements application, undoable {
    public $userid;
    public $courseid;
    public $moodleid;

    private $wdscourse;
    private $user;
    private $moodle;

    public function moodle() {
        global $DB;

        if (empty($this->moodle)) {
            if ($this->moodleid) {
                $params = array('id' => $this->moodleid);
            } else {
                $params = array('shortname' => $this->build_shortname());
            }
            $this->moodle = $DB->get_record('course', $params);
        }

        return $this->moodle;
    }

    public function course() {
        if (empty($this->wdscourse) and $this->courseid) {
            $this->wdscourse = wds_course::by_id($this->courseid);
        }

        return $this->wdscourse;
    }

    public function user() {
        if (empty($this->user) and $this->userid) {
            $this->user = wds_user::by_id($this->userid);
        }

        return $this->user;
    }

    public function build_shortname() {
        $pattern = get_config('block_wdsprefs', 'material_shortname');

        $a = new stdClass;
        $a->department = $this->course()->department;
        $a->course_number = $this->course()->course_number;
        $a->fullname = fullname($this->user());
        return wds::format_string($pattern, $a);
    }

    public function unapply() {
        $mcourse = $this->moodle();

        if (empty($mcourse)) {
            return true;
        }

        $enrol = self::get_enrol_plugin();
        $instance = $enrol->get_instance($mcourse->id);

        $enrol->unenrol_user($instance, $this->userid);
        return true;
    }

    public function apply() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $shortname = $this->build_shortname();

        $mcourse = $DB->get_record('course', array('shortname' => $shortname));

        $enrol = self::get_enrol_plugin();

        if (!$mcourse) {
            $category = $enrol->manifest_category($this->course());

            $course = new stdClass;
            $course->visible = 0;
            $course->numsections = get_config('moodlecourse', 'numsections');
            $course->format = get_config('moodlecourse', 'format');

            $course->fullname = $shortname;
            $course->shortname = $shortname;
            $course->summary = $shortname;
            $course->category = $category->id;
            $course->startdate = time();

            $settings = wds_setting::get_all(wds::where()
                ->userid->equal($this->userid)
                ->name->starts_with('creation_')
            );

            foreach ($settings as $setting) {
                $key = str_replace('creation_', '', $setting->name);
                $course->$key = $setting->value;
            }

            $mcourse = create_course($course);
        }

        $instance = $enrol->get_instance($mcourse->id);

        $primary = $enrol->setting('editingteacher_role');
        $enrol->enrol_user($instance, $this->userid, $primary);

        $this->moodleid = $mcourse->id;

        return true;
    }
}

class wds_creation extends wds_preferences implements application {
    public $userid;
    public $semesterid;
    public $courseid;
    public $enroll_days;
    public $create_days;

    public function apply() {
        $params = array(
            'semesterid' => $this->semesterid,
            'courseid' => $this->courseid
        );

        // All the sections for this course and semester.
        $sections = wds_section::get_all($params);

        $userid = $this->userid;

        $byteacher = function ($section) use ($userid) {
            $primary = $section->primary();

            if (empty($primary)) {
                $primary = current($section->teachers());
            }

            if (empty($primary)) {
                return false;
            }

            return $userid == $primary->userid;
        };

        $associated = array_filter($sections, $byteacher);

        wds::inject_manifest($associated);
    }
}

class wds_setting extends wds_preferences {
    public $userid;
    public $name;
    public $value;

    public static function is_valid($semesters) {
        global $USER;
        return parent::is_valid($semesters) || is_siteadmin($USER->id);
    }

    public static function get_to_name($params) {
        $settings = self::get_all($params);

        $tonamedsettings = array();
        foreach ($settings as $setting) {
            $tonamedsettings[$setting->name] = $setting;
        }

        return $tonamedsettings;
    }
}

class wds_split extends manifest_updater implements application, undoable {
    public $userid;
    public $sectionid;
    public $groupingid;

    public static function is_valid($semesters) {
        $valids = self::filter_valid($semesters);
        return !empty($valids);
    }

    public static function filter_valid_courses($courses) {
        return array_filter($courses, function ($course) {
            return count($course->sections) > 1;
        });
    }

    public static function filter_valid($semesters) {
        return array_filter($semesters, function($semester) {
            $courses = wds_split::filter_valid_courses($semester->courses);
            return count($courses) > 0;
        });
    }

    public static function in_course($course) {
        global $USER;

        if (empty($course->sections)) {
            $course->sections = array();

            $teacher = wds_teacher::get(array('id' => $USER->id));

            $sections = wds_unwant::active_sections_for($teacher, true);

            foreach ($sections as $section) {
                if ($section->courseid != $course->id) {
                    continue;
                }
                $course->sections[$section->id] = $section;
            }
        }

        $splitfilters = wds::where()
            ->userid->equal($USER->id)
            ->sectionid->in(array_keys($course->sections));

        $splitfilters->tablex = 'block_wdsprefs_splits';
        $splits = self::get_all($splitfilters);

        return $splits;
    }

    public static function exists($course) {
        return self::in_course($course) ? true : false;
    }

    public static function groups($splits) {
        if (empty($splits)) {
            return 0;
        }

        return array_reduce($splits, function ($in, $split) {
            return $split->groupingid > $in ? $split->groupingid : $in;
        });
    }

    // This creates a new idnumber for course splitting.
    public function new_idnumber() {
        $section = $this->section();
        $semester = $section->semester();
        // DALO FIX: What to do about session_key???? (staging has A, B, C)
        if (property_exists($semester, "session_key")) {
            $session_key = $semester->session_key;
        } else {
            $session_key = '';
        }

        $course = $section->course();

        // $semstr = "$semester->year$semester->name$session_key";
        $semstr = "$semester->period_year$semester->period_type$session_key";
        // $coursestr = "$course->department$course->cou_number";
        $coursestr = "$course->course_subject_abbreviation$course->course_number";

        $idnumber = "$semstr$coursestr{$this->userid}split{$this->groupingid}";

        return $idnumber;
    }

    public function apply() {
        $sections = array($this->section());

        wds::inject_manifest($sections);
    }

    public function unapply() {
        $sections = array($this->section());

        wds::inject_manifest($sections, function ($sec) {
            $sec->idnumber = '';
        });
    }
}
