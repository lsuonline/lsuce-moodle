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

class Exams
{

    // private $current_site;
    // private $is_local;
    // private $term;
    // private $url_no_term;

    public function __construct()
    {
        $this->bugging = false;
         if (debugging()) {
            $this->bugging = true;
        }
    }

    public function unixToDate($unix_time)
    {
        // $opening_date = date('Y-m-d H:i:s', $get_exam_info->opening_date);
    }

    /**
     *  According to American English:
     *  Monday, September 6, 2019
     * 
     * TCMS Format Example
     * Saturday, March 18th, 2019 - 9:56am
     */
    public function dateToUnix($date_time)
    {
        // error_log("\nWhat is the date_time passed in: ". $date_time);
        $format = 'l, F jS, Y - g:ia';
        $tcs_format_test = DateTime::createFromFormat($format, $date_time);
        return $tcs_format_test->getTimestamp();
    }

    public function fetchManualStudentList(&$manual_exams_list) {
        global $DB;
        // simply get the list of students and add them to the data
        foreach ($manual_exams_list as &$get_exam) {
            $get_student_list = $DB->get_records_sql(
                'SELECT username
                FROM mdl_local_tcms_manual_exams
                WHERE manual_exam_id = ?',
                array($get_exam->id)
            );
            // error_log("\nWTF is the list: ". print_r($get_student_list, 1));
            $list_size = count($get_student_list);
            $get_student_list = array_values($get_student_list);
            $std_name_list = '';
            
            // if ($list_size == 0) {
            //     return '';
            // } else if ($list_size == 1) {
            //     return $get_student_list[0]->username;
            // }

            for($i = 0; $i < $list_size; $i++) {
            // foreach ($get_student_list as $std) {
                // $std_name_list .= $std->username.',';
                // Don't add the last comma as it BREAKS JSON conversions.
                if ($list_size == ($i + 1)) {
                    $std_name_list .= $get_student_list[$i]->username;
                } else {
                    $std_name_list .= $get_student_list[$i]->username.',';
                }
            }
            $get_exam->student_list = $std_name_list;
        }
    }    

    public function getExams($params)
    {
        return $this->getAllExams($params);
    }

    public function getAllOpenExams($params)
    {
        // $expired = true;
        $tmp = new stdClass();
        $tmp->expired = true;
        return $this->getAllExams($tmp);
    }

    public function getAllExams($params)
    {
        global $DB, $CFG;
        include_once("DateTimeLib.php");
        $dtl = new DateTimeLib();

        $expired = isset($params->expired) ? $params->expired : false;
        $range_start = isset($params->range_start) ? $params->range_start : null;
        $range_end = isset($params->range_end) ? $params->range_end : null;

        if ($this->bugging) {
            error_log("\n\n");
            error_log("Exams -> getAllExams() -> START\n");
            error_log("\n\n");
        }
        // ========================================
        // ALL Moodle exams
        
        $subnet_str = "";
        $expired_str = " AND date(to_timestamp(mq.timeclose)) >= (select date(now()))";
        $order_by = " ORDER BY closing_date DESC";

        if (isset($CFG->local_tcs_query_iprestricted_exams) && $CFG->local_tcs_query_iprestricted_exams == 1) {
            // error_log("CFG->local_tcs_query_iprestricted_exams subnet string is set and going to include in query");
            $subnet_str = " AND subnet like '%" . $CFG->local_tcs_quiz_ip_restriction . "%'";
        }
        
        if ($this->bugging) {
            error_log("\n");
            error_log("\nExams -> getAllExams() -> What is the subnet to be used: ". $subnet_str);
            error_log("\nExams -> getAllExams() -> What is CFG->local_tcs_query_iprestricted_exams: ". $CFG->local_tcs_query_iprestricted_exams);
            error_log("\nExams -> getAllExams() -> What is CFG->local_tcs_quiz_ip_restriction: ". $CFG->local_tcs_quiz_ip_restriction);
            error_log("\nExams -> getAllExams() -> What is the subnet to be used: ". $subnet_str);
            error_log("\n");
        }
        

        if ((isset($CFG->local_tcs_query_closed_exams) && 
            $CFG->local_tcs_query_closed_exams == 1) ||
            $expired == true ) {
            // error_log("CFG->local_tcs_query_closed_exams closed string is set and going to include in query");
            $expired_str = "";
        }

        $exam_query_1 = "SELECT quiz.id as exam_id, quiz.fullname as course_name,
                quiz.course_id as course_id,
                quiz.name as exam_name, 
                quiz.timeopen as opening_date, 
                quiz.timeclose as closing_date, 
                quiz.subnet as subnet, 
                (cast(quiz.timeclose as bigint) - cast(quiz.timeopen as bigint)) as time_diff,
                quiz.password as password,
                false::text AS manual,
                false::text AS finished,
                true::text AS visible,
                ''::text AS notes,
                ''::text AS student_list
            FROM (
                SELECT mq.id, 
                    mq.course, 
                    mq.timeopen, 
                    mq.timeclose, 
                    mq.password,
                    mq.name, 
                    mc.fullname,
                    mc.id as course_id,
                    CASE 
                        WHEN mq.subnet = '' THEN 'false'
                        ELSE 'true'
                    END AS subnet
                FROM mdl_quiz mq, mdl_course mc 
                WHERE 
                    mq.course = mc.id" . $subnet_str . $expired_str .
            ") as quiz" . $order_by;

        $get_moodle_exams = $DB->get_records_sql($exam_query_1);

        // foreach ($get_moodle_exams as &$temp_exam) {
        //     // error_log("\n\nWhat is temp_exam: ". $temp_exam);
        //     if ($temp_exam->subnet == "") {
        //         $temp_exam->subnet = false;
        //     } else {
        //         $temp_exam->subnet = true;
        //     }
        // }
        // unset($temp_exam);
        // foreach ($get_moodle_exams as $temp_exam_key => $temp_exam_val) {
        //     // error_log("\n\nWhat is temp_exam: ". $temp_exam);
        //     if ($temp_exam->subnet == "") {
        //         $temp_exam->subnet = false;
        //     } else {
        //         $temp_exam->subnet = true;
        //     }
        // }
        // unset($temp_exam);

        if ($this->bugging) {
            error_log("\n");
            error_log("Exams -> getAllExams() -> how many exams are there: ". count($get_moodle_exams));
            error_log("\n");
        }
        /*
            $get_moodle_exams - get ALL OPEN MOODLE Exams
            $moodle_manual_mix - get ALL Manual Exams where 'manual' == FALSE
            
            $get_manual_exams_info - get ALL Manual Exams where 'finished' == FALSE
            $get_finished_manual_exams - get ALL Manual Exams where 'finished' == TRUE

            NOW if there's an exam in Moodle Exams AND one in Manual Exams, remove it!
            (this is from adding extra special notes or something for a course, it has a custom
            addition)
            
            So the list will be as follows:
            $get_manual_exams_info
            + ($get_moodle_exams - $moodle_manual_mix)
            + $get_finished_manual_exams
            --------------------------------------------
            = The final list
        */

        // ========================================
        // ALL Manual exams
        $get_manual_exams_info = $DB->get_records_sql(
            // 'SELECT * id,course_name, exam_name, opening_date, closing_date, password, notes, finished, visible, manual
            // 'SELECT lte.id as exam_id, lte.* FROM mdl_local_tcms_exam lte' . $order_by
            // "(SELECT id as exam_id, closing_date, course_id, course_name, exam_name, finished, manual, notes, opening_date, password, visible
            // FROM mdl_local_tcms_exam lte" . $order_by . " WHERE manual='true')
            // UNION ALL
            // (SELECT * FROM mdl_local_tcms_exam WHERE manual='false')"
            
            "(SELECT id, course_id, course_name, id as exam_id, exam_name, opening_date, closing_date, password, notes, finished, visible, manual
            FROM mdl_local_tcms_exam lte 
            WHERE manual='true' AND finished='false')
            UNION ALL
            (SELECT * FROM mdl_local_tcms_exam WHERE manual='false' AND finished='false')". $order_by
        );
        
        if ($this->bugging) {
            error_log("\n");
            error_log("Exams -> get_manual_exams_info() -> how many exams are there: ". count($get_manual_exams_info));
            error_log("\n");
        }

        $get_finished_manual_exams = $DB->get_records_sql(
            "(SELECT lte.*, ''::text AS student_list, exam_id as exam_id FROM mdl_local_tcms_exam lte WHERE manual='false' AND finished='true')
            UNION ALL
            (SELECT  ltee.*, ''::text AS student_list, id as exam_id FROM mdl_local_tcms_exam ltee WHERE manual='true' AND finished='true')"
            . $order_by
        );

        if ($this->bugging) {
            error_log("\n");
            error_log("Exams -> get_finished_manual_exams() -> how many exams are there: ". count($get_finished_manual_exams));
            error_log("\n");
        }
        // Now Add the Student List for any Manual Exam that has one
        // $get_manual_exams_info
        // $get_finished_manual_exams
        $this->fetchManualStudentList($get_manual_exams_info);
        $this->fetchManualStudentList($get_finished_manual_exams);
        
        // We need to match any regular Moodle exam against any exam in the mdl_local_tcms_exam table
        // to see any custom notes, overrides, etc.
        $moodle_manual_mix = $DB->get_records_sql(
            "SELECT * FROM mdl_local_tcms_exam lte WHERE manual = 'false'"
        );
        // Now remove from the list of all Moodle exams
        foreach ($moodle_manual_mix as $yoink) {
            unset($get_moodle_exams[$yoink->exam_id]);
        }

        // done with moodle_manual_mix, so let's just remove it
        unset($moodle_mandual_mix);

        // - Need to merge manual and actual exams into 1 array.
        // - This means when creating manual exams need to auto populate for courses and exams. 
        // BUT Also need to create new exam titles which means NO EXAM ID and Course ID
        
        // foreach ($get_manual_exams_info as $get_manual_exam_info) {
        //     $manual_obj = clone $get_manual_exam_info;
        //     // $manual_obj->manual = true;
        //     $get_moodle_exams[] = $manual_obj;
        // }
        $final_exam_list = array_merge($get_manual_exams_info, $get_moodle_exams);
        // now add the 'finished' exams at the end
        $final_exam_list = array_merge($final_exam_list, $get_finished_manual_exams);
        // $final_exam_list = array_values($final_exam_list);
        $cc = 0;
        foreach($final_exam_list as &$this_exam) {
            $this_exam->id = $cc++;
        }


        // error_log("Exams -> getAllExams() -> What is the total course count now: ". count($final_exam_list));
        return array(
            'success' => true,
            'data' => $final_exam_list
        );
    }

    /*
    public function getManualExams($params)
    {
        // TODO: remove load_manual_exams.php
        global $DB, $USER;
        // Was going to add data functions here but for now just local. If other
        // classes need this then I'll move it.
        // include('TCSLib.php');
        $exam_id = isset($params['manualexam_id']) ? $params['manualexam_id'] : null;

        // $exam_id = $_POST["manualexam_id"];

        $get_exams = $DB->get_records_sql(
            'SELECT id,course_name, exam_name, opening_date, closing_date, password, notes
            FROM mdl_local_tcms_exam_pass where id = ?',
            array($exam_id)
        );

        $i = 0;
        $success = true;
        $msg = "";

        if (count($get_exams) == 0) {
            $success = false;
            $msg = "Sorry, this id: ". $exam_id. " did not return any results in the DB";
        }

        foreach ($get_exams as $get_exam) {
            $get_std_list = $DB->get_records_sql(
                'SELECT username
                FROM local_tcms_manual_exams
                WHERE manual_exam_id = ?',
                array($get_exam->id)
            );

            $std_name_list = '';
            foreach ($get_std_list as $std) {
                $std_name_list .= $std->username.',';
            }

            $rows[$i++] = array(
                'id' => $get_exam->id,
                'course_name' => $get_exam->course_name,
                'exam_name' => $get_exam->exam_name,
                'opening_date' => date('Y-m-d H:i:s', $get_exam->opening_date),
                'closing_date' => date('Y-m-d H:i:s', $get_exam->closing_date),
                'password' => $get_exam->password,
                'student_list'=> $std_name_list,
                'notes' => $get_exam->notes
            );
        }

        die(json_encode(array("success" => $success, "msg" => $msg, "data" => $rows)));
        // echo json_encode($rows);
    }
    */

    // TODO: remove add_manual_exams.php
    public function addManualExams($params)
    {
        global $DB, $USER;
        
        $course_name = isset($params->course_name) ? $params->course_name : null;
        $exam_name = isset($params->exam_name) ? $params->exam_name : null;
        $opening_date = isset($params->opening_date) ? $params->opening_date : null;
        $closing_date = isset($params->closing_date) ? $params->closing_date : null;
        $password = isset($params->password) ? $params->password : null;
        $notes = isset($params->notes) ? $params->notes : "";
        $student_list = isset($params->student_list) ? $params->student_list : null;
        
        $opening_date = $this->dateToUnix($opening_date);
        $closing_date = $this->dateToUnix($closing_date);
        
        if ($student_list != "") {
            $std_username_list = explode(',', $student_list);
        } else {
            $std_username_list = null;
        }

        //insert a row
        $record = new stdClass();
        $record->course_id = 0;
        $record->course_name = $course_name;
        $record->exam_name = $exam_name;
        $record->opening_date = $opening_date;
        $record->closing_date = $closing_date;
        $record->password = $password;
        $record->notes = $notes;
        $record->manual = "true";
        $record->finished = "false";
        $record->visible = "true";
        
        $success = true;
        $msg = "";
        
        $inserted_id = $DB->insert_record('local_tcms_exam', $record, true);
        
        if (!$inserted_id) {
            $msg_type = "error";
            $msg_title = "Ooops";
            $msg = "Error: Exam: ". $exam_name ." for ". $course_name ." was NOT added, please contact the Teaching Centre.";
        } else {
            $msg_type = "success";
            $msg_title = "Success";
            $msg = "Manual Exam: ". $exam_name ." for ". $course_name ." has been added.";
            // tag on the exam id to the record object
            $record->exam_id = $inserted_id;

            foreach ($std_username_list as $std_username) {
                if ($std_username != '') {
                    $loop_record = new stdClass();
                    $loop_record->manual_exam_id = $inserted_id;
                    $loop_record->username = $std_username;
                    $inserted_data_id = $DB->insert_record('local_tcms_manual_exams', $loop_record, true);
                }
            }
        }

        // add the student list to the data chunk to populate the UI Table Row
        $record->student_list = $student_list;
        
        return array(
            'msg_type' => $msg_type,
            'show_msg' => array(
                'title' => $msg_title,
                "position" => "topRight",
                'message' => $msg
            ),
            'data' => $record
        );
    }
    // This function is for Moodle Exams that have been added to the local_tcms_exam table
    // due to a note being added
    public function addUpdateMoodleExam($params)
    {
        // require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
        global $DB, $USER, $CFG;

        $exam_id = isset($params->exam_id) ? $params->exam_id : null;
        $course_id = isset($params->course_id) ? $params->course_id : null;
        $row_id = isset($params->row_id) ? $params->row_id : null;

        // error_log("\n AUME -> What is exam_id: ". $exam_id);
        // error_log("\n AUME -> What is course_id: ". $course_id);
        // error_log("\n AUME -> What is row_id: ". $row_id);

        $to_match = array(
            'exam_id' => $exam_id,
            'course_id' => $course_id
        );

        if ($found_record = $DB->get_record('local_tcms_exam', $to_match)) {
            // need to update
            // TODO:
            // error_log("\n What is the found record: ". print_r($found_record, 1));
            $found_record->notes = $params->notes;
            $inserted_id = $DB->update_record('local_tcms_exam', $found_record);

        } else {
            // nope, this doesn't exist yet so let's add it.
            $record_exam = clone $params;
            $record_exam->manual = "false";
            // $record_exam->visible = ($record_exam->visible == "true") ? 1 : 0;
            // $record_exam->finished = ($record_exam->visible == "true") ? 1 : 0;
            
            unset($record_exam->id);
            unset($record_exam->row_id);
            
            // error_log("\nWhat is record exam: ". print_r($record_exam, 1));
            // $record_exam->exam_id = $exam_id;
            // $record_exam->status = $status;
    
            $inserted_id = $DB->insert_record('local_tcms_exam', $record_exam, true);
        }
    
        if ($inserted_id) {
            $msg_type = "success";
            $title = "Success";
            $message = "Moodle Exam has been updated.";
            
        } else {
            $msg_type = "error";
            $title = "Ooops";
            $message = "ERROR, the exam status has NOT been saved";
        }

        return array(
            'msg_type' => $msg_type,
            'show_msg' => array(
                'title' => $title,
                "position" => "topRight",
                'message' => $message
            )
        );
    }

    // TODO: remove update_manual_exam_status.php
    public function updateMoodleExamStatus($params)
    {
        // require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
        global $DB, $USER, $CFG;

        $exam_id = isset($params['exam_id']) ? $params['exam_id'] : null;
        $status = isset($params['status']) ? $params['status'] : null;

        $exam_is_in_record = $DB->get_record('local_tcms_exam_status', array('exam_id'=> $exam_id));

        $success = false;
        $msg = null;
        $data = null;

        if (!$exam_is_in_record) {
            $record_exam = new stdClass();
            $record_exam->exam_id = $exam_id;
            $record_exam->status = $status;

            $inserted_id = $DB->insert_record('local_tcms_exam_status', $record_exam, true);

            if ($inserted_id) {
                $data = $inserted_id;
                $success = true;
                $msg = "The exam status has successfully been saved";
            } else {
                $success = false;
                $msg = "ERROR, the exam status has NOT been saved";
            }

        } else {
            $record_update = new stdClass();
            $record_update->id = $exam_is_in_record->id;
            $record_update->status = $status;

            if ($DB->update_record('local_tcms_exam_status', $record_update, false)) {
                $success = true;
                $msg = "The exam status has successfully been updated";
            } else {
                $success = false;
                $msg = "ERROR, the exam status failed in the update.";
            }
        }

        die(json_encode(array("success" => $success, "msg" => $msg, "data" => $data)));
    }

    public function dummy() {
        return array(
            'msg_type' => "success",
            'show_msg' => array(
                'title' => "Dummy Msg",
                "position" => "topRight",
                'message' => "This is a dummy msg"
            )
        );
    }
    // TODO: remove add_manual_exams.php
    public function updateExam($params)
    {
        global $DB, $USER;

        if (debugging()) {
            error_log("\n\neditManualExam() -> START");
        }

        // Let's copy the object and use it for the DB 
        $record_update = clone $params;
        // Row ID is the UI table row id
        $row_id = isset($params->row_id) ? $params->row_id : null;
        /* Exam ID is one of two things
            1. If it's a Moodle Exam the exam_id will be the quiz id
            2. If it's a Manual Exam the exam_id will be the database column id
        */
        $exam_id = isset($params->exam_id) ? $params->exam_id : null;
        $student_list = isset($params->student_list) ? $params->student_list : false;
        $manual = $params->manual;
        
        // let's deal with the possibility of the property "manual"
        // if (isset($record_update->manual)) {
        //     if ($record_update->manual == "true") {
        //         $record_update->manual = 1;
        //     } else {
        //         $record_update->manual = 1;
        //     }
        //     // unset($record_update->manual);
        // } else {
        //     $record_update->manual = 0;
        // }
        
        // Need to make sure that we have exam id and row id (row id for js table)
        if (is_null($row_id) || is_null($exam_id)) {
            return array(
                'msg_type' => 'error',
                'show_msg' => array(
                    'title' => 'Error: Data Missing',
                    "position" => "topRight",
                    'message' => "Data was missing in your request, please contact the Teaching Centre"
                )
            );
        }

        $record_update->id = $params->exam_id;
        unset($record_update->row_id);
        unset($record_update->exam_id);
        unset($record_update->manual);


        // adjust the dates to UNIX TS as they will be in human readable form.
        if (isset($params->opening_date)) {
            $params->$opening_date = $this->dateToUnix($params->opening_date);
        }
        if (isset($params->closing_date)) {
            $params->$closing_date = $this->dateToUnix($params->closing_date);
        }
        
        // // Setup Obj for DB Update
        // $record_update = new stdClass();
        // $record_update->id = $exam_id;
        // $record_update->course_name = $course_name;
        // $record_update->exam_name = $exam_name;
        // $record_update->opening_date = $opening_date;
        // $record_update->closing_date = $closing_date;
        // $record_update->password = $password;
        // $record_update->notes = $notes;


        // $course_name = isset($params->course_name) ? $params->course_name : null;
        // $exam_name = isset($params->exam_name) ? $params->exam_name : null;
        // $opening_date = isset($params->opening_date) ? $params->opening_date : null;
        // $closing_date = isset($params->closing_date) ? $params->closing_date : null;
        // $password = isset($params->password) ? $params->password : null;
        // $notes = isset($params->notes) ? $params->notes : null;
        // $student_list = isset($params->student_list) ? $params->student_list : null;
        
        

        // opening and closing date will have the following format:
        // Saturday, March 18th, 2019 - 9:56am
        // need to convert to unix timestamp
        // $opening_date = $this->dateToUnix($opening_date);
        // $closing_date = $this->dateToUnix($closing_date);
        
        // error_log("\n\neditManualExam() -> exam_id: ". $exam_id);
        // error_log("\n\neditManualExam() -> course_name: ". $course_name);
        // error_log("\n\neditManualExam() -> exam_name: ". $exam_name);
        // error_log("\n\neditManualExam() -> opening_date: ". $opening_date);
        // error_log("\n\neditManualExam() -> closing_date: ". $closing_date);
        // error_log("\n\neditManualExam() -> password: ". $password);
        // error_log("\n\neditManualExam() -> notes: ". $notes);
        // error_log("\n\neditManualExam() -> student_list: ". $student_list);
        
        
        // is this a manually added exam or a quiz with an edit?
        if ($manual == "false") {
            // ok, this quiz is a regular quiz and we need to see if it exists or not.
            // if not found let's add it to the tcms_exam table
            $is_found = $DB->get_record('local_tcms_exam', array('id' => $exam_id));
            if (!$is_found) {
                error_log("\nDo stuff here.....");
                // if ($DB->update_record('local_tcms_exam', $record_update, false)) {

                // }

            }
        } else {
            $success_msg = false;
            $student_list_updated = false;
            // let's not update just yet, print out dummy
            // error_log("\nWhat is the data to be updated: ". print_r($params, 1));

            // Because the student list is outside of this table we need to extract it
            if ($student_list != false) {
                // at this point the object size is at LEAST size of 2
                // when we unset the student_list (as it's not in this table)
                // it'll be either size 1 or >1
                unset($record_update->student_list);
                $student_list_updated = true;
                // ======================================================================
                // ======================================================================
                $std_name_list = explode(',', $student_list);
                // It's possible that students can be added or removed from this list
                // rather than checking each one and let's just purge and add the fresh 
                // list
                // $get_manual_exam = $DB->get_record_sql(
                //     'SELECT status FROM mdl_local_tcms_exam where id = ?',
                //     array($record_update->id)
                // );
                // $status = $get_manual_exam->status;
                    // $manualexam_id = $exam_id;
                    // $exam_id = intval($exam_id);
                // }
                $select = 'manual_exam_id = '. $record_update->id;
                $params = null;
                $DB->delete_records_select('local_tcms_manual_exams', $select, $params);
                
                for ($i = 0; $i < sizeof($std_name_list); $i++) {
                    if ($std_name_list[$i] != '') {
                        $record = new stdClass();
                        $record->manual_exam_id = $record_update->id;
                        $record->username = $std_name_list[$i];
                        $inserted_data_id = $DB->insert_record('local_tcms_manual_exams', $record, true);
                    }
                }
                // ======================================================================
                $success_msg = true;
            }


            $updated_obj_size = count(get_object_vars($record_update));
            // ok, at this point we either are updating the student_list
            // OR student list AND some other column.
            
            
            
            // Now, let's check if any data needs to be updated
            if ($updated_obj_size > 1) {
                // there is other data to be updated
                if (!$DB->update_record('local_tcms_exam', $record_update, false)) {
                    $success_msg = "partial";
                } else {
                    $success_msg = true;
                }
            }
                // we have updated the student list and there's no further updating to be done.



            if ($success_msg == true) {
                $msg = "Successfully updated the manual exam.";
                $msg_type = 'success';
                $msg_title = 'OK';
                $msg_switch = '';
            } else if ($success_msg == "partial") {
                if( $student_list_updated) {
                    $msg = "The list of students was successfully update BUT the other fields were NOT.";
                    $msg .= " Please try again or contact the Teaching Centre.";
                } else {
                    $msg = "Sorry, one of the fields didn't update.";
                    $msg .= " Please try again or contact the Teaching Centre.";
                }

                $msg_type = 'warning';
                $msg_title = 'WARNING';
                $msg_switch = '';
            } else {
                $msg = "Failed to update manual exam";
                $msg_type = 'error';
                $msg_title = 'FAIL';
                $msg_switch = 'NOT ';
            }
        }
        
        
        // let's add the row_id now to update the row
        $record_update->id = $row_id;
        if ($student_list != false) {
            // Special case: student list is seperate, need to 
            // add back in.
            $record_update->student_list = $student_list;
        }

        return array(
            'msg_type' => $msg_type,
            'show_msg' => array(
                'title' => $msg_title,
                "position" => "topRight",
                'message' => $msg
            ),
            'data' => $record_update
        );
    }
}
