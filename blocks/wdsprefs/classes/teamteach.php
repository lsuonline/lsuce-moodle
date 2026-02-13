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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/wdsprefs/classes/wdsprefs.php');
require_once($CFG->dirroot . '/enrol/workdaystudent/classes/workdaystudent.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_wdsprefs_teamteach {

    /**
     * Search for teachers by name or email.
     *
     * @param string $query
     * @param string $academic_period_id
     * @return array
     */
    public static function search_teachers(string $query, string $academic_period_id = ''): array {
        global $DB, $USER;

        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        // Avoid self-search.
        $excludeuserid = $USER->id;

        $joins = "";
        $where_period = "";
        $params = [
            'excludeuserid' => $excludeuserid,
            'q1' => '%' . $query . '%',
            'q2' => '%' . $query . '%',
            'q3' => '%' . $query . '%',
            'q4' => '%' . $query . '%',
            'q5' => '%' . $query . '%',
            'q6' => '%' . $query . '%'
        ];

        if (!empty($academic_period_id)) {
            $joins = " JOIN {enrol_wds_teacher_enroll} te ON t.universal_id = te.universal_id
                       JOIN {enrol_wds_sections} s ON te.section_listing_id = s.section_listing_id ";
            $where_period = " AND s.academic_period_id = :period_id ";
            $params['period_id'] = $academic_period_id;
        }

        // Use parameterized query for safety.
//TODO: DEAL WITH LIMIT.
        $sql = "SELECT DISTINCT t.userid, t.universal_id,
                       COALESCE(t.preferred_firstname, t.firstname) as firstname,
                       COALESCE(t.preferred_lastname, t.lastname) as lastname,
                       u.email
                FROM {enrol_wds_teachers} t
                JOIN {user} u ON t.userid = u.id
                $joins
                WHERE t.userid != :excludeuserid
                  $where_period
                  AND (
                      t.firstname LIKE :q1 OR
                      t.lastname LIKE :q2 OR
                      t.preferred_firstname LIKE :q3 OR
                      t.preferred_lastname LIKE :q4 OR
                      u.email LIKE :q5 OR
                      CONCAT(COALESCE(t.preferred_firstname, t.firstname), ' ', COALESCE(t.preferred_lastname, t.lastname)) LIKE :q6
                  )
                LIMIT 20";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get sections for a teacher in a specific period.
     *
     * @param int $teacher_userid
     * @param string $academic_period_id
     * @return array
     */
    public static function get_teacher_sections(int $teacher_userid, string $academic_period_id): array {
        global $DB;

        // We need the universal_id for the teacher.
        $teacher = $DB->get_record('enrol_wds_teachers', ['userid' => $teacher_userid]);
        if (!$teacher) {
            return [];
        }

        $sql = "SELECT s.id, s.section_number,
                       c.course_subject_abbreviation, c.course_number,
                       s.moodle_status, s.idnumber
                FROM {enrol_wds_sections} s
                JOIN {enrol_wds_courses} c ON s.course_listing_id = c.course_listing_id
                JOIN {enrol_wds_teacher_enroll} te ON s.section_listing_id = te.section_listing_id
                WHERE te.universal_id = :universal_id
                  AND s.academic_period_id = :period_id
                  AND te.role = 'Primary'
                  AND s.controls_grading = 1
                ORDER BY c.course_subject_abbreviation, c.course_number, s.section_number";

        $params = [
            'universal_id' => $teacher->universal_id,
            'period_id' => $academic_period_id
        ];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Create a new team teach request.
     *
     * @param int $requester_userid
     * @param int $target_course_id
     * @param int $requested_userid
     * @param array $requested_section_ids
     * @return int|bool Request ID or false
     */
    public static function create_request(int $requester_userid, int $target_course_id, int $requested_userid, array $requested_section_ids) {
        global $DB;

        if (empty($requested_section_ids)) {
            return false;
        }

        // Generate token.
        $token = bin2hex(random_bytes(32));

        // Expiry.
        $hours = get_config('block_wdsprefs', 'teamteach_expiry_hours');
        if (empty($hours)) {
            $hours = 48;
        }
        $expirytime = time() + ($hours * 3600);

        $record = new stdClass();
        $record->requester_userid = $requester_userid;
        $record->target_course_id = $target_course_id;
        $record->requested_userid = $requested_userid;
        $record->requested_section_ids = json_encode(array_values($requested_section_ids));
        $record->token = $token;
        $record->status = 'pending';
        $record->timecreated = time();
        $record->timemodified = time();
        $record->expirytime = $expirytime;

        $id = $DB->insert_record('block_wdsprefs_teamteach', $record);

        if ($id) {
            self::send_request_email($id);
        }

        return $id;
    }

    /**
     * Send the invitation email.
     *
     * @param int $requestid
     * @return bool
     */
    public static function send_request_email(int $requestid): bool {
        global $DB, $CFG;

        $request = $DB->get_record('block_wdsprefs_teamteach', ['id' => $requestid]);
        if (!$request) {
            return false;
        }

        $requester = $DB->get_record('user', ['id' => $request->requester_userid]);
        $target_user = $DB->get_record('user', ['id' => $request->requested_userid]);
        $course = $DB->get_record('course', ['id' => $request->target_course_id]);

        if (!$requester || !$target_user || !$course) {
            return false;
        }

        // Get section details for the email.
        $section_ids = json_decode($request->requested_section_ids);
        $sections_str = '';
        if (!empty($section_ids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($section_ids);
            $sql = "SELECT s.section_number, c.course_subject_abbreviation, c.course_number
                    FROM {enrol_wds_sections} s
                    JOIN {enrol_wds_courses} c ON s.course_listing_id = c.course_listing_id
                    WHERE s.id $insql";
            $sections = $DB->get_records_sql($sql, $inparams);

            $parts = [];
            foreach ($sections as $s) {
                $parts[] = $s->course_subject_abbreviation . ' ' . $s->course_number . ' ' . $s->section_number;
            }
            $sections_str = implode(', ', $parts);
        }

        $subject = get_config('block_wdsprefs', 'teamteach_email_subject');
        if (empty($subject)) {
            $subject = get_string('wdsprefs:teamteach_email_subject_default', 'block_wdsprefs', ['requester' => fullname($requester)]);
        } else {

             // Replace basic placeholders in subject if any.
             $subject = str_replace('{requester}', fullname($requester), $subject);
        }

        $body = get_config('block_wdsprefs', 'teamteach_email_body');
        if (empty($body)) {
             $body = get_string('wdsprefs:teamteach_email_body_default', 'block_wdsprefs');
        }

        $link = new moodle_url('/blocks/wdsprefs/teamteach_action.php', ['token' => $request->token]);
        $expiry_hours = get_config('block_wdsprefs', 'teamteach_expiry_hours');

        $replacements = [
            '{requester}' => fullname($requester),
            '{course}' => $course->fullname,
            '{sections}' => $sections_str,
            '{link}' => $link->out(false),
            '{expiry}' => $expiry_hours
        ];

        foreach ($replacements as $key => $value) {
            $body = str_replace($key, $value, $body);
        }

        $from = $requester;

        // Let's use the requester as 'from' so they can reply.
        return email_to_user($target_user, $requester, $subject, text_to_html($body), $body);
    }

    /**
     * Get request by token.
     *
     * @param string $token
     * @return stdClass|bool
     */
    public static function get_request_by_token(string $token) {
        global $DB;
        return $DB->get_record('block_wdsprefs_teamteach', ['token' => $token]);
    }

    /**
     * Decline a request.
     *
     * @param int $requestid
     * @return bool
     */
    public static function decline_request(int $requestid): bool {
        global $DB;

        $request = $DB->get_record('block_wdsprefs_teamteach', ['id' => $requestid]);
        if (!$request || $request->status != 'pending') {
            return false;
        }

        return $DB->delete_records('block_wdsprefs_teamteach', ['id' => $requestid]);
    }

    /**
     * Approve a request and process enrollments.
     *
     * @param int $requestid
     * @return bool
     */
    public static function approve_request(int $requestid): bool {
        global $DB;

        $request = $DB->get_record('block_wdsprefs_teamteach', ['id' => $requestid]);
        if (!$request || $request->status != 'pending') {
            return false;
        }

        if ($request->expirytime < time()) {
             $request->status = 'expired';
             $DB->update_record('block_wdsprefs_teamteach', $request);
             return false;
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $section_ids = json_decode($request->requested_section_ids);
            if (empty($section_ids)) {
                throw new Exception('No sections in request');
            }

            $target_course = $DB->get_record('course', ['id' => $request->target_course_id]);
            if (!$target_course) {
                throw new Exception('Target course not found');
            }

            // Get settings for roles.
            $s = workdaystudent::get_settings();
            $plugin = enrol_get_plugin('workdaystudent');
            $instance = $DB->get_record('enrol', ['courseid' => $target_course->id, 'enrol' => 'workdaystudent']);
            if (!$instance) {
                $instance = workdaystudent::wds_create_enrollment_instance($target_course->id);
            }

            foreach ($section_ids as $section_id) {
                $section = $DB->get_record('enrol_wds_sections', ['id' => $section_id]);
                if (!$section) {
                    continue;
                }

                // Get course info for group naming.
                $course_info = $DB->get_record('enrol_wds_courses', ['course_listing_id' => $section->course_listing_id]);
                $coursenumber = $course_info->course_number;

                // Create/Get Group in Target Course.
                $groupname = $course_info->course_subject_abbreviation . ' ' . $coursenumber . ' ' . $section->section_number;

                $group = $DB->get_record('groups', ['courseid' => $target_course->id, 'name' => $groupname]);
                if (!$group) {
                    $groupdata = new stdClass();
                    $groupdata->courseid = $target_course->id;
                    $groupdata->name = $groupname;
                    $groupdata->description = 'Team Teach section ' . $groupname;
                    $groupdata->timecreated = time();
                    $groupdata->timemodified = time();
                    $groupid = groups_create_group($groupdata);
                } else {
                    $groupid = $group->id;
                }

                // Store original moodle_status (course id) to clean up later.
                $original_course_id = (is_numeric($section->moodle_status) && $section->moodle_status > 0) ? $section->moodle_status : null;

                // Update section's idnumber and moodle_status.
                $section->moodle_status = $target_course->id;
                $section->idnumber = $target_course->idnumber;
                $DB->update_record('enrol_wds_sections', $section);

                // Move Students.
                $senrollsql = "SELECT se.*, s.userid
                               FROM {enrol_wds_student_enroll} se
                               JOIN {enrol_wds_students} s ON se.universal_id = s.universal_id
                               WHERE se.section_listing_id = :slid
                               AND (se.status = 'enroll' OR se.status = 'enrolled')";
                $students = $DB->get_records_sql($senrollsql, ['slid' => $section->section_listing_id]);

                foreach ($students as $stu) {
                    if ($stu->userid) {

                        // Enroll in target.
                        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
                        $plugin->enrol_user($instance, $stu->userid, $studentrole, $stu->registered_date, $stu->drop_date, ENROL_USER_ACTIVE);

                        // Add to group.
                        groups_add_member($groupid, $stu->userid);

                        // Unenroll from original course.
                        if ($original_course_id && $original_course_id != $target_course->id) {
                             $oldinstance = $DB->get_record('enrol', ['courseid' => $original_course_id, 'enrol' => 'workdaystudent']);
                             if ($oldinstance) {
                                 $plugin->unenrol_user($oldinstance, $stu->userid);
                             }
                        }
                    }
                }

                // Move Teachers.
                $tenrollsql = "SELECT te.*, t.userid
                               FROM {enrol_wds_teacher_enroll} te
                               JOIN {enrol_wds_teachers} t ON te.universal_id = t.universal_id
                               WHERE te.section_listing_id = :slid";

                // Let's move all active teachers for that section.
                $teachers = $DB->get_records_sql($tenrollsql, ['slid' => $section->section_listing_id]);

                foreach ($teachers as $tea) {
                     if ($tea->userid) {

                         // Enroll in target.
                         $roleid = ($tea->role == 'Primary') ? $s->primaryrole : $s->nonprimaryrole;

                         $plugin->enrol_user($instance, $tea->userid, $roleid, time(), 0, ENROL_USER_ACTIVE);
                         groups_add_member($groupid, $tea->userid);

                         // TODO: DO NOT DO THIS WHOLLY. DO BETTER CHECKS.
                         // Unenroll from original course.
                         if ($original_course_id && $original_course_id != $target_course->id) {
                             $oldinstance = $DB->get_record('enrol', ['courseid' => $original_course_id, 'enrol' => 'workdaystudent']);
                             if ($oldinstance) {
                             }
                         }
                     }
                }

                // Check if original course can be deleted.
                if ($original_course_id && $original_course_id != $target_course->id) {
                    if (wdsprefs::can_delete_original_course($original_course_id)) {
                        wdsprefs::delete_original_course($original_course_id);
                    }
                }
            }

            $request->status = 'approved';
            $request->timemodified = time();
            $DB->update_record('block_wdsprefs_teamteach', $request);

            $transaction->allow_commit();
            return true;

        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Cancel a pending request (by requester).
     *
     * @param int $requestid
     * @param int $userid
     * @return bool
     */
    public static function cancel_request(int $requestid, int $userid): bool {
        global $DB;

        $request = $DB->get_record('block_wdsprefs_teamteach', ['id' => $requestid]);
        if (!$request) {
            return false;
        }

        // Only requester can cancel.
        if ($request->requester_userid != $userid) {
            return false;
        }

        // Only pending requests can be cancelled.
        if ($request->status != 'pending') {
            return false;
        }

        return $DB->delete_records('block_wdsprefs_teamteach', ['id' => $requestid]);
    }

    /**
     * Undo or Revoke an approved request.
     *
     * @param int $requestid
     * @param int $userid
     * @return bool
     */
    public static function undo_request(int $requestid, int $userid): bool {
        global $DB, $CFG;

        $request = $DB->get_record('block_wdsprefs_teamteach', ['id' => $requestid]);
        if (!$request) {
            return false;
        }

        // Must be approved.
        if ($request->status != 'approved') {
            return false;
        }

        // Must be requester (Undo) or requested (Revoke).
        if ($request->requester_userid != $userid && $request->requested_userid != $userid) {
            return false;
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $section_ids = json_decode($request->requested_section_ids);
            if (empty($section_ids)) {

                // If there is nothing to undo, fall through to update status.
            } else {

                // Get Target Course.
                $target_course = $DB->get_record('course', ['id' => $request->target_course_id]);

                // Get settings.
                $s = workdaystudent::get_settings();
                $coursedefaults = get_config('moodlecourse');
                $plugin = enrol_get_plugin('workdaystudent');

                // Get target enrollment instance if target course exists.
                $target_instance = null;
                if ($target_course) {
                    $target_instance = $DB->get_record('enrol', ['courseid' => $target_course->id, 'enrol' => 'workdaystudent']);
                }

                foreach ($section_ids as $section_id) {
                    $section = $DB->get_record('enrol_wds_sections', ['id' => $section_id]);
                    if (!$section) {
                        continue;
                    }

                    // Remove from Target Group first (we need course info for name).
                    $course_info = $DB->get_record('enrol_wds_courses', ['course_listing_id' => $section->course_listing_id]);
                    $coursenumber = $course_info->course_number;
                    $groupname = $course_info->course_subject_abbreviation . ' ' . $coursenumber . ' ' . $section->section_number;

                    if ($target_course) {
                        $group = $DB->get_record('groups', ['courseid' => $target_course->id, 'name' => $groupname]);
                        if ($group) {

                            // We will remove members later.
                        }
                    }

                    // Identify the Primary Teacher of this section (Requested User) to reconstruct the IDNumber.
                    $teacher_record = $DB->get_record('enrol_wds_teachers', ['userid' => $request->requested_userid]);
                    $requested_user = $DB->get_record('user', ['id' => $request->requested_userid]);

                    // Determine Original Course.
                    $original_course = null;

                    // Check if it belongs to a Cross-split.
                    $cross_section = $DB->get_record('block_wdsprefs_crosssplit_sections', ['section_id' => $section_id]);
                    if ($cross_section) {
                        $crosssplit = $DB->get_record('block_wdsprefs_crosssplits', ['id' => $cross_section->crosssplit_id]);
                        if ($crosssplit && $crosssplit->moodle_course_id) {
                            $original_course = $DB->get_record('course', ['id' => $crosssplit->moodle_course_id]);
                        }
                    }

                    // If not found, use Standard Shell logic.
                    if (!$original_course) {

                        // Period Info.
                        $period = $DB->get_record('enrol_wds_periods', ['academic_period_id' => $section->academic_period_id]);

                        // Period Name.
                        $periodname = wdsprefs::get_current_taught_periods($section->academic_period_id);
                        $periodname = reset($periodname);
                        $pname = preg_replace('/(\d{4}) /', '$1', $periodname);
                        $pname = str_replace(' (Online)', 'Online', $pname);

                        // Construct IDNumber.
                        $mshell = new stdClass();
                        $mshell->course_subject_abbreviation = $course_info->course_subject_abbreviation;
                        $mshell->course_number = $course_info->course_number;
                        $mshell->sections = $section->section_number;
                        $mshell->universal_id = $teacher_record->universal_id;

                        $idnumber = wdsprefs::build_mshell_idnumber($s, $mshell, $pname);

                        $original_course = $DB->get_record('course', ['idnumber' => $idnumber]);

                        // If still not found, Create it.
                        if (!$original_course) {
                            $fullname = $periodname . ' ' . $course_info->course_subject_abbreviation . ' ' . $course_info->course_number . ' for ' . $requested_user->firstname . ' ' . $requested_user->lastname;

                            $cat = wdsprefs::get_subject_category($course_info->course_subject_abbreviation);
                            $catid = $cat ? $cat->id : 1;

                            $coursedata = new stdClass();
                            foreach ($coursedefaults as $name => $value) {
                                if (!property_exists($coursedata, $name)) {
                                    $coursedata->$name = $value;
                                }
                            }
                            $coursedata->fullname = $fullname;
                            $coursedata->shortname = $fullname;
                            $coursedata->idnumber = $idnumber;
                            $coursedata->numsections = $coursedefaults->numsections;
                            $coursedata->category = $catid;
                            $coursedata->visible = 1;
                            $coursedata->enablecompletion = $coursedefaults->enablecompletion;
                            $coursedata->groupmode = $coursedefaults->groupmode;
                            $coursedata->groupmodeforce = $coursedefaults->groupmodeforce;
                            $coursedata->startdate = $period->start_date;
                            $coursedata->enddate = $period->end_date + (($s->erange / 3) * 86400);

                            $original_course = create_course($coursedata);

                            // Enrol Primary Teacher.
                            $original_instance = workdaystudent::wds_create_enrollment_instance($original_course->id);
                            $teacherroleid = $s->primaryrole;
                            $plugin->enrol_user($original_instance, $requested_user->id, $teacherroleid, time(), 0, ENROL_USER_ACTIVE);
                        }
                    }

                    // Ensure Original Instance exists.
                    $original_instance = $DB->get_record('enrol', ['courseid' => $original_course->id, 'enrol' => 'workdaystudent']);
                    if (!$original_instance) {
                        $original_instance = workdaystudent::wds_create_enrollment_instance($original_course->id);
                    }

                    // Update Section.
                    $section->moodle_status = $original_course->id;
                    $section->idnumber = $original_course->idnumber;
                    $DB->update_record('enrol_wds_sections', $section);

                    // Move Students.
                    $senrollsql = "SELECT se.*, s.userid
                                   FROM {enrol_wds_student_enroll} se
                                   JOIN {enrol_wds_students} s ON se.universal_id = s.universal_id
                                   WHERE se.section_listing_id = :slid
                                   AND (se.status = 'enroll' OR se.status = 'enrolled')";
                    $students = $DB->get_records_sql($senrollsql, ['slid' => $section->section_listing_id]);

                    $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);

                    foreach ($students as $stu) {
                        if ($stu->userid) {

                            // Enrol in Original.
                            $plugin->enrol_user($original_instance, $stu->userid, $studentrole, $stu->registered_date, $stu->drop_date, ENROL_USER_ACTIVE);

                            // Unenrol from Target.
                            if ($target_instance) {
                                $plugin->unenrol_user($target_instance, $stu->userid);
                            }

                            // Remove from Target Group.
                            if (isset($group) && $group) {
                                groups_remove_member($group->id, $stu->userid);
                            }
                        }
                    }

                    // Move Teachers.
                    $tenrollsql = "SELECT te.*, t.userid
                                   FROM {enrol_wds_teacher_enroll} te
                                   JOIN {enrol_wds_teachers} t ON te.universal_id = t.universal_id
                                   WHERE te.section_listing_id = :slid";
                    $teachers = $DB->get_records_sql($tenrollsql, ['slid' => $section->section_listing_id]);

                    foreach ($teachers as $tea) {
                        if ($tea->userid) {
                            $roleid = ($tea->role == 'Primary') ? $s->primaryrole : $s->nonprimaryrole;

                            // Enrol in Original.
                            $plugin->enrol_user($original_instance, $tea->userid, $roleid, time(), 0, ENROL_USER_ACTIVE);

                            // Unenrol from Target (Only if not requester/target-owner).
                            if ($target_instance && $tea->userid != $request->requester_userid) {
                                $plugin->unenrol_user($target_instance, $tea->userid);
                            }

                            if (isset($group) && $group) {
                                groups_remove_member($group->id, $tea->userid);
                            }
                        }
                    }
                }
            }

            self::send_revoke_email($request, $userid);
            $DB->delete_records('block_wdsprefs_teamteach', ['id' => $requestid]);

            $transaction->allow_commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Send email notification about revoked/undone request.
     *
     * @param object $request
     * @param int $actor_userid
     * @return bool
     */
    public static function send_revoke_email($request, $actor_userid): bool {
        global $DB;

        $requester = $DB->get_record('user', ['id' => $request->requester_userid]);
        $requested = $DB->get_record('user', ['id' => $request->requested_userid]);
        $target_course = $DB->get_record('course', ['id' => $request->target_course_id]);

        if (!$requester || !$requested) {
            return false;
        }

        $actor = ($actor_userid == $requester->id) ? $requester : $requested;
        $recipient = ($actor_userid == $requester->id) ? $requested : $requester;

        $action_name = ($actor_userid == $requester->id) ? get_string('wdsprefs:undone', 'block_wdsprefs') : get_string('wdsprefs:revoked', 'block_wdsprefs');

        $subject = get_string('wdsprefs:teamteach_revoked_subject', 'block_wdsprefs', ['course' => $target_course->fullname]);
        $body = get_string('wdsprefs:teamteach_revoked_body', 'block_wdsprefs', [
            'recipient' => fullname($recipient),
            'actor' => fullname($actor),
            'action' => $action_name,
            'course' => $target_course->fullname
        ]);

        return email_to_user($recipient, $actor, $subject, text_to_html($body), $body);
    }

    /**
     * Get pending requests made by a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_pending_requests_by_requester(int $userid): array {
        global $DB;
        return $DB->get_records('block_wdsprefs_teamteach',
            ['requester_userid' => $userid, 'status' => 'pending'],
            'timecreated DESC'
        );
    }

    /**
     * Get history (non-pending) requests made by a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_history_requests_by_requester(int $userid): array {
        global $DB;
        return $DB->get_records_select('block_wdsprefs_teamteach',
            'requester_userid = :userid AND status != :status',
            ['userid' => $userid, 'status' => 'pending'],
            'timemodified DESC'
        );
    }

    /**
     * Get approved requests made by a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_approved_requests_by_requester(int $userid): array {
        global $DB;
        return $DB->get_records('block_wdsprefs_teamteach',
            ['requester_userid' => $userid, 'status' => 'approved'],
            'timecreated DESC'
        );
    }

    /**
     * Get pending requests received by a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_pending_requests_by_requested(int $userid): array {
        global $DB;
        return $DB->get_records('block_wdsprefs_teamteach',
            ['requested_userid' => $userid, 'status' => 'pending'],
            'timecreated DESC'
        );
    }

    /**
     * Get history (non-pending) requests received by a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_history_requests_by_requested(int $userid): array {
        global $DB;
        return $DB->get_records_select('block_wdsprefs_teamteach',
            'requested_userid = :userid AND status != :status',
            ['userid' => $userid, 'status' => 'pending'],
            'timemodified DESC'
        );
    }

    /**
     * Get approved requests received by a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_approved_requests_by_requested(int $userid): array {
        global $DB;
        return $DB->get_records('block_wdsprefs_teamteach',
            ['requested_userid' => $userid, 'status' => 'approved'],
            'timecreated DESC'
        );
    }

    /**
     * Check the status of a section to see if it is already cross-listed, split, or team taught.
     *
     * @param int $section_id The ID of the section to check.
     * @param int $teacher_id The ID of the teacher.
     * @return array [available (bool), message (string)]
     */
    public static function check_section_status(int $section_id, int $teacher_id): array {
        global $DB;

        // Check if section is cross-listed, split, or cross-enrolled.
        $cross_section = $DB->get_record('block_wdsprefs_crosssplit_sections', ['section_id' => $section_id]);
        if ($cross_section) {
            $crosssplit = $DB->get_record('block_wdsprefs_crosssplits', ['id' => $cross_section->crosssplit_id]);
            $shell_name = $crosssplit ? $crosssplit->shell_name : 'Unknown Shell';
            return [
                'available' => false,
                'message' => get_string('wdsprefs:section_already_crosslisted', 'block_wdsprefs', $shell_name)
            ];
        }

        // Check if section is already involved in a team teach request (pending or approved).
        $sql = "SELECT * FROM {block_wdsprefs_teamteach}
                WHERE requested_userid = :teacherid
                  AND (status = 'pending' OR status = 'approved')";
        $requests = $DB->get_records_sql($sql, ['teacherid' => $teacher_id]);

        foreach ($requests as $request) {
            $requested_sections = json_decode($request->requested_section_ids);
            if (is_array($requested_sections) && in_array($section_id, $requested_sections)) {

                // Ignore expired pending requests.
                if ($request->status == 'pending' && $request->expirytime < time()) {
                    continue;
                }

                $course = $DB->get_record('course', ['id' => $request->target_course_id]);
                $course_name = $course ? $course->fullname : 'Unknown Course';
                return [
                    'available' => false,
                    'message' => get_string('wdsprefs:section_already_teamtaught', 'block_wdsprefs', $course_name)
                ];
            }
        }

        return ['available' => true, 'message' => ''];
    }

    /**
     * Check if a shell is eligible to be a target course for team teaching.
     *
     * @param int $moodle_course_id The Moodle course ID of the shell.
     * @param array $section_ids Array of section IDs contained in the shell.
     * @return bool True if eligible, false otherwise.
     */
    public static function check_shell_eligibility(int $moodle_course_id, array $section_ids): bool {
        global $DB;
        static $tt_requests_cache = null;

        // If a course has no sections left, what should we do with it?
        if (empty($section_ids)) {
            return false;
        }

        /*
        // TODO: Should I be doing this? Not according to Erica and Melissa.
        // Check if shell is a crosssplit course.
        if ($DB->record_exists('block_wdsprefs_crosssplits', ['moodle_course_id' => $moodle_course_id])) {
            return false;
        }
        */

        /*
        // TODO: Should I be doing this? Not according to Erica and Melissa.
        // Check if shell is already a target course in a team teach request.
        $sql_tt_target = "SELECT id, status, expirytime FROM {block_wdsprefs_teamteach}
                          WHERE target_course_id = :courseid
                            AND (status = 'pending' OR status = 'approved')";
        $target_requests = $DB->get_records_sql($sql_tt_target, ['courseid' => $moodle_course_id]);

        foreach ($target_requests as $req) {
             if ($req->status == 'approved') {
                 return false;
             }
             if ($req->status == 'pending' && $req->expirytime > time()) {
                 return false;
             }
        }
        */

        /*
        // TODO: Should I be doing this? Not according to Erica and Melissa.
        // Check if any section is part of a crosssplit.
        list($insql, $inparams) = $DB->get_in_or_equal($section_ids);
        $sql_cs_sec = "SELECT id FROM {block_wdsprefs_crosssplit_sections} WHERE section_id $insql";
        if ($DB->record_exists_sql($sql_cs_sec, $inparams)) {
             return false;
        }
        */

        // Check if any section is part of a team teach request.
        if ($tt_requests_cache === null) {
            $sql_tt_req = "SELECT id, requested_section_ids, status, expirytime
                           FROM {block_wdsprefs_teamteach}
                           WHERE status = 'pending' OR status = 'approved'";
            $tt_requests_cache = $DB->get_records_sql($sql_tt_req);
        }

        foreach ($tt_requests_cache as $req) {
             if ($req->status == 'pending' && $req->expirytime < time()) {
                 continue;
             }

             $req_sections = json_decode($req->requested_section_ids, true);
             if (is_array($req_sections)) {
                 if (array_intersect($section_ids, $req_sections)) {
                     return false;
                 }
             }
        }

        return true;
    }
}
