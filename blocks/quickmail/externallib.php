<?php

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
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . "/externallib.php");


/**
 * Local - Test Centre Management System
 *
 * @package     block_tcs
 * @copyright   2019 David Lowe <david.lowe@uleth.ca>
 * @author.     2019 David Lowe <david.lowe@uleth.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_quickmail_external extends external_api {
    
    /*
     * Returns description of method parameters
     * @return external_function_parameters
     *
    public static function hello_world_parameters() {
        // error_log("\nhello_world_parameters() -> FRACK-YA START");
        return new external_function_parameters(
            array(
                'welcomemessage' => new external_value(
                    PARAM_TEXT,
                    'The welcome message. By default it is "Hello world,"'
                )
            )
        );
    }
    */
    /*
     * Returns welcome message
     * @return string welcome message
     *
    public static function hello_world($welcomemessage) {
        error_log("\nhello_world() -> FRACK-YA");
        global $USER;

        
        $params = self::validate_parameters(self::hello_world_parameters(),
                array('welcomemessage' => $welcomemessage));

        // error_log("\n\nWhat are the params: ". print_r($params, 1));
        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);


        // error_log("\n\nWhat is the message passed in: ". $welcomemessage);
        // error_log("\nOk, going to pass some vars back");
        // error_log("\ndo we have a name btw......? ". $USER->firstname);


        $params = [
            'test0' => 99,
            'test1' => $USER->firstname,
            'test2' => $welcomemessage
        ];

        return $params;
    }
    */
    /*
     * Returns description of method result value
     * @return external_description
     *
    public static function hello_world_returns() {
        // error_log("\nhello_world_returns() -> FRACK-YA FINISHED");
        // return new external_value(PARAM_TEXT, 'The welcome message + user first name');
        return new external_single_structure(
            array(
                'test0' => new external_value(PARAM_INT, 'Result status: 0|1 (SUCCESS|FAILURE).')
                // 'test1' => new external_value(PARAM_TEXT, 'The validated user username.'),
                // 'test2' => new external_value(PARAM_TEXT, 'Error message on failure; empty on success.')
            )
        );
    }
    */

    // **********************************************************************
    // **********************************************************************
    /*
     * Returns description of method parameters
     * @return external_function_parameters
     *
    public static function tcsAjax_parameters() {
        // error_log("\n tcsAjax_parameters() -> FRACK-YA START");
        return new external_function_parameters(
            array(
                'datachunk' => new external_value(
                    PARAM_TEXT,
                    'Encoded Params"'
                )
            )
        );
    }
    */

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function qmAjax($datachunk) {
        // error_log("\n tcsAjax() -> FRACK-YA");
        // error_log("\n tcsAjax() -> Do we have a data chunk: ". print_r($datachunk, 1));
        global $CFG, $USER;
        // include('lib/StudentListAjax.php');

        // $stud = new StudentListAjax();
        // $params = self::validate_parameters(self::tcs_ajax_parameters(),
        //         array('datachunk' => $datachunk));

        // error_log("\n\nWhat are the params: ". print_r($params, 1));
        // //Context validation
        // //OPTIONAL but in most web service it should present
        // $context = get_context_instance(CONTEXT_USER, $USER->id);
        // self::validate_context($context);

        $datachunk = json_decode($datachunk);

        $class_obj = isset($datachunk->class) ? $datachunk->class : null;
        $function = isset($datachunk->call) ? $datachunk->call : null;
        $params = isset($datachunk->params) ? $datachunk->params : null;
        // error_log("\n tcsAjax() -> what is chunk n dunk: ". print_r($datachunk, 1));
        // check if we are sending GET data....
        // $class_obj = isset($_GET['class']) ? $_GET['class'] : null;
        // $function = isset($_GET['call']) ? $_GET['call'] : null;
        // $params = isset($_GET['params']) ? $_GET['params'] : null;
        
        // $class_obj = isset($input['class']) ? $input['class'] : null;
        // $function = isset($input['call']) ? $input['call'] : null;
        // $params = isset($input['params']) ? $input['params'] : null;
        
        // if class_obj is null then we must check POST data....
        // if (!isset($class_obj)) {
        //     $CFG->local_tcs_logging ? error_log("hmmmm, ") : null;
        //     $class_obj = isset($_POST['class']) ? $_POST['class'] : null;
        //     $function = isset($_POST['call']) ? $_POST['call'] : null;
        //     $params = isset($_POST['params']) ? $_POST['params'] : 'none';
        // }

        // // Alrighty, strike 2! Let's check GET now....
        // if (!isset($class_obj)) {
        //     $CFG->local_tcs_logging ? error_log("hmmmm, ") : null;
        //     $class_obj = isset($_GET['class']) ? $_GET['class'] : null;
        //     $function = isset($_GET['call']) ? $_GET['call'] : null;
        //     $params = isset($_GET['params']) ? $_GET['params'] : 'none';
        // }

        // $CFG->local_tcs_logging ? error_log("Here's what has been passed in:") : null;
        // $CFG->local_tcs_logging ? error_log("Which php file (class): ".$class_obj) : null;
        // $CFG->local_tcs_logging ? error_log("what function: ".$function) : null;
        
        if (!isset($params)) {
            // $CFG->local_tcs_logging ? error_log("The params: ".print_r($params, 1)) : null;
        // } else {
            // $CFG->local_tcs_logging ? error_log("No params have been sent!") : null;
            $params = array("empty" => "true");
        }


        // it could be either GET or POST, let's check......
        if (isset($class_obj)) {
            // $CFG->local_tcs_logging ? error_log("\nAJAX.php => include this file: ".$class_obj.".php") : null;
            include_once('lib/'.$class_obj.'.php');
            $qmajax = new $class_obj();
            // $CFG->local_tcs_logging ? error_log("\nAJAX.php => object is ready") : null;
        // } else {
            // $CFG->local_tcs_logging ? error_log("\nAJAX.php => Rejected, no file specified!!!") : null;
            // die (json_encode(array("success" => "false")));
        }

        // now let's call the method
        $ret_obj_data = null;
        if (method_exists($qmajax, $function)) {
            // $CFG->local_tcs_logging ? error_log("\nAJAX.php => Success, now calling ".
                // $function." from ".get_class($qmajax).".php") : null;

            $ret_obj_data = call_user_func(array($qmajax, $function), $params);
            // $CFG->local_tcs_logging ? error_log("\nAJAX.php => Done\n") : null;
        // } else {
            // $CFG->local_tcs_logging ? error_log("\nAJAX.php => Rejected, method does not exist!!!") : null;
            // die (json_encode(array("success" => "false")));
        }

        $ret_json_data = [
            'data' => json_encode($ret_obj_data)
        ];
        return $ret_json_data;
    }

    /*
     * Returns description of method result value
     * @return external_description
     *
    public static function tcsAjax_returns() {
        // error_log("\n tcsAjax_returns() -> FRACK-YA FINISHED");
        // return new external_value(PARAM_TEXT, 'The welcome message + user first name');
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'JSON encoded goodness')
                // 'test1' => new external_value(PARAM_TEXT, 'The validated user username.'),
                // 'test2' => new external_value(PARAM_TEXT, 'Error message on failure; empty on success.')
            )
        );
    }
    */
    /*
    public static function get_grade_returns() {
        return new external_single_structure(
            array(
                'item' => new external_single_structure(
                    array(
                        'courseid' => new external_value(PARAM_INT, 'Course id'),
                        'categoryid' => new external_value(PARAM_INT, 'Grade category id'),
                        'itemname' => new external_value(PARAM_RAW, 'Item name'),
                        'itemtype' => new external_value(PARAM_RAW, 'Item type'),
                        'idnumber' => new external_value(PARAM_INT, 'Course id'),
                        'gradetype' => new external_value(PARAM_INT, 'Grade type'),
                        'grademax' => new external_value(PARAM_FLOAT, 'Maximum grade'),
                    ), 'An array of items associated with the grade item', VALUE_OPTIONAL
                ),
                'grades' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'userid' => new external_value(PARAM_INT, 'Student ID'),
                            'grade' => new external_value(PARAM_FLOAT, 'Student grade'),
                        )
                    ), 'An array of grades associated with the grade item', VALUE_OPTIONAL
                ),
            )
        );
    }
    */
    // **********************************************************************
    // **********************************************************************
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    /*public static function loadUserExams_parameters() {
        return new external_function_parameters(
            array(
                'isnum' => new external_value(
                    PARAM_BOOL,
                    'simple num check, false by default',
                    VALUE_DEFAULT,
                    false
                ),
                'username' => new external_value(
                    PARAM_TEXT,
                    'The users username',
                    VALUE_DEFAULT,
                    ''
                )
            )
        );
    }
    */

    /**
     * Description Get all of the exams that a student 
     * @param type $params this will be an array of params that came from javascript/ajax
     *        isnum - flag to say it's an id number as all params that come across will be strings
     *        userid - user id
     *        ax - this func was called via ajax or by other method
     * @return json encoded array with success as true or false, the data and any necessary message
     */
    /*
    public function loadUserExams($isnum = false, $username) {
        global $DB, $USER, $CFG;

        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is params: ".print_r($params, 1)) : null;
        
        $rows = null;
        $uofl_id = null;
        $get_user = null;
        
        // $isnum = isset($params['isnum']) ? $params['isnum'] : null;
        // $username = isset($params['userid']) ? $params['userid'] : null;
        // $ajax = isset($params['ax']) ? true : false;
        $ajax = false;
        
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is ajax set: ".$ajax) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is isnum set: ".$isnum) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is username set: ".$username) : null;

        // let's make sure that the quiz ip to compare to is sufficient
        if (strlen($CFG->local_tcs_quiz_ip_restriction) < 6) {
            return array(
                "success" => "false",
                // 'data' => array(),
                'msg' => "Sorry but the Quiz Settings IP Restriction is too short!"
            );
        }

        if ($isnum) {
            $uofl_id = isset($params['userid']) ? $params['userid'] : null;
            // $get_user = $DB->get_record('SELECT username FROM mdl_user where uofl_id=?', array($uofl_id));
            $get_user = $DB->get_record('user', array('uofl_id' => (int)$uofl_id));
            if (!$get_user) {
                $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> NO user id found: ".print_r($get_user, 1)) : null;
                die(json_encode(array("success" => "false", 'msg' => 'Sorry, that id was not found matching '.$uofl_id.'!', 'extra' => array('username' => $username, 'uofl_id' => $uofl_id))));
            }
            $username = $get_user->username;
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> isnum is set so we have id of: ".$uofl_id." and username of: ".$username) : null;
        } else {
            // $get_user = $DB->get_record('SELECT uofl_id FROM mdl_user where username=?', array($username));
            $get_user = $DB->get_record('user', array('username' => $username));
            // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is get_user obj: ".print_r($get_user, 1)) : null;
            if (!$get_user) {
                $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> NO username found: ".print_r($get_user, 1)) : null;
                die(json_encode(array("success" => "false", 'msg' => 'Sorry, no usernames were found matching '.$username.'!', 'extra' => array('username' => $username, 'uofl_id' => $uofl_id))));
            }
            $uofl_id = $get_user->uofl_id;
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> isnum is NOT set so we have id of: ".$uofl_id." and username of: ".$username) : null;
        }

        // are they already in the system?
        $student_entered = $this->loadStudentStatus(array('username' => $username));
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> is the student already in? : ".$student_entered) : null;
        if ($student_entered) {
            // student is in the tcs already, check them out.
            $user_test_record = $DB->get_records_sql(
                'SELECT username, id
                 FROM mdl_local_tcs_std_entry
                 WHERE username = ? AND (deleted = 2 OR deleted = 0)',
                array($username)
            );
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is the student info to be removed: ".print_r($user_test_record, 1)) : null;
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> what is the id from username: ".$user_test_record[$username]->id) : null;
            $is_removed = $this->removeStudentFromList(array('id' => $user_test_record[$username]->id, 'return_local' => 1));
            
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUserExams() -> have removed user now, what is result: ".$is_removed) : null;
            if ($is_removed) {
                return array(
                    "success" => "true",
                    // 'data' => array(),
                    'extra' => array(
                        'username' => $username,
                        'swipe_remove' => true,
                        'entry_id' => $user_test_record[$username]->id
                    )
                );
            } else {
                return array(
                    "success" => "false",
                    // 'data' => array(),
                    'extra' => array(
                        'username' => $username,
                        'swipe_remove' => true,
                        'entry_id' => $user_test_record[$username]->id
                    )
                );
            }
        }

        $crazy_sql = 'SELECT CAST(mq.id AS text) AS id,
                   mu.uofl_id AS uoflid,
                   fullname || \'-\' || mq.name AS examname
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
               AND (mq.subnet LIKE \'%\' || ? || \'%\' OR ? LIKE \'%\' || mq.subnet || \'%\')
               AND (SELECT extract(epoch FROM now())) <= mq.timeclose
               AND NOT EXISTS (SELECT \'Y\'
                      FROM mdl_quiz_attempts mqa
                     WHERE mqa.attempt = mq.attempts
                       AND mqa.userid = mu.id
                       AND mqa.quiz = mq.id)
             UNION
            SELECT \'ManualExam-\' || tcep.id AS id,
                   mu.uofl_id AS uoflid,
                   \'ManualExam-\' || tcep.coursename || \'-\' || tcep.examname AS examname
              FROM mdl_local_tcs_exam_pass tcep
             INNER JOIN mdl_local_tcs_manualexam_std me
                ON (tcep.id = me.manual_examid)
             INNER JOIN mdl_user mu
                ON (mu.username = me.username)
             INNER JOIN mdl_quiz mq
                ON (me.manual_examid = mq.id)
             WHERE me.username = ?
               AND (SELECT extract(epoch FROM now())) <= tcep.closing_date
               AND NOT EXISTS (SELECT \'Y\'
                      FROM mdl_quiz_attempts mqa
                     WHERE mqa.attempt = mq.attempts
                       AND mqa.userid = mu.id
                       AND mqa.quiz = mq.id)
             ORDER BY examname ASC';
            
            $get_moodle_exams = $DB->get_records_sql($crazy_sql, array(
                $username,
                $CFG->local_tcs_quiz_ip_restriction,
                $CFG->local_tcs_quiz_ip_restriction,
                $username
            ));

        // );

        // error_log("\n");
        // error_log("\nWhat is the query to be run: ". $crazy_sql);
        // error_log("\nWhat is the username: ". $username);
        // error_log("\nWhat is the local_tcs_quiz_ip_restriction: ". $CFG->local_tcs_quiz_ip_restriction);

        if ($get_moodle_exams == null) {
            return array(
                "success" => "false",
                'msg' => 'Sorry, no exams were found!',
                'data' => array(),
                'extra' => array(
                    'username' => $username,
                    'uofl_id' => $uofl_id
                )
            );

        } else {
            $counter = 0;
            foreach ($get_moodle_exams as $get_moodle_exam) {
                $rows[] = array(
                    'exrow' => $counter,
                    'label' => $get_moodle_exam->examname,
                    'value' =>$get_moodle_exam->id,
                    'uoflid' => $get_moodle_exam->uoflid
                );
                $counter++;
            }

            return array(
                "success" => "true",
                "data" => $rows,
                'extra' => array(
                    'username' => $username,
                    'uofl_id' => $uofl_id
                )
            );
        }
    }
    */

    /**
     * Returns description of method result value
     * @return external_description
     */
    /*
    public static function loadUserExams_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_TEXT, 'Result status: (true|false).'),
                'msg' => new external_value(PARAM_TEXT, 'Any message to send to client.'),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'exrow' => new external_value(PARAM_INT, 'row counter'),
                            'label' => new external_value(PARAM_TEXT, 'The exam name'),
                            'value' => new external_value(PARAM_INT, 'The exam id'),
                            'uoflid' => new external_value(PARAM_INT, 'The users id')
                        )
                    )
                ),
                'extra' => new external_single_structure(
                    array(
                        'username' => new external_value(PARAM_INT, 'username of user'),
                        'uofl_id' => new external_value(PARAM_INT, 'UofL id')
                    )
                )
            )
        );
    }
    */
    // **********************************************************************
    // **********************************************************************
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    /*
    public static function loadUsers_parameters() {
        return new external_function_parameters(
            array(
                'page' => new external_value(
                    PARAM_INT,
                    'The page we are on',
                    VALUE_DEFAULT,
                    false
                ),
                'pagetotal' => new external_value(
                    PARAM_TEXT,
                    'The users username',
                    VALUE_DEFAULT,
                    ''
                )
            )
        );
    }
    */
    /**
     * Description: Get all the users once and store in browser for this session to have fast autocomplete
     * @param type $params - none
     * @return json encoded array with success as true or false, the data and any necessary message
     *         data - all the students
     */
    /*
    public function loadUsers($page, $pagetotal)
    {
        global $DB, $USER, $CFG;
        include('lib/StudentListAjax.php');


        $i = 0;
        
        // $page = isset($params['page']) ? $params['page'] : null;
        // $pagetotal = isset($params['total']) ? $params['total'] : 0;
        // $local_call = isset($params['local_call']) ? $params['local_call'] : 0;
        
        ini_set('memory_limit', '512M');
        $CFG->local_tcs_logging ? error_log("\n") : null;
        // $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> what is params: ".print_r($params, 1)) : null;
        
        $memlimit = ini_get('memory_limit');

        $total_user_count = $DB->get_record_sql(
            'SELECT count(id) from mdl_user'
        );
        $total_user_count = $total_user_count->count;

        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> total user count is: ". print_r($total_user_count, 1)) : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> total user count is: ". $total_user_count) : null;

        $tack_on_offset = '';

        if ($page && $page > 0) {
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> page is set and greater than 0") : null;
            
            $partial = (int)($total_user_count / $pagetotal);
            $add_limit =  $partial * $page;
            $offset = $partial * ($page - 1);

            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> partial is: ". $partial) : null;
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> add_limit is: ". $add_limit) : null;
            $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> offset is: ". $offset) : null;
            
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
            'SELECT u.username
            FROM mdl_user as u
            JOIN (
                SELECT userid from mdl_role_assignments
                WHERE roleid <> 3
                GROUP BY userid
            ) AS ra ON ra.userid = u.id
            WHERE u.policyagreed = 1
            ORDER BY u.username'
        );
        
        foreach ($get_users as $get_user) {
           $rows[$i++] = array("id" => $i, "name" => $get_user->username);
        }

        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> going to return list now: ") : null;
        $CFG->local_tcs_logging ? error_log("\nStudentListAjax -> loadUsers() -> what is the size of the list: ". count($get_users)) : null;

        // if ($local_call) {
            // return $get_users;
            $this_val = array('data' => $rows);
            // error_log("\n loadUsers() -> What is the rows obj: ". print_r($this_val, 1));
        return $this_val;
        // } else {
            // return array("success" => "true", "data" => $rows)));
        // }
    }
    */
    /**
     * Returns description of method result value
     * @return external_description
     */
    /*
    public static function loadUsers_returns() {
        return new external_single_structure(
            array(
                // 'success' => new external_value(PARAM_TEXT, 'Result status: (true|false).'),
                // 'msg' => new external_value(PARAM_TEXT, 'Any message to send to client.'),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'users id'),
                            'name' => new external_value(PARAM_TEXT, 'The username')
                        )
                    )
                )
            )
        );
    }
    */
    // **********************************************************************
    // **********************************************************************
    
    // **********************************************************************
    // **********************************************************************
    /*
    public static function hello_world_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     *
    public static function hello_world($welcomemessage = 'Hello world, ') {
        global $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::hello_world_parameters(),
                array('welcomemessage' => $welcomemessage));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        return $params['welcomemessage'] . $USER->firstname ;;
    }

    /**
     * Returns description of method result value
     * @return external_description
     *
    public static function hello_world_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }
    */
    // **********************************************************************
    // **********************************************************************

}
