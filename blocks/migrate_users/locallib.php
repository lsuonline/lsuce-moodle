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
 * @package    block_migrate_users
 * @copyright  2019 onwards Louisiana State University
 * @copyright  2019 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class migrate {

    /*
     * Updates the user_enrollments for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_user_enrollments($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {user_enrolments} SET userid = :userto WHERE userid = :userfrom AND enrolid IN (SELECT id FROM {enrol} WHERE courseid = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the role enrollment for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_role_enrollments($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $dbfamily = $DB->get_dbfamily();
            if ($dbfamily == 'mssql') {
                $sql = 'UPDATE mdl_role_assignments
                            SET mdl_role_assignments.userid = :userto
                        FROM mdl_role_assignments INNER JOIN mdl_context ON mdl_role_assignments.contextid = mdl_context.id
                            INNER JOIN mdl_role ON mdl_role_assignments.roleid = mdl_role.id
                        WHERE mdl_role.shortname = \'student\'
                            AND mdl_context.instanceid = :courseid
                            AND mdl_role_assignments.userid = :userfrom
                            AND mdl_context.contextlevel = 50';
            } else if ($dbfamily == 'postgres') {
                $sql = 'UPDATE {role_assignments}
                            SET {role_assignments}.userid = :userto
                        FROM {role_assignments} INNER JOIN {context} ON {role_assignments}.contextid = {context}.id
                            INNER JOIN {role} ON {role_assignments}.roleid = {role}.id
                        WHERE {role}.shortname = "student"
                            AND {context}.instanceid = :courseid
                            AND {role_assignments}.userid = :userfrom
                            AND {context}.contextlevel = "50"';
            } else {
                $sql = 'UPDATE {role_assignments}
                            INNER JOIN {context} ON {role_assignments}.contextid = {context}.id
                            INNER JOIN {role} ON {role_assignments}.roleid = {role}.id
                            SET {role_assignments}.userid = :userto
                        WHERE {role}.shortname = "student"
                            AND {context}.instanceid = :courseid
                            AND {role_assignments}.userid = :userfrom
                            AND {context}.contextlevel = "50"';
            }
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }


    /*
     * Updates the group membership for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_groups_membership($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $dbfamily = $DB->get_dbfamily();
            if ($dbfamily == 'postgres' or $dbfamily == 'mssql') {
                $sql = 'UPDATE {groups_members}
                            SET {groups_members}.userid = :userto
                        FROM {groups_members} INNER JOIN {groups} ON {groups_members}.groupid = {groups}.id
                        WHERE {groups}.courseid = :courseid
                            AND {groups_members}.userid = :userfrom';
            } else {
                $sql = 'UPDATE {groups_members}
                            INNER JOIN {groups} ON {groups_members}.groupid = {groups}.id
                            SET {groups_members}.userid = :userto
                        WHERE {groups}.courseid = :courseid
                            AND {groups_members}.userid = :userfrom';
            }
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the log data for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_logs($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {log} SET userid = :userto WHERE course = :courseid AND userid = :userfrom';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the standard_log for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_standard_logs($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {logstore_standard_log} SET userid = :userto WHERE courseid = :courseid AND userid = :userfrom';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the event data for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_events($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {event} SET userid = :userto WHERE courseid = :courseid AND userid = :userfrom';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the forum post history for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_posts($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {post} SET userid = :userto WHERE courseid = :courseid AND userid = :userfrom';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the course modules completion for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_course_modules_completions($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {course_modules_completion} SET userid = :userto 
                    WHERE coursemoduleid IN (
                        SELECT id FROM {course_modules} 
                        WHERE course= :courseid and userid=:userfrom)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the course completions for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_course_completions($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {course_completions} SET userid = :userto WHERE course = :courseid AND userid = :userfrom';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the course completion criteria for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_course_completion_criteria($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {course_completion_crit_compl} SET userid = :userto WHERE course = :courseid AND userid = :userfrom';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the grades for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_grades($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {grade_grades} SET userid = :userto WHERE userid = :userfrom and itemid IN (SELECT id FROM {grade_items} WHERE courseid = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the grades history for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_grades_history($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {grade_grades_history} SET userid = :userto WHERE userid = :userfrom and itemid IN (SELECT id FROM {grade_items} WHERE courseid = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the assignment grades for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_assign_grades($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {assign_grades} SET userid = :userto WHERE userid = :userfrom AND assignment IN (SELECT id FROM {assign} WHERE course = :courseid)';                    
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the assignment submissions for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_assign_submissions($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {assign_submission} SET userid = :userto WHERE userid = :userfrom AND assignment IN (SELECT id FROM {assign} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the assignment user flags for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_assign_user_flags($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {assign_user_flags} SET userid = :userto WHERE userid = :userfrom AND assignment IN (SELECT id FROM {assign} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the assignment user mapping for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_assign_user_mapping($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {assign_user_mapping} SET userid = :userto WHERE userid = :userfrom AND assignment IN (SELECT id FROM {assign} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the lesson attempts for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_lesson_attempts($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {lesson_attempts} SET userid = :userto WHERE userid = :userfrom AND lessonid IN (SELECT id FROM {lesson} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the lesson grades for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_lesson_grades($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {lesson_grades} SET userid = :userto WHERE userid = :userfrom AND lessonid IN (SELECT id FROM {lesson} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the quiz attempts for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_quiz_attempts($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {quiz_attempts} SET userid = :userto WHERE userid = :userfrom AND quiz IN (SELECT id FROM {quiz} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the quiz grades for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_quiz_grades($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {quiz_grades} SET userid = :userto WHERE userid = :userfrom AND quiz IN (SELECT id FROM {quiz} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the scorm tracking data for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_scorm_scoes($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {scorm_scoes_track} SET userid = :userto WHERE userid = :userfrom AND scormid IN (SELECT id FROM {scorm} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /*
     * Updates the choice answers for the specified users / course
     *
     * @return true
     *
     */
    public static function handle_choice_answers($userfrom, $userto, $courseid) {
        global $DB;
        // Check if the user can do this.
        if (!self::can_use()) {
            return get_string('securityviolation', 'block_migrate_users');
        } else {
            $sql = 'UPDATE {choice_answers} SET userid = :userto WHERE userid = :userfrom AND choiceid IN (SELECT id FROM {choice} WHERE course = :courseid)';
            return $DB->execute($sql, array('userfrom' => self::get_userid($userfrom), 'userto' => self::get_userid($userto), 'courseid' => $courseid));
        }
    }

    /**
     * Returns the userid for the username in question.
     *
     * @return int
     */
    public static function get_userid($username) {
        global $DB;
        $user = $DB->get_record('user', array('username' => $username));
        $userid = $user->id;
        return $userid;
    }

    /**
     * Returns the user object for the username in question.
     *
     * @return object
     */
    public static function get_user($username) {
        global $DB;
        $user = $DB->get_record('user', array('username' => $username));
        return $user;
    }
    /**
     * Returns if a user can use the tool or not.
     *
     * @return bool
     */
    public static function can_use() {
        global $CFG, $USER;
        $allowed_users = array();
        if (!isset($CFG->block_migrate_users_allowed)) {
            return true;
        }
        $allowed_users = array_map("trim",explode(',', $CFG->block_migrate_users_allowed));
        if (count($allowed_users) == 0) {
            return true;
        }
        $allowed = is_siteadmin() && in_array($USER->username, $allowed_users);
        return $allowed;
    }
}
