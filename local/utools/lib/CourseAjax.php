<?php
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

class CourseAjax
{
    private $ulethlib = null;

    public function __construct()
    {
        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();
    }

    /**
     * Description - The TCMS widget may or may not need the URL for an instance to fetch
     *               data from so let's return what is set in the Utools Settings page.
     * @param type $params - nothing needs to be passed in.
     * @return json msg with the url.
     */
    public function getTcmsUrl($params = null)
    {
        die(json_encode(array("success" => true, "data" => $CFG->local_utools_tcms_extra_instance)));
    }

    /**
     * Description - Get the course type info
     * @param type $params - term
     * @return json msg with the count
     */
    public function get_course_type_info($params = null)
    {
        global $DB, $CFG;

        $this->ulethlib->printToLog("\n CourseAjax -> get_course_type_info() -> going to find current get_course_type_info.");
        $name_of_term = isset($params['term']) ? $params['term'] : null;
        $get_visible_course = $DB->get_records_sql(
            'SELECT *
            FROM {utools_course_stat}
            WHERE term = ?
            ORDER BY id DESC LIMIT 1',
            array($name_of_term)
        );
        die(json_encode(array($get_visible_course)));
    }

    public function getTerm($params = null)
    {
        global $DB, $CFG;

        $course_term = substr($this->ulethlib->getCurrentTerm(), -6);

        die(json_encode(array("success" => true, "data" => $course_term)));
        // die(json_encode(array($course_term)));
    }

    public function availableTerm($params = null)
    {
        global $DB;

        $get_term = $DB->get_records_sql(
            'SELECT DISTINCT ON (term) term FROM {utools_course_stat} GROUP BY term'
        );
        // error_log("\n");
        // error_log("\nCourseAjax -> availableTerm() -> What is the get_term obj: ". print_r($get_term, 1));

        die(json_encode(array("success" => true, "data" => $get_term)));

        // die(json_encode(array($get_term)));
    }

    public function get_all_term($params = null)
    {
        global $DB;
        $this->ulethlib->printToLog("\n CourseAjax -> get_all_term() -> going to find current get_all_term.");
        $term_1 = isset($params['term_one']) ? $params['term_one'] : null;
        $term_2 = isset($params['term_two']) ? $params['term_two'] : null;
        
        $get_visible_course = $DB->get_records_sql(
            'SELECT *
            FROM {utools_course_stat}
            WHERE term = ?
            UNION
            SELECT *
            FROM {utools_course_stat}
            WHERE term = ?
            ORDER BY id ASC LIMIT 2',
            array($term_1, $term_2)
        );
        die(json_encode(array($get_visible_course)));
    }

    public function getDailyData($params = null)
    {
        global $DB;
        $day_picked = isset($params['day_picked']) ? $params['day_picked'] : null;
        $timestamp = strtotime($day_picked);
        $get_day_info = $DB->get_records_sql(
            'SELECT *
            FROM {utools_course_stat}'
        );

        die(json_encode(array($get_day_info)));
    }

    /**
     * Description - Check to see if a student is in a course
     * @param string - CRN of the course
     * @param string - BID of the student
     * @return object - The student object that was found
     */
    public function isStudentInCourse($crn, $bid) {
        global $DB;

        $cid = $DB->get_record_sql('SELECT id FROM mdl_course WHERE idnumber=?', array($crn));
        
        // if (isset($cid->id)) {

            // let's check to see if this user is in the course:
        $context = context_course::instance($cid->id);
        // $userfields = 'u.id, u.idnumber';
        $userfields = 'u.id, u.idnumber, u.firstname, u.lastname, u.email, u.uofl_id, r.shortname';
        $students = get_role_users(5, $context, false, $userfields);
        // $found_student = false;
        // $this_student
        foreach ($students as $student) {
            // error_log(print_r($student, 1));
            if ($student->idnumber == $bid) {
                return $student;
            }
        }

        return false;
        // if ($found_student) {
            // return true;
        // } else {
        // }
        // }
    }

    /**
     * Description - Get the course type info
     * @param type $params - term
     * @return json msg with the count
     */
    public function unenrollUserFromCourse($params = null)
    {
        global $DB, $CFG;
        include_once($CFG->dirroot.'/enrol/lmb/lib.php');
        
        $bid = isset($params['bid']) ? $params['bid'] : null;
        $crn = isset($params['crn']) ? $params['crn'] : null;
        
        if ($this->ulethlib->isLocal()) {
            // obviously you need to change this for local testing purposes.
            $this_file = '/Users/davidlowe/Sites/XML_FILES/unenroll_user.xml';
        } else {
            $this_file = '/moodle/dump/CLI/unenroll_user.xml';
        }

        // error_log("\n\n");


        $this_student = $this->isStudentInCourse($crn, $bid);
        if (!$this_student) {
            // nope, student is already gone.
            // error_log("\n\nStudent is already gone, aborting");
            die(json_encode(array("success" => "false", "msg" => "Sorry, the student wasn't found.")));
        }
        
        $enrol = new enrol_lmb_plugin();
        $force = 1;
        $enrol->silent = 0;
        $enrol->log_line("Removing this user (bid): " . $bid . " from CRN: ". $crn);
        // error_log("\n\n");
        // error_log("\nRemoving this user (bid): " . $bid . " from CRN: ". $crn);
        
        $file_to_write = '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE enterprise SYSTEM "ldisp-2.0.dtd">
            <enterprise>
                <properties lang="en">
                    <datasource>NAME SCT Banner</datasource>
                    <datetime>2014-01-21T03:05:33</datetime>
                </properties>
                <membership>
                    <sourcedid>
                        <source>NAME SCT Banner</source>
                        <id>' . $crn . '</id>
                    </sourcedid>
                    <member>
                        <sourcedid>
                            <source>NAME SCT Banner</source>
                            <!-- use the Banner ID -->
                            <id>' . $bid . '</id>
                        </sourcedid>
                        <idtype>1</idtype>
                        <role roletype = "01">
                            <!-- 01 for STUDENT, 02 for TEACHER  -->
                            <status>0</status>
                        </role>
                    </member>
                </membership>
            </enterprise>';

        // write to directory
        file_put_contents($this_file, $file_to_write);
        
        // must set the definePath var in order for the lib to use our path.
        $_SESSION['definePath'] = $this_file;
        $enrol->process_file($this_file, $force);

        // clear out any LMB fwrite logging info.
        ob_clean();
        if (!$this->isStudentInCourse($crn, $bid)) {
            // nope, student is now gone.
            // error_log("\n\nStudent is NOW gone, return success.");

            die(json_encode(array("success" => "true", "msg" => "Success, ". $bid . " has been removed from " . $crn)));
        } else {
            // error_log("\n\nStudent is STILL in the course, WTF??");
            die(json_encode(array("success" => "false", "msg" => "Ooops, ". $bid . " has NOT been removed from " . $crn)));
        }
    }
}
