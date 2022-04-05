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
/**
 * Summary.
 *
 * Description.
 *
 * @since x.x.x
 *
 */
class StatsController
{
    public function __construct()
    {
        // global $CFG;
        // $CFG->local_tcs_logging ? error_log("\n Stats -> constructor()") : null;
        include 'StatsModel.php';
        $this->QR = new StatsModel();
    }

    /**
     * Get the number of students in the Test Centre
     * 
     * @param none
     * @return int the number of students in the Test Centre.
     */
    public function getCurrentStudents()
    {
        global $CFG;
        // ========================================================================
        // Let's get the number of students currently in the TCS
        $get_number_writers_qry = $this->QR->queryCurrentStudents();

        // get_record_sql returns an obj
        return $get_number_writers_qry->exams_no;
    }

    /**
     * Exams Scheduled for Only Today
     * - This query is for all exams that are scheduled to be in the Test Centre as it'll have
     * an IP restriction on the quiz.
     * @param none
     * @return int The number of exams for the day.
     */
    public function getScheduledExamsToday()
    {
        global $CFG;
        // $CFG->local_tcs_logging ? error_log("\n ========================>>>> getScheduledExamsToday -> START <<<<========================") : null;
        // ========================================================================
        // Scheduled Exams Today 
        $todays_exam_count_qry = $this->QR->queryScheduledExamsToday();
        
        $ecount = count($todays_exam_count_qry);
        // $CFG->local_tcs_logging ? error_log("\n getScheduledExamsToday -> What is the count: ". $ecount) : null;
        return $ecount ? $ecount : 0;
    }

    /**
     * Exams Scheduled for Only Today
     *
     * @param none
     * @return int The number of exams for the day.
     */
    public function getAllExamsWrittenToday()
    {
        $written_today_qry = $this->QR->queryAllExamsWrittenOnDay();

        return $written_today_qry->count;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getExamsWrittenToday()
    {
        // ========================================================================
        // Exams Written Today 
        $written_today_qry = $this->QR->queryExamsWrittenToday();
        
        return $written_today_qry->count;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getTotalWrittenExams()
    {
        // ========================================================================
        // Total written exams this semester
        $written_exams_so_far_qry = $this->QR->queryTotalWrittenExams();

        // get_record_sql returns an obj
        return $written_exams_so_far_qry->count;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function countAllExams()
    {
        global $DB;
        // ========================================================================
        // All exams in Moodle
        $all_exams = $this->QR->queryCountAllExams();
        
        return $all_exams;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getUniqueStudents()
    {
        // ========================================================================
        // All exams in Moodle
        // return 99;
        $all_exams = $this->QR->queryUniqueStudents();
        
        return $all_exams;
    }


    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getCurrentRoomCount()
    {
        global $DB;
        // ========================================================================
        // Let's get the number of students currently in the TCS
        $room_occupance = $this->QR->queryCurrentRoomCount();

        return $room_occupance;
    }
    

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getTotalRoomCount()
    {
        // ========================================================================
        // Let's get the number of students currently in the TCS
        $room_occupance = $this->QR->queryTotalRoomCount();

        return $room_occupance;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getAverageTime()
    {
        global $CFG;
        // $CFG->local_tcs_logging ? error_log("\n ========================>>>> getAverageTime -> START <<<<========================") : null;
        
        $users = $this->QR->queryAverageTime();

        $counter = 0;
        $total_count = 0;
        $result = 0;
        foreach($users as $user) {
            $counter++;
            $total_count += $user->signed_out - $user->signed_in;
        }

        // error_log("What is the count for this avg time stat: ". $counter);
        // error_log("What is the total count for this avg time stat: ". $total_count);
        if ($counter > 1) {
            $result = round(($total_count / $counter) / 60, 2);
            // error_log("What is the avg time: ". $result);
        }

        return $result . " minutes";
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function processExamLogs($search = 0, $sort = 0, $order = 0, $offset = 0, $limit = 0)
    {
        // error_log("\nSTATSCONTROLLER -> processExamLogs() ------->>>>>> START <<<<<<-------");
        $package = array();

        // Count the number of finished exams
        $std_entry_count = $this->QR->queryTotalWrittenExams();

        // Get all students that have finished their exam and all Manual Exams
        $get_stds_from_std_entry = $this->QR->queryFinishedStudentExams($sort, $order);

        // FROM mdl_local_tcms_student_entry', array($limit)
        foreach ($get_stds_from_std_entry as $std_from_std_entry) {
            $exam_type = intval($std_from_std_entry->exam_type);
            if ($exam_type == 1) {

                // $get_stds_info = $DB->get_records_sql(
                //     'SELECT distinct course_name, exam_name, opening_date, closing_date, password, notes, finished, visible, manual
                //     FROM mdl_local_tcms_exam ep where ep.id = ?',
                //     array($std_from_std_entry->examid)
                // );

                $get_stds_info = $this->QR->queryExamResults($std_from_std_entry->examid);

                foreach ($get_stds_info as $get_std_info) {
                    $timesigned = date('Y-m-d H:i:s', strtotime($std_from_std_entry->signintime));
                    $timesignedout='';
                    if (!$std_from_std_entry->signouttime) {
                        $timesignedout = 'Not Yet Set';
                    } else {
                        $timesignedout = date('Y-m-d H:i:s', strtotime($std_from_std_entry->signouttime));
                    }

                    $examfullname = $get_std_info->quiz_fullname.$get_std_info->exam_name;

                    $rowid = 'manualexamid'.$std_from_std_entry->id;
                    $checkboxvalue = 'manualexamid'.$std_from_std_entry->id;

                   /* $mform->addElement('html', "<tr id = $std_from_std_entry->id><td>$std_from_std_entry->id</td><td>$std_from_std_entry->username</td><td>$examfullname</td><td>$timesigned</td><td>$timesignedout</td><td>$std_from_std_entry->room</td><td>$std_from_std_entry->status</td><td>$std_from_std_entry->comments</td></tr>");*/
        
                   $temp = array(
                       'id' => $std_from_std_entry->id,
                       'username' => $std_from_std_entry->username,
                       'exam' => $examfullname,
                       'signed_in' => $timesigned,
                       'signed_out' => $timesignedout,
                       'notes' => $get_std_info->notes
                    );
                    array_push($package, $temp);
                    // $mform->addElement('html', "<tr id = $std_from_std_entry->id><td>$std_from_std_entry->username</td><td>$examfullname</td><td>$timesigned</td><td>$timesignedout</tr>");
                }

            } else {

                $get_stds_info = $this->QR->queryManualExamResults($std_from_std_entry->examid);

                foreach ($get_stds_info as $get_std_info) {
                    $timesigned = $std_from_std_entry->signintime;

                    $timesignedout = '';
                    if (!$std_from_std_entry->signouttime) {
                        $timesignedout = 'Not Yet Set';
                    } else {
                        $timesignedout = $std_from_std_entry->signouttime;
                    }

                    $examfullname = $get_std_info->coursename.$get_std_info->examname;
                    $rowid = 'moodleexamid'.$std_from_std_entry->id;
                    $checkboxvalue = 'moodleexamid'.$std_from_std_entry->id;
                    /*$mform->addElement('html', "<tr id = $std_from_std_entry->id><td>$std_from_std_entry->id</td><td>$std_from_std_entry->username</td><td>$examfullname</td><td>$timesigned</td><td>$timesignedout</td><td>$std_from_std_entry->room</td><td>$std_from_std_entry->status</td><td>$std_from_std_entry->comments</td></tr>");*/
                    $temp = array(
                       'id' => $std_from_std_entry->id,
                       'username' => $std_from_std_entry->username,
                       'exam' => $examfullname,
                       'signed_in' => $timesigned,
                       'signed_out' => $timesignedout
                    //    'notes' => $get_std_info->notes
                    );
                    array_push($package, $temp);
                    // $mform->addElement('html', "<tr id = $std_from_std_entry->id><td>$std_from_std_entry->username</td><td>$examfullname</td><td>$timesigned</td><td>$timesignedout</td></tr>");
                }
            }
        }

        // error_log("\n\nStatsController -> processExamLogs() -> What is the final total: ".$std_entry_count);
        // error_log("\n\nStatsController -> processExamLogs() -> What is the rows: ".$package);
        

        return array(
            "total" => $std_entry_count,
            "rows" => $package
        );
    }


    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getExamBreakDown($course_id = 0, $quiz_attempts, $exam_name, $course_shortname)
    {
        global $DB;
        include_once("DateTimeLib.php");

        $dtl = new DateTimeLib();

        if ($course_id == 0) {
            return false;
        }

        // error_log("\n\nWhat is the course_shortname: ". $course_shortname);
        // $exam_stats = new stdClass();
        /* Need to replicate this below
        array('table_data' => array(
            array('table_data_chunk' => 'Exam'),
            array('table_data_chunk' => 'Avg. Time'),
            array('table_data_chunk' => 'Written'),
            array('table_data_chunk' => 'Attempts Allowed??')
        )),
        */
        $exam_stats = array(
            'table_data' => array()
        );
        // error_log("\n\nWhat is the exam passed in: ". print_r($quiz_attempts, 1));

        // number of students
        // "SELECT COUNT u.id, u.idnumber, u.username, u.firstname, u.lastname, u.email, u.uofl_id

        $peeps_in_course = $this->QR->queryCountStudentsClass($course_id);

        // looking to find average time
        $total_time = 0;
        $total_time_count = 0;
        $fastest_time = 9999999999999999;
        $slowest_time = 0;
        $still_writing = 0;
        $written = 0;
        $students_completed_attempt = array();
        foreach ($quiz_attempts as $attempt) {
            if ($attempt->attempt > 0) {
                // is someone writing right this moment?
                if ($attempt->timefinish == 0 || $attempt->timefinish == "0" || $attempt->timefinish == false) {
                    $still_writing++;
                } else {
                    // how many have written the exam
                    $written++;

                    // let's add the student to an array if they have attempted the quiz
                    if (!in_array($attempt->userid, $students_completed_attempt)) {
                        $students_completed_attempt[] = $attempt->userid;
                    }
                    // STOPPED HERE, Need to find if the student has already made an 
                    // attempt on a multi attempt quiz
                    
                    // average
                    $attempt_time = $attempt->timefinish - $attempt->timestart;
                    // keep count of student attempts
                    $total_time += $attempt_time;
                    // record count
                    $total_time_count++;
                    // Longest (smaller number)
                    ($attempt_time < $fastest_time) ? $fastest_time = $attempt_time : null;
                    // Shortest (bigger number)
                    ($attempt_time > $slowest_time) ? $slowest_time = $attempt_time : null;
                }
            }
            // $time_breakdown = $this->epochTimeDiff($attempt->timestart, $attempt->timefinish);
        }

        // Exam, Class Count, Still Writing, Total Time Count, Fastest Time, Slowest Time, Average Time

        // Exam Name
        $exam_stats['table_data'][] = array('table_data_chunk' => $course_shortname ." - ".$exam_name);

        // How many students in this class
        $exam_stats['table_data'][] = array('table_data_chunk' => $peeps_in_course);

        // students writing atm
        // $exam_stats['table_data'][] = array('table_data_chunk' => $still_writing);
        
        // Attempts
        $exam_stats['table_data'][] = array('table_data_chunk' => count($students_completed_attempt));

        // number of attempts
        $exam_stats['table_data'][] = array('table_data_chunk' => $written);
        // $exam_stats['table_data'][] = array('table_data_chunk' => $total_time_count);

        // Fastest written attempt
        if ($fastest_time == 9999999999999999) {
            $exam_stats['table_data'][] = array('table_data_chunk' => 0);
        } else {
            $exam_stats['table_data'][] = array('table_data_chunk' => $dtl->epochPretty($fastest_time));
        }

        // Longest written attempt
        if ($slowest_time == 0) {
            $exam_stats['table_data'][] = array('table_data_chunk' => 0);
        } else {
            $exam_stats['table_data'][] = array('table_data_chunk' => $dtl->epochPretty($slowest_time));
        }

        // Average Time on attempts
        if ($total_time_count > 1) {
            $exam_stats['table_data'][] = array('table_data_chunk' => $dtl->epochPretty($total_time / $total_time_count));
        } else {
            $exam_stats['table_data'][] = array('table_data_chunk' => 0);
        }
        // $time_breakdown = $this->epochTimeDiff($attempt->timestart, $attempt->timefinish);

        // attempts allowed (maybe)
        return $exam_stats;
    }


    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getLiveExamsGraph1()
    {
        $exams_today = $this->QR->queryLiveExamsGraph1();

        /*
        Func Test course
        Exam id:    1778
        Exam Name:  Midterm Exam
        Course id:  2451

        mdl_quiz_attempts
            timestart
            timefinish
            state = finish (could check for 'inprogress')
            attempt


        Enrollment count in each Course
        =================================================
        */

        $live_exams = array(
            'header_title' => 'Live Exams Today',
            'header_colspan' => 7,
            'table_head' => array(
                array('table_head_chunk' => 'Exam'),
                array('table_head_chunk' => 'Class Count'),
                array('table_head_chunk' => 'Student Attempts'),
                array('table_head_chunk' => 'Total Attempts'),
                array('table_head_chunk' => 'Fastest Time'),
                array('table_head_chunk' => 'Slowest Time'),
                array('table_head_chunk' => 'Average Time')
            ),
            'table_row' => array()
                // array('table_data' => array(
                //     array('table_data_chunk' => 'Exam'),
                //     array('table_data_chunk' => 'Class Count'),
                //     array('table_data_chunk' => 'Student Attempts'),
                //     array('table_data_chunk' => 'Total Attempts'),
                //     array('table_data_chunk' => 'Fastest Time'),
                //     array('table_data_chunk' => 'Slowest Time'),
                //     array('table_data_chunk' => 'Average Time')
                // ))
            // )
        );

        foreach ($exams_today as $exam) {
            // get exam quiz attempts
            $this_exam = $this->QR->queryQuizAttempts($exam->exam_id);
            
            // get details of this exam
            $temp_facker = $this->getExamBreakDown($exam->course_id, $this_exam, $exam->exam_name, $exam->course_shortname);
            
            $live_exams['table_row'][] = $temp_facker;

            unset($temp_facker);
        }
        return $live_exams;
    }

    /**
     * Table full of General Stats
     *
     * This table will have general stats and display the following:
           Exams to be written this week
           Exams to be written today
           Potential students today
           Average Time to write
           Today's Fastest Written Exam
           Today's Longest Written Exam
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function getLiveExamsGraph2()
    {
        global $DB;

        $exams_written_today = $this->QR->queryAllExamsWrittenOnDay();
        $exams_written_today_count = count($exams_written_today);
        
        // now let's count the number of students in each course
        $student_course_counts = 0;
        $student_attempt_counts = 0;
        $still_writing = 0;
        foreach($exams_written_today as $dis_course) {
            $student_course_counts += $this->QR->queryCountStudentsClass($dis_course->course_id);
            $att = $this->QR->queryQuizAttempts($dis_course->exam_id);

            $students_completed_attempt = array();

            // ==============================
            foreach ($att as $attempt) {
                if ($attempt->attempt > 0) {
                    // is someone writing right this moment?
                    if ($attempt->timefinish == 0 || $attempt->timefinish == "0" || $attempt->timefinish == false) {
                        $still_writing++;
                    } else {
                        // let's add the student to an array if they have attempted the quiz
                        if (!in_array($attempt->userid, $students_completed_attempt)) {
                            $students_completed_attempt[] = $attempt->userid;
                        }
                    }
                }
            }

            // ==============================

            // error("\n\nWhat is the attempt count: ". print_r($att, 1));
            // error("\n\nWhat is the attempt count: ". count($att));
            $student_counts += count($students_completed_attempt);
            $student_attempt_counts += count($att);
            unset($students_completed_attempt);
        }

        return array(
            'header_title' => 'General Stats',
            'header_colspan' => 2,
            'table_head' => array(
                // 'table_head_chunk' => 'Exam',
                // 'table_head_chunk' => 'Class Count',
                // 'table_head_chunk' => 'Student Attempts',
                // 'table_head_chunk' => 'Total Attempts',
                // 'table_head_chunk' => 'Fastest Time',
                // 'table_head_chunk' => 'Slowest Time',
                // 'table_head_chunk' => 'Average Time'
            ),
            'table_row' => array(

                array('table_data' => array(
                    array('table_data_chunk' => 'Exams running this week'),
                    array('table_data_chunk' => count($this->QR->queryAllExamsWrittenWeek()))
                )),
                array('table_data' => array(
                    array('table_data_chunk' => 'Exams running today'),
                    array('table_data_chunk' => $exams_written_today_count)
                )),
                array('table_data' => array(
                    array('table_data_chunk' => 'Max potential students to write today'),
                    array('table_data_chunk' => $student_course_counts)
                )),
                array('table_data' => array(
                    array('table_data_chunk' => 'Students written at least 1 attempt today'),
                    array('table_data_chunk' => $student_counts)
                )),
                array('table_data' => array(
                    array('table_data_chunk' => 'Attempts today'),
                    array('table_data_chunk' => $student_attempt_counts)
                )),
                array('table_data' => array(
                    array('table_data_chunk' => 'Attempt is progress now'),
                    array('table_data_chunk' => $still_writing)
                ))
                /*,
                array('table_data' => array(
                    array('table_data_chunk' => 'Today\'s Fastest Written Exam'),
                    array('table_data_chunk' => '10 minutes')
                )),
                array('table_data' => array(
                    array('table_data_chunk' => 'Today\'s Longest Written Exam'),
                    array('table_data_chunk' => '240 minutes')
                ))
                */
            )
        );
    }


    public function getGraphData1($range = 7)
    {
        global $DB;
        $graph_array = array();
        $neg_range = $range * -1;
        $exam_count = array();
        $student_count = array();

        for ($i = 0; $i < ($range * 2); $i++) {
            $exams_written_today = $this->QR->queryAllExamsWrittenOnDay($i - $range);

            // now let's count the number of students in each course
            $student_course_counts = 0;
            $student_attempt_counts = 0;
            $still_writing = 0;
            foreach($exams_written_today as $dis_course) {
                $student_course_counts += $this->QR->queryCountStudentsClass($dis_course->course_id);
            }

            // $graph_array[$i + $range] = array(
            //     'exams' => count($exams_written_today),
            //     'students' => $student_course_counts
            // );
            $exam_count[] = count($exams_written_today);
            $student_count[] = $student_course_counts;
        }

        // error_log("\n\n======>>>  GRAPH DATA  <<<=======");
        // error_log("\nWhat is exam_count: ". print_r($exam_count, 1));
        // error_log("\nWhat is student_count: ". print_r($student_count, 1));

        return array(
            'exam_count' => $exam_count,
            'student_count' => $student_count
        );
    }

    public function getLongestTime()
    {
        global $DB;
    }
    public function getFastestTime()
    {
        global $DB;
    }
    public function placeholder()
    {
        return 80085;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function countStudentsClass($course_id = 0)
    {
        global $CFG;

        $student_count = $this->QR->queryCountStudentsClass($course_id);

        return $student_count;
    }
}
