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

require_once($CFG->dirroot . '/blocks/wdsprefs/lib.php');
require_once($CFG->dirroot . '/blocks/wdsprefs/classes/profile_field_helper.php');

abstract class blocks_wdsprefs_wds_handler {

    /**
     *
     * @global type $DB
     * @param stdClass $user previously, this has been of type wds_user
     * @see enrol_wds_plugin::create_user
     * @return boolean
     */
    public static function user_updated($user) {
        global $DB;

        $firstname = wds_setting::get(array(
            'universal_id' => $user->id,
            'name' => 'user_firstname'
        ));

        // No preference or firstname is the same as preference.
        if (empty($firstname) or $user->firstname == $firstname->value) {
            return true;
        }

        $user->firstname = $firstname->value;
        return $DB->update_record('user', $user);
    }

    /**
     * For users who have previously set their preferred name
     * and who have now had their name changed officially (so that
     * provider returns this name as firstname), delete the setting
     * for firstname.
     * @param type $user
     */
    public static function preferred_name_legitimized($user) {
        $params = array(
            'universal_id' => $user->id,
            'name' => 'user_firstname'
        );
        wds_setting::delete_all($params);
    }

    public static function wds_primary_change($data) {
        // Empty enrollment / idnumber.
        wds::unenroll_users(array($data->section));

        // Safe keeping.
        $data->section->idnumber = '';
        $data->section->status = wds::PROCESSED;
        $data->section->save();

        // Set to re-enroll.
        wds_student::reset_status($data->section, wds::PROCESSED);
        wds_teacher::reset_status($data->section, wds::PROCESSED);

        return $data;
    }

    public static function wds_teacher_process($wdsteacher) {
        $threshold = get_config('block_wdsprefs', 'course_threshold');

        $course = $wdsteacher->section()->course();

        // Must abide by the threshold.
        if ($course->course_number >= $threshold) {
            $unwantparams = array(
                'universal_id' => $wdsteacher->userid,
                'section_listing_id' => $wdsteacher->sectionid
            );

            $unwant = wds_unwant::get($unwantparams);

            if (empty($unwant)) {
                $unwant = new wds_unwant();
                $unwant->fill_params($unwantparams);
                $unwant->save();
            }
        }

        return true;
    }

    public static function wds_teacher_release($wdsteacher) {
        // Check for promotion or demotion.
        $params = array(
            'universal_id' => $wdsteacher->userid,
            'section_listing_id' => $wdsteacher->sectionid,
            'status' => wds::PROCESSED
        );

        $otherself = wds_teacher::get($params);

        if ($otherself) {
            $promotion = $otherself->primary_flag == 1;
            $demotion = $otherself->primary_flag == 0;
        } else {
            $promotion = $demotion = false;
        }

        $deleteparams = array('universal_id' => $wdsteacher->userid);

        $allsectionsettings = array('unwant', 'split', 'crosslist');

        if ($promotion) {
            // Promotion means all settings are in tact.
            return $wdsteacher;
        } else if ($demotion) {
            // Demotion means crosslist and split behavior must be effected.
            unset($allsectionsettings[0]);
        }

        $bysuccessfuldelete = function($in, $setting) use ($deleteparams, $wdsteacher) {
            $class = 'wds_'.$setting;
            return $in && $class::delete_all($deleteparams + array(
                'section_listing_id' => $wdsteacher->sectionid
            ));
        };

        $success = array_reduce($allsectionsettings, $bysuccessfuldelete, true);

        $creationparams = array(
            'course_listing_id' => $wdsteacher->section()->courseid,
            'semesterid' => $wdsteacher->section()->semesterid
        );

        $success = (
            wds_creation::delete_all($deleteparams + $creationparams) and
            wds_team_request::delete_all($deleteparams + $creationparams) and
            $success
        );

        return $wdsteacher;
    }

    public static function wds_section_process($section) {
        $semester = $section->semester();

        $primary = $section->primary();
        
        // $dis_user = $primary::get_userid($primary->universal_id);
        // $dis_user = $class::get_all($params);
        // $users[$user->id]->user = $dis_user;
        // $primary->userid = $dis_user->userid;
        
        // TODO debug this: 1 why  use current ? is the choice of teacher arbitrary ?
        // We know a teacher exists for this course, so we'll use a non-primary.
        if (!$primary) {
            $primary = current($section->teachers());
        }

        // Unwanted interjection.
        // mdl_block_wdsprefs_unwants
        $unwanted = wds_unwant::get(array(
            'userid' => $primary->userid,
            'sectionid' => $section->id,
            'tablex' => 'block_wdsprefs_unwants'
        ));

        if ($unwanted) {
            $section->wd_status = wds::PENDING;
            return $section;
        }

        // Creation and Enrollment interjection.
        $creationparams = array(
            'userid' => $primary->userid,
            'semesterid' => $section->semester->id,
            'courseid' => $section->moodle_status,
            'tablex' => 'block_wdsprefs_creations'
        );

        $creation = wds_creation::get($creationparams);
        if (!$creation) {
            $creation = new wds_creation();
            $creation->create_days = get_config('block_wdsprefs', 'create_days');
            $creation->enroll_days = get_config('block_wdsprefs', 'enroll_days');
        }

        $classesstart = $semester->start_date;
        $diff = $classesstart - time();

        $diffdays = ($diff / 60 / 60 / 24);

        if ($diffdays > $creation->create_days) {
            $section->wd_status = wds::PENDING;
            return $section;
        }

        if ($diffdays > $creation->enroll_days) {
            wds_student::reset_status($section, wds::PENDING, wds::PROCESSED);
        }

        // foreach (array('split', 'crosslist', 'team_section') as $setting) {
        foreach (array('split') as $setting) {
            $class = 'wds_'.$setting;
            $applied = $class::get(array(
                'sectionid' => $section->id,
                'tablex' => 'block_wdsprefs_'.$setting.'s'
            ));

            if ($applied) {
                $section->idnumber = $applied->new_idnumber();
            }
        }

        return $section;
    }

    public static function wds_section_drop($section) {
        $sectionsettings = array('unwant', 'split', 'crosslist', 'team_section');

        foreach ($sectionsettings as $settting) {
            $class = 'wds_' . $settting;

            $class::delete_all(array('sectionid' => $section->id));
        }

        return true;
    }

    public static function wds_semester_drop($semester) {
        $semestersettings = array('wds_creation', 'wds_team_request');

        foreach ($semestersettings as $class) {
            $class::delete_all(array('semesterid' => $semester->id));
        }

        return true;
    }

    /**
     * Manipulates the shortname and fullname of a split, crosslist, or team-teach
     * of a newly created course shell. It may be invoked either by a wds_course_created
     * event being triggered or by direct invocation (which passes a course object).
     * If it was invoked by the trigger, the course object is created by database lookup
     * from the courseid stored in the event object.
     *
     * @param $eventorcourse The event object or course object that invoked this handler.
     * @return               The modified course object.
     */
    public static function wds_course_created($eventorcourse) {
        global $DB;

        if ($eventorcourse instanceof \blocks_wdsprefs\event\wds_course_created) {
            $event = $eventorcourse;
            // Extract courseid from event object.  Event data is protected, so we must use the public accessor.
            $eventinfo = $event->get_data();
            $courseid = $eventinfo['courseid'];

            // TODO: The block below needs to do the standard 'Programming error detected...' output.
            if (!$courseid) {
                debugging("Course ID is null in wds_course_created event handler.");
                die("Course ID is null in wds_course_created event handler.");
            }

            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        } else {
            $course = $eventorcourse;
        }
        $sections = wds_section::from_course($course);
        if (count($sections) > 1) {
            error_log("DIE BITCH");
            die();
        }
        
        // when undoing the sections, it could be the object.
        if (is_array($sections)) {
            $temp_sec = array_values($sections);
            $section_num = $temp_sec[0]->section_number;
        } else {
            $section_num = $sections->section_number;
        }

        if (empty($sections)) {
            return $course;
        }

        $section = reset($sections);

        $primary = $section->primary();

        if (empty($primary)) {
            return $course;
        }
        // $creationsettings = wds_setting::get_all(wds::where()
        //     ->userid->equal($primary->userid)
        //     ->name->starts_with('creation_')
        // );

        $semester = $section->semester();
        $session = $semester->get_session_key();

        $wdscourse = $section->course();

        $ownerparams = array(
            'userid' => $primary->userid,
            'sectionid' => $section->id,
            'tablex' => 'block_wdsprefs_splits'
        );

        // Properly fold.
        $fullname = $course->fullname;
        $shortname = $course->shortname;
        $pattern = '';
        $a = new stdClass;
        $user = $DB->get_record('user', array('id' => $primary->userid));

        $split = wds_split::get($ownerparams);
        if ($split) {
            $a->period_year = $semester->period_year;
            $a->period_type = $semester->period_type;

            $a->course_subject_abbreviation = $section->course_subject_abbreviation;
            $a->course_number = $wdscourse->course_number;
            $a->firstname = $user->firstname;
            $a->lastname = $user->lastname;
            $a->delivery_mode = $section->delivery_mode;

            // $a->session = $session;
            // $a->department = $wdscourse->department;
            // $a->course_number = $wdscourse->course_number;
            // $a->shell_name = $split->shell_name;
            // $a->fullname = fullname($user);

            $stringkey = 'split_shortname';
            // $pattern = "WDS - {period_year} {period_type} {course_subject_abbreviation} {course_number} for {firstname} {lastname} {delivery_mode}";
        }


        // DALO - gotta look into this
        /* 
        $crosslist = wds_crosslist::get($ownerparams);
        if ($crosslist) {
            $a->year = $semester->year;
            $a->name = $semester->name;
            $a->session = $session;
            $a->shell_name = $crosslist->shell_name;
            $a->fullname = fullname($primary->user());

            $stringkey = 'crosslist_shortname';
        }

        $teamteach = wds_team_section::get(array('sectionid' => $section->id));
        if ($teamteach) {
            $a->year = $semester->year;
            $a->name = $semester->name;
            $a->session = $session;
            $a->shell_name = $teamteach->shell_name;

            $stringkey = 'team_request_shortname';
        }
        */
        if (isset($stringkey)) {
            $pattern = get_config('block_wdsprefs', $stringkey);

            $fullname = wds::format_string($pattern, $a);
            $shortname = wds::format_string($pattern, $a);
        }

        $course->fullname = $fullname.' - '.$section_num;
        $course->shortname = $shortname.' - '.$section_num;

        // Instructor overrides only on creation.
        /*
        if (empty($course->id)) {
            foreach ($creationsettings as $setting) {
                $key = str_replace('creation_', '', $setting->name);

                $course->$key = $setting->value;
            }
        }
        */

        return $course;
    }

    public static function wds_course_severed($course) {
        // This event only occurs when a Moodle course will no longer be supported.
        // Good news is that the section that caused this severage will still be link to the idnumber until the end of the
        // unenrollment process. Should there be no grades, no activities, and no resourceswe can safely assume that
        // this course is no longer used.

        $performdelete = (bool) get_config('block_wdsprefs', 'course_severed');
        // DALO TODO: Make this a setting.
        if (!$performdelete) {
            return true;
        }

        global $DB;

        $res = $DB->get_records('resource', array('course' => $course->id));

        $gradeitemsparams = array(
            'courseid' => $course->id,
            'itemtype' => 'course'
        );

        $ci = $DB->get_record('grade_items', $gradeitemsparams);

        $grades = function($ci) use ($DB) {
            if (empty($ci)) {
                return false;
            }

            $countparams = array('itemid' => $ci->id);
            $grades = $DB->count_records('grade_grades', $countparams);

            return !empty($grades);
        };

        if (empty($res) and !$grades($ci)) {
            delete_course($course, false);
            return true;
        }

        $sections = wds_section::from_course($course);

        if (empty($sections)) {
            return true;
        }

        $section = reset($sections);

        $primary = $section->primary();

        $byparams = array (
            'universal_id' => $primary->userid,
            'section_listing_id' => $section->id
        );

        if (wds_unwant::get($byparams)) {
            delete_course($course, false);
        }

        return true;
    }

    public static function wds_lsu_student_data_updated($user) {
        if (empty($user->user_keypadid)) {
            return blocks_wds_profile_field_helper::clear_field_data($user, 'user_keypadid');
        }

        return blocks_wds_profile_field_helper::process($user, 'user_keypadid');
    }

    public static function wds_azure_student_data_updated($user) {
        if (empty($user->user_keypadid)) {
            return blocks_wds_profile_field_helper::clear_field_data($user, 'user_keypadid');
        }

        return blocks_wds_profile_field_helper::process($user, 'user_keypadid');
    }

    // Accommodate the Generic XML provider.
    public static function wds_xml_student_data_updated($user) {
        // Todo: Refactor to actually use Event 2 rather than simply calling the handler directly.
        self::wds_lsu_student_data_updated($user);
    }

    public static function wds_azure_anonymous_updated($user) {
        if (empty($user->user_anonymous_number)) {
            return blocks_wds_profile_field_helper::clear_field_data($user, 'user_anonymous_number');
        }

        return blocks_wds_profile_field_helper::process($user, 'user_anonymous_number');
    }

    public static function wds_lsu_anonymous_updated($user) {
        if (empty($user->user_anonymous_number)) {
            return blocks_wds_profile_field_helper::clear_field_data($user, 'user_anonymous_number');
        }

        return blocks_wds_profile_field_helper::process($user, 'user_anonymous_number');
    }

    // Accommodate the Generic XML provider.
    public static function wds_xml_anonymous_updated($user) {
        mtrace(sprintf("xml_anon event triggered !"));
        // Todo: Refactor to actually use Event 2 rather than simply calling the handler directly.
        self::wds_lsu_anonymous_updated($user);
    }

    public static function wds_group_emptied($params) {
        return true;
    }
}
