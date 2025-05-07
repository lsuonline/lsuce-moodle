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

    /**
     * Creates a crosslisted course shell and assigns sections to it.
     *
     * @param @int $userid User ID creating the crosslist.
     * @param @string $periodid The academic period ID.
     * @param @array $sectionids Array of section IDs to be included in the crosslisted course.
     * @param @string $shellname Name for the crosslisted shell.
     * @return @int | @bool The new crosslist_id if successful, false on failure.
     */
    public static function create_crosslist_shell($userid, $periodid, $sectionids, $shellname) {
        global $DB, $CFG;

        // Require workdaystudent for course creation functionality.
        require_once($CFG->dirroot . '/enrol/workdaystudent/classes/workdaystudent.php');

        // Get user's universal_id.
        $user = $DB->get_record('user', ['id' => $userid], '*');

        // Set this for later.
        $universalid = $user->idnumber;

        // Get settings.
        $s = workdaystudent::get_settings();

        // Get the faculty preferences.
        $userprefs = self::get_faculty_preferences($userid);

        // Get the Moodle course defaults.
        $coursedefaults = get_config('moodlecourse');

        // Get period info.
        $period = self::get_period_from_periodid($periodid);

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Create crosslist record.
            $crosslist = new stdClass();
            $crosslist->userid = $userid;
            $crosslist->universal_id = $universalid;
            $crosslist->academic_period_id = $period->academic_period_id;
            $crosslist->shell_name = $shellname;
            $crosslist->status = 'pending';
            $crosslist->timecreated = time();
            $crosslist->timemodified = time();

            // Insert record first.
            $crosslistid = $DB->insert_record('block_wdsprefs_crosslists', $crosslist);

            if (!$crosslistid) {
                throw new Exception('Failed to create crosslist record');
            }

            // Get the first section to use for course info and category info.
            $firstsection = $DB->get_record('enrol_wds_sections', ['id' => reset($sectionids)]);
            if (!$firstsection) {
                throw new Exception('Failed to find section data');
            }

            // Build the SQL.
            $csql = "SELECT c.*
                FROM {enrol_wds_courses} c
                WHERE c.course_listing_id = :courselistingid";

            // Build out the parms.
            $parms = ['courselistingid' => $firstsection->course_listing_id];

            // Get the course info for the first section.
            $courseinfo = $DB->get_record_sql($csql, $parms);

            if (!$courseinfo) {
                throw new Exception('Failed to find course data');
            }

            // Set this for the course record and shortname.
            $timecreated = time();

            // Create course shell.
            $shortname = 'Crosslist-' . $period->period_year .
                     '-' . $period->period_type .
                     '-' . $timecreated .
                     '-' . $userid;

            // TODO: This is gross. figure some shit out.
            $fullname = 'Crosslisted: ' . $shellname;

            // Set course parameters.
            $course = new stdClass();
            $course->shortname = $shortname;
            $course->fullname = $fullname;
            $course->numsections = $coursedefaults->numsections;
            $course->summary = 'Crosslisted course shell containing sections from multiple courses';

            // Get the category based on subject of first course.
            $cat = self::get_subject_category($courseinfo->course_subject_abbreviation);

            // TODO: Build out this shit in settings.
            $course->category = get_config('block_wdsprefs', 'blueprint_category_forced') ?
                get_config('block_wdsprefs', 'blueprint_category') :
                $cat->id;

            // Course visibility.
            $course->visible = $s->visible;

            // Use user's preferred course format.
            $course->format = $userprefs->format;

            // Create course in Moodle
            $course = create_course($course);

            // Make sure it was created
            if (!$course->id) {
                throw new Exception('Failed to create course');
            }

            // Update crosslist record with moodle course id.
            $crosslist = new stdClass();
            $crosslist->id = $crosslistid;
            $crosslist->moodle_course_id = $course->id;
            $crosslist->status = 'created';
            $crosslist->timemodified = $timecreated;

            // Update the record.
            $DB->update_record('block_wdsprefs_crosslists', $crosslist);

            // Process each section.
            foreach ($sectionids as $sectionid) {
                // Get section details
                $section = $DB->get_record('enrol_wds_sections', ['id' => $sectionid]);

                if ($section) {
                    $crosslistsection = new stdClass();
                    $crosslistsection->crosslist_id = $crosslistid;
                    $crosslistsection->section_id = $sectionid;
                    $crosslistsection->section_listing_id = $section->section_listing_id;
                    $crosslistsection->status = 'pending';
                    $crosslistsection->timecreated = $timecreated;
                    $crosslistsection->timemodified = $timecreated;

                    // Save section record.
                    $DB->insert_record('block_wdsprefs_crosslist_sections', $crosslistsection);
                }
            }

            // Commit the transaction.
            $transaction->allow_commit();

        } catch (Exception $e) {
            // Rollback the transaction.
            $transaction->rollback($e);
            return false;
        }

        // Enroll user as teacher in the course.
        $teacherroleid = $s->primaryrole;

        if (!enrol_try_internal_enrol($course->id, $userid, $teacherroleid)) {
            throw new Exception('Failed to enroll creator as teacher');
        }

        // Enroll students from each section
        self::process_crosslist_enrollments($crosslistid);

        return $crosslistid;
    }

    /**
     * Gets subject category based on subject abbreviation.
     *
     * @param string $subjectabbrev The subject abbreviation
     * @return int Category ID or false if not found
     */
    public static function get_subject_category($subjectabbrev) {
        global $DB;

        // Get settings.
        $s = workdaystudent::get_settings();

        // Set the table.
        $table = 'course_categories';

        // Set the parms.
        $parms = [
            'parent' => $s->parentcat,
            'name' =>  $subjectabbrev
        ];

        // Get BP category.
        $cats = $DB->get_records($table, $parms);

        // In the weird event we have more than one, please grab the 1st one.
        $category = reset($cats);

        return $category;
    }

    /**
     * Processes student enrollments for a crosslisted course.
     *
     * @param int $crosslistid The crosslist ID
     * @return bool Success or failure
     */
    public static function process_crosslist_enrollments($crosslistid) {
        global $DB, $CFG;

        // Require workdaystudent for enrollment functionality.
        require_once($CFG->dirroot . '/enrol/workdaystudent/classes/workdaystudent.php');

        // Get the crosslist record.
        $crosslist = $DB->get_record('block_wdsprefs_crosslists', ['id' => $crosslistid]);
        if (!$crosslist || !$crosslist->moodle_course_id) {
            return false;
        }

        // Get all sections in this crosslist.
        $sections = $DB->get_records('block_wdsprefs_crosslist_sections', ['crosslist_id' => $crosslistid]);
        if (empty($sections)) {
            return false;
        }

        // Get the enrollment plugin.
        $plugin = enrol_get_plugin('workdaystudent');

        // Try to get existing instance.
        $instance = $DB->get_record('enrol',
            ['courseid' => $crosslist->moodle_course_id, 'enrol' => 'workdaystudent']);

        // If no instance exists, create a new one.
        if (!$instance) {
            $instance = workdaystudent::wds_create_enrollment_instance($crosslist->moodle_course_id);
        }

        // Set the students table.
        $stutable = 'enrol_wds_students';

        // Set the section student enrollment table.
        $stuenrtable = 'enrol_wds_student_enroll';

        // Set the section table.
        $stable = 'enrol_wds_sections';

        // Process each section.
        foreach ($sections as $clsection) {

            // Get the actual section.
            $section = $DB->get_record($stable, ['id' => $clsection->section_id]);

            if (!$section) {
                continue;
            }

            // Set this for later use.
            $originalcourseid = $section->moodle_status;

            // Assign the section to the new course shell id.
            $DB->set_field($stable, 'moodle_status', $crosslist->moodle_course_id,
                ['id' => $section->id]
            );

            // Get all student enrollments for this section.
            $studentenrolls = $DB->get_records($stuenrtable,
                ['section_listing_id' => $section->section_listing_id]
            );

            // Process each student enrollment.
            foreach ($studentenrolls as $studenroll) {
                // Get student record.
                $student = $DB->get_record($stutable,
                    ['universal_id' => $studenroll->universal_id]
                );

                if ($student && $student->userid) {

                    // Enroll student in crosslisted course.
                    $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
                    $plugin->enrol_user($instance,
                        $student->userid,
                        $studentroleid,
                        $studenroll->registered_date,
                        $studenroll->drop_date,
                        ENROL_USER_ACTIVE
                    );

                    // Update original enrollment status if needed.
                    if ($section->controls_grading == 1) {

                        /* TODO: DEAL with this shit somehow?

                        Mark the enrollment as crosslisted in wds tables.
                        $DB->set_field($stuenrtable, 'status', 'crosslisted',
                            ['id' => $studenroll->id]
                        );

                        */

                        // Unenroll from original course if it exists.
                        if ($section->moodle_status && is_numeric($section->moodle_status)) {

                            // Get the old enrolment plugin instance for the old course.
                            $oldinstance = $DB->get_record('enrol',
                                ['courseid' => $originalcourseid, 'enrol' => 'workdaystudent']
                            );

                            if ($oldinstance) {

                                // Get the enrolment manager.
                                $enrolmanager = enrol_get_plugin('workdaystudent');

                                // Unenroll the student.
                                $enrolmanager->unenrol_user($oldinstance, $student->userid);
                            }
                        }
                    }
                }
            }

            // Update teacher enrollments.
            self::process_crosslist_teacher_enrollments($crosslist->moodle_course_id, $section);

            // Update section's crosslist status.
            $DB->set_field('block_wdsprefs_crosslist_sections', 'status', 'enrolled',
                ['id' => $clsection->id]
            );
        }

        return true;
    }

    /**
     * Processes teacher enrollments for a section in a crosslisted course.
     *
     * @param int $courseid The crosslisted course ID
     * @param object $section The section object
     * @return bool Success or failure
     */
    public static function process_crosslist_teacher_enrollments($courseid, $section) {
        global $DB;

        // Get teacher enrollments for this section
        $teacherenrolls = $DB->get_records('enrol_wds_teacher_enroll',
            ['section_listing_id' => $section->section_listing_id]
        );

        if (empty($teacherenrolls)) {
            return false;
        }

        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        // Process each teacher enrollment
        foreach ($teacherenrolls as $teacherenroll) {

            // Get teacher record
            $teacher = $DB->get_record('enrol_wds_teachers',
                ['universal_id' => $teacherenroll->universal_id]
            );

            if ($teacher && $teacher->userid) {

                // Enroll teacher in crosslisted course
                enrol_try_internal_enrol($courseid, $teacher->userid, $teacherroleid);
            }
        }

        return true;
    }

    /**
     * Gets existing crosslisted shells for a user.
     *
     * @param int $userid The user ID
     * @return array Existing crosslist records
     */
    public static function get_user_crosslists($userid) {
        global $DB;

        // Build the SQL to get the user's crosslisted courses
        $sql = "SELECT c.*
            FROM {block_wdsprefs_crosslists} c
            WHERE c.userid = :userid
            ORDER BY c.timemodified DESC";

        // Set the parameters
        $params = ['userid' => $userid];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Gets sections assigned to a crosslisted shell.
     *
     * @param int $crosslistid The crosslist ID
     * @return array Array of section objects with additional data
     */
    public static function get_crosslist_sections($crosslistid) {
        global $DB;

        // Build the SQL to get detailed section information
        $sql = "SELECT cs.id, cs.crosslist_id, cs.section_id, cs.status,
                   s.section_number, s.section_listing_id,
                   c.course_subject_abbreviation, c.course_number,
                   p.period_year, p.period_type
            FROM {block_wdsprefs_crosslist_sections} cs
            JOIN {enrol_wds_sections} s ON s.id = cs.section_id
            JOIN {enrol_wds_courses} c ON c.course_listing_id = s.course_listing_id
            JOIN {enrol_wds_periods} p ON p.academic_period_id = s.academic_period_id
            WHERE cs.crosslist_id = :crosslistid
            ORDER BY c.course_subject_abbreviation, c.course_number, s.section_number";

        // Set the parameters
        $params = ['crosslistid' => $crosslistid];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Gets detailed information about a crosslisted shell.
     *
     * @param int $crosslistid The crosslist ID
     * @return object Crosslist record with additional data
     */
    public static function get_crosslist_info($crosslistid) {
        global $DB;

        // Build the SQL to get detailed crosslist information
        $sql = "SELECT c.*,
            p.period_year,
            p.period_type,
            p.academic_period,
            course.id as course_id,
            course.fullname,
            course.shortname
            FROM {block_wdsprefs_crosslists} c
            JOIN {enrol_wds_periods} p ON p.academic_period_id = c.academic_period_id
            LEFT JOIN {course} course ON course.id = c.moodle_course_id
            WHERE c.id = :crosslistid";

        // Set the parameters
        $params = ['crosslistid' => $crosslistid];

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Handles the submission of the crosslist form and creates the crosslisted shells.
     *
     * @param object $data Form data
     * @param string $period Period information
     * @param string $teacher Teacher information
     * @param int $shellcount Number of shells to create
     * @return array Array of results with shell information and assigned sections
     */
    public static function process_crosslist_form($data, $period, $teacher, $shellcount) {
        global $USER, $DB;

        // Get the period id.
        $periodid = $period->id;

        // Build the period name.
        $periodname = $period->period_year . ' ' . $period->period_type;

        // Prepare array to store results
        $results = [];

        // Process each shell's data from hidden fields
        for ($i = 1; $i <= $shellcount; $i++) {
            $fieldname = "shell_{$i}_data";
            $shellsections = [];
            $sectionids = [];

            if (!empty($data->$fieldname)) {
                // Decode JSON array of section IDs
                $sectionids = json_decode($data->$fieldname, true);

                // Skip if no sections assigned or decode failed
                if (!is_array($sectionids) || empty($sectionids)) {
                    continue;
                }

                // Create the shell name
                $shellname = "$periodname (Shell $i) for $teacher";

                // Create the crosslisted shell
                $crosslistid = self::create_crosslist_shell($USER->id, $periodid, $sectionids, $shellname);

                if ($crosslistid) {

                    // Get info about the sections.
                    $sections = [];

                    foreach ($sectionids as $sectionid) {

                        // Build outy the sql.
                        $ssql = "SELECT sec.section_number,
                                cou.course_subject_abbreviation,
                                cou.course_number
                         FROM {enrol_wds_sections} sec
                         JOIN {enrol_wds_courses} cou ON cou.course_listing_id = sec.course_listing_id
                         WHERE sec.id = :sectionid";

                         // Build the parms.
                         $parms = ['sectionid' => $sectionid];

                         // Get teh data.
                         $section = $DB->get_record_sql($ssql, $parms);

                        if ($section) {
                            $sections[] = $section->course_subject_abbreviation . ' ' .
                                      $section->course_number . ' ' .
                                      $section->section_number;
                        }
                    }

                    // Store in results.
                    $results[$shellname] = [
                        'crosslist_id' => $crosslistid,
                        'sections' => $sections
                    ];
                }
            }
        }

        return $results;
    }

























    /**
     * Gets courses taught by the instructor.
     *
     * @param @string $userid The user ID.
     * @return @array Courses taught by the instructor.
     */
    public static function get_instructor_courses($userid) {
        global $DB, $USER;

        // Get the user's idnumber.
        $user = $DB->get_record('user', ['id' => $userid], 'idnumber');

        // Set this for parms.
        $universalid = $user->idnumber;

        // Query to get unique courses (by course_definition_id) taught by this instructor.
        $sql = "SELECT DISTINCT c.course_definition_id,
                c.course_subject_abbreviation,
                c.course_number,
                c.course_abbreviated_title AS course_title
            FROM {enrol_wds_courses} c
            INNER JOIN {enrol_wds_sections} s
                ON s.course_listing_id = c.course_listing_id
            INNER JOIN {enrol_wds_teacher_enroll} te
                ON te.section_listing_id = s.section_listing_id
            INNER JOIN {enrol_wds_teachers} t
                ON te.universal_id = t.universal_id
            INNER JOIN {enrol_wds_periods} p
                ON p.academic_period_id = s.academic_period_id
            WHERE te.universal_id = :universalid
            AND p.end_date >= :currenttime
            ORDER BY c.course_subject_abbreviation, c.course_number";

        // Build out the parms.
        $parms = [
            'universalid' => $universalid,
            'currenttime' => time()
        ];

        return $DB->get_records_sql($sql, $parms);
    }

    /**
     * Gets existing blueprint shells for a user.
     *
     * @param @string $userid The user ID.
     * @return @array Existing blueprint shell records.
     */
    public static function get_user_blueprints($userid) {
        global $DB;

        // Build the SQL to get the user's BP courses.
        $sql = "SELECT b.*
            FROM {block_wdsprefs_blueprints} b
            WHERE b.userid = :userid
            ORDER BY b.timemodified DESC";

        // Set the parms.
        $parms = ['userid' => $userid];

        return $DB->get_records_sql($sql, $parms);
    }

    /**
     * Gets course information by course_definition_id.
     *
     * @param @string $cdid The course definition ID.
     * @return @object Course information.
     */
    public static function get_course_info_by_definition_id($cdid) {
        global $DB;

        $sql = "SELECT c.*, c.course_abbreviated_title AS course_title
            FROM {enrol_wds_courses} c
            WHERE c.course_definition_id = :course_definition_id
            LIMIT 1";

        $parms = ['course_definition_id' => $cdid];

        return $DB->get_record_sql($sql, $parms);
    }

    /**
     * Retrieves faculty preferences for a given user.
     *
     * If personal preferences are missing, return the global settings or fallbacks.
     *
     * @param int $userid The user ID.
     * @return stdClass An object containing the user's preferences.
     */
    public static function get_faculty_preferences($userid) {
        global $DB;

        // Validate user ID.
        if (!is_numeric($userid) || $userid <= 0) {
            var_dump($userid);
            throw new invalid_parameter_exception('Invalid user ID provided.');
        }

        // Retrieve user preferences related to 'wdspref_'.
        $sql = "SELECT * FROM {user_preferences}
            WHERE name LIKE 'wdspref_%'
                AND userid = ?";

        // Get the data.
        $preferences = $DB->get_records_sql($sql, [$userid]);

        // Get global settings.
        $s = workdaystudent::get_settings();

        // Define default values.
        $defaults = [
            'wdspref_createprior' => isset($s->createprior) ? (int) $s->createprior : 28,
            'wdspref_enrollprior' => isset($s->enrollprior) ? (int) $s->enrollprior : 14,
            'wdspref_courselimit' => isset($s->numberthreshold) ? (int) $s->numberthreshold : 8000,
            'wdspref_format' => 'topics'
        ];

        // Initialize user preferences with defaults.
        $userprefs = new stdClass();
        foreach ($defaults as $key => $value) {
            $shortkey = str_replace('wdspref_', '', $key);
            if ($shortkey == 'format') {
                $userprefs->$shortkey = $value;
            } else {
                $userprefs->$shortkey = (int) $value;
            }
        }

        // Override defaults with retrieved preferences.
        foreach ($preferences as $pref) {
            $shortkey = str_replace('wdspref_', '', $pref->name);
            if ($shortkey == 'format') {
                $userprefs->$shortkey = $pref->value;
            } else {
                $userprefs->$shortkey = (int) $pref->value;
            }
        }

        return $userprefs;
    }

    /**
     * Creates a blueprint shell for the instructor.
     *
     * @param @string $userid The user ID.
     * @param @string $cdid The course definition ID.
     * @return @bool Success or failure.
     */
    public static function create_blueprint_shell($userid, $cdid) {
        global $DB, $CFG;

        // Require workdaystudent for course creation functionality.
        require_once($CFG->dirroot . '/enrol/workdaystudent/classes/workdaystudent.php');

        // Get settings.
        $s = workdaystudent::get_settings();

        // Get the faculty preferences.
        $userprefs = self::get_faculty_preferences($userid);

        // Get the Moodle course defaults.
        $coursedefaults = get_config('moodlecourse');

        // Get user's universal_id.
        $user = $DB->get_record('user', ['id' => $userid], '*');

        // Set this...should I just include this in the parent call so I don't keep getting it?
        $universalid = $user->idnumber;

        // Get course info.
        $courseinfo = self::get_course_info_by_definition_id($cdid);

        if (!$courseinfo) {
            return false;
        }

        // Get the course category.
        $cat = self::get_subject_category($courseinfo->course_subject_abbreviation);

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Create blueprint record.
            $blueprint = new stdClass();
            $blueprint->userid = $userid;
            $blueprint->universal_id = $universalid;
            $blueprint->course_definition_id = $cdid;
            $blueprint->status = 'pending';
            $blueprint->timecreated = time();
            $blueprint->timemodified = time();

            // Insert record first.
            $blueprintid = $DB->insert_record('block_wdsprefs_blueprints', $blueprint);

            if (!$blueprintid) {
                throw new Exception('Failed to create blueprint record');
            }

            // Set this for the course record and shortname.
            $timecreated = time();

            // Create course shell.
            $shortname = 'Blueprint-' . $courseinfo->course_subject_abbreviation .
                     '-' . $courseinfo->course_number .
                     '-' . $timecreated .
                     '-' . $userid;

            $fullname = 'Blueprint: ' . $courseinfo->course_subject_abbreviation .
                    ' ' . $courseinfo->course_number .
                    ' - ' . $courseinfo->course_title .
                    ' for ' . $user->firstname .
                    ' ' . $user->lastname;

            // Set course parameters.
            $course = new stdClass();
            $course->shortname = $shortname;
            $course->fullname = $fullname;

            // TODO: Build out this shit in settings.
            $course->category = get_config('block_wdsprefs', 'blueprint_category_forced') ?
                get_config('block_wdsprefs', 'blueprint_category') :
                $cat->id;

            // Set the course visibility based on WDS settings.
            $course->visible = $s->visible;

            // Grab the course default number of srctions.
            $course->numsections = $coursedefaults->numsections;

            // Use user's preferred course format.
            $course->format = $userprefs->format;

            // Create course in Moodle.
            $course = create_course($course);

            // Make sure it was created.
            if (!$course->id) {
                throw new Exception('Failed to create course');
            }

            // Update blueprint record with moodle course id.
            $blueprint = new stdClass();
            $blueprint->id = $blueprintid;
            $blueprint->moodle_course_id = $course->id;
            $blueprint->status = 'created';
            $blueprint->timemodified = $timecreated;

            // Update the record.
            $DB->update_record('block_wdsprefs_blueprints', $blueprint);

            // Commit the transaction.
            $transaction->allow_commit();

        // If something broke.
        } catch (Exception $e) {

            // Rollback the transaction.
            $transaction->rollback($e);
            return false;
        }

        // Get the teacher role ID.
        $teacherroleid = $s->primaryrole;

        // Enroll user as teacher in the course.
        if (!enrol_try_internal_enrol($course->id, $userid, $teacherroleid)) {
            throw new Exception('Failed to enroll user as teacher');
        }

        return true;
    }

    /**
     * Gets the period object from the periodid.
     *
     * @param @string $periodid The periodid to fetch sections for.
     * @return @object The period object.
     */
    public static function get_period_from_periodid($periodid) {
        global $DB;

        // Set the table.
        $table = 'enrol_wds_periods';

        // Set the apid.
        $parms = ['id' => $periodid];

        // Get the period record.
        $period = $DB->get_record($table, $parms);

        return $period;
    }

    /**
     * Gets the period object from the period id.
     *
     * @param @string $periodid The academic period ID to fetch sections for.
     * @return @object The period object.
     */
    public static function get_period_from_id($periodid) {
        global $DB;

        // Set the table.
        $table = 'enrol_wds_periods';

        // Set the apid.
        $parms = ['academic_period_id' => $periodid];

        // Get the period record.
        $period = $DB->get_record($table, $parms);

        return $period;
    }

    /**
     * Gets future and current taught academic periods.
     *
     * @return @array Formatted array of periods.
     */
    public static function get_current_taught_periods(): array {
        global $USER, $DB;

        // Get the user's idnumber.
        $uid = $USER->idnumber;

        // Get settings to limit semesters to current ones.
        $s = workdaystudent::get_settings();

        // Set the semester range for getting future and recent semesters.
        $fsemrange = isset($s->brange) ? ($s->brange * 86400) : 0;

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
                AND p.end_date > UNIX_TIMESTAMP()
            GROUP BY p.academic_period_id
                HAVING COUNT(p.academic_period_id) > 1
            ORDER BY p.start_date ASC, p.period_type ASC";

        // Use named parameters for security.
        $parms = [
            'userid' => $uid,
            'fsemrange' => $fsemrange
        ];

        // Get the actual data.
        $records = $DB->get_records_sql($sql, $parms);

        // Build the periods array.
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

    /**
     * Gets sections taught by current user for a specific academic period.
     *
     * @param @string $periodid The academic period ID to fetch sections for.
     * @return @array Formatted array of sections grouped by course.
     */
    public static function get_sections_by_course_for_period(string $periodid): array {
       global $USER, $DB;

       // Get the user's idnumber.
       $uid = $USER->idnumber;

       // Build SQL query to get all relevant section information.
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

       // Build the formatteddata array.
       $formatteddata = [];

       // Loop through the records to buuild the formatted array.
       foreach ($records as $record) {

           // Create the course group key.
           $coursekey = "{$record->period_year} {$record->period_type} ";
           $coursekey .= "{$record->course_subject_abbreviation} ";
           $coursekey .= "{$record->course_number} for ";
           $coursekey .= "{$record->firstname} {$record->lastname}";

           // Create the section value.
           $sectionvalue = "{$record->course_subject_abbreviation} ";
           $sectionvalue .= "{$record->course_number} {$record->section_number}";

           // Initialize the array for this course if it doesn't exist.
           if (!isset($formatteddata[$coursekey])) {
               $formatteddata[$coursekey] = [];
           }

           // Add this section to the course group.
           $formatteddata[$coursekey][$record->sectionid] = $sectionvalue;
       }

       return $formatteddata;
    }

    /**
     * Gets course/sections for the requested user.
     *
     * @param @string $userid The user id.
     * @return @array Array of sections per academic period.
     */
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

    /**
     * Determines if an academic period is an ONLINE period.
     *
     * @param @string $period The academic period ID to fetch idata for.
     * @return @string ' (Online) or an empty string depending if it's online or not.
     */
    public static function get_period_online(string $period): string {

        // If the period contains the term "online", desired string, otherwise empty.
        $online = stripos($period, 'Online') !== false ? ' (Online)' : '';

        // Return it.
        return $online;
    }

    /**
     * Gets faculty enrollment for the requested user and section.
     *
     * @param @string $userid The user id.
     * @param @string $sectionid The section id.
     * @return @object An object with the required info for building out a course.
     */
    public static function get_faculty_enrollment($userid, $sectionid) {
        global $DB;

        // Set the parms.
        $parms = ['sectionid' => $sectionid, 'userid' => $userid];

        // Build the sql.
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

        // Actually get the data.
        $enrollment = $DB->get_record_sql($gsql, $parms);

        return $enrollment;
    }

    /**
     * Updates the teacher enrollment record.
     *
     * @param @object The $enrollment object.
     * @return @bool Depends on if the update was successful or not.
     */
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

    /**
     * Updates the student enrollment record.
     *
     * @param @object The $enrollment object.
     * @return @bool Depends on if the update was successful or not.
     */
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

    /**
     * Master function for updating records when a course is unwanted.
     *
     * @param @string The user id.
     * @param @string The section id.
     * @return @bool
     */
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

    /**
     * Determines if the user is a teacher or not.
     *
     * @param @object The $user object.
     * @return @bool
     */
    public static function get_instructor($user) : bool {
        global $DB;

        // Get a bool if they exist in this table or not.
        $instructor = $DB->record_exists('enrol_wds_teachers', ['userid' => $user->id]);

        // Return the value.
        return $instructor;
    }

    /**
     * Determines if the user is a student or not.
     *
     * @param @object The $user object.
     * @return @bool
     */
    public static function get_student($user) : bool {
        global $DB;

        // Get a bool if they exist in this table or not.
        $student = $DB->record_exists('enrol_wds_students', ['userid' => $user->id]);

        // Return the value.
        return $student;
    }

}
