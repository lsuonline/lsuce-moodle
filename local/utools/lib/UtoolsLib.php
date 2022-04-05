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
// namespace Utools\Lib;

class UtoolsLib
{

    private $current_site;
    private $is_local;
    private $environment;
    private $term;
    private $log_instance;
    private $url_no_term;
    private static $donefirst;
    
    public function __construct()
    {

        global $CFG;

        $this->is_local = false;
        $this->environment = "";
        $this_current_site_is = $CFG->wwwroot;
        // $this->log_instance = $CFG->local_utools_LMB_instance;
        if (!isset($this->log_instance) || $this->log_instance == '-prod') {
            $this->log_instance = '';
        }

        // make sure the log instance is set to something
        if ($this->log_instance == '' || $this->log_instance == '-test' || $this->log_instance == '-uat') {
            ; // we are safe
        } else {
            $this->log_instance = '-test';
        }

        preg_match_all("/(uleth.ca)/", $this_current_site_is, $matches);

        $url_main = substr($this_current_site_is, 7);

        $this_current_site_is_ssl = null;
        if (@$matches[0][0]) {
            preg_match_all("/(-uat.uleth)/", $this_current_site_is, $is_uat);
            if (@$is_uat[0][0]) {
                $this->environment = "uat";
            } else {
                preg_match_all("/(-uat.uleth)/", $this_current_site_is, $is_test);
                if (@$is_uat[0][0]) {
                    $this->environment = "test";
                }
            }

            $this->current_site = $CFG->wwwroot."/";
            $this->is_local = false;
            
            $spot_it = strrpos($CFG->wwwroot, "uleth.ca");
            // get the term
            $spot_it = $spot_it + 9;
            $this->term = substr($CFG->wwwroot, $spot_it, 6);

            // get the raw url
            $this->url_no_term = substr($CFG->wwwroot, 0, $spot_it);

        } else {
            $url_main = substr($this_current_site_is, 7);
            $this->current_site = "http://".$url_main."/";
            $url_main = str_replace('u', '', $url_main); // Replaces all spaces with hyphens.
            $this->term = $url_main;
            $this->is_local = true;
            $this->environment = "local";
        }
    }


    public static function UofL_Load_Module($module)
    {
        global $PAGE;
        // error_log("\n");

        if (!self::$donefirst) {
            
            // error_log("\nUofL_Load_Module() -> Going to set donefirst to true");

            $PAGE->requires->jquery();
            $PAGE->requires->js(new \moodle_url('/local/utools/js/define.js'));
            self::$donefirst = true;
        }
        
        // error_log("\nUofL_Load_Module() -> Going to set donefirst to true");

        $PAGE->requires->js_init_code('UofL_Utools_add_pending("' . $module . '");');
        $PAGE->requires->js(new \moodle_url('/local/utools/' . $module . '.js'));
    }

    public static function UofL_Call_Module($module, $fn, array $params = array())
    {
        global $PAGE;
        // error_log("\n");

        $jsparams = '';
        // error_log("\nUofL_Call_Module() -> START");

        foreach ($params as $param) {
            if ($jsparams !== '') {
                $jsparams .= ',';
            }
            if (is_int($param)) {
                $jsparams .= $param;
            } else if (is_string($params)) {
                $jsparams .= '"' . addslashes_js($param) . '"';
            } else {
                throw new \coding_exception('Unexpected JS param');
            }
        }
        // error_log("\nUofL_Call_Module() -> FINISHED");

        $PAGE->requires->js_init_code('window._modules.' . $module .'.' . $fn . '(' . $jsparams . ')');
    }

    /**
     * Description, print to the log's based on what can and cannot print
     * @param type  string - the message
     *              string - what app is wanting to write to log?
     *              any - print this variable
     *              bool - do we need a print_r()?
     * @return type nothing, just print the message.
     */
    public function printToLog($message = "No message to print", $obj = null, $blowup = 0)
    {
        global $CFG;
        // make sure logging is on otherwise don't print anything.
        $bugger = 0;
        if (gettype($CFG->local_utools_logging) == "string") {
            $bugger = (int)$CFG->local_utools_logging;
        }
        if ($bugger == 1 || $bugger == 3) {
            if ($blowup) {
                error_log($message . print_r($obj, 1));
            } else {
                error_log($message . $obj);
            }
        } else {
            return;
        }
    }

    /**
     * return if this object is local or remote
     * @return bool, true if local
     */
    public function getCFG()
    {
        global $CFG;
        return $CFG;
    }

    /**
     * This grabs a log file and returns it as a json file as it's called from AJAX
     * @return json encoded data
     */
    public function getLogFile()
    {
    
        // $ulethlib = new UtoolsLib();
        global $CFG;

        if ($this->is_local) {
            $filename = $CFG->dirroot . "/../logs/enrol_" . $this->term.'.log';
        } else {
            $this_instance = "";
            if ($this->environment == "uat") {
                $this_instance = "-uat";
                $filename = '/moodle/logs/enrol/' . $this->term . '' . $this_instance .'.log';
            } else {
                $filename = '/moodle/logs/enrol/' . $this->term . '' . $this_instance .'.log';
            }
        }
        
        // $log_file = file_get_contents($filename);
        
        $fp = fopen($filename, "r");
        $log_file = "";
        while (! feof($fp)) {
            $log_file .= fgets($fp). "<br />";
        }

        fclose($fp);
        
        die (json_encode($log_file));

    }

    /**
     * return the site back so we know if we are local or remote
     * @return get the current site
     */
    public function getCurrentSite()
    {
        return $this->current_site;
    }

    /**
     * Return the term we are on, 201401, 201402....etc.
     * @return string
     */
    public function getCurrentTerm()
    {
        return $this->term;
    }
    
    /**
     * Return the term we are on, 201401, 201402....etc.
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
    
    /**
     * Return the raw URL with no term.
     * @return string
     */
    public function getRawURL()
    {
        return $this->url_no_term;
    }
    
    /**
     * return if this object is local or remote
     * @return bool, true if local
     */
    public function isLocal()
    {
        return $this->is_local;
    }
    
    /**
     * return what log instance we need
     * @return string should be either -dev, -testing, or prod
     */
    public function getLogInstance()
    {
        return $this->log_instance;
    }

    /**
     * Is this an admin user?
     * @return bool
     */
    public function checkAdminUser()
    {

        //require_once('../../../config.php');

        $context = context_system::instance();
        $admin_access = has_capability('moodle/site:config', $context);
        if ($admin_access) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Is this an utools user?
     * @param array - pass in the user id
     * @return string as either Administrator, Watcher, Instructor or hack, if hack then user is not allowed.
     */
    public function checkUtoolsUserLevel($params = null)
    {
        global $DB, $CFG, $USER;

        // 3 levels are:
        // Administrator
        // Watcher
        // Instructor
        
        $this_user = isset($params['user']) ? $params['user'] : null;
        if ($this_user == null) {
            if ($this->checkAdminUser()) {
                return 'Administrator';
            }
            
            // error_log("UtoolsLib -> this_user is null, going to grab USER->id: ". $USER->id);
            $params = array($USER->id);
        } else {
            // error_log("UtoolsLib -> this_user is NOTnull, going to grab this_user: ". $this_user);
            $params = array($this_user);
        }
        // error_log("UtoolsLib -> What is params: ". print_r($params, 1));

        $sql = "SELECT role FROM {utools_user_access} WHERE userid = ?";
        $hack = 'hack';
        
        $user_role = $DB->get_records_sql($sql, $params);
        // error_log("UtoolsLib -> What is userrole BEFORE: ". print_r($user_role, true). " and hack: ". $hack);

        foreach ($user_role as $da_user) {
            $hack = $da_user->role;
        }
        $this->printToLog("UtoolsLib -> What is userrole AFTER: ", "", $user_role, true);
        // error_log("UtoolsLib -> What is userrole: ". print_r($user_role, true). " and hack: ". $hack);
        return $hack;
    }

    public function getRandomNumbers($min = 1, $max = 10, $count = 1, $margin = 0)
    {
        $range = range(0, $max-$min);
        $return = array();
        for ($i=0; $i<$count; $i++) {
            if (!$range) {
                trigger_error("Not enough numbers to pick from!", E_USER_WARNING);
                return $return;
            }
            $next = rand(0, count($range)-1);
            $return[] = $range[$next]+$min;
            array_splice($range, max(0, $next-$margin), $margin*2+1);
        }
        return $return;
    }

    /**
     * Convert to stdClass Objects to Multidimensional Arrays
     * @param array
     * @return object
     */
    public function objectToArray($d)
    {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map(__FUNCTION__, $d);
        } else {
            // Return array
            return $d;
        }
    }

    /**
     * Convert Multidimensional Arrays to stdClass Objects
     * @param array
     * @return object
     */
    public function arrayToObject($d)
    {
        if (is_array($d)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return (object) array_map(__FUNCTION__, $d);
        } else {
            // Return object
            return $d;
        }
    }

    /**
     * This is an old function to view students in a course, still needed but will
     * eventually need to be updated.
     * @return message of success or fail with data
     */
    public function getJSON($params = null)
    {
    
        global $DB;
        
        $which_part = isset($params['getUsersInCoursesJSON']) ? $params['getUsersInCoursesJSON'] : 'null';
        
        if ($which_part == "getUsersInCoursesJSON") {
            //
            $all_courses = $DB->get_records('course', array(), 'fullname ASC');
            $json_list = array();
            $j_count = 0;
            ini_set('memory_limit', '256M');
            foreach ($all_courses as $this_course) {
                // $peeps_in_course = $DB->get_records_sql($query, array($this_course->id));
                $context = context_course::instance($this_course->id);
                $peeps_in_course = get_role_users(5, $context);
            
                foreach ($peeps_in_course as $student) {
                    $student_list[] = array(
                        'SID' => $student->idnumber,
                        'firstname' => $student->firstname,
                        'lastname' => $student->lastname,
                        'email' => $student->email,
                        'role' => $student->roleshortname
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
            // die (json_encode($json_list));
            die (json_encode(array("success" => true, "data" => $json_list)));

        } elseif ($which_part == "showUsersInCourses") {
            if (isset($params['enrol_request'])) {
                $cid = $params['enrol_request'];
                $context = context_course::instance($cid);
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
                die (json_encode(array("success" => true, "data" => $student_list)));

                // die (json_encode($student_list));
            } else {
                die (json_encode(array("success" => false, "data" => array())));
            }
        }

        die (json_encode(array("success" => false, "data" => array())));
        
    }

    /**
     * File Manager
     * @param string param, should be either file list or file name. If you want to remove
     * certain file types from the list then you can 'exclude' it, for example. 'mbz'
     * @return message of success or fail with data
     */
    public function utoolsFileManager($params = "")
    {
        global $DB, $CFG;
        
        include_once($CFG->dirroot . '/lib/moodlelib.php');
        include_once($CFG->dirroot . '/lib/externallib.php');

        $context = context_system::instance();
        $fs = get_file_storage();

        $exclude = isset($params['exclude']) ? $params['exclude'] : null;
        $file_action = isset($params['file_action']) ? $params['file_action'] : null;

        if ($file_action == "get_list") {
            $sql = "SELECT * FROM mdl_files WHERE component like '%utools%'";
            $file_list = $DB->get_records_sql($sql);
            if ($exclude != null) {
                // need to remove the excluded extensions from this list
                foreach ($file_list as $file_temp => $file_value) {
                    //
                    if (substr($file_list[$file_temp]->filename, -3) == $exclude) {
                        unset($file_list[$file_temp]);
                    }
                }
            }
            // sometimes we get "." as a file, let's just remove that nonsense.
            foreach ($file_list as $file_temp => $file_value) {
                if ($file_list[$file_temp]->filename == ".") {
                    unset($file_list[$file_temp]);
                }
            }

            return $file_list;
        } elseif ($file_action == "get_file") {
            $file_id = isset($params['file_id']) ? $params['file_id'] : null;

            if ($file_id) {
                $found_file = $fs->get_file_by_id($file_id);
                // $funny_string = $found_file->get_content();
                if ($found_file) {
                    return $found_file->get_content();
                } else {
                    return false;
                }
            } else {
                // maybe the filename was passed in? (will be true for stack reporting)
                $filename = isset($params['filename']) ? $params['filename'] : null;
                if ($filename) {
                    // $contextid, $component, $filearea, $itemid, $filepath, $filename
                    $found_file = $fs->get_file($context->id, "local_utools", "testfiles", null, "/", $filename);
                    // $funny_string = $found_file->get_content();
                    if ($found_file) {
                        return $found_file->get_content();
                    } else {
                        return false;
                    }
                }
                return false;
            }
        }
    }

    /**
     * UI builder
     * @param string title (may or may not be used
     * @return type
     */
    public function printUtoolsBar($title = "")
    {
        global $CFG;
        if (!$title) {
            $title = 'UofL Tools';
        }

        $nav = '<div class="navbar">
            <div class="navbar-inner">
                <a class="brand" href="'.$CFG->wwwroot.'/local/utools/">'.$title.'</a>
                <div class="nav-no-collapse header-nav">
                    <ul class="nav navbar-nav navbar-right pull-right">
                        <li><a href="'.
                        $CFG->wwwroot.
                        '/admin/settings.php?section=local_utools"><i class="icon-cog"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>';

        return $nav;
    }


    public function simulatedJenkinsCron($params)
    {

        global $DB;
        require_once('lib/UtoolsAjax.php');

        $params = array(
            "jenkins_url" => "http://parmenion.netsrv.uleth.ca:8080/jenkins/job/UMoodle-Nightly-Dev-All-Build-Selenium/api/json?pretty=true&depth=2"
        );

        $jenkins = new UtoolsAJAX();
        $jenkinsJsonObject = $jenkins->jenkinsCurlCall($params);
        $jenkins->parseAndStoreJenkins($jenkinsJsonObject);

    }
}
