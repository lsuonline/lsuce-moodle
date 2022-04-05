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

class Stats
{
    private $q;
    private $stat_list;

    public function __construct()
    {
        global $CFG;
        $CFG->local_tcs_logging ? error_log("\n Stats -> constructor()") : null;
        include_once 'StatsController.php';
        include_once 'TcsLib.php';
        include_once 'StudentListAjax.php';
        $this->QR = new StatsController();
        $this->tcslib = new TcsLib();
        $this->SLA = new StudentListAjax();

        $this->stat_list = "1,2,3,4";
    }

    /**
     * Description Get all the stats needed for the stats page
     * @param none
     * @return json a crap load of data in an array
     */

    public function getAllStats($params = false)
    {
        global $CFG;
        $stat_list = isset($params->stat_list) ? $params->stat_list : null;
        $stat_list = "all";

        $CFG->local_tcs_logging ? error_log("\n ========================>>>> getAllStats -> START <<<<========================") : null;
        $all_stats = array(
            'stats' => $this->getStats($stat_list),
            'smallstats' => $this->getRoomStats(1),
            'static_tables' => $this->getTableStats()
        );

        return $all_stats;
    }

    /**
     * Description Build static tables 
     * This is currently hosted in the stats section of the TCS
     * @param none
     * @return json encoded array with number of current users
     */
    public function getTableStats() {
        
        // error_log("\n\n\n\n\nStats->getTableStats() -> START \n\n");
        
        $table = array(
            $this->QR->getLiveExamsGraph1(),
            $this->QR->getLiveExamsGraph2()
        );

        return $table;
    }

    /**
     * Description Build static tables 
     * This is currently hosted in the stats section of the TCS
     * @param none
     * @return json encoded array with number of current users
     */
    public function getGraphData1($params = false) {
        
        // error_log("\n================================================================\n");
        // error_log("\n\nStats->getGraphData1() -> START \n\n");
        // error_log("\nWhat are the params: ". print_r($params, 1). "\n");
        // $range = isset($params['range']) ? $params['range'] : null;
        $range = isset($params->range) ? $params->range : null;
        // error_log("\nWhat is the range: ". $range. "\n");

        return $this->QR->getGraphData1($range);
    }


    /**
     * Description:
     * @param none
     * @return json array of data for template
     */

    public function getDashStats($params = false)
    {
        global $CFG;
        // error_log("\n================================================================\n");
        // error_log("\n\nStats->getDashStats() -> START \n\n");
        
        // this will be the params hash
        $passed_hash = "";
        
        // current hash in DB
        $current_hash = "";
        $is_admin = false;
        $student_table_list = array();

        if (isset($params->hash) && strlen($params->hash) == 32) {
            $passed_hash = $params->hash;
            $current_hash_obj = $this->tcslib->getSetting("dash_hash");
            $current_hash = $current_hash_obj->t_value;
        }

        // we passed in a hash from JS, are they the same?
        // If so then that means there's no change.
        if ((strlen($current_hash) > 10) && $current_hash == $passed_hash) {
            // send back an empty array and the same hash so JS knows nothing has changed
            $final = array();
            $student_table_list = array();
        } else {
            // nope......hashes are NOT the same.
            $room_stats = $this->getRoomStats();
            $general_stats = $this->getStats();
            $final = array_merge($room_stats, $general_stats);

            if ($this->tcslib->checkSiteAdminUser() || $this->tcslib->checkTestCentreUser() || $this->tcslib->checkDisabilityUser()) {
                $student_table_list = $this->SLA->getUsersInExam()["users_in_centre"];
                $is_admin = true;
            } else {
                error_log(" \n Stats->getDashStats() -> THIS USER IS A STUDENT");
            }
        }
        // error_log("\n\n Stats->getDashStats() -> FINISHED \n");
        // error_log("\n\n ================================================================ \n\n");

        return array(
            // "success" => true,
            'msg_type' => 'success',
            'data' => $final,
            'student_table_list' => $student_table_list,
            'is_admin' => $is_admin,
            'dash_hash' => $current_hash
        );
    }

    /**
     * Description:
     * @param none
     * @return json array of data for template
     */

    public function getRoomStats($long_running = false)
    {
        global $CFG;

        // error_log("\n================================================================\n");
        // error_log("\n\nStats->getRoomStats() -> START \n\n");

        if ($long_running == false) {
            // $dash_stats = $this->getRoomStatsData();
            $dash_stats = $this->QR->getCurrentRoomCount();
        } else {
            $dash_stats = $this->QR->getTotalRoomCount();
        }

        $number_of_rooms = $CFG->local_tcs_dash_rooms;
        $CFG->local_tcs_logging ? error_log("\n How many rooms: ". $number_of_rooms) : null;

        //  Now let's build the data for the template
        $room_colors = array(
            "stat_blue",
            "stat_green",
            "stat_yellow",
            "stat_orange",
            "stat_salmon",
            "stat_danger",
            "stat_light_pink",
            "stat_purple",
            "stat_teal",
            "stat_charcoal",
            "stat_light_blue"
        );
        
        $data_set = array();

        // Long running numbers?
        if ($long_running) {
            $room_count = "Total Count of R";
        } else {
            $room_count = "R";
        }
        // error_log("\ngetRoomStats() -> num of rooms: ". $number_of_rooms);
        for($i = 1; $i < ($number_of_rooms + 1); $i++) {
            // error_log("\ngetRoomStats() -> loop i: ". $i);
            $data_set[] = array(
                "stat_name" => "tcs_room_stat_". $i,
                "stat_title" => $room_count . $i,
                // "stat_small_title" => "",
                "stat_data" => (isset($dash_stats[$i]))
                    ? $dash_stats[$i]->count
                    : 0,
                // "stat_icon" => "fa fa-pencil-square-o",
                "stat_color" => $room_colors[$i - 1]
            );
        }

        // error_log("\n getRoomStats() -> What is the data set: \n". print_r($data_set, 1));
        // error_log("\n getRoomStats() -> ========================>>>> getRoomStats -> FINISHED <<<<========================\n");
        return $data_set;
        // not using this
        return array(
            // "success" => true,
            'msg_type' => 'success',
            'data' => $data_set
        );
    }


    /**
     * Description: Called by the Dashboard this displays some general TC info.
     * @param none
     * @return json array of data for template
     */
    // public function getStats($jaxy = false)
    public function statLib($stat_num = 0)
    {
        global $CFG;

        // Now build the data set needed for the template
        // Function to call, Name, Title, Small Title, Data, Icon, Color
        $stat_set = array(
            array(),
            array("getCurrentStudents","tcs_student_count","Student","Count","","fa fa-users","stat_blue","student_stat"),
            array("getScheduledExamsToday","tcs_exam_count","Exams","Today","","fa fa-calendar","stat_orange","exam_stat"),
            array("getExamsWrittenToday","tcs_written_today","Written","Today","","fa fa-pencil-square-o","stat_charcoal","exam_stat"),
            array("getTotalWrittenExams","tcs_written_semester","Written","Semester","","fa fa-pencil-square-o","stat_purple","exam_stat"),
            array("countAllExams","tcs_total_all_exams","Total Exams","in TC","","fa fa-pencil-square-o","stat_teal","exam_stat"),
            array("getUniqueStudents","tcs_total_all_exams","Total Exam Count","in Moodle","","fa fa-pencil-square-o","stat_teal","exam_stat"),
            array("getAverageTime","tcs_today_avg_time","Today's Average Exam Time","","","fa fa-bathtub","stat_charcoal","student_stat"),
            // array("placeholder","tcs_today_fast_time","Today's Fastest Time","","","fa fa-bathtub","stat_green","student_stat"),
            // array("placeholder","tcs_today_long_time","Today's Longest Time","","","fa fa-bathtub","stat_salmon","student_stat"),
            // array("placeholder","blah1","Blah Title 1 Here Blah","","","fa fa-bathtub","stat_danger","other_stat"),
            // array("placeholder","blah2","Blah Title 2 Here Blah","","","fa fa-bathtub","stat_danger","other_stat"),
            // array("placeholder","blah3","Blah Title 3 Here Blah","","","fa fa-bathtub","stat_danger","other_stat"),
            // array("placeholder","blah4","Blah Title 4 Here Blah","","","fa fa-bathtub","stat_danger","other_stat"),
            // array("placeholder","blah5","Blah Title 5 Here Blah","","","fa fa-bathtub","stat_danger","other_stat"),
            // array("placeholder","blah6","Blah Title 6 Here Blah","","","fa fa-bathtub","stat_danger","other_stat"),
            // array("placeholder","blah7","Blah Title 7 Here Blah","","","fa fa-bathtub","stat_danger","other_stat")
        );

        // Now build the data set
        if ($stat_num == "all") {
            $stat_num = range(1, (count($stat_set) - 1));
        }

        $final_set = array();
        foreach($stat_num as $x) {
            $func_name = $stat_set[$x][0];
            $wtf_is_this = $this->QR->$func_name();

            $temp_arr = array(
                "stat_name" => $stat_set[$x][1],
                "stat_title" => $stat_set[$x][2],
                "stat_small_title" => $stat_set[$x][3],
                "stat_data" => $wtf_is_this,
                "stat_icon" => $stat_set[$x][5],
                "stat_color" => $stat_set[$x][6],
                "stat_type" => $stat_set[$x][7]
            );
            $final_set[] = $temp_arr;
        }
        return $final_set;
    }
    /**
     * Description: Called by the Dashboard this displays some general TC info.
     * @param string - pass a string of comma seperated dashboard items
     * @return json array of data for template
     */
    // public function getStats($jaxy = false)
    public function getStats($list_of_cards = false)
    {
        global $CFG;
        // $CFG->local_tcs_logging ? error_log("\n ========================>>>> getStats -> START <<<<========================") : null;
        if ($list_of_cards == false) {
            // show only what is listed in the TCS settings page
            $cards_to_show = explode(',', $CFG->local_tcs_dash_stat_cards);
        } else if ($list_of_cards == "all") {
            // show all stats
            $cards_to_show = "all";
        } else {
            // show only what was passed in
            $cards_to_show = explode(',', $list_of_cards);
        }

        $data_set = $this->statLib($cards_to_show);
        // $dash_stats = $this->getDashboardData();
        $CFG->local_tcs_logging ? error_log("\n what are the dash_stats: ". print_r($data_set, 1)) : null;

        // error_log("\n what are the dash_stats: ". print_r($data_set, 1));
        // error_log("\n getDashboardData() -> ========================>>>> getStats -> FINISHED <<<<========================");

        return $data_set;
    }


    /**
     * This is just for testing puposes to get dummy data
     * @return json_encoded array of stuff.
     */
    public function getExamLogs($params = false)
    {
        // error_log("\nSTATS -> getExamLogs() ------->>>>>> START <<<<<<-------");
        $search = isset($params->search) ? $params->search : null;
        $sort = isset($params->sort) ? $params->sort : null;
        $order = isset($params->order) ? $params->order : null;
        $offset = isset($params->offset) ? $params->offset : null;
        $limit = isset($params->limit) ? $params->limit : null;

        // error_log("\nSTATS -> getExamLogs() going to call processExamLogs");

        return $this->QR->processExamLogs($search, $sort, $order, $offset, $limit);
    }

    /**
     * This is just for testing puposes. Use Postman and call this function
     * @return json_encoded array of stuff.
     */
    public function postman($jaxy = false)
    {
        // error_log("\n\n");
        // error_log("\nMade it to postman function");
        $student_count = $this->QR->countStudentsClass(843);

        // error_log("\nWhat is the student count: ". $student_count. "\n\n");
        // error_log("\n-------- END ---------\n");
    }

    /**
     * This is just for testing puposes to get dummy data
     * @return json_encoded array of stuff.
     */
    public function getTestStats($jaxy = false)
    {
        // include('Stats.php');
        // $stats = new Stats();
        // Get the number of students in the Test Centre

        // $stats->currentUsersIn();
        $sample_data = array(

            array(
                "stat_name" => "tcs_student_count",
                "stat_title" => "Student",
                "stat_small_title" => "Count",
                "stat_data" => 47,
                "stat_icon" => "fa fa-users",
                "stat_color" => "stat_blue"
            ),
            array(
                "stat_name" => "tcs_exam_count",
                "stat_title" => "Exams",
                "stat_small_title" => "Today",
                "stat_data" => 12,
                "stat_icon" => "fa fa-calendar",
                "stat_color" => "stat_yellow"
            ),
            array(
                "stat_name" => "tcs_written_today",
                "stat_title" => "Written",
                "stat_small_title" => "Today",
                "stat_data" => 567,
                "stat_icon" => "fa fa-pencil-square-o",
                "stat_color" => "stat_salmon"
            ),
            array(
                "stat_name" => "tcs_written_semester",
                "stat_title" => "Written",
                "stat_small_title" => "Semester",
                "stat_data" => 5748,
                "stat_icon" => "fa fa-pencil-square-o",
                "stat_color" => "stat_lightblue"
            )
        );

        // $data = 0;
        // if ($jaxy === true) {
        //     die(json_encode(array("success" => "true", "data" => $data, "msg" => "This is a sample of dummy data")));
        // } else {
        return $sample_data;
        // }
    }
}
