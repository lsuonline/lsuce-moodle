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

class TcmsAjax
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
        // local_utools_tcms_extra_instance
        // global $CFG;

        // $ulethlib->printToLog("Hello World, this is some info, blah blah blah", 0);
        // $ulethlib->printToLog("Test 4", "", true);
        $this->ulethlib->printToLog("\n TcmsAjax -> getTcmsUrl() -> what is the url: ", $CFG->local_utools_tcms_extra_instance);

        die(json_encode(array("success" => true, "data" => $CFG->local_utools_tcms_extra_instance)));
    }

    /**
     * Description - Get the current number of students that are in the centre.
     * @param type $params - nothing needs to be passed in.
     * @return json msg with the count
     */
    public function getCurrentStudentCount($params = null)
    {
        // local_utools_tcms_extra_instance
        global $DB, $CFG;
        $this->ulethlib->printToLog("\n TcmsAjax -> getCurrentCount() -> going to find current students.");

        $get_number_exams = $DB->get_record_sql(
            'SELECT count(*) as exams_no 
            FROM mdl_local_tcms_student_entry
            WHERE (deleted = 0 OR deleted = 2)'
        );

        $this->ulethlib->printToLog("\n TcmsAjax -> getCurrentCount() -> number of students is: ", $get_number_exams->exams_no);
        $this->ulethlib->printToLog("\n TcmsAjax -> getCurrentCount() -> RETURNING", "tcms");

        die(json_encode(array("success" => true, "data" => array('count' => $get_number_exams->exams_no, 'seats' => $CFG->local_tcms_seat_size_count))));
    }


    /**
     * Description - Get the number of exams written throughout the day by the hour
     * @param type $params - nothing needs to be passed in.
     * @return json msg with the count
     */
    public function getFullDayCounts($params = null)
    {
        global $DB;
        $date = isset($params['date']) ? $params['date'] : null;
        $this->ulethlib->printToLog("\n TcmsAjax -> getFullDayCounts() -> what is the date: ", $date, true);

        $get_week_hour_stats = $DB->get_records_sql(
            'SELECT date_part(\'hour\', to_timestamp(signed_in)) as hr, count(*)
            FROM mdl_local_tcms_student_entry ltse
            WHERE  date(to_timestamp(ltse.signed_in)) between date(?) and date(?)
            GROUP BY date_part(\'hour\', to_timestamp(signed_in))',
            array($date, $date)
        );

        if ($get_week_hour_stats) {
            $i = 0;
            foreach ($get_week_hour_stats as $get_week_hour_stat) {
                $rows[$i++] = array('count' => $get_week_hour_stat->count, 'hr_index' =>$get_week_hour_stat->hr);
            }
        } else {
            $rows[0] = array('count' => 0, 'hr_index' =>0);
        }
        $this->ulethlib->printToLog("\n TcmsAjax -> getFullDayCounts() -> what is the results: ", $rows, true);

        die(json_encode($rows));
    }
    /**
     * Description - Get the current number of exams scheduled for today.
     * @param type $params - nothing needs to be passed in.
     * @return json msg with the count
     */
    public function getExamCount($params = null)
    {
        global $DB, $CFG;
        $non_manual_count = 0;
        $manual_count = 0;

        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> going to get today's total exam count.", "tcms");

        //Moodle exam
        $get_exams_info = $DB->get_records_sql(
            'SELECT quiz.id as id, quiz.fullname as coursename, 
            quiz.name as examname, 
            quiz.timeopen as opening_date, 
            quiz.timeclose as closing_date, 
            quiz.password as password
            FROM (SELECT mq.id, 
                         mq.course, 
                         mq.timeopen, 
                         mq.timeclose, 
                         mq.password,
                         mq.name, 
                         mc.fullname 
                 FROM mdl_quiz mq, mdl_course mc 
                 WHERE subnet like \'%' . $CFG->local_tcms_quiz_ip_restriction . '%\'
                 AND mq.course = mc.id
                 AND date(to_timestamp(mq.timeopen)) <= (select date(now()))  
                 AND date(to_timestamp(mq.timeclose)) >= (select date(now()))) as quiz'
        );

        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> non manual query done.", "tcms");

        $non_manual_count = count($get_exams_info);
        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> count is: ", $non_manual_count);
        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> exam count obj is: ", $get_exams_info, true);

        //Manual exam
        $get_manual_exams_info = $DB->get_records_sql(
            'SELECT id,coursename, examname, opening_date, closing_date, password, notes, status
            FROM mdl_local_tcms_exam_pass 
            WHERE date(to_timestamp(opening_date)) <= (select date(now())) AND date(to_timestamp(closing_date)) >= (select date(now()))'
        );
        
        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> manual query done.", "tcms");

        $manual_count = count($get_manual_exams_info);

        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> manual count is: ", $non_manual_count);
        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> manual count obj is: ", $get_manual_exams_info, true);

        $this->ulethlib->printToLog("\n TcmsAjax -> getExamCount() -> RETURNING", "tcms");
        
        die(json_encode(array("success" => true, "data" => $non_manual_count + $manual_count)));

        // TODO: need to actually get the count within certain days. Either 1 day or many.....

        // foreach ($get_exams_info as $get_exam_info) {
        //     if ($get_exam_info->opening_date>0 && $get_exam_info->closing_date>0) {
        //         $opening_date = date('Y-m-d H:i:s', $get_exam_info->opening_date);
        //         $closing_date = date('Y-m-d H:i:s', $get_exam_info->closing_date);
        //     } else {
        //         $opening_date = 'Not Yet Set';
        //         $closing_date = 'Not Yet Set';
        //     }
        //     $get_exams_status = $DB->get_record_sql(
        //         'SELECT status,notes
        //          FROM mdl_local_tcms_exam_status
        //          WHERE  examid=?',
        //         array($get_exam_info->id)
        //     );
        //     $moodle_exam_status_id='moodleexamstatusselect'.$get_exam_info->id;
        //     $result = json_decode(json_encode($get_exams_status), true);
        //     $exam_status=$result['status'];
        //     $exam_notes=$result['notes'];
        //     $moodle_exam_row_id='moodleexamid'.$get_exam_info->id;

        
        //     if ($exam_status == '1') {
        //          $mform->addElement('html', "<tr id = $moodle_exam_row_id style=\"color: blue\"><td>$get_exam_info->coursename</td><td>$get_exam_info->examname</td><td>$opening_date</td><td>$closing_date</td></tr>");
        //     }

        // }
      
        // foreach ($get_manual_exams_info as $get_manual_exam_info) {
        //     if ($get_manual_exam_info->opening_date>0 && $get_manual_exam_info->closing_date>0) {
        //         $opening_date = date('Y-m-d H:i:s', $get_manual_exam_info->opening_date);
        //         $closing_date = date('Y-m-d H:i:s', $get_manual_exam_info->closing_date);
        //     } else {
        //         $opening_date = 'Not Yet Set';
        //         $closing_date = 'Not Yet Set';
        //     }
        //     $editButtonId='editbutton'.$get_manual_exam_info->id;
        //     $deleteButtonId='deletebutton'.$get_manual_exam_info->id;
        //     $rowId='row'.$get_manual_exam_info->id;
        //     $manual_exam_status_id='manualexamstatusselect'.$get_manual_exam_info->id;
        //     if ($get_manual_exam_info->status == '1') {
        //          $mform->addElement('html', "<tr id=$rowId><td>$get_manual_exam_info->coursename</td><td>$get_manual_exam_info->examname</td><td>$opening_date</td><td>$closing_date</td></tr>");
        //     }
        // }
    }

    public function getExamsWritten($params = null)
    {
        global $DB, $CFG;
        // $current_date = date('Y-m-d H:i:s', time());

        $this->ulethlib->printToLog("\n TcmsAjax -> getExamsWritten() -> START.", "tcms");
        
        date_default_timezone_set('America/Edmonton');
        // $exam_start = strtotime(date('Y-m-d') . ' 9:00:0');
        // $exam_done = strtotime(date('Y-m-d') . ' 9:00:0') + (1 * 12 * 60 * 60);
        // $exam_done2 = strtotime(date('Y-m-d') . ' 9:00:0') + (1 * 12 * 60 * 60);

        $exam_start = strtotime('today 9:00am');
        $exam_done = strtotime('today 9:00pm');

        $this->ulethlib->printToLog("\n TcmsAjax -> getExamsWritten() -> what is exam start: ", $exam_start);
        $this->ulethlib->printToLog("\n TcmsAjax -> getExamsWritten() -> what is exam end: ", $exam_done);

        $exam_count = $DB->get_records_sql(
            'SELECT count(*) as exams_no
             FROM mdl_local_tcms_student_entry
             WHERE signed_in > ? AND signed_out < ?',
            array($exam_start, $exam_done)
        );

        // reset the array key values to get index of zero otherwise it'll just be the count for the index
        $exam_count = array_values($exam_count);

        if (count($exam_count) > 0) {
            $this->ulethlib->printToLog("\n TcmsAjax -> getExamsWritten() -> How many exams scheduled for today: ", $exam_count[0]->exams_no);
            die(json_encode(array("success" => true, "data" => $exam_count[0]->exams_no)));
        } else {
            $this->ulethlib->printToLog("\n TcmsAjax -> getExamsWritten() -> Gettingexams scheduled for today FAILED", "tcms");
            die(json_encode(array("success" => true, "data" => 0)));
        }
    }

    public function getTotalExamsWritten($params = null)
    {
        global $DB, $CFG;
        // $current_date = date('Y-m-d H:i:s', time());
        $count = 0;

        $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> START.", "tcms");
        
        date_default_timezone_set('America/Edmonton');
        // are both the start and end dates set in settings?
        if (isset($CFG->local_utools_tcms_total_exams_start) && isset($CFG->local_utools_tcms_total_exams_end)) {
            $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> both configs are set, using them to calc.", "tcms");
            $exam_start = strtotime($CFG->local_utools_tcms_total_exams_start . ' 9:00:0');
            $exam_done = strtotime($CFG->local_utools_tcms_total_exams_end . ' 9:00:0') + (1 * 12 * 60 * 60);
        } else {
            // fall back to just today's date.
            $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> config SETTINGS FAILED, using today.", "tcms");
            $exam_start = strtotime(date('Y-m-d') . ' 9:00:0');
            $exam_done = strtotime(date('Y-m-d') . ' 9:00:0') + (1 * 12 * 60 * 60);
        }

        $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> what is exam start: ", $exam_start);
        $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> what is exam end: ", $exam_done);

        // $exam_count = $DB->get_records_sql(
        //     'SELECT count(*) as exams_no
        //      FROM mdl_local_tcms_exam_pass
        //      WHERE opening_date < ? AND closing_date > ?',
        //     array($exam_start, $exam_done)
        // );

        $exam_count = $DB->get_records_sql(
            'SELECT count(*) as exams_no
             FROM mdl_local_tcms_student_entry
             WHERE signed_in < ? AND signed_out > ?',
            array($exam_done, $exam_start)
        );

        foreach ($exam_count as $obj) {
            $count += $obj->exams_no;
        }

        $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> TOTAL count of exams: ", $count);
        $this->ulethlib->printToLog("\n TcmsAjax -> getTotalExamsWritten() -> RETURNING.", "tcms");

        die(json_encode(array("success" => true, "data" => $count)));
    }

    // TODO: merge the curl into one call
    public function callCarlAndSayHi($params = null)
    {
        global $DB, $CFG;

        $date = isset($params['date']) ? $params['date'] : null;
        $stat_type = isset($params['stat_type']) ? $params['stat_type'] : null;
        $page_to_call = isset($params['page']) ? $params['page'] : null;

        $prefix = 'http://';
        $site = $CFG->wwwroot;

        // error_log("What is the cfg for tcms instance: " . $CFG->local_utools_tcms_extra_instance);
        if (isset($CFG->local_utools_tcms_extra_instance)) {
            $site = $CFG->local_utools_tcms_extra_instance;
        }
        // error_log("What is the site tcms instance: " . $site);

        // $findme = 'uleth.ca/201501';

        $findme = '201501';
        $findmeprod = 'uleth.ca/';

        $pos = strpos($site, $findme);
        $prod_pos = strpos($site, $findmeprod);

        if ($pos !== false) {
            $tcms = 'testcentre_ms';
        }

        if ($prod_pos !== false) {
            // the use might be on local machine but hitting the stack, not local stack
            $site = 'https://' . $site;
        }

        $full_url = $site . 'local/' . $tcms . '/' . $page_to_call;

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $full_url,
            CURLOPT_USERAGENT => 'TCMS stats request',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'stat_type' => $stat_type,
                'date' => $date
            )
        ));
        // Send the request & save response to $resp
        $final_data = curl_exec($curl);
        curl_close($curl);
        die($final_data);
    }


    // TODO: merge the curl into one call
    public function getExternalSiteData($params = null)
    {

        global $DB, $CFG;

        $prefix = 'http://';
        $site = $CFG->wwwroot;
        $tcms = 'tcms';
        $student_count = 0;
        $seats = 48;

        if (isset($CFG->local_utools_tcms_extra_instance)) {
            $site = $CFG->local_utools_tcms_extra_instance;
        }

        $findme = '201501';
        $findmeprod = 'uleth.ca/';

        $pos = strpos($site, $findme);
        $prod_pos = strpos($site, $findmeprod);

        if ($pos !== false) {
            $tcms = 'testcentre_ms';
        }

        if ($prod_pos !== false) {
            // the use might be on local machine but hitting the stack, not local stack
            $site = 'https://' . $site;
        }

        $full_url = $site . 'local/' . $tcms . '/tc_load_meter.php';
        // error_log("What is the full url to curl: ".$full_url);

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $full_url,
            CURLOPT_USERAGENT => 'TCMS stats request',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'param' => 'update_meter',
            )
        ));
        // Send the request & save response to $resp
        $user_count = curl_exec($curl);
        // $this->ulethlib->printToLog("\n TcmsAjax -> getExternalSiteData() -> What is user count: ", $user_count);
        
        $user_count = json_decode($user_count);
        $student_count = (int)$user_count;

        curl_close($curl);

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $full_url,
            CURLOPT_USERAGENT => 'TCMS stats request',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'param' => 'load_meter_face',
            )
        ));
        // Send the request & save response to $resp
        $total_seats = curl_exec($curl);
        
        $total_seats = json_decode($total_seats);
        $seats = (int)$total_seats;

        // Close request to clear up some resources
        // error_log("What is the seat count result from curl: " . $total_seats);
        curl_close($curl);

        die(json_encode(array("success" => true, "data" => array('count' => $student_count, 'seats' => $seats))));

    }
}
