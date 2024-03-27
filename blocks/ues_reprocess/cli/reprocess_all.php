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
 * @package    ues_reprocess
 * @copyright  2024 onwards LSUOnline & Continuing Education
 * @copyright  2024 onwards Robert Russo
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

// Start the timer.
$timestart = microtime(true);

// Include the main Moodle config.
require(__DIR__ . '/../../../config.php');

// This is so we can use the CFG var.
global $CFG;

// Include the CLI lib so we can do this stuff via CLI.
require_once("$CFG->libdir/clilib.php");

// Require the main reprocess lib.
require_once('../lib.php');

// Grab the credentials needed to fetch data from DAS.
$creds = repall::get_creds();

// Fetch the current sections to be reprocessed.
$sections = repall::fetch_current_ues_sections('AAAS', false);

// Count the number of sections.
$sectioncount = count($sections);

// Log that we're beginning.
mtrace("Starting to reprocess $sectioncount sections.");

// Start the section counter.
$sectioncounter = 0;

// Loop through the sections.
foreach ($sections as $section) {
    // Get start time.
    $sectionstart = microtime(true);

    // Fetch enrollments for each section.
    $enrollments = repall::fetch_ues_section_enrollment($creds, $section);

    // Set everyone in that section to be unenrolled.
    $tempunenrolled = repall::temp_unenroll_ues($section);

    // Repopulate the correct enrollment requests based on DAS data.
    $populateues = repall::populate_ues($section, $enrollments);

    // Actually do the enrollments.
    $enrollinsections = repall::ues_enrollment($section, $enrollments);

    // Increment the section counter.
    $sectioncounter++;

    // Get finish time.
    $sectionend = microtime(true);

    // Get the elapsed time.
    $sectiontime = round($sectionend - $sectionstart, 2);

    // Get the sections remaining count.
    $sectionsremaining = $sectioncount - $sectioncounter;

    // Log it.
    mtrace("  $section->idnumber took $sectiontime seconds to reprocess. $sectionsremaining left to reprocess.");
}

// Set the time end.
$timeend = microtime(true);

// Derive the total time.
$totaltime = round($timeend - $timestart, 2);

// Log it.
mtrace("Total elapsed time is $totaltime seconds to reprocess $sectioncount sections.");


class repall {
    public static function encode_semester($semesteryear, $semestername) {
        // Helper function for switch below.
        $partial = function ($year, $name) {
            return sprintf('%d%s', $year, $name);
        };

        // Derive and return the mainframe required semester term.
        switch ($semestername) {
            case 'Fall':
                return $partial($semesteryear + 1, '1S');
            case 'First Fall':
                return $partial($semesteryear + 1, '1L');
            case 'Second Fall':
                return $partial($semesteryear + 1, '1P');
            case 'WinterInt':
                return $partial($semesteryear + 1, '1T');
            case 'Summer':
                return $partial($semesteryear, '3S');
            case 'First Summer':
                return $partial($semesteryear, '3D');
            case 'Second Summer':
                return $partial($semesteryear + 1, '1D');
            case 'Spring':
                return $partial($semesteryear, '2S');
            case 'First Spring':
                return $partial($semesteryear, '2D');
            case 'Second Spring':
                return $partial($semesteryear, '2L');
            case 'SummerInt':
                return $partial($semesteryear, '3T');
            case 'SpringInt':
                return $partial($semesteryear, '2T');
        }
    }

    public static function ues_enrollment($section, $enrollments) {
        global $DB, $CFG;

        // Grab the required class.
        require_once("$CFG->dirroot/enrol/ues/lib.php");

        $studentrole = get_config('enrol_ues', 'student_role');
        // Grab the role id if one is present, otherwise use the Moodle default.
        $roleid = isset($studentrole) ? $studentrole : 5;

        // Set this up for getting the enroll instance.
        $etable = 'enrol';
        $econditions = array('courseid' => $section->courseid, 'enrol' => 'ues');

        // Get the enroll instance.
        $einstance = $DB->get_record($etable, $econditions);

        // Set this up for getting the course.
        $ctable = 'course';
        $cconditions = array('id' => $section->courseid);

        // Get the course object.
        $course = $DB->get_record($ctable, $cconditions, $fields = '*');

        // If we do not have an existing enrollment instance, add it.
        if (empty($einstance)) {
            $enrollid = $enroller->add_instance($course);
            $einstance = $DB->get_record('enrol', array('id' => $enrollid));
        }

        // Instantiate the enroller.
        $enroller = new enrol_ues_plugin();

        // Determine if we're removing or suspending oa user on unenroll.
        $unenroll = get_config('enrol_ues', 'suspend_enrollment');

        // If we're unenrolling a student.
        if ($enrollstatus == 0) {
            // If we're removing them from the course.
            if ($unenroll == 1) {
                // Do the nasty.
                $enrolluser   = $enroller->unenrol_user(
                                    $einstance,
                                    $stu->userid);
                mtrace("User $stu->userid unenrolled from course: $stu->course.");
            // Or we're suspending them.
            } else {
                // Do the nasty.
                $enrolluser   = $enroller->update_user_enrol(
                                    $einstance,
                                    $stu->userid, ENROL_USER_SUSPENDED);
                mtrace("    User ID: $stu->userid suspended from course: $stu->course.");
            }
        // If we're enrolling a student in the course.
        } else if ($enrollstatus == "enroll") {
            // Check to see if a user is enrolled via D1.
            $check = self::check_d1_enr($stu->course, $stu->userid);

            // Set the start date if it's there.
            $enrollstart = isset($enrollstart) ? $enrollstart : 0;

            // Set their end date if it's there.
            $enrollend = isset($enrollend) ? $enrollend : 0;

            if (!isset($check->enrollid)) {
                // Do the nasty.
                $enrolluser = $enroller->enrol_user(
                              $einstance,
                              $stu->userid,
                              $roleid,
                              $enrollstart,
                              $enrollend,
                              $status = ENROL_USER_ACTIVE);
                mtrace("    User ID: $stu->userid enrolled into course: $stu->course.");

                // Require check once.
                if (!function_exists('grade_recover_history_grades')) {
                    global $CFG;
                    require_once($CFG->libdir . '/gradelib.php');
                }
                $grade = grade_recover_history_grades($userid, $courseid);
                if ($grade) {
                    mtrace("      Grades recovered for userid: $userid in course: $courseid.");
                }
            } else if ($check->enrollend <> $enrollend) {
                $enrolluser = $enroller->enrol_user(
                              $einstance,
                              $stu->userid,
                              $roleid,
                              $enrollstart,
                              $enrollend,
                              $status = ENROL_USER_ACTIVE);
                mtrace("    User ID: $stu->userid enddate modified in course: $stu->course.");
            } else {
                mtrace("    Skipping user: $stu->userid already enrolled in course: $stu->course.");
            }
        }
    }

    public static function check_ues_enr($courseid, $userid, $enrollend = null) {
        global $DB;

        if (is_null($enrollend)) {
            $where = "";
        } else {
            $where = "AND ue.timeend = ' . $enrollend . '";
        }

        $sql = 'SELECT ue.id AS enrollid,
                       ue.timeend AS enrollend
                FROM mdl_course c
                    INNER JOIN mdl_enrol e ON e.courseid = c.id
                    INNER JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
                WHERE e.enrol = "d1"
                    AND ue.status = 0
                    AND c.id = ' . $courseid . '
                    ' . $where . '
                    AND ue.userid = ' . $userid;

        $d1enrolled = $DB->get_record_sql($sql);
        return $d1enrolled;
    }


    public static function fetch_current_ues_sections($department, $enrolled = false) {
        global $DB;

        // Set this up fro inclusion in SQL.
        $enrolledsql = $enrolled
                       ? 'INNER JOIN {enrol_ues_students} stu ON stu.sectionid = sec.id'
                       : '';

        // Build the SQL.
        $sql = 'SELECT c.id AS courseid,
                    c.idnumber AS idnumber,
                    sec.id AS sectionid,
                    sem.campus AS campus,
                    cou.department AS department,
                    cou.cou_number AS coursenumber,
                    sec.sec_number AS section,
                    sem.year AS semesteryear,
                    sem.name AS semestername,
                    sem.session_key AS session
                FROM {enrol_ues_semesters} sem
                    INNER JOIN {enrol_ues_sections} sec ON sec.semesterid = sem.id
                    INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
                    INNER JOIN {course} c ON c.idnumber = sec.idnumber
                    INNER JOIN {course_categories} cc ON cc.id = c.category AND cc.name = cou.department
                    ' . $enrolledsql . '
                WHERE sec.idnumber IS NOT NULL
                    AND sec.idnumber != ""
                    AND c.idnumber IS NOT NULL
                    AND c.idnumber != ""
                    AND sem.grades_due > UNIX_TIMESTAMP()
                    AND sem.classes_start < UNIX_TIMESTAMP()
                    AND cou.department = "' . $department . '"
                GROUP BY sem.id,
                    cou.department,
                    cou.cou_number,
                    sec.id
                ORDER BY cc.name ASC,
                    cou.department ASC,
                    cou.cou_number ASC,
                    sec.sec_number ASC';

        $sections = $DB->get_records_sql($sql);

        return $sections;
    }

    public static function create_ues_user($uesuser) {
        global $DB;

        // Decode name.
        $parts = explode(',', $uesuser->fullname);
        $lastname = trim($parts[0]);
        $firstpart = explode(' ', trim($parts[1]));
        $firstname = trim($firstpart[0]);
        $middlename = isset($firstpart[1]) ? trim($firstpart[1]) : null;

        // Create a new user record
        $userobj = create_user_record($uesuser->username, null, 'oauth2');

        // Customize additional fields (if needed)
        $userobj->firstname = $firstname;
        $userobj->lastname = $lastname;
        $userobj->middlename = $middlename;
        $userobj->email = $uesuser->username;
        $userobj->idnumber = $uesuser->lsuid;

        // Update the user record in the database
        $DB->update_record('user', $userobj);

        return $userobj;
    }

    public static function populate_ues($section, $enrollments) {
        global $DB;

        // Loop through our enrollments.
        foreach ($enrollments as $enrollment) {
            $uesuser = new stdClass();
            $uesuser->lsuid = (int) $enrollment->LSU_ID;
            $uesuser->username = (string) $enrollment->PRIMARY_ACCESS_ID;
            $uesuser->fullname = (string) $enrollment->INDIV_NAME;

            // First thing we do is fetch the user.
            $user = $DB->get_record('user',
                  array(
                        'deleted' => 0,
                        'idnumber' => $uesuser->lsuid,
                        'username' => $uesuser->username
                       ),
                       $fields = '*'
                  );

            // Now we make sure they exist and if they do not, we create them.
            if (!isset($user->id)) {
                $user = self::create_ues_user($uesuser);
            }

            // Fetch the student object.
            $student = self::fetch_ues_student($section->sectionid, $user->id);
            if (!isset($student->id)) {
                // Create the student object.
                $student = self::insert_ues_student($enrollment, $section, $user);
            }

            // Update the students present in the XML to enroll. 
            $updated = self::update_ues_students($section->sectionid, $user->id, $student->id, 'enroll');
        }
    }

    public static function insert_ues_student($enrollment, $section, $user) {
        global $DB;

        // Define the table.
        $table = 'enrol_ues_students';

        // Build the student object for insertion.
        $studobj = new stdClass();
        $studobj->userid = (int) $user->id;
        $studobj->sectionid = (int) $section->sectionid;
        $studobj->credit_hours = (float) $enrollment->CREDIT_HRS;
        $studobj->status = 'enroll';

        // Insert the record.
        $studentid = $DB->insert_record($table, $studobj, $returnid = true, $buld = false);

        // Fetch the student for return.
        $student = $DB->get_record(
                       $table,
                       array('id' => $studentid),
                       $fields = '*',
                       $strictness = IGNORE_MISSING);
 
        // Return the student object.
        return $student;
    }

    public static function fetch_ues_student($sectionid, $userid) {
        global $DB;

        // What table are we hitting?
        $table = 'enrol_ues_students';

        // Grab the student object.
        $student = $DB->get_record($table,
                 array(
                       'sectionid' => $sectionid,
                       'userid' => $userid
                      ),
                      $fields = '*'
                 );

        return $student;
    }

    public static function update_ues_students($sectionid, $userid, $studentid, $status) {
         global $DB;

        // Build the SQL to set everyone to their appropriate status.
        $sql = 'UPDATE {enrol_ues_students}
                  SET status = "' . $status . '"
                  WHERE id = ' . $studentid . '
                  AND sectionid = ' . $sectionid . '
                  AND userid = ' . $userid;

        // Set enrollments to all unenroll.
        $status = $DB->execute($sql);

        // Return the boolean.
        return $status;


    }

    public static function fetch_ues_section_enrollment($creds, $section) {
        // Get the campus.
        $lsuorlaw = $section->campus == 'LSU' ? '01' : '08';

        // Get the serviceID.
        $serviceid = get_config('local_azure', 'student_source');

        // Set the base URL.
        $baseurl = 'https://das.lsu.edu/data_access_service/DynamicSqlServlet?';

        // Get the semester term.
        $semesterterm = self::encode_semester($section->semesteryear, $section->semestername);

        // Build the parms.
        $parms = array(
            'widget1' => $creds->username, 
            'widget2' => $creds->password, 
            'serviceId' => $serviceid,
            '1' => $lsuorlaw,
            '2' => $semesterterm,
            '3' => $section->department,
            '4' => $section->coursenumber,
            '5' => $section->section,
            '6' => $section->session);

        // Build these into a query string.
        $qs = http_build_query($parms, '', '&');

        // Build the URL.
        $url = $baseurl . $qs; 

        // Set the get header.
        $header = array('Content-Type: application/xml');

        // Initiate the curl handler.
        $curl = curl_init($url);

        // Se the CURL options.
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        // Get the response.
        $xml_response = curl_exec($curl);

        // Set the HTTP code for debugging.
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close the curl handler.
        curl_close($curl);

        // Decode the response.
        $enrollments = simplexml_load_string($xml_response);

        // Return the decoded data.
        return $enrollments;
    }

    public static function temp_unenroll_ues($section) {
        global $DB;

        // Build the SQL to set everyone to unenroll.
        $sql = 'UPDATE {enrol_ues_students}
                  SET status = "unenroll"
                  WHERE sectionid = ' . $section->sectionid;

        // Set enrollments to all unenroll.
        $status = $DB->execute($sql);

        // Return the boolean.
        return $status;
    }

    public static function get_creds() {
         global $CFG;

        require_once $CFG->libdir . '/filelib.php';

        // Get the credential location.
        $url = get_config('local_azure', 'credential_location');

        // Initialte the curl request.
        //$curl = new curl(array('cache' => false));
        $curl = new curl();

        // Set up and grab the response.
        $resp = $curl->post($url, array('credentials' => 'get'));

        // Populate the username and password.
        list($username, $password) = explode("\n", $resp);

        // Throw an exception if nothing is sent.
        if (empty($username) or empty($password)) {
            throw new Exception('bad_resp');
        }

        // Build the creds object.
        $creds = new stdClass();

        // Build out the object.
        $creds->username = trim($username);
        $creds->password = trim($password);

        // Return the credentials.
        return $creds;
    }
}

/**
 * LSU UES enrollment plugin.
 *
 */

class enrol_ues extends enrol_plugin {

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public static function add_enroll_instance($course) {
        return $instance;
    }
}
