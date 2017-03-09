<?php
 
class local_cas_help_links_logger {

    /**
     * Persists a log record for a "link clicked" activity
     * 
     * @param  int  $user_id  moodle user id
     * @param  int  $course_id  moodle course id
     * @param  int  $link_id  'help links' table record id
     * @return boid
     */
    public static function log_link_click($user_id, $course_id, $link_id = 0)
    {
        // if there is no explicit link id passed, this was a general help click
        if ( ! $link_id) {
            $linkType = 'site';
            $linkUrl = get_config('local_cas_help_links', 'default_help_link');

        // otherwise, attempt to fetch this link record
        } else {
            // if there is a link record, log the appropriate info
            if ($link = \local_cas_help_links_utility::get_link($link_id)) {
                $linkType = $link->type;
                $linkUrl = $link->link;

            // otherwise, there is a problem (link should exist here)
            } else {
                // bail out @TODO - log this error internally?
                return;
            }
        }

        // attempt to fetch this ues course's data
        if ($uesCourseData = \local_cas_help_links_utility::get_ues_course_data($course_id)) {
            // if no result, fallback to empty strings so as not to distrupt the redirect
            if (empty($uesCourseData)) {
                $courseDept = '';
                $courseNumber = '';
                $courseId = 0;
            
            // otherwise, log the appropriate info
            } else {
                $courseDept = $uesCourseData->department;
                $courseNumber = $uesCourseData->cou_number;
                $courseId = $uesCourseData->id;
            }

        // otherwise, something has gone wrong
        } else {
            $courseDept = '';
            $courseNumber = '';
            $courseId = 0;
        }

        global $DB;

        $log_record = new stdClass;
        $log_record->user_id = $user_id;
        $log_record->time_clicked = time();
        $log_record->link_type = $linkType;
        $log_record->link_url = $linkUrl;
        $log_record->course_dept = $courseDept;
        $log_record->course_number = $courseNumber;
        $log_record->course_id = $courseId;

        $DB->insert_record(self::get_log_table_name(), $log_record);
    }

    /**
     * Returns the name of the 'help links log' table
     * 
     * @return string
     */
    private static function get_log_table_name()
    {
        return 'local_cas_help_links_log';
    }

    /**
     * Returns current semester usage data which includes: a list of weeks (x-axis), a list of respective unique user and total clicks
     *
     * Optionally, filters by a given department name
     * 
     * @param  string $selectedDept
     * @return array
     */
    public static function get_all_current_semester_usage_data($selectedDept = '')
    {
        $weeks = [];
        $userTotals = [];
        $clickTotals = [];

        foreach (self::get_current_semester_week_list() as $week) {
            $weeks[] = $week['date'];
            $userTotals[] = self::get_usage_totals_for_range('users', $week['starttime'], $week['endtime'], $selectedDept);
            $clickTotals[] = self::get_usage_totals_for_range('clicks', $week['starttime'], $week['endtime'], $selectedDept);
        }

        return [
            $weeks, 
            $userTotals,
            $clickTotals
        ];
    }

    /**
     * Returns a total amount of clicks of a given type (unique "users", or "clicks") for a given range and dept
     * 
     * @param  string $totalType  users|clicks(default)
     * @param  int $startTime  unix timestamp
     * @param  int $endTime  unix timestamp
     * @param  string $filterDept
     * @return int
     */
    private static function get_usage_totals_for_range($totalType, $startTime, $endTime, $filterDept = '')
    {
        global $DB;

        $select = $totalType == 'users' ? 'COUNT(DISTINCT user_id)' : 'COUNT(id)';

        $filter = $filterDept ? ' AND course_dept="' . $filterDept . '"' : '';

        $result = $DB->get_records_sql('SELECT ' . $select . ' as total FROM {local_cas_help_links_log} WHERE time_clicked >= ? AND time_clicked <= ?' . $filter, array($startTime, $endTime));

        if (property_exists(reset($result), 'total'))
            return reset($result)->total;

        return 0;
    }

    /**
     * Returns current semester usage data scoped to a specific teacher user which includes: a list of weeks (x-axis), a list of respective unique user and total clicks
     *
     * Optionally, filters by a given course id
     * 
     * @param  int $userId
     * @param  int $courseId
     * @return array
     */
    public static function get_teacher_current_semester_usage_data($userId, $courseId = 0)
    {
        $weeks = [];
        $userTotals = [];
        $clickTotals = [];

        foreach (self::get_current_semester_week_list() as $week) {
            $weeks[] = $week['date'];
            $userTotals[] = self::get_usage_totals_for_user_range('users', $week['starttime'], $week['endtime'], $userId, $courseId);
            $clickTotals[] = self::get_usage_totals_for_user_range('clicks', $week['starttime'], $week['endtime'], $userId, $courseId);
        }

        return [
            $weeks, 
            $userTotals,
            $clickTotals
        ];
    }

    /**
     * Returns a total amount of clicks of a given type (unique "users", or "clicks") for a given range and dept, scoped to a specific user's classes
     * 
     * @param  string $totalType  users|clicks(default)
     * @param  int $startTime  unix timestamp
     * @param  int $endTime  unix timestamp
     * @param  int $userId
     * @param  int $courseId
     * @return int
     */
    private static function get_usage_totals_for_user_range($totalType, $startTime, $endTime, $userId, $courseId = 0)
    {
        global $DB;

        $select = $totalType == 'users' ? 'COUNT(DISTINCT user_id)' : 'COUNT(id)';

        // get this teacher user's current course ids
        $courseIds = \local_cas_help_links_utility::get_teacher_course_selection_array($userId, true);

        // transform for sql
        $courseIdList = implode(',', $courseIds);

        // if we're filtering by a single course, make it so, if not, pull all of this teacher's courses
        $filter = $courseId ? ' AND course_id=' . $courseId : ' AND course_id IN (' . $courseIdList . ')';

        $result = $DB->get_records_sql('SELECT ' . $select . ' as total FROM {local_cas_help_links_log} WHERE time_clicked >= ? AND time_clicked <= ?' . $filter, array($startTime, $endTime));

        if (property_exists(reset($result), 'total'))
            return reset($result)->total;

        return 0;
    }

    /**
     * Returns an array of week data for the current semester
     * 
     * @return array
     */
    private static function get_current_semester_week_list()
    {
        $semester = self::get_current_ues_semester();

        $startTime = (int) $semester->classes_start;
        $endTime = (int) $semester->grades_due;
        $weeksInRange = self::get_number_of_weeks_in_range($startTime, $endTime);

        $weeks = [];

        foreach (range(1, $weeksInRange) as $number) {
            $nextWeekStartTime = $startTime + 604800;

            $weeks[] = [
                'number' => $number,
                'starttime' => $startTime,
                'endtime' => $nextWeekStartTime - 1,
                'date' => userdate($startTime, '%b %d')
            ];

            $startTime = $nextWeekStartTime;
        }

        return $weeks;
    }

    /**
     * Returns the total number of weeks (including partial) of a given time range
     * 
     * @param  int $startTime  unix timestamp
     * @param  int $endTime  unix timestamp
     * @return int
     */
    private static function get_number_of_weeks_in_range($startTime, $endTime)
    {
        $timeDifference = $endTime - $startTime;
        
        return (int) ceil($timeDifference / 604800);
    }

    /**
     * Returns the current UES semester
     * 
     * @return object
     */
    private static function get_current_ues_semester()
    {
        global $DB;
        
        $result = $DB->get_records_sql('SELECT * FROM {enrol_ues_semesters} WHERE classes_start < ? AND campus = "LSU" AND session_key = "" ORDER BY classes_start DESC', array(time()));

        if ( ! empty($result)) {
            return reset($result);
        }

        return false;
    }

    /**
     * Fetches the usage for the system
     *
     * @return array
     */
    private static function get_usage_data()
    {
        global $DB;

        $result = $DB->get_records_sql('SELECT Department, Course_Number, Full_Name_of_User, Link_Type, External_URL, Time_Clicked FROM (
            SELECT
                llog.id AS uniqer,
                uec.department AS Department,
                uec.cou_number AS Course_Number,
                CONCAT(u.firstname, " ", u.lastname) AS Full_Name_of_User,
                link.type AS Link_Type,
                link.link AS External_URL,
                FROM_UNIXTIME(llog.time_clicked) AS Time_Clicked
            FROM {course} c
                INNER JOIN {enrol_ues_sections} sec ON sec.idnumber = c.idnumber
                INNER JOIN {enrol_ues_courses} uec ON uec.id = sec.courseid
                INNER JOIN {local_cas_help_links_log} llog ON c.id = llog.course_id
                INNER JOIN {user} u ON u.id = llog.user_id
                INNER JOIN {local_cas_help_links} link ON link.id = llog.link_id
            WHERE c.idnumber <> "" AND c.idnumber IS NOT NULL AND link.user_id = 0
            UNION ALL
            SELECT
                llog.id AS uniqer,
                uec.department AS Department,
                uec.cou_number AS Course_Number,
                CONCAT(u.firstname, " ", u.lastname) AS Full_Name_of_User,
                "Site" AS Link_Type,
                NULL AS External_URL,
                FROM_UNIXTIME(llog.time_clicked) AS Time_Clicked
            FROM {course} c
                INNER JOIN {enrol_ues_sections} sec ON sec.idnumber = c.idnumber
                INNER JOIN {enrol_ues_courses} uec ON uec.id = sec.courseid
                INNER JOIN {local_cas_help_links_log} llog ON c.id = llog.course_id
                INNER JOIN {user} u ON u.id = llog.user_id
                LEFT JOIN {local_cas_help_links} link ON link.id = llog.link_id
            WHERE c.idnumber <> "" AND c.idnumber IS NOT NULL AND link.id IS NULL
            UNION ALL
            SELECT
               llog.id AS uniqer,
               uec.department AS Department,
               uec.cou_number AS Course_Number,
               CONCAT(u.firstname, " ", u.lastname) AS Full_Name_of_User,
               IF(link.user_id>0,"User Category", IFNULL(link.type, "Site")) AS Link_Type,
               link.link AS External_URL,
               FROM_UNIXTIME(llog.time_clicked) AS Time_Clicked
            FROM {course} c
                INNER JOIN {enrol_ues_sections} sec ON sec.idnumber = c.idnumber
                INNER JOIN {enrol_ues_courses} uec ON uec.id = sec.courseid
                INNER JOIN {local_cas_help_links_log} llog ON c.id = llog.course_id
                INNER JOIN {user} u ON u.id = llog.user_id
                INNER JOIN {local_cas_help_links} link ON link.id = llog.link_id
            WHERE c.idnumber <> "" AND c.idnumber IS NOT NULL AND link.user_id > 0) t
            GROUP BY uniqer
        ');
        return $result;
    }
    
}
