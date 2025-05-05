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

require_once("$CFG->dirroot/enrol/workdaystudent/classes/workdaystudent.php");

class wdsprefs {

    public static function get_current_taught_periods(): array {
        global $USER, $DB;

        $uid = $USER->idnumber;
        $uid = '00011898';
        $s = workdaystudent::get_settings();

        // Set the semester range for getting future and recent semesters.
        $fsemrange = isset($s->brange) ? ($s->brange * 86400) : 0;
        $psemrange = isset($s->erange) ? ($s->erange * 86400) : 0;

        // Build the SQL.
        $sql = "SELECT p.academic_period_id,
                p.period_type,
                p.period_year,
                p.academic_period
            FROM {enrol_wds_periods} p
                INNER JOIN {enrol_wds_sections} sec
                    ON sec.academic_period_id = p.academic_period_id
                INNER JOIN {enrol_wds_teacher_enroll} tenr
                    ON tenr.section_listing_id = sec.section_listing_id
            WHERE tenr.universal_id = :userid
              AND p.start_date < UNIX_TIMESTAMP() + :fsemrange
              AND p.end_date > UNIX_TIMESTAMP() - :psemrange
            GROUP BY p.academic_period_id
            ORDER BY p.start_date ASC, p.period_type ASC";

        // Use named parameters for security.
        $parms = [
            'userid' => $uid,
            'fsemrange' => $fsemrange,
            'psemrange' => $psemrange
        ];

        // Get the actual data.
        $records = $DB->get_records_sql($sql, $parms);
        $periods = [];

        // Loop through the data.
        foreach ($records as $record) {

            // Determine if this is an online period or not.
            $online = self::get_period_online($record->academic_period);

            // Gett eh academic period id.
            $pid = $record->academic_period_id;

            // Get the period name matching the course designation.
            $pname = $record->period_year . ' ' . $record->period_type . $online;

            // Add the key/value pair to the array.
            $periods[$pid] = $pname;
        }

        return $periods;
    }

    public static function get_sections_by_course_for_period(string $periodid): array {
        global $USER, $DB;

        $uid = $USER->idnumber;
        $uid = '00011898';

        $sql = "SELECT sec.id AS sectionid,
            p.period_year,
            p.period_type,
            c.course_subject_abbreviation,
            c.course_number,
            sec.section_number,
            sec.section_listing_id,
            COALESCE(t.preferred_firstname, t.firstname) AS firstname,
            COALESCE(t.preferred_lastname, t.lastname) AS lastname,
            sec.delivery_mode
            FROM {enrol_wds_periods} p
                INNER JOIN {enrol_wds_sections} sec
                    ON sec.academic_period_id = p.academic_period_id
                INNER JOIN {enrol_wds_courses} c
                    ON c.course_listing_id = sec.course_listing_id
                INNER JOIN {enrol_wds_teacher_enroll} tenr
                    ON tenr.section_listing_id = sec.section_listing_id
                INNER JOIN {enrol_wds_teachers} t
                    ON t.universal_id = tenr.universal_id
            WHERE tenr.universal_id = :userid
              AND sec.academic_period_id = :periodid
            GROUP BY sec.id
            ORDER BY sec.section_listing_id ASC";

        // Use named parameters for security.
        $parms = [
            'userid' => $uid,
            'periodid' => $periodid
        ];

        // Get the actual data.
        $records = $DB->get_records_sql($sql, $parms);

echo"<pre>"; 
var_dump($records);
echo"</pre>"; 
die();


        return match ($periodid) {
            'LSUAM_ONLINE_SUMMER_1_2025' => [
                '2025 Summer 1 ENGL 1001 for Robert Russo (Online)' => [
                    101 => 'ENG 1001 001-LEC-SM',
                    102 => 'ENG 1001 002-LEC-SM',
                ],
                '2025 Summer 1 MATH 1021 for Robert Russo (Online)' => [
                    201 => 'MATH 1021 001-LEC-SM',
                    202 => 'MATH 1021 002-LEC-SM',
                    203 => 'MATH 1021 003-LEC-SM',
                ],
                '2025 Summer 1 BIOL 1201 for Robert Russo (Online)' => [
                    301 => 'BIOL 1201 001-LEC-SM',
                    302 => 'BIOL 1201 002-LEC-SM',
                    303 => 'BIOL 1201 003-LEC-SM',
                    304 => 'BIOL 1201 004-LEC-SM',
                    305 => 'BIOL 1201 005-LEC-SM',
                    306 => 'BIOL 1201 006-LEC-SM',
                ]
            ],
            'LSUAM_SUMMER_2025' => [
                '2025 Summer ENGL 2025 for Robert Russo' => [
                    401 => 'ENG 2025 001-LEC-SM',
                ],
                '2025 Summer ENGL 3050 for Robert Russo' => [
                    501 => 'MATH 3050 001-LEC-SM',
                    502 => 'MATH 3050 002-LEC-SM',
                    503 => 'MATH 3050 003-LEC-SM',
                ],
                '2025 Summer ENGL 4150 for Robert Russo' => [
                    601 => 'BIOL 4150 001-LEC-SM',
                    602 => 'BIOL 4150 002-LEC-SM',
                ]
            ],
            'LSUAM_SUMMER_1_2025' => [
                '2025 Summer 1 ENGL 1001 for Robert Russo' => [
                    701 => 'ENG 1001 001-LEC-SM',
                    702 => 'ENG 1001 002-LEC-SM',
                ],
                '2025 Summer 1 HNRS 4101 for Robert Russo' => [
                    801 => 'HNRS 4101 001-LEC-SM',
                    802 => 'HNRS 4101 002-LEC-SM',
                    803 => 'HNRS 4101 003-LEC-SM',
                ],
                '2025 Summer 1 HNRS 4101G for Robert Russo' => [
                    903 => 'HNRS 4101G 003-LEC-SM',
                    904 => 'HNRS 4101G 004-LEC-SM',
                    905 => 'HNRS 4101G 005-LEC-SM',
                    906 => 'HNRS 4101G 006-LEC-SM',
                ]
            ],
            'LSUAM_SUMMER_2_2025' => [
                '2025 Summer 2 ENGL 1001 for Robert Russo' => [
                    121 => 'ENG 1001 001-LEC-SM',
                    122 => 'ENG 1001 002-LEC-SM',
                ],
                '2025 Summer 2 ENGL 1051 for Robert Russo' => [
                    221 => 'ENGL 1051 001-LEC-SM',
                    222 => 'ENGL 1051 002-LEC-SM',
                    223 => 'ENGL 1051 003-LEC-SM',
                ],
                '2025 Summer 2 ENGL 4101 for Robert Russo' => [
                    322 => 'ENGL 4101 002-LEC-SM',
                    324 => 'ENGL 4101 004-LEC-SM',
                    326 => 'ENGL 4101 006-LEC-SM',
                ]
            ],
            default => [], // Default case returns an empty array
        };
    }

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
