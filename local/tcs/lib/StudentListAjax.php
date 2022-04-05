<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

// require_once($CFG->libdir . "/externallib.php");

class StudentListAjax
// class StudentListAjax extends external_api {
{
    public $log_store;

    public function __construct()
    {
        global $CFG;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> constructor() -> new object now being built") : null;
        $this->log_store = '/Users/davidlowe/Sites/logs/php_terminal_output.txt';
        include_once 'TcsLib.php';
        // include_once 'Stats.php';
        $this->tcslib = new TcsLib();
        // $this->stats = new Stats();
    }


    /**
     * Description Get all of the exams for a student when searching
     * @param type $params this will be an array of params that came from javascript/ajax
     *        isnum - flag to say it's an id number as all params that come across will be strings
     *        userid - user id
     *        ax - this func was called via ajax or by other method
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    // public function loadUserExams($isnum = false, $username)
    // public function loadUserExams($isnum = false, $username)
    // public function loadUserExams($isnum = false, $username)
    // public function loadUserExams($isnum = false, $username)
    public function loadUserExams($params)
    {
        // error_log("\n loadUserExams() -> ---------------   START   ---------------");
        global $DB, $USER, $CFG;

        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is params: ".print_r($params, 1)) : null;
        // error_log("\nloadUserExams() -> what are the params: ". print_r($params, 1));
        
        $rows = null;
        $uofl_id = null;
        $get_user = null;
        $attempt_override = 0;

        $isnum = isset($params->isnum) ? $params->isnum : null;
        $username = isset($params->username) ? $params->username : null;
        $uofl_id = isset($params->userid) ? $params->userid : null;
        $ajax = isset($params->ax) ? true : false;

        if (isset($params->attempt_override) && $params->attempt_override == 1) {
            $attempt_override = 1;
        } else {
            $attempt_override = 0;
        }
        
        // error_log("\n ^^^^^^^^^^^^^^^^\n");
        // error_log("\n loadUserExams() -> What is attempt_override: ". $attempt_override. " \n\n");
        // error_log("\n loadUserExams() -> What is params->attempt_override: ". $params->attempt_override. " \n\n");

        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is ajax set: ".$ajax) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is isnum set: ".$isnum) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is username set: ".$username) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is uofl_id set: ".$uofl_id) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> we skipping the attempts? ".$attempt_override) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> CFG->local_tcs_quiz_ip_restriction: ".$CFG->local_tcs_quiz_ip_restriction,) : null;

        // let's make sure that the quiz ip to compare to is sufficient
        if (strlen($CFG->local_tcs_quiz_ip_restriction) < 6) {
            error_log("\n\n Sorry but the Quiz Settings IP Restriction is too short! \n");
            return array(
                // 'success' => false,
                'msg_type' => 'error',
                "show_msg" => array(
                    "title" => "Error",
                    "position" => "topRight",
                    "message" => "Sorry but the Quiz Settings IP Restriction is too short!"
                ),
            );
        }

        // if ($isnum) {
        // $uofl_id = isset($params['userid']) ? $params['userid'] : null;
        // $get_user = $DB->get_record('SELECT username FROM mdl_user where uofl_id=?', array($uofl_id));
        // $get_user = $DB->get_record('user', array('uofl_id' => (int)$uofl_id));
        // if (!$get_user) {
        //     $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> NO user id found: ".print_r($get_user, 1)) : null;
        //     die(json_encode(array("success" => "false", 'msg' => 'Sorry, that id was not found matching '.$uofl_id.'!', 'extra' => array('username' => $username, 'uofl_id' => $uofl_id))));
        // }
        // $username = $get_user->username;
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> isnum is set so we have id of: ".$uofl_id." and username of: ".$username) : null;
        // } else {
        //     // $get_user = $DB->get_record('SELECT uofl_id FROM mdl_user where username=?', array($username));
        //     $get_user = $DB->get_record('user', array('username' => $username));
        //     // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is get_user obj: ".print_r($get_user, 1)) : null;
        //     if (!$get_user) {
        //         $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> NO username found: ".print_r($get_user, 1)) : null;
        //         die(json_encode(array("success" => "false", 'msg' => 'Sorry, no usernames were found matching '.$username.'!', 'extra' => array('username' => $username, 'uofl_id' => $uofl_id))));
        //     }
        //     $uofl_id = $get_user->uofl_id;
        //     $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> isnum is NOT set so we have id of: ".$uofl_id." and username of: ".$username) : null;
        // }

        // are they already in the system?
        $student_entered = $this->loadStudentStatus(array('username' => $username));
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is the student already in? : ".$student_entered) : null;
        if ($student_entered) {
            // student is in the tcs already, check them out.
            $user_test_record = $DB->get_records_sql(
                'SELECT *
                 FROM mdl_local_tcms_student_entry
                 WHERE username = ? AND (finished = 2 OR finished = 0)',
                array($username)
            );
            
            $user_test_record = array_values($user_test_record);
            $wacky_test = (object) $user_test_record;

            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is the student info to be removed: ".print_r($user_test_record, 1)) : null;
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is the id from username: ".$user_test_record[$username]->id) : null;
            
            $is_removed = $this->removeStudentFromList(
                (object) array(
                    'id' => $user_test_record[0]->id,
                    'user_id' => $user_test_record[0]->userid,
                    'exam_id' => $user_test_record[0]->examid,
                    'room' => $user_test_record[0]->room
                )
            );

            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> have removed user now, what is result: ".$is_removed) : null;
            if ($is_removed) {
                // die(json_encode(array("success" => "true", 'extra' => array('username' => $username, 'swipe_remove' => true, 'entry_id' => $user_test_record[$username]->id))));
                return array(
                    'row_id' => $user_test_record[0]->id,
                    'username' => $username,
                    'room' => $user_test_record[0]->room,
                    'swipe_remove' => true,
                    'msg_type' => 'success',
                    'show_msg' => array(
                        'title' => 'OK',
                        "position" => "topRight",
                        'message' => $username . " has been removed."
                    )
                );
            } else {
                return array(
                    // 'success' => false,
                    'row_id' => $user_test_record[$username]->id,
                    'username' => $username,
                    'swipe_remove' => true,
                    'msg_type' => 'error',
                    'show_msg' => array(
                        'title' => 'Error',
                        "position" => "topRight",
                        'message' => $username . " has NOT been removed, Ooops Something went wrong....Please contact the Teaching Centre."
                    )
                );
            }
        }
        /*  Going to keep this here.......just in case. It is the old query.
        $crazy_sql = 'SELECT CAST(mq.id AS text) AS id,
                mu.uofl_id AS uoflid, mc.shortname as course_shortname,
                mq.name AS examname
            FROM mdl_user mu
            INNER JOIN mdl_user_enrolments mue
                ON (mu.id = mue.userid)
            INNER JOIN mdl_enrol me
                ON (mue.enrolid = me.id)
            INNER JOIN mdl_course mc
                ON (me.courseid = mc.id)
            INNER JOIN mdl_quiz mq
                ON (mq.course = mc.id)
            WHERE mu.username = ?
                -- AND (mq.subnet LIKE \'%\' || ? || \'%\' OR ? LIKE \'%\' || mq.subnet || \'%\')
                -- AND (SELECT extract(epoch FROM now())) <= mq.timeclose
                AND ((SELECT extract(epoch FROM now())) >= mq.timeopen AND (SELECT extract(epoch FROM now())) <= mq.timeclose)
                AND NOT EXISTS (SELECT \'Y\'
                    FROM mdl_quiz_attempts mqa
                    WHERE mqa.attempt = mq.attempts
                        AND mqa.userid = mu.id
                        AND mqa.quiz = mq.id)
            UNION
            SELECT \'ManualExam-\' || tcep.id AS id,
                mu.uofl_id AS uoflid, mc.shortname as course_shortname,
                \'ManualExam-\' || tcep.exam_name AS examname
            FROM mdl_local_tcms_exam tcep
            INNER JOIN mdl_local_tcms_manual_exams me
                ON (tcep.id = me.manual_exam_id)
            INNER JOIN mdl_user mu
                ON (mu.username = me.username)
            INNER JOIN mdl_quiz mq
                ON (me.manual_exam_id = mq.id)
            INNER JOIN mdl_course mc
                ON (mq.course = mc.id)
            WHERE me.username = ?
                -- AND (SELECT extract(epoch FROM now())) <= tcep.closing_date
                AND ((SELECT extract(epoch FROM now()) >= tcep.opening_date) AND (SELECT extract(epoch FROM now()) <= tcep.closing_date))
                AND NOT EXISTS (SELECT \'Y\'
                    FROM mdl_quiz_attempts mqa
                    WHERE mqa.attempt = mq.attempts
                        AND mqa.userid = mu.id
                        AND mqa.quiz = mq.id)
            ORDER BY examname ASC';
        */
            $ip_list = explode(',', $CFG->local_tcs_quiz_ip_restriction);
            error_log("What is the ip_list: ". print_r($ip_list, 1));
            $ip_list_count = count($ip_list);
            error_log("What is the ip_list count: ". count($ip_list));

            if ($ip_list_count == 0) {
                $subnet_query = "";
            } else {
                $subnet_query = " AND (";
                for ($i = 0; $i < $ip_list_count; $i++) {

                    $subnet_query .= "mq.subnet LIKE '%".$ip_list[$i] . "%'";
                    if (($i + 1) < $ip_list_count) {
                        $subnet_query .= " OR ";
                    }
                }
                $subnet_query .= ")";
            }

            // This option is for ignoring any attempts on the exam
            $ignore_attempt_qry = "";
            if ($attempt_override == 0) {
                $ignore_attempt_qry = "AND NOT EXISTS (SELECT 'Y'
                    FROM mdl_quiz_attempts mqa
                    WHERE mqa.attempt = mq.attempts
                        AND mqa.userid = mu.id
                        AND mqa.quiz = mq.id)";
            }

            $crazy_sql = "SELECT CAST(mq.id AS text) AS id,
                mu.uofl_id AS uoflid, mc.shortname as course_shortname,
                mq.name AS examname
            FROM mdl_user mu
            INNER JOIN mdl_user_enrolments mue
                ON (mu.id = mue.userid)
            INNER JOIN mdl_enrol me
                ON (mue.enrolid = me.id)
            INNER JOIN mdl_course mc
                ON (me.courseid = mc.id)
            INNER JOIN mdl_quiz mq
                ON (mq.course = mc.id)
            WHERE mu.username = ?
                $subnet_query 
                AND ((SELECT extract(epoch FROM now())) >= mq.timeopen AND (SELECT extract(epoch FROM now())) <= mq.timeclose)
                $ignore_attempt_qry
            UNION
            SELECT 'ManualExam-' || tcep.id AS id, mu.uofl_id AS uoflid, tcep.course_name AS course_shortname, 'ManualExam-' || tcep.exam_name AS examname
            FROM mdl_local_tcms_exam tcep
            INNER JOIN mdl_local_tcms_manual_exams me ON (tcep.id = me.manual_exam_id)
            INNER JOIN mdl_user mu ON (mu.username = me.username)
            WHERE 
                me.username = ? AND 
                ((SELECT extract(epoch FROM now()) >= tcep.opening_date) AND (SELECT extract(epoch FROM now()) <= tcep.closing_date))
            
            ORDER BY examname ASC";

        error_log("\ngoing to exec final query: \n\n");
        error_log($crazy_sql);
        error_log("\n\n");

        try {
            $get_moodle_exams = $DB->get_records_sql($crazy_sql, array(
                $username,
                $username
            ));
        }
        catch (exception $e) {
            error_log("\n\n");
            error_log("\nERROR: ". $e);
            $get_moodle_exams = array();
        }

        if ($get_moodle_exams == null) {
            error_log("\n-------- NO EXAMS BITCH ------------ \n\n");
            return array("success" => "false", 'msg' => 'Sorry, no exams were found!', 'extra' => array('username' => $username, 'uofl_id' => $uofl_id));
        } else {
            error_log("\n-------- YAAAYYYYYY EXAMS BITCH ------------ \n\n");
            $counter = 0;
            foreach ($get_moodle_exams as $get_moodle_exam) {
                $rows[] = array(
                    'exrow' => $counter,
                    'exam_name' => $get_moodle_exam->examname,
                    'course_shortname' => $get_moodle_exam->course_shortname,
                    'value' =>$get_moodle_exam->id,
                    'uoflid' => $get_moodle_exam->uoflid
                );
                $counter++;
            }

            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> finished and return ".count($rows)." exams") : null;
        }

        $data_set = array(
            "exams" => $rows,
            // "dash_hash" => md5(serialize($rows))
            "dash_hash" => $this->tcslib->getSetting("dash_hash")
        );

        error_log("What is the data set: ". print_r($data_set,1));
        return $data_set;
    }

    /**
     * Description: Get all the users once and store in browser for this session to have fast autocomplete
     * @param type $params - none
     * @return json encoded array with success as true or false, the data and any necessary message
     *         data - all the students
     */
    public function loadUsers($params = false)
    {
        global $DB, $USER, $CFG;
        $i = 0;
        if ($params == false) {
            $page = 0;
            $pagetotal = 1;
            $local_call = false;
        } else {
            $page = $params->page;
            $pagetotal = $params->total;
            $local_call = isset($params->local_call) ? $params->local_call : false;
        }
        
        // error_log("\n WTF is local_call: ". $local_call);
        // if ($local_call) {
        //     error_log("\n local_call is TRUE");
        // } else {
        //     error_log("\n WTF local_call is FALSE");
        // }

        ini_set('memory_limit', '512M');
        // $CFG->local_tcs_logging ? error_log("\n") : null;
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> what is params: ".print_r($params, 1)) : null;
        
        $memlimit = ini_get('memory_limit');

        $total_user_count = $DB->get_record_sql(
            'SELECT count(id) from mdl_user'
        );
        $total_user_count = $total_user_count->count;

        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> total user count is: ". print_r($total_user_count, 1)) : null;
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> total user count is: ". $total_user_count) : null;

        $tack_on_offset = '';

        if ($page && $page > 0) {
            // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> page is set and greater than 0") : null;
            
            $partial = (int)($total_user_count / $pagetotal);
            $add_limit =  $partial * $page;
            $offset = $partial * ($page - 1);

            // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> partial is: ". $partial) : null;
            // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> add_limit is: ". $add_limit) : null;
            // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> offset is: ". $offset) : null;
            
            $tack_on_offset = ' LIMIT '.$add_limit. ' OFFSET '.$offset;
        }
        $policy = 1;
        // $sql = ' ';

        
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> query is: ". $sql) : null;

        // $get_users = $DB->get_records_sql(
        //     'SELECT id, username, firstname||\' \'||lastname as name, uofl_id
        //     FROM mdl_user
        //     WHERE policyagreed = ?
        //     ORDER BY username', array($policy)
        // );

        $get_users = $DB->get_records_sql(
            // 'SELECT u.username, u.firstname||\' \'||u.lastname as u.name, u.uofl_id
            'SELECT u.username, u.firstname, u.lastname, u.uofl_id
            FROM mdl_user as u
            JOIN (
                SELECT userid from mdl_role_assignments
                WHERE roleid <> 3
                GROUP BY userid
            ) AS ra ON ra.userid = u.id
            -- WHERE u.policyagreed = 1
            ORDER BY u.username'
        );
        
        foreach ($get_users as $get_user) {
           $rows[$i++] = array(
                // "id" => $i,
               "value" => $get_user->username. " - " .$get_user->uofl_id,
               "data" => array(
                    "username" => $get_user->username,
                    "firstname" => $get_user->firstname,
                    "lastname" => $get_user->lastname,
                    "uofl_id" => $get_user->uofl_id
               )
            //    "policyagreed" => $get_user->policyagreed
            );
        }

        $user_hash = md5(serialize($rows));
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> going to return list now: ") : null;
        $CFG->local_tcs_logging ? error_log("\n\n StudentListAjax -> loadUsers() -> what is the size of the list: ". count($get_users). "\n") : null;

        // let's update the user hash
        $current_setting_value = $DB->get_record('local_tcms_settings', array('t_name' => 'user_hash'));
        
        // if setting value is zero then it's old AF
        // if ($current_setting_value->t_value == "0") {

        // All Users Hash
    
            // error_log("\ngetHash() -> What is the hashed value from the users: ". $hashed_value . "\n");
        if ($user_hash != $current_setting_value) {
            // now store the newly generated hash for users
            $this->tcslib->setSetting('user_hash', $user_hash);
        }
        // }


        $data_set = array(
            "users" => $rows,
            "hash" => $user_hash
        );

        return $data_set;
        // } else {
            // die(json_encode(array("success" => "true", "data" => $get_users)));
            // error_log("\n StudentListAjax -> loadUsers() -> DIE AJAX DIE\n");
            // die(json_encode(array("success" => "true", "data" => $rows)));
        // }
    }

    /**
     * Description - Check to see if the user is in the system.
     * @param type $params this will be an array, just need username
     *        username - student username
     * @return either true or false, 1 or 0 to indicate if student is in tcs already
     */
    public function loadStudentStatus($params)
    {        
        global $DB, $USER, $CFG;
        
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentStatus() -> What is params: ".print_r($params, 1)) : null;

        // $uofl_id = isset($params['uoflid']) ? $params['uoflid'] : null;
        $username = isset($params['username']) ? $params['username'] : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentStatus() -> What is username: ".$username) : null;

        // make sure we have a username
        if (!$username) {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentStatus() -> Error, there is no username") : null;
            return 4;
        }

        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentStatus() -> running query now") : null;

        // get exams
        $std_testcentre_info = $DB->get_records_sql(
            'SELECT id,examid, finished
             FROM mdl_local_tcms_student_entry
             WHERE username=? AND (finished = 2 OR finished = 0)',
            array($username)
        );
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentStatus() -> What is size of query: ".count($std_testcentre_info)) : null;

        if (count($std_testcentre_info)) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Description - Get all students currently writing an Exam
     *    Note - this will be called by the startup JS func and ajax
     *    If called by startup we need the student list regardless.
     *    If called by AJAX check hash and if same then do nothing
     *    else get list, update hash and return list.
     * @param obj containing either startup and/or hash
     * @return json encoded array of all the users writing an exam
     */
    public function getUsersInExam($params = false)
    {
        global $DB, $USER, $CFG;
        // set the timezone based on the Moodle system(?)
        date_default_timezone_set(get_user_timezone());

        // error_log("\n @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ \n");
        // error_log("\n\n @@@@@@ Stats->getUsersInExam() -> START \n\n");

        $entered_users = $DB->get_records_sql(
            // 'SELECT id, userid,examid, username, coursename as course, room, to_timestamp(signed_in) as signintime, comments, id_type, exam_type
            'SELECT std.id, std.userid, std.examid, std.username, std.coursename as course, 
                qq.name as examname, std.room, std.signed_in as signintime, std.comments, std.id_type, std.exam_type
            FROM mdl_local_tcms_student_entry as std
            INNER JOIN mdl_quiz as qq ON std.examid = qq.id
            WHERE (std.finished = 0 OR std.finished = 2)'
        );

        $list_of_users = array();
        foreach ($entered_users as $entry) {
            $list_of_users[] = array(
                'id' => $entry->id,
                'user_id' => $entry->userid,
                'exam_id' => $entry->examid,
                'username' => $entry->username,
                'course' => $entry->course. ' - ' .$entry->examname,
                'examname' => $entry->examname,
                'room' => $entry->room,
                'signintime' => $entry->signintime,
                'comments' => $entry->comments,
                'id_type' => $entry->id_type,
                'exam_type' => $entry->exam_type
            );
        }

        $data_set = array(
            "users_in_centre" => $list_of_users
        );

        // error_log("\n getUsersInExam() -> ========>>>>>>  Finished  <<<<<<========");
        // error_log("\n @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ \n");

        return $data_set;
    }

    /**
     * Description - Add the student to the list
     * @param type $params this will be an array of params that came from javascript/ajax
     *        username - student username
     *        exam_id - exam id
     *        id_type - identification of the user
     *        room - computer number that student used
     *        comments - any comments
     *        exam_type - exam by Moodle or built manually for special case situations
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    public function addStudentToList($params)
    {
        global $DB, $USER, $CFG;
        // TODO: Need to fix the part that checks if the user is already in the system writing an exam
        $CFG->local_tcs_logging ? error_log("\n\naddStudentToList() -> =====>>>>> START <<<<<=====") : null;

        $CFG->local_tcs_logging ? error_log("\naddStudentToList() -> What is params: ".print_r($params, 1)) : null;

        $username = isset($params->username) ? $params->username : null;
        $exam_id = isset($params->exam_id) ? $params->exam_id : null;
        $id_type = isset($params->id_type) ? $params->id_type : null;
        $room = isset($params->room) ? $params->room : null;
        $comments = isset($params->comments) ? $params->comments : null;
        $exam_type = isset($params->exam_type) ? $params->exam_type : null;
        $examname = '';
        $std_records = null;
        $username_record = null;
        $coursename_record = null;

        // $status = $this->loadStudentStatus(array('uoflid' => $uofl_id, 'username' => $username));
        $status = $this->loadStudentStatus(array('username' => $username));
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> What is status of user: ".$status) : null;
    
        if ($status == 1) {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> User is in exam already: ".$status) : null;
            // die(json_encode(array("success" => "false", 'msg' => 'Sorry, the student is already checked in!')));
            return array(
                // "success" => false,
                'msg_type' => 'error',
                "show_msg" => array(
                    "title" => "Error",
                    "position" => "topRight",
                    "message" => "Sorry, the student is already checked in!"
                )
            );
        } else {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> User is NOT in exam, granting access: ".$status) : null;
        }

        $std_records = $DB->get_records('local_tcms_student_entry', array('username' =>$username , 'examid'=> $exam_id,'exam_type'=> $exam_type));
        $username_record = $DB->get_record('user', array('username' => $username));

        if ($exam_type == 1) {
            $CFG->local_tcs_logging ? error_log("\naddStudentToList() -> manaul exam id: ".$exam_id) : null;
            $manual_examname_record = $DB->get_record('local_tcms_exam_pass', array('id' => $exam_id));
            // let's get the exam title and course title
            $exam_name = $manual_examname_record->examname;
            $course_name = $manual_examname_record->coursename;

            $CFG->local_tcs_logging ? error_log("\naddStudentToList() -> manaul exam name: ".$examname) : null;
            
        } else {
            $examname_record = $DB->get_record('quiz', array('id' => $exam_id));
            $coursename_record = $DB->get_record('course', array('id' => $examname_record->course));
            // let's get the exam title and course title
            $exam_name = $examname_record->name;
            $course_name = $coursename_record->shortname;
            // $examname = $coursename_record->fullname.'-'.$examname_record->name;
            $CFG->local_tcs_logging ? error_log("\naddStudentToList() -> manual exam name: ".$examname) : null;
        }


        // add a timestamp to this entry
        // $last_changed_obj = new DateTime();
        // $last_changed = $last_changed_obj->getTimestamp();
        
        // ok, now is the student already logged as being IN the Test Centre?
        // If not, let's process them
        if (!$std_records) {

            date_default_timezone_set(get_user_timezone());

            $last_changed = time();
            $new_dash_hash = md5($last_changed);

            $record = new stdClass();
            $record->username = $username;
            $record->examid = $exam_id;
            $record->room = $room;
            $record->signed_in = time();
            $record->comments = $comments;
            $record->id_type = $id_type;
            $record->finished = 0;
            $record->exam_type = $exam_type;
            $record->coursename = $course_name;
            $record->last_changed = $last_changed;

            if (isset($username_record->uofl_id)) {
                $record->userid = $username_record->uofl_id;
            } else {
                $record->userid = 0;
            }

            $inserted_id = $DB->insert_record('local_tcms_student_entry', $record, true);
            // update the hash for all users currently in the Test Centre
            // $this->tcslib->setSetting("writers_hash", $new_writers_hash);
            // reset the dash hash, any watchers will be updated.
            $this->tcslib->setSetting('dash_hash', $new_dash_hash);

            $row = array('id' => $inserted_id,
                'username' => $username_record->username,
                'examname' => $exam_name,
                'course' => $course_name,
                'room' => $room,
                'id_type' => $id_type,
                'timesigned' => $record->signed_in, // let moment js handle the formatting
                'comments' => $comments
            );

            return array(
                // "success" => true,
                "dash_hash" => $new_dash_hash,
                "data" => $row,
                "row_id" => $inserted_id,
                'msg_type' => 'success',
                "show_msg" => array(
                    "title" => "OK",
                    "position" => "topRight",
                    "message" => "User: ".$username_record->username." has entered for: ".$exam_name
                )
            );
            // return $data_set;
        } else {
            $is_std_in_testcentre = false;
            foreach ($std_records as $std_record) {
                $student_status = array('finished' => $std_record->finished);

                if ($student_status['finished'] == 0 || $student_status['finished'] == 2) {
                    $is_std_in_testcentre = true;
                    // die(json_encode(array("success" => "false", "msg" => "Error Message 1, this exam is already opened! Please close it to reopen.")));
                    return array(
                        "success" => false,
                        "msg" => "Error Message 1, this exam is already opened! Please close it to reopen."
                    );
                }
            }

            if (!$is_std_in_testcentre) {

                $last_changed = time();
                $new_dash_hash = md5($last_changed);

                $record = new stdClass();
                $record->username = $username;
                $record->examid = $exam_id;
                $record->room = $room;
                // date_default_timezone_set('America/Denver');
                $record->signed_in = time();
                $record->comments = $comments;
                $record->id_type = $id_type;
                $record->finished = 2;
                $record->exam_type = $exam_type;
                $record->coursename = $course_name;
                $record->last_changed = $last_changed;

                if (isset($username_record->uofl_id)) {
                    $record->userid = $username_record->uofl_id;
                } else {
                    $record->userid = 0;
                }

                $inserted_id = $DB->insert_record('local_tcms_student_entry', $record, true);
                // update the hash for all users currently in the Test Centre
                // $this->tcslib->setSetting("writers_hash", $new_dash_hash);

                // reset the dash hash, any watchers will be updated.
                $this->tcslib->setSetting('dash_hash', $new_dash_hash);

                $row = array('id' => $inserted_id,
                    'username' => $username_record->username,
                    'examname' => $exam_name,
                    'course' => $course_name,
                    'room' => $room,
                    'id_type' => $id_type,
                    'timesigned' => date('Y-m-d H:i:s', $record->signed_in),
                    'comments' => $comments
                );

                return array(
                    // "success" => true,
                    "dash_hash" => $new_dash_hash,
                    "data" => $row,
                    "row_id" => $inserted_id,
                    'msg_type' => 'success',
                    "show_msg" => array(
                        "title" => "OK",
                        "position" => "topRight",
                        "message" => "User: ".$username_record->username." has entered for: ".$examname
                    )
                );
            }
        }
    }

    /**
     * Description - Update the table entry. This uses some editable js library that
     *               automatically injects the return for you so instead of returning
     *               the json object return the updated contents ONLY.
     * @param type $params this will be an array of params that came from javascript/ajax
     *        row_id - table row id
     *        value - the content of the cell
     *        column - column number to update
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    public function updateStudentList($params)
    {
        global $DB, $USER, $CFG;

        $CFG->local_tcs_logging ? error_log("\n updateStudentList() -> What is params: ".print_r($params, 1)) : null;
        $id = isset($params->id) ? $params->id : null;
        $new_comment = isset($params->comment) ? $params->comment : null;
        $room = isset($params->room) ? $params->room : null;
        $username = isset($params->username) ? $params->username : null;
        // $username = isset($params->username) ? $params->username : null;
        // $exam_id = isset($params->exam_id) ? $params->exam_id : null;
        // $editedid = isset($params->row_id) ? $params->row_id : null;
        // $editedValue = isset($params->value) ? $params->value : null;
        // $columnindex = isset($params->column) ? $params->column : null;
        
        $record_update = new stdClass();
        $record_update->id = $id;
        // let's update the room
        if ($room) {
            $CFG->local_tcs_logging ? error_log("\n updateStudentList() -> room is going to be updated to: ". $room) : null;
            $record_update->room = $room;
            // $DB->update_record('local_tcms_student_entry', $record_update, false);
            // die(json_encode(array("success" => "true", "data" => $editedValue)));
            // die($editedValue);
        // } else {
            // $CFG->local_tcs_logging ? error_log("\n updateStudentList() -> ROOM IS NOT UPDATING") : null;
        }
        
        // let's update the comment
        if ($new_comment) {
            $CFG->local_tcs_logging ? error_log("\n updateStudentList() -> comment is going to be updated to: ". $new_comment) : null;
            $record_update->comments = $new_comment;
            // die(json_encode(array("success" => "true", "data" => $editedValue)));
            // die($editedValue);
        // } else {
            // $CFG->local_tcs_logging ? error_log("\n updateStudentList() -> COMMENT IS NOT UPDATING") : null;
        }

        $last_changed = time();
        $new_dash_hash = md5($last_changed);
        $this->tcslib->setSetting('dash_hash', $new_dash_hash);

        $record_update->last_changed = $last_changed;
        $DB->update_record('local_tcms_student_entry', $record_update, false);
        return array(
            // "success" => true,
            'msg_type' => 'success',
            "show_msg" => array(
                "title" => "OK",
                "position" => "topRight",
                "message" => "Comment for $username has been updated"
            ),
            // "writers_hash" => $new_writers_hash
            "dash_hash" => $new_dash_hash
        );   
    }

    /**
     * Description Remove user out of the system
     * @param type $params this will be an array of params that came from javascript/ajax
     *        id - student id
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    public function removeStudentFromList($params)
    {
        global $DB, $USER, $CFG;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> removeStudentFromList() -> START") : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> removeStudentFromList() -> params sent: ". print_r($params, 1)) : null;

        $rowid = isset($params->id) ? $params->id : null;
        $user_id = isset($params->user_id) ? $params->user_id : null;
        $exam_id = isset($params->exam_id) ? $params->exam_id : null;
        $is_local = isset($params->return_local) ? true : false;
        $username = isset($params->username) ? $params->username : null;
        $room = isset($params->room) ? $params->room : null;

        $last_changed = time();
        $new_dash_hash = md5($last_changed);
        
        $record_update = new stdClass();
        $record_update->id = $rowid;
        $record_update->finished = 1;
        $record_update->signed_out = time();
        $record_update->last_changed = $last_changed;
        
        /*
        if ($is_local) {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> removeStudentFromList() -> THIS IS LOCALLY CALLED") : null;
        } else {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> removeStudentFromList() -> THIS IS CALLED VIA AJAX") : null;
            // get the username to return to js, otherwise this is handled already in the
            // key card swipe process.
            $user_test_record = $DB->get_records_sql(
                'SELECT id, username, examid
                 FROM local_tcms_student_entry
                 WHERE id = ? AND finished = 2',
                array($user_id)
            );
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> removeStudentFromList() -> what is the student info to be removed: ".print_r($user_test_record, 1)) : null;
            // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> removeStudentFromList() -> what is the id from username: ".$user_test_record[$user_id]->username) : null;
            if (isset($user_test_record[$user_id])) {
                $username = $user_test_record[$user_id]->username;
            } else {
                // finished might be 0 (first time user) not sure why we have 0, 1 or 2????
                // let's just get the username so we don't get a jquery error
                $user_chunk = $DB->get_record_sql(
                    'SELECT username
                     FROM local_tcms_student_entry
                     WHERE id = ?',
                    array($user_id)
                );
                $username = $user_chunk->username;
            }
        }
        */
        if ($DB->update_record('local_tcms_student_entry', $record_update, false)) {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> signoutStd() -> user has successfully signed out") : null;
            
            // update the hash for all users currently in the Test Centre
            // $this->tcslib->setSetting("writers_hash", $new_writers_hash);

            // reset the dash hash, any watchers will be updated.
            $this->tcslib->setSetting('dash_hash', $new_dash_hash);

            // reset the table hash, any watchers will be updated.
            // $this->tcslib->setSetting('s_table_hash', "");

            return array(
                // "success" => true,
                'msg_type' => 'success',
                "show_msg" => array(
                    "title" => "OK",
                    "position" => "topRight",
                    "message" => $username . " has been removed."
                ),
                "row_id" => $rowid,
                "room" => $room,
                // "writers_hash" => $new_writers_hash
                "dash_hash" => $new_dash_hash
            );   
            // if (!$is_local) {
            //     die(json_encode(array("success" => "true", "entry_id" => $user_id, "username" => $username)));
            // } else {
            //     return true;
            // }

        } else {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> signoutStd() -> user has NOT successfully signed out") : null;
            return array(
                // "success" => false,
                'msg_type' => 'error',
                "show_msg" => array(
                    "title" => "Error",
                    "position" => "topRight",
                    "message" => "$username is showing they are already gone or never started."
                )
            );   
            // if (!$is_local) {
            //     $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> signoutStd() -> user has NOT signed out, err") : null;
            //     die(json_encode(array("success" => "false", "msg" => "Sorry, update did not work.")));
            // } else {
            //     return false;
            // }
        }
    }

    /**
     * Description - Simple way to grab debugging mode from settings to use in javascript console
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    public function isDebugging($params)
    {
        global $CFG;

        // $CFG->local_tcs_logging ? error_log("\nisDebugging has been called, what is CFG->local_tcs_logging: ".$CFG->local_tcs_logging) : null;

        if (isset($CFG->local_tcs_logging)) {
            // $CFG->local_tcs_logging ? error_log("\nisDebugging has been called and local_tcs_logging is set.") : null;
            if ($CFG->local_tcs_logging > 0) {
                die(json_encode(array("success" => "true", "debug" => $CFG->local_tcs_logging)));
            }
        }
        
        // $CFG->local_tcs_logging ? error_log("\nisDebugging local_tcs_logging is NOT set.") : null;
        die(json_encode(array("success" => "false")));
    }

    //======================================================================
    //======================================================================
    //======================================================================
    //======================================================================
    //======================================================================
    //======================================================================
    
    // Not sure if these function(s) are used somewhere else.....or not....?
    
    //======================================================================
    //======================================================================
    //======================================================================
    //======================================================================
    //======================================================================
    //======================================================================
    /**
     * Description - get list of open exams?
     * @param type $params this will be an array of params that came from javascript/ajax
     *        username - student username
     *        exam_id - the exam id
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    public function openExams($params)
    {
        global $DB, $USER;

        $user_name = isset($params['username']) ? $params['username'] : null;
        $exam_id = isset($params['exam_id']) ? $params['exam_id'] : null;

        $get_exam_record = $DB->get_record('local_tcms_student_entry', array('username' => $user_name, 'examid' => $exam_id));
        $examname_record = $DB->get_record('quiz', array('id' => $exam_id));
        $coursename_record = $DB->get_record('course', array('id' => $examname_record->course));

        $record_update = new stdClass();
        $record_update->id = $get_exam_record->id;
        $record_update->finished = 2;
        date_default_timezone_set('America/Denver');
        $record_update->signed_in = time();
        $record_update->signed_out = 0;
        //$record_update->comment = "Reopened";

        $DB->update_record('local_tcms_student_entry', $record_update, false);

        $row = array(
            'id' => $get_exam_record->id,
            'username' => $get_exam_record->username,
            'examname' => $coursename_record->fullname.'-'.$examname_record->name,
            'room' => $get_exam_record->room,
            'timesigned' => date('Y-m-d H:i:s', $record_update->signed_in),
            'comments' => $get_exam_record->comments
        );

        die(json_encode(array("success" => "true", "data" => $row)));
    }

    /**
     * Description: Delete a set of student records
     * @param type $params this will the username
     *        stds_to_be_finished - list of students
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    public function deleteStudents($params)
    {
        global $DB, $USER;
        $stds_to_be_deleted = isset($params['stds_to_be_deleted']) ? $params['stds_to_be_deleted'] : null;

        if (!$stds_to_be_deleted) {
            die(json_encode(array("success" => "false")));
        }

        $record_update = new stdClass();
        foreach ($stds_to_be_deleted as $stdid) {
            $record_update->id = $stdid;
            $record_update->finished = 1;
            $record_update->signed_out = time();
            
            //check if the record was reopened, if it was, update the comment by reopened.
            if ($is_reopened = $DB->get_record('local_tcms_student_entry', array('id'=> $stdid, 'finished' => 2))) {
                if ($is_reopened->comments) {
                    $record_update->comments = 'This was reopened beacuse '.$is_reopened->comments;
                } else {
                    $record_update->comments = 'This was reopened';
                }
            }
            
            if ($DB->update_record('local_tcms_student_entry', $record_update, false)) {
                die(json_encode(array("success" => "true")));
            } else {
                die(json_encode(array("success" => "false")));
            }
        }
    }

    /*      Field of Death for Functions     */

    /**
     * Description: Get the student record
     * @param type $params this will the username
     * @return json encoded data
     */
    /*
    public function loadStudentInfo($params){
        global $DB, $USER, $CFG;

        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentInfo() -> What is params: ".print_r($params,1)) : null;

        // set vars
        $uofl_id = isset($params['uoflid']) ? $params['uoflid'] : null;
        $courses = isset($params['courses']) ? $params['courses'] : null;
        $ajax = isset($params['ax']) ? true : false;
        $courses = null;
        $results = null;

        if($get_user = $DB->get_record('user', array('uofl_id' => (int)$uofl_id))){
            $record = array('username' => $get_user->username);
            $courses = $this->loadUserExams($record);
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentInfo() -> found student: ".$get_user->username." return their course (".count($courses).").") : null;
            $results = array('user' => $record, 'courses' => $courses);
            die(json_encode(array("success" => "true", "data" => $results)));
        } else {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadStudentInfo() -> DID NOT FIND student!!") : null;
            die(json_encode(array("success" => "false")));
        }
    }
    */

    /**
     * Description: A simple function to perform ajax tests
     * @param type $params - none
     * @return json encoded data
     */
    
    public function ajaxTest($params){
        global $DB, $USER, $CFG;
        // error_log("\n\n");
        // error_log("\najaxTest() -> ============>>>>>  START  <<<<<===========");
        return $this->getUsersInExam();
        // error_log("\najaxTest() -> ============>>>>>  Finished  <<<<<===========");
    }
}
