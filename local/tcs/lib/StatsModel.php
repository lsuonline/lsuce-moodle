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
class StatsModel
{
    // private $DB;

    public function __construct()
    {
        // $CFG->local_tcs_logging ? error_log("\n Stats -> constructor()") : null;
        // $this->DB = $DB;
        // $this->CFG = $CFG;
    }

    /**
     * Get the number of students in the Test Centre
     * 
     * @param none
     * @return int the number of students in the Test Centre.
     */
    public function queryCurrentStudents()
    {
        global $DB, $CFG;
        // ========================================================================
        // Let's get the number of students currently in the TCS
        $get_number_writers_qry = $DB->get_record_sql(
            'SELECT count(*) as exams_no 
            FROM mdl_local_tcms_student_entry
            WHERE (finished = 0 OR finished = 2)'
        );
        // get_record_sql returns an obj
        return $get_number_writers_qry;
    }

    /**
     * Exams Scheduled for Only Today in the TEST CENTRE
     * - This query is for all exams that are scheduled to be ONLY in the Test Centre as it'll have
     * an IP restriction on the quiz. (haha, which is currently removed)
     * @param none
     * @return int The number of exams for the day.
     */
    public function queryScheduledExamsToday()
    {
        global $DB, $CFG;
        // $this->CFG->local_tcs_logging ? error_log("\n ========================>>>> getScheduledExamsToday -> START <<<<========================") : null;
        // ========================================================================
        // Scheduled Exams Today 
        $todays_exam_count_qry = $DB->get_records_sql(
            'SELECT quiz.id as id, quiz.fullname as coursename, 
            quiz.name as examname, 
            quiz.timeopen as opening_date, 
            quiz.timeclose as closing_date, 
            quiz.password as password
        FROM (
            SELECT mq.id, mq.course, mq.timeopen, mq.timeclose, mq.password,mq.name, mc.fullname 
            FROM mdl_quiz mq, mdl_course mc 
            WHERE mq.course = mc.id
                AND date(to_timestamp(mq.timeopen)) <= now()::date
                AND date(to_timestamp(mq.timeclose)) >= now()::date
            ) as quiz'
        );

        // usually have an ip restriction on, taken out for COVID
        // WHERE subnet like \'%' . $CFG->local_tcs_quiz_ip_restriction . '%\'

        return $todays_exam_count_qry;
    }

    /**
     * ALL Exams Scheduled for Only Today
     * This is for all exams in Moodle, not just Test Centre IP restricted
     *
     * @param This will be plus or minus number of days
     *        OR
     *        string - the date format in yyyy-mm-dd
     * @return int The number of exams for the day.
     */
    public function queryAllExamsWrittenOnDay($current = 0)
    {
        global $DB;
        // error_log("\n\nStatsModel->queryAllExamsWrittenOnDay() -> START \n\n");

        // Let's see if we are getting exams for today or some other day
        if ($current == 0) {
            $current = 'CURRENT_TIMESTAMP';
        } else if (gettype($current) == "string") {
            $current = 'date '. $current;
        } else {
            if ($current > 0) {
                $current = "(SELECT CURRENT_TIMESTAMP + INTERVAL '$current day')";
            } else {
                $current *= -1;
                $current = "(SELECT CURRENT_TIMESTAMP - INTERVAL '$current day')";
            }
        }

        // error_log("\nWhat is the time to use for date: ". $current);

        // This is for all exams in Moodle, not just Test Centre IP restricted
        $written_today_qry = $DB->get_records_sql(
            'SELECT quiz.id as exam_id, quiz.fullname as course_name,
                quiz.course_id as course_id,
                quiz.name as exam_name, 
                quiz.timeopen as opening_date, 
                quiz.timeclose as closing_date, 
                quiz.password as password
            FROM (
                SELECT mq.id, 
                    mq.course, 
                    mq.timeopen, 
                    mq.timeclose, 
                    mq.password,
                    mq.name, 
                    mc.fullname,
                    mc.id as course_id
                FROM mdl_quiz mq, mdl_course mc
                WHERE 
                    mq.course = mc.id
                    AND mq.timeopen <= (SELECT extract(epoch from (
                        SELECT (DATE_TRUNC(\'day\', '. $current. ') + interval \'1 day\') - INTERVAL \'1 sec\'
                    )))
                    AND mq.timeclose >= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC(\'day\', '. $current. ')
                    )))
            ) as quiz'
        );

        // error_log("\n\nStatsModel.php -> queryAllExamsWrittenOnDay() -> What is the query to run: ");
        // error_log("\n\n".$sql."\n\n");


        // error_log("\n\nStatsModel.php -> queryAllExamsWrittenOnDay() -> QUERY DONE what is the result: ");
        // error_log("\n------->> ".print_r($written_today_qry, 1));
        return $written_today_qry;
    }

    /**
     * ALL Exams Scheduled for this Week
     * This is for ALL exams in Moodle, not just Test Centre IP restricted
     *
     * @param none
     * @return int The number of exams for the week.
     */
    public function queryAllExamsWrittenWeek()
    {
        global $DB;

        // This is for all exams in Moodle, not just Test Centre IP restricted
        $written_today_qry = $DB->get_records_sql('
            SELECT quiz.id as exam_id, quiz.fullname as course_name,
                quiz.course_id as course_id,
                quiz.name as exam_name, 
                quiz.timeopen as opening_date, 
                quiz.timeclose as closing_date, 
                quiz.password as password
            FROM (
                SELECT mq.id, 
                    mq.course, 
                    mq.timeopen, 
                    mq.timeclose, 
                    mq.password,
                    mq.name, 
                    mc.fullname,
                    mc.id as course_id
                FROM mdl_quiz mq, mdl_course mc
                WHERE 
                    mq.course = mc.id
                    AND mq.timeopen <= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC(\'week\', CURRENT_TIMESTAMP) + interval \'7 day\'
                    )))
                    AND mq.timeclose >= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC(\'week\', CURRENT_TIMESTAMP)
                    )))
            ) as quiz'
        );

        return $written_today_qry;
    }

    /**
     * ALL Exams Scheduled +- 4 days
     * This will help show what the past has been like for exams and what the next 
     * few days will also look like
     * @param none
     * @return array The number of exams for the week.
     */
    public function getExamCountForcast()
    {
        global $DB;

        // This is for all exams in Moodle, not just Test Centre IP restricted
        $written_today_qry = $DB->get_records_sql('
            SELECT quiz.id as exam_id, quiz.fullname as course_name,
                quiz.course_id as course_id,
                quiz.name as exam_name, 
                quiz.timeopen as opening_date, 
                quiz.timeclose as closing_date, 
                quiz.password as password
            FROM (
                SELECT mq.id, 
                    mq.course, 
                    mq.timeopen, 
                    mq.timeclose, 
                    mq.password,
                    mq.name, 
                    mc.fullname,
                    mc.id as course_id
                FROM mdl_quiz mq, mdl_course mc
                WHERE 
                    mq.course = mc.id
                    AND mq.timeopen <= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC(\'week\', CURRENT_TIMESTAMP) + interval \'7 day\'
                    )))
                    AND mq.timeclose >= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC(\'week\', CURRENT_TIMESTAMP)
                    )))
            ) as quiz'
        );

        return $written_today_qry;
    }

    /**
     * Get the number of exams that have been written in the Test Centre for only Today
     *
     * All students who have 'finished' = 1 are students who have completed an exam today.
     *
     * @return int - The query result.
     */
    public function queryExamsWrittenToday()
    {
        global $DB;
        // ========================================================================
        // Exams Written Today 
        $written_today_qry = $DB->get_record_sql(
            'SELECT count(*)
            FROM mdl_local_tcms_student_entry
            WHERE finished = 1
            AND date(to_timestamp(signed_in)) <= now()::date
            AND date(to_timestamp(signed_out)) >= now()::date'
        );
        
        return $written_today_qry;
    }

    /**
     * Get the TOTAL number of exams that have been written in the Test Centre
     *
     * All students who have 'finished' = 1 are students who have completed an exam.
     *
     * @return int - The query result
     */
    public function queryTotalWrittenExams()
    {
        global $DB;
        // ========================================================================
        // Total written exams this semester
        $written_exams_so_far_qry = $DB->get_record_sql(
            'SELECT COUNT(id)
            FROM mdl_local_tcms_student_entry
            WHERE finished = 1'
        );
        // get_record_sql returns an obj
        return $written_exams_so_far_qry;
    }

    /**
     * How many exams are there in Moodle 
     *
     * This is for ALL EXAMS
     *
     * @return int The number of all exams.
     */
    public function queryCountAllExams()
    {
        global $DB;
        // ========================================================================
        // All exams in Moodle
        $all_exams = $DB->count_records_sql(
            'SELECT COUNT(*)
            FROM mdl_quiz'
        );
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
     * @param int - the exam id
     * @return type Description.
     */
    public function queryExamResults($exam_id = 0)
    {
        global $DB;
        // ========================================================================
        // All exams in Moodle
        if ($exam_id == 0) {
            return false;
        }

        return $DB->get_records_sql(
            'SELECT distinct course_name, exam_name, opening_date, closing_date, password, notes, finished, visible, manual
            FROM mdl_local_tcms_exam ep where ep.id = ?',
            array($exam_id)
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
    public function queryManualExamResults($exam_id = 0)
    {
        global $DB;
        // ========================================================================
        // All exams in Moodle
        if ($exam_id == 0) {
            return false;
        }

        return $DB->get_records_sql(
            'SELECT mq.name as examname, mc.fullname as coursename
            FROM  mdl_quiz mq, mdl_course mc
            WHERE mq.course = mc.id
            AND mq.id = ?',
            array($exam_id)
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
    public function queryQuizAttempts($exam_id = 0)
    {
        global $DB;
        // ========================================================================
        // All exams in Moodle
        if ($exam_id == 0) {
            return false;
        }

        return $DB->get_records_sql(
            "SELECT * from mdl_quiz_attempts
            where quiz = ?",
            array($exam_id)
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
    public function queryFinishedStudentExams($sort = "", $order = "")
    {
        global $DB;
        // ========================================================================
        // 
        if ($sort == "") {
            $sort = "DESC";
        }
        if ($order == "") {
            $order_by = " ORDER BY signintime " . $sort;
        } else {
            $order_by = " ORDER BY ". $order. " ". $sort;
        }

        // error_log("\n\nStatsModel -> queryFinishedStudentExams() -> What is the order by: ". $order_by);

        $sql = "SELECT id, userid,examid, username, coursename, room, to_timestamp(signed_in) as signintime, to_timestamp(signed_out) as signouttime, comments, id_type, exam_type,
            CASE WHEN finished = 1 THEN 'closed' 
            WHEN finished = 2 THEN 'reopened' ELSE 'opened' END as status
            FROM mdl_local_tcms_student_entry
            $order_by";
            // -- LIMIT $limit OFFSET $offset";
        
        // error_log("\n\nStatsModel -> queryFinishedStudentExams() -> What is the SQL: \n\n". $sql."\n\n");

        return $DB->get_records_sql($sql);
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
    public function queryUniqueStudents()
    {
        global $DB;
        // ========================================================================
        // All exams in Moodle
        // return 99;
        $all_exams = $DB->count_records_sql(
            'SELECT COUNT(DISTINCT username)
            FROM mdl_local_tcms_student_entry'
        );
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
    public function queryCurrentRoomCount()
    {
        global $DB;
        // ========================================================================
        // Let's get the number of students currently in the TCS
        $room_occupance = $DB->get_records_sql(
            'SELECT room, count(room)
            FROM mdl_local_tcms_student_entry
            WHERE (finished = 0 OR finished = 2)
            GROUP BY room'
        );
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
    public function queryTotalRoomCount()
    {
        global $DB;
        // ========================================================================
        // Let's get the number of students currently in the TCS
        $room_occupance = $DB->get_records_sql(
            'SELECT room, count(room)
            FROM mdl_local_tcms_student_entry
            -- WHERE (finished = 0 OR finished = 2)
            GROUP BY room'
        );
        return $room_occupance;
    }

    /**
     * Query all the exams written for today.
     *
     * The data will be used to find the average time for the exams
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
    public function queryAverageTime()
    {
        global $DB, $CFG;
        $this->CFG->local_tcs_logging ? error_log("\n ========================>>>> getAverageTime -> START <<<<========================") : null;
        $users = $DB->get_records_sql(
            "SELECT * FROM mdl_local_tcms_student_entry
            WHERE signed_in > (SELECT extract(epoch from (
                SELECT DATE_TRUNC('day', CURRENT_TIMESTAMP)
            ))) AND signed_out < (SELECT extract(epoch from (
                SELECT DATE_TRUNC('day', CURRENT_TIMESTAMP) + interval '1 day'
            )))"
        );

        return $users;
    }

    /**
     * Get all Exams Scheduled for Today.
     *
     * Query all exams starting at 12:00am to Midnight
     *
     * @return array - All exams in all courses that are listed.
     */
    public function queryLiveExamsGraph1()
    {
        global $DB;
        $exams_today = $DB->get_records_sql(
            "SELECT quiz.id as exam_id,
                quiz.course_name as course_shortname,
                quiz.fullname as quiz_fullname,
                quiz.course_id as course_id,
                quiz.name as exam_name,
                quiz.timeopen as opening_date,
                quiz.timeclose as closing_date,
                quiz.password as password
            FROM (
                SELECT mq.id,
                    mq.course,
                    mq.timeopen,
                    mq.timeclose,
                    mq.password,
                    mq.name,
                    mc.fullname,
                    mc.shortname as course_name,
                    mc.id as course_id
                FROM mdl_quiz mq, mdl_course mc
                WHERE
                    mq.course = mc.id
                    AND mq.timeopen <= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC('day', CURRENT_TIMESTAMP) + interval '1 day'
                    )))
                    AND mq.timeclose >= (SELECT extract(epoch from (
                        SELECT DATE_TRUNC('day', CURRENT_TIMESTAMP)
                    )))
            ) as quiz
            ORDER BY course_shortname, quiz_fullname"
        );

        return $exams_today;
    }

    /**
     * Course Count
     *
     * Query the number of students for a particular course.
     *
     * @param int - Course ID
     * @return array - returns all the students in the course.
     */
    public function queryCountStudentsClass($course_id = 0)
    {
        global $DB, $CFG;

        $student_count = $DB->count_records_sql(
            'SELECT COUNT(mu.username)
            FROM mdl_role_assignments AS asg
            JOIN mdl_context AS context ON asg.contextid = context.id AND context.contextlevel = 50
            JOIN mdl_user AS mu ON mu.id = asg.userid
            JOIN mdl_course AS course ON context.instanceid = course.id
            WHERE asg.roleid = 5 
            AND course.id = '. $course_id
        );

        return $student_count;
    }

    /**
     * Course Count (Strict)
     *
     * Query the number of students for a particular course.
     *
     * @param int - Course ID
     * @return array - returns all the students in the course.
     */
    public function getStudentsInCourse($course_id = 0)
    {
        global $DB;
        if ($course_id == 0) {
            return false;
        }
        
        $peeps_in_course = $DB->count_records_sql(
            "SELECT u.*
            FROM mdl_user u
            JOIN mdl_user_enrolments ue ON ue.userid = u.id
            JOIN mdl_enrol e ON e.id = ue.enrolid
            JOIN mdl_role_assignments ra ON ra.userid = u.id
            JOIN mdl_context ct ON ct.id = ra.contextid AND ct.contextlevel = 50
            JOIN mdl_course c ON c.id = ct.instanceid AND e.courseid = c.id
            JOIN mdl_role r ON r.id = ra.roleid AND r.shortname = ?
            WHERE e.status = 0 AND u.suspended = 0 AND u.deleted = 0 
            AND c.id = ? AND e.enrol=?",
            array('student', $course_id, 'lmb')
        );

        return $peeps_in_course;
    }
}
