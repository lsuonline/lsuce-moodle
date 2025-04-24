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
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class wdsprefs {

    public static function get_courses($userid) {
        global $DB;

        // Define the SQL.
        $ssql = "SELECT sec.id,
                per.period_year,
                per.period_type,
                per.academic_period_id,
                per.academic_period,
                sec.course_subject_abbreviation,
                cou.course_number,
                sec.section_listing_id,
                tea.userid,
                sec.section_number,
                COALESCE(c.id, 'pending') AS moodle_courseid
            FROM {enrol_wds_sections} sec
                INNER JOIN {enrol_wds_courses} cou
                    ON cou.course_definition_id = sec.course_definition_id
                INNER JOIN {enrol_wds_periods} per
                    ON per.academic_period_id = sec.academic_period_id
                INNER JOIN {enrol_wds_teacher_enroll} tenr
                    ON sec.section_listing_id = tenr.section_listing_id
                INNER JOIN {enrol_wds_teachers} tea
                    ON tea.universal_id = tenr.universal_id
                LEFT JOIN {course} c ON c.id = sec.moodle_status
            WHERE per.end_date > UNIX_TIMESTAMP()
                AND sec.controls_grading = 1
                AND tenr.role = 'primary'
                AND tea.userid = :userid
            ORDER BY per.start_date ASC,
                per.end_date ASC,
                sec.course_subject_abbreviation ASC,
                cou.course_number ASC,
                sec.section_number ASC";

        // Fetch all sections for the user on this page.
        $sections = $DB->get_records_sql($ssql, ['userid' => $userid]);

        // Group sections by academic_period_id.
        $gsections = [];
        foreach ($sections as $section) {
            $gsections[$section->academic_period_id][] = $section;
        }

        // Return them.
        return $gsections;
    }

    public static function get_period_online(string $period): string {

        // If the period contains the term "online", desired string, otherwise empty.
        $online = stripos($period, 'Online') !== false ? ' (Online)' : '';

        // Return it.
        return $online;
    }



    public static function get_faculty_enrollment($userid, $sectionid) {
        global $DB;

        $parms = ['sectionid' => $sectionid, 'userid' => $userid];

        $gsql = "SELECT tenr.id AS enrollment_id,
            sec.id AS sectionid,
            sec.section_listing_id,
            sec.academic_period_id AS periodid,
            c.id AS courseid,
            u.id AS userid,
            tenr.universal_id,
            cou.course_subject_abbreviation AS department,
            cou.course_number,
            sec.section_number,
            CONCAT(
                cou.course_subject_abbreviation, ' ',
                cou.course_number, ' ',
                sec.section_number
            ) AS groupname,
            tenr.role,
            tenr.prevrole,
            tenr.status AS moodle_enrollment_status,
            tenr.prevstatus AS moodle_prev_status
            FROM {enrol_wds_sections} sec
                INNER JOIN {enrol_wds_courses} cou
                    ON cou.course_listing_id = sec.course_listing_id
                INNER JOIN {enrol_wds_teacher_enroll} tenr
                    ON sec.section_listing_id = tenr.section_listing_id
                INNER JOIN {enrol_wds_teachers} tea
                    ON tea.universal_id = tenr.universal_id
                INNER JOIN {user} u
                    ON u.id = tea.userid
                    AND u.idnumber = tea.universal_id
                LEFT JOIN {course} c
                    ON c.id = sec.moodle_status
                    AND c.idnumber = sec.idnumber
                    AND sec.idnumber IS NOT NULL
                    AND sec.moodle_status != 'pending'
            WHERE tenr.role = 'primary'
                AND sec.id = :sectionid
                AND u.id = :userid
            ORDER BY c.id ASC,
                tenr.id ASC";

        $enrollment = $DB->get_record_sql($gsql, $parms);

        return $enrollment;
    }

    public static function update_teacher_enroll_records($enrollment) {
        global $DB;

        // Set the table.
        $tenrtable = 'enrol_wds_teacher_enroll';

        // Build out the updated array.
        $tenrrecord = [
            'id' => $enrollment->enrollment_id,
            'status' => 'unenroll',
            'prevstatus' => $enrollment->moodle_enrollment_status
        ];

        // Try/catch this.
        try {

            // Update the data.
            $DB->update_record($tenrtable, $tenrrecord);

            return true;
        } catch (dml_exception $e) {

            // Log the failure.
            mtrace('Teacher enrollment IDB update failed: ' . $e->getMessage());

            return false;
        }
    }

    public static function update_student_enroll_records($enrollment) {
        global $DB;

        // Set the parms.
        $parms = [
            'status' => 'unenroll',
            'slid' => $enrollment->section_listing_id
        ];

        // Build the SQL.
        $sql = "UPDATE {enrol_wds_student_enroll} stuenr
            SET stuenr.status = :status
            WHERE stuenr.section_listing_id = :slid";

        // Try/catch this.
        try {
            // Update the records.
            $rows = $DB->execute($sql, $parms);

            // Return true if we updated anything, false if not.
            return ($rows > 0);

        } catch (dml_exception $e) {

            // Log the failure.
            mtrace('Student enrollment IDB update failed: ' . $e->getMessage());

            return false;
        }
    }

    public static function update_faculty_enrollment($userid, $sectionid) {
        global $CFG;

        // Workdaystudent enrollment stuff.
        require_once($CFG->dirroot . '/enrol/workdaystudent/classes/workdaystudent.php');

        // Get the enrollment record for the teacher.
        $enrollment = self::get_faculty_enrollment($userid, $sectionid);

        // Update the teacher record in the idb.
        $tenrupdate = self::update_teacher_enroll_records($enrollment);

        // Update the student records for this sectionid in the idb.
        $stuenrupdate = self::update_student_enroll_records($enrollment);

        // Overwrite this to process enrollment in realtime in the course.
        $enrollment->moodle_enrollment_status = 'unenroll';

        // Check to see if we actually have a course id.
        if (is_null($enrollment->courseid)) {

            // We don't so we can leave it here.
            return true;

        // We do have a course id.
        } else {

            // Actually unenroll the teachers/students.
            $unenrollme = enrol_workdaystudent::wds_bulk_faculty_enrollments([$enrollment]);
        }

        return true;
    }

    public static function get_instructor($user) : bool {
        global $DB;

        // Get a bool if they exist in this table or not.
        $instructor = $DB->record_exists('enrol_wds_teachers', ['userid' => $user->id]);

        // Return the value.
        return $instructor;
    }

    public static function get_student($user) : bool {
        global $DB;

        // Get a bool if they exist in this table or not.
        $student = $DB->record_exists('enrol_wds_students', ['userid' => $user->id]);

        // Return the value.
        return $student;
    }

}
