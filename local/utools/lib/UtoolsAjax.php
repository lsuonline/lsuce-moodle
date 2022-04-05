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
//namespace Utools\Lib

class UtoolsAjax
{
    private $ulethlib = null;

    public function __construct()
    {
        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();
    }

    /**
     * Description - clean way to get contents of a file using include then remove from buffer.
     * @param type - filename
     * @return contents.
     */
    // public function get_include_contents($filename) {
    //     if (is_file($filename)) {
    //         ob_start();
    //         include $filename;
    //         return ob_get_clean();
    //     }
    //     return false;
    // }

    /**
     * Description
     * @param type - none
     * @return json object containing all the settings for Utools
     */
    public function getUtoolsSettings($params = array())
    {
        global $CFG;
        
        // $this->ulethlib->printToLog("\n UtoolsAjax -> getUtoolsSettings() -> what is the url: ".
        // $CFG->local_utools_tcms_instance);
        
        $usettings = new stdClass();
        $usettings->nr_token = $CFG->local_utools_newrelic_extra_auth_token;
        $usettings->nr_server_ids = explode(",", $CFG->local_utools_newrelic_extra_server_ids);
        $usettings->nr_server_names = $CFG->local_utools_newrelic_extra_server_names;
        // $usettings->nr_iframe = $CFG->local_utools_newrelic_iframes;

        return $usettings;


        $return_type = false;
        if (isset($params['local'])) {
            $return_type = true;
        }
        // let's get the widget names
        // $script_style = $this->getJSandCSS($CFG->dirroot . "/local/utools/widgets/");

        $active = array();
        $logs = array();
        $intervals = array();
        $extra_settings = array();

        foreach ($script_style as $cssjs => $cssjs_val) {
            $what_type = gettype($cssjs_val);
            if (gettype($cssjs_val) == "string") {
                // active
                $temp_active_name = 'local_utools_'. $cssjs_val . '_enabled';
                $active[$cssjs_val] = isset($CFG->$temp_active_name) ? $CFG->$temp_active_name : 0;
                // logs
                $temp_logging_name = 'local_utools_'. $cssjs_val . '_logging';
                $logs[$cssjs_val] = isset($CFG->$temp_logging_name) ? $CFG->$temp_logging_name : 0;
                // intervals
                $temp_intval_name = 'local_utools_'. $cssjs_val . '_interval_time';
                $intervals[$cssjs_val] = isset($CFG->$temp_intval_name) ? $CFG->$temp_intval_name : 0;

                // get the additional css and js files
                include_once($CFG->dirroot . '/local/utools/widgets/'.$cssjs_val.'/config.php');
                $script_style_more[$cssjs_val] = call_user_func($cssjs_val . 'Config');

                    
            }
        }

        // TODO: get_config('local_utools'); try this global function
        $local_utools_cfgs = array();
        foreach ($CFG as $cfg_key => $cfg_val) {
            if (preg_match("/local_utools_/", $cfg_key, $matches)) {
                $this_utool_cfg = explode('_', $cfg_key);
                if (isset($this_utool_cfg[3]) && $this_utool_cfg[3] == "extra") {
                    $extra_settings[implode("_", array_slice($this_utool_cfg, 2))] = $cfg_val;
                }
            }
        }
        // let's include the currentTerm, isLocal and refreshtimer
        $extra_settings['general_extra_current_term'] = $this->ulethlib->getCurrentTerm();
        $extra_settings['general_extra_is_local'] = $this->ulethlib->isLocal();
        $extra_settings['general_extra_lmb_refresh_timer'] = $CFG->local_utools_LMB_refresh_timer;
        $extra_settings['general_extra_environment'] = $this->ulethlib->getEnvironment();
        
        if ($return_type) {
            // this allows other func's to get this info
            return  array('active' => $active, 'resources' => $script_style);

        } else {
            // If you add a new widget that needs an interval then add to the list in Utools.js init().
            die (json_encode(array(
                'success' => 'true',
                'data' => array(
                    // interval times
                    'active' => $active,
                    'logs' => $logs,
                    'intervals' => $intervals,
                    'extra_settings' => $extra_settings,
                ),
                'resources' => $script_style,
                'additional_resources' => $script_style_more
            )));
        }
    }

    /**
     * Description
     * @param type $params
     * @return type
     */
    public function getMissingStudentIds($params)
    {
    
        global $DB, $CFG;
        $this->ulethlib->printToLog("\n UtoolsAjax -> getMissingStudentIds() -> START");

        $search = $params['search_word'];
        $params = null;
        
        $sort = "ASC";

        if (is_numeric($search)) {
            $select = "idnumber='".(int)$search."'";

        } else {
            if (strlen($search) > 0) {
                $select = "username LIKE '%".$search."%'";
            } else {
                $select = "idnumber='' OR username='' OR uofl_id=0";
            }
        }
        $select .= " OR CHAR_LENGTH(idnumber) > 6";
        // 000100001 is the first UofL id number

        $users = $DB->get_records_select('user', $select, null);

        if (count($users)) {
            $this->ulethlib->printToLog(
                "\n UtoolsAjax -> getMissingStudentIds() -> this many users: " . count($users)
            );
            $this->ulethlib->printToLog("\n UtoolsAjax -> getMissingStudentIds() -> RETURNING");
            die (json_encode(array("success" => true, "data" => $users)));

        } else {
            $users = array();
            $this->ulethlib->printToLog("\n UtoolsAjax -> getMissingStudentIds() -> RETURNING");
            die (json_encode(array("success" => true, "data" => $users)));
        }

    }
     
    /**
     * Description get all the js and css files for each widget so they can be loaded
     * @param type $params
     * @return type
     */
    public function getJSandCSS($dir)
    {
        // $dir    = '/Users/davidlowe/Sites/201503/local/utools/widgets';
        $cdir = scandir($dir);
        $widgets = array();
        foreach ($cdir as $key => $value) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                if ($value == '.' || $value == '..') {
                    continue;
                }

                if (!isset($widgets[$value])) {
                    $widgets[] = $value;
                    $newdir = $dir . $value . DIRECTORY_SEPARATOR;
                }

                // ==================================================
                // NOW get the JS and the CSS
                if ($handle = opendir($newdir . 'js/')) {
                    // list directory contents

                    while (($file = readdir($handle)) !== false) {
                        if ($file == '.' || $file == '..') {
                            continue;
                        }
                        
                        // only grab file names
                        // if (is_file($newdir . $file)) {
                        if (substr($file, -3) == ".js") {
                            $widgets[$value]['js'][] = $file;
                        }
                    }
                    closedir($handle);
                }
                if ($handle = opendir($newdir . 'css')) {
                    // list directory contents
                    while (($file = readdir($handle)) !== false) {
                        if ($file == '.' || $file == '..') {
                            continue;
                        }
                        
                        // only grab file names
                        if (substr($file, -4) == ".css") {
                            $widgets[$value]['css'][] = $file;
                        }
                    }
                    closedir($handle);
                }
            }
        }
        return $widgets;
    }

    /**
     * Description
     * @param type $params
     * @return type
     */
    public function updateMissingStudentId($params)
    {

        global $DB;

        $updated_info = array(
            // 'firstname' => $params['firstname'],
            // 'larstname' => $params['lastname'],
            'uofl_id' => $params['uofl_id'],
            'username'  => $params['username'],
            'idnumber'  => $params['idnumber'],
            'id'  => $params['id']
        );

        if ($DB->update_record('user', $updated_info)) {
            die (json_encode(array("success" => "true")));
        } else {
            die (json_encode(array("success" => "false")));
        }
    }

    public function getMissingCourseIds($params)
    {
        global $DB;
        $select = "idnumber=''";
        $all_courses = $DB->get_records_select('course', $select, null, 'fullname ASC');
        
        die (json_encode(array(
            'success' => 'true',
            'data' => $all_courses
        )));
    }
    

    public function parseAndStoreJenkins($jenkinsJsonObject)
    {
        global $DB;
        $decoded_object = json_decode($jenkinsJsonObject, true);
       
        foreach ($decoded_object['builds'] as $build) {
            $build_date =  substr($build['id'], 0, 10);
            $todays_date = date('Y-m-d');
            if ($build_date == $todays_date) {
                $record = new stdClass();
                $record->build_id = $build['id'] ;
                $record->build_no = $build['number'];
                $record->url = $build['url'];
                $record->result = $build['result'];
                $record->duration = $build['duration'];
                $record->timestamp =  $build['timestamp'];
                $record->build_date = $build_date;
                
                $existing_records = $DB->get_records_sql(
                    'SELECT * FROM mdl_utools_selenium_results WHERE build_id=?',
                    array($build['id'])
                );

                if (!$existing_records) {
                    $DB->insert_record('utools_selenium_results', $record);
                }
            }
        }
    }
    
    /**
     * Description - You can view the date ranges of the Jenkins builds, just need to pass in the range
     * @param type array - pass in the date range
     * @return JSON set of data
     */
    public function returnJsonFromDB($dates_array)
    {
         global $DB;
         $datesArray = explode(',', $dates_array['date_values']);
         
         list($dsql, $dparams) = $DB->get_in_or_equal($datesArray);
         $sql = 'SELECT max(id) as id,
                        count(*) as number_of_records, 
                        count(CASE WHEN result = \'SUCCESS\' THEN 1 END) as successful_builds_no,
                        min(CASE WHEN result = \'FAILURE\' THEN 0 ELSE 1 END) as is_success,
                        min(duration) as best_duration,
                        max(build_date) as build_date     
                 FROM   mdl_utools_selenium_results
                 WHERE build_date '.$dsql.'
                 GROUP BY build_date';
         
         $get_selenium_results = $DB->get_records_sql($sql, $dparams);
         die(json_encode($get_selenium_results));
    }
    
    public function returnPieChartFromDB($dates_array)
    {
         global $DB;
         $datesArray = explode(',', $dates_array['date_values']);
         list($dsql, $dparams) = $DB->get_in_or_equal($datesArray);
         $sql = 'SELECT  (count(CASE WHEN successful_queries.is_success=1 THEN 1 END)/count(*)::float)*100 as Passed, 100-(count(CASE WHEN successful_queries.is_success=1 THEN 1 END)/count(*)::float)*100 as Failed   
                 FROM(
                        SELECT   min(CASE WHEN result = \'FAILURE\' THEN 0 ELSE 1 END) as is_success
                        FROM     mdl_utools_selenium_results
                        WHERE build_date '.$dsql.'	
                        GROUP BY build_date
                ) as successful_queries';

         
        $get_pie_chart_results = null;
        $get_pie_chart_results_count = $DB->count_records("mdl_utools_selenium_results");
        if ($get_pie_chart_results_count > 0) {
            $get_pie_chart_results = $DB->get_records_sql($sql, $dparams);
        }

             
        die(json_encode($get_pie_chart_results));
    }
    
    /**
     * Description - This is to view enrollments that are stuck in the enrol_people table but not
     *               in the mdl_user table which prevents them from being enrolled in any courses.
     * @param type optional array - currently nothing
     * @return JSON set of data
     */
    public function refreshDeadEnrollments($params = null)
    {
        include_once('../../../enrol/lmb/tools/DeadEnrollments.php');

        $dead_humanoid = new DeadEnrollments();
        $dead_result = $dead_humanoid->refreshUsers();

        die (json_encode(array("success" => "true", "data" => $dead_result)));
    }

    public function showBuildsFromDB($dates_array)
    {
        global $DB;
        $build_date = $dates_array['date_values'];
        //list($dsql, $dparams) = $DB->get_in_or_equal($datesArray);
        $get_all_build_results = $DB->get_records_sql(
            'SELECT build_no, url, result, build_date FROM mdl_utools_selenium_results WHERE build_date = ?',
            array($build_date)
        );
        die(json_encode($get_all_build_results));
    }
    
    public function jenkinsCurlCall($params = null)
    {
        /**
         * Description
         * @param string $function_name
         * @param array of params
         * @param string pass in the token
         * @return string
         */
        $serverurl = $params['jenkins_url'];

        if ($serverurl == "false" || !isset($serverurl)) {
            $serverurl = "http://parmenion.netsrv.uleth.ca:8080/jenkins/job/UMoodle-Nightly-Dev-All-Build-Selenium/api/json?pretty=true&depth=2";
        }

        $token = 'cdfe4435935bc37af723a3b7beef0d06';
        $username = "david.lowe";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serverurl);
        curl_setopt($ch, CURLOPT_USERPWD, $username.":".$token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_HEADER,  false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

        $response = curl_exec($ch);
        //echo $response;
        //return $response;

        curl_close($ch);
     
        return $response;

    }

    public function showUsersInCourses($params = null)
    {

        global $DB;

        $cid = isset($params['courseid']) ? $params['courseid'] : -1;
        $getJSON = isset($params['json']) ? $params['json'] : false;

        if ($cid == -1) {
            die (json_encode(array("success" => "false", "msg" => "Missing course id")));
        }

        if ($getJSON) {
            $all_courses = $DB->get_records('course', array(), 'fullname ASC');
            $json_list = array();
            $j_count = 0;
            ini_set('memory_limit', '256M');
            foreach ($all_courses as $this_course) {
                $context = context_course::instance($this_course->id);
                $userfields = 'u.id, u.idnumber, u.firstname, u.lastname, u.email, u.uofl_id, r.shortname';
                $peeps_in_course = get_role_users(5, $context, $userfields);
                
                foreach ($peeps_in_course as $student) {
                    $student_list[] = array(
                        'SID' => $student->idnumber,
                        'firstname' => $student->firstname,
                        'lastname' => $student->lastname,
                        'email' => $student->email,
                        'u_id' => $student->uofl_id,
                        'role' => $student->shortname
                    );
                    $json_list[$j_count] = array(
                        'sid' => $student->idnumber,
                        'sourcedid' => $this_course->idnumber,
                        'enrol_count' => count($peeps_in_course),
                        'course_name' => $this_course->fullname
                    );
                    $j_count++;
                }
            }
            die (json_encode(array("success" => "true", "data" => $json_list)));

        } else {
            $context = context_course::instance($cid);
            $userfields = 'u.id, u.idnumber, u.firstname, u.lastname, u.email, u.uofl_id, r.shortname';
            $students = get_role_users(5, $context, false, $userfields);
            $student_list = null;

            foreach ($students as $student) {
                // error_log(print_r($student, 1));
                $student_list[] = array(
                    'SID' => $student->idnumber,
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'email' => $student->email,
                    'u_id' => $student->uofl_id,
                    'role' => $student->shortname
                );
            }
            die (json_encode(array("success" => "true", "data" => $student_list)));
        }

        die;
    }

    /**
     * Description - This function compares a list of courses, from banner, that have been passed in, with user counts,
     *      and finds which ones are out of sync.
     * @param type string - massive list of course CRN's followed by user count in JSON format,
     *      ex: {{course:30002,users:69}, {course:30003,users:60}}
     * @return json list all the courses that are not in sync.
     */
    public function bannerMoodleCompare($params = null)
    {
        global $DB, $CFG;
        // error_log("\n");
        // error_log("\nUtoolsAjax -> bannerMoodleCompare() - START ");
        
        $course_data = isset($params['course_data']) ? $params['course_data'] : false;
        
        // error_log("\nUtoolsAjax -> bannerMoodleCompare -> what is course data: ". print_r($course_data, 1));

        // $course_list = explode(",", $course_data);
        $course_data = html_entity_decode($course_data);
        $course_list = json_decode($course_data);
        
        // error_log("\nUtoolsAjax -> bannerMoodleCompare -> what is course_list: ". print_r($course_list, 1));
        
        // error_log("\nWhat is the last json error: ". json_last_error());
        
        // error_log("\nUtoolsAjax -> bannerMoodleCompare -> what is course list size: ". count($course_list));

        $not_synced = array();

        $select = 'idnumber=:idnumber'; // .$course_obj['course'];
        $params = array();


        foreach ($course_list as $course_obj) {
            // error_log("\n--------------------");
            // error_log("What is the course object: ". print_r($course_obj, 1));
            // error_log("What is the course: ". $course->course);
            // error_log("What is the user count: ". $course->users);
            $params['idnumber'] = '201503.' . $course_obj->course;
            // error_log("What is the select: ". $select);
            
            // error_log("What is the idnumber params: ". $params['idnumber']);

            $this_course_id = $DB->get_record_select(
                'course',
                "idnumber=:idnumber",
                array('idnumber' => $course_obj->course . '.201503')
            );

            // $this_course_id = $DB->get_record_select("course", $select, $params);
            // $this_course_id = $DB->get_record_select("course", $select, $params);
            // error_log("What is the course id object: ". print_r($this_course_id, 1));
            // error_log("What is the json CRN: ". $course_obj->course. " and the Moodle course id: ". $this_course_id->id);
            if (isset($this_course_id->id)) {
                //
                $context = context_course::instance($this_course_id->id);
                $students = get_role_users(5, $context);
                
                $moodle_student_count = count($students);
                $banner_student_count = (int)$course_obj->users;

                if ($moodle_student_count != $banner_student_count) {
                    // error_log(
                    //     "How many students according to Moodle: ".
                    //     $moodle_student_count. " and banner: ".
                    //     $banner_student_count
                    // );

                    $not_synced[] = array(
                        "id" => $this_course_id->id,
                        "sname" => $this_course_id->shortname,
                        "idnumber" => $this_course_id->idnumber,
                        "banner_count" => $banner_student_count,
                        "moodle_count" => $moodle_student_count
                    );
                }
            } else {
                error_log("\nThis course has failed: ". $course_obj->course);
            }
    
        }
        
        // error_log("\nUtoolsAjax -> bannerMoodleCompare() - RETURNING");
        
        die (json_encode(array("success" => "true", "data" => $not_synced)));


    }

    public function newBootPage($params = null)
    {
        global $DB, $CFG;
        include_once('bootPagination.php');
        
        $course_search = isset($params['search']) ? $params['search'] : false;
        $this_page = isset($params['page']) ? $params['page'] : 0;

        $pg = new BootPagination();

        if ($course_search && $course_search != "false") {
            $select = "LIKE \"%".$course_search."%\"";
            $select = "lower(fullname) SIMILAR TO '(".$course_search.")%'";

            $this_page = substr($course_search, 0, 1);
            $this_num = $pg->letterToPageNumber($this_page);
            $pg->pagenumber = $this_num;

        } else {
            $this_letter = $pg->alphaPageNumber($this_page);
            if ($this_letter == "start") {
                $this_letter = "a";
            }

            $select = "lower(fullname) SIMILAR TO '(".$this_letter.")%'";
            $pg->pagenumber = $this_page;

        }
        
        if (!isset($_SESSION['utools_cue_course_count'])) {
            $all_courses_big = $DB->get_records('course');
            $_SESSION['utools_cue_course_count'] = count($all_courses_big);
        }

        $table = 'course';

        $all_courses_with_letter = $DB->get_records_select($table, $select, null, 'fullname ASC');

        $course_count = count($all_courses_with_letter);

        $pg->pagesize = $course_count;
        $pg->totalrecords = $_SESSION['utools_cue_course_count'];
        $pg->showfirst = true;
        $pg->showlast = true;
        $pg->paginationcss = "pagination-normal";
        $pg->paginationstyle = 2; // 0: normal, 1: advanced, 2: alpha
        // $pg->defaultUrl = "index.php";
        // $pg->paginationUrl = "index.php?p=[p]";

        //Display Menu
        // echo $OUTPUT->header();

        /* print the header bar */
        // $ulethlib->printUtoolsBar();
        $paginator_result = $pg->process();
        

        // ************************************************************************************
        // now build the rows

        $odd_even = 0;
        $row_dark = "utools_course_users_enrolled_row_dark";
        $row_light = "utools_course_users_enrolled_row_light";
        $this_html = "";

        foreach ($all_courses_with_letter as $this_course) {
            //
            $context = context_course::instance($this_course->id);
            $students = get_role_users(5, $context);
            $student_list = null;
            foreach ($students as $student) {
                $student_list[] = array(
                    'SID' => $student->idnumber,
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'email' => $student->email,
                    'role' => $student->roleshortname
                );
            }
            if ($odd_even % 2) {
                $this_css_row = $row_dark;
            } else {
                $this_css_row = $row_light;
            }

            $this_html .= '<div class="row-fluid utools_course_users_enrolled_row ' . $this_css_row . '">
                <div class="col-sm-8">
                    <a href="' .
                $CFG->wwwroot . '/course/view.php?id=' . $this_course->id . '">' .
                $this_course->idnumber . ' - ' . $this_course->fullname . '</a>
                </div>
                
                <div class="col-sm-4">
                    <button class="courseUsersEnrolled_view_user_list btn btn-primary pull-right" 
                        data-utools_users_enrolled_cid="' . $this_course->id . '"
                        data-utools_users_enrolled_name="' . $this_course->fullname . '">View '.
                        count($students).
                        ' users
                    </button>
                </div>
            </div>';
            $odd_even++;
        }
        die (json_encode(array("success" => "true", "html" => $this_html, "page_result" => $paginator_result)));
    }

    /**
     * Description - This function is for testing purposes only, call runTest in Utools
     * @param type string - massive list of course CRN's followed by user count in JSON format,
     *      ex: {{course:30002,users:69}, {course:30003,users:60}}
     * @return json list all the courses that are not in sync.
     */
    public function runTest($params = null)
    {
        global $DB, $CFG;
        
        error_log("\n");
        error_log("\nUtoolsAjax -> runTest() - START ");
        
        date_default_timezone_set('America/Edmonton');
        // $exam_start = strtotime(date('Y-m-d') . ' 9:00:0');
        // $exam_done = strtotime(date('Y-m-d') . ' 9:00:0') + (1 * 12 * 60 * 60);
        $exam_start = DateTime::createFromFormat('m/d/Y - H:i', date('m/d/Y') . ' - 09:00', new DateTimeZone('America/Edmonton'));
        $exam_end = DateTime::createFromFormat('m/d/Y - H:i', date('m/d/Y') . ' - 09:00', new DateTimeZone('America/Edmonton'));
        $exam_end->modify("+12 hours");

        $est = $exam_start->getTimestamp();
        $eet = $exam_end->getTimestamp();


        error_log("\n nUtoolsAjax -> runTest() -> what is exam start: ". $est);
        error_log("\n nUtoolsAjax -> runTest() -> what is exam end: ". $eet);

        $exam_count = $DB->get_records_sql(
            'SELECT count(*) as exams_no
             FROM mdl_local_tcms_student_entry
             WHERE signed_in < ? AND signed_out > ?',
            array($est, $eet)
        );

        error_log("\n nUtoolsAjax -> runTest() -> what is exam count: ". print_r($exam_count, 1));

        /*
        include_once('UtoolsLib.php');

        $ulib = new UtoolsLib();

        $what_dis = $ulib->checkUtoolsUserLevel(array('user' => '44992'));

        error_log("What is the user level: ". $what_dis);

        return;

        $context = context_course::instance('927');

        $userfields = 'u.id, u.username, u.uofl_id, ' . get_all_user_name_fields(true, 'u');
        // $roleusers = get_role_users($roleid, $context, false, $userfields);
        $students = get_role_users(5, $context, false, $userfields);
        
        // error_log("What is the list of students: " . print_r($students, 1));
            // error_log($student->id . " - " . $student->username . " - " . $student->uofl_id);
        // }

        //Open the File Stream
        $handle = fopen("/Users/davidlowe/Sites/logs/PHP_writer", "a");
        $data = "";
        //Lock File, error if unable to lock
        if (flock($handle, LOCK_EX)) {
            error_log("Going to write to file!");

            foreach ($students as $student) {
                $data .= $student->id . " - " . $student->username . " - " . $student->uofl_id . "\n";
            }
            // do anything to fill variable $data
            fwrite($handle, $data);    //Write the $data into file
            flock($handle, LOCK_UN);    //Unlock File
        } else {
            error_log("Could not Lock File!");
        }

        //Close Stream
        fclose($handle);

        // $moodle_student_count = count($students);
                
        */

        error_log("\nUtoolsAjax -> runTest() - RETURNING ");
    }

    public function getSystemLoad() {
        $loads = sys_getloadavg();
        error_log("\nWTF is loads all over your face: ". print_r($loads, 1));
        $core_nums = trim(shell_exec("grep -P '^physical id' /proc/cpuinfo|wc -l"));
        $load = $loads[0]/$core_nums;
        
        error_log("\nwho blew their load over your face: ". print_r($load, 1));

    }

    public function getServerLoadLinuxData() {
        if (is_readable("/proc/stat")) {
            $stats = @file_get_contents("/proc/stat");

            if ($stats !== false) {
                // Remove double spaces to make it easier to extract values with explode()
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);

                // Separate lines
                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);

                // Separate values and find line for main CPU load
                foreach ($stats as $statLine) {
                    $statLineData = explode(" ", trim($statLine));

                    // Found!
                    if ((count($statLineData) >= 5) && ($statLineData[0] == "cpu")) {
                        return array(
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        );
                    }
                }
            }
        }

        return null;
    }

    // Returns server load in percent (just number, without percent sign)
    public function getServerLoad() {
        $load = null;

        if (stristr(PHP_OS, "win")) {
            $cmd = "wmic cpu get loadpercentage /all";
            @exec($cmd, $output);

            if ($output) {
                foreach ($output as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $load = $line;
                        break;
                    }
                }
            }
        } else {
            if (is_readable("/proc/stat")) {
                // Collect 2 samples - each with 1 second period
                // See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
                $statData1 = $this->getServerLoadLinuxData();
                sleep(1);
                $statData2 = $this->getServerLoadLinuxData();

                if ((!is_null($statData1)) && (!is_null($statData2))) {
                    // Get difference
                    $statData2[0] -= $statData1[0];
                    $statData2[1] -= $statData1[1];
                    $statData2[2] -= $statData1[2];
                    $statData2[3] -= $statData1[3];

                    // Sum up the 4 values for User, Nice, System and Idle and calculate
                    // the percentage of idle time (which is part of the 4 values!)
                    $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

                    // Invert percentage to get CPU time, not idle time
                    $load = 100 - ($statData2[3] * 100 / $cpuTime);
                }
            }
        }

        return $load;
    }

    //----------------------------
    public function getSystemLoad2() {

        $cpuLoad = $this->getServerLoad();
        if (is_null($cpuLoad)) {
            error_log("CPU load not estimateable (maybe too old Windows or missing rights at Linux or Windows)");
        } else {
            error_log($cpuLoad . "%");
        }
    }

    public function getSystemInfo() {
        //cpu stat
        $prevVal = shell_exec("cat /proc/stat");
        $prevArr = explode(' ',trim($prevVal));
        $prevTotal = $prevArr[2] + $prevArr[3] + $prevArr[4] + $prevArr[5];
        $prevIdle = $prevArr[5];
        usleep(0.15 * 1000000);
        $val = shell_exec("cat /proc/stat");
        $arr = explode(' ', trim($val));
        $total = $arr[2] + $arr[3] + $arr[4] + $arr[5];
        $idle = $arr[5];
        $intervalTotal = intval($total - $prevTotal);
        $stat['cpu'] =  intval(100 * (($intervalTotal - ($idle - $prevIdle)) / $intervalTotal));
        $cpu_result = shell_exec("cat /proc/cpuinfo | grep model\ name");
        $stat['cpu_model'] = strstr($cpu_result, "\n", true);
        $stat['cpu_model'] = str_replace("model name    : ", "", $stat['cpu_model']);
        //memory stat
        $stat['mem_percent'] = round(shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'"), 2);
        $mem_result = shell_exec("cat /proc/meminfo | grep MemTotal");
        $stat['mem_total'] = round(preg_replace("#[^0-9]+(?:\.[0-9]*)?#", "", $mem_result) / 1024 / 1024, 3);
        $mem_result = shell_exec("cat /proc/meminfo | grep MemFree");
        $stat['mem_free'] = round(preg_replace("#[^0-9]+(?:\.[0-9]*)?#", "", $mem_result) / 1024 / 1024, 3);
        $stat['mem_used'] = $stat['mem_total'] - $stat['mem_free'];
        //hdd stat
        $stat['hdd_free'] = round(disk_free_space("/") / 1024 / 1024 / 1024, 2);
        $stat['hdd_total'] = round(disk_total_space("/") / 1024 / 1024/ 1024, 2);
        $stat['hdd_used'] = $stat['hdd_total'] - $stat['hdd_free'];
        $stat['hdd_percent'] = round(sprintf('%.2f',($stat['hdd_used'] / $stat['hdd_total']) * 100), 2);
        //network stat
        $stat['network_rx'] = round(trim(file_get_contents("/sys/class/net/eth0/statistics/rx_bytes")) / 1024/ 1024/ 1024, 2);
        $stat['network_tx'] = round(trim(file_get_contents("/sys/class/net/eth0/statistics/tx_bytes")) / 1024/ 1024/ 1024, 2);
        //output headers
        header('Content-type: text/json');
        header('Content-type: application/json');
        //output data by json
        $data = "{\"cpu\": " . $stat['cpu'] . ", \"cpu_model\": \"" . $stat['cpu_model'] . "\"" . //cpu stats
        ", \"mem_percent\": " . $stat['mem_percent'] . ", \"mem_total\":" . $stat['mem_total'] . ", \"mem_used\":" . $stat['mem_used'] . ", \"mem_free\":" . $stat['mem_free'] . //mem stats
        ", \"hdd_free\":" . $stat['hdd_free'] . ", \"hdd_total\":" . $stat['hdd_total'] . ", \"hdd_used\":" . $stat['hdd_used'] . ", \"hdd_percent\":" . $stat['hdd_percent'] . ", " . //hdd stats
        "\"network_rx\":" . $stat['network_rx'] . ", \"network_tx\":" . $stat['network_tx'] . //network stats
        "}";

        die (json_encode(array("success" => true, "data" => $data)));
    }
}
