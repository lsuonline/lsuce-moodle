<?php
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe, Md Asif Khan                                 **
 * ************************************************************************
 * ********************************************************************** */

class DevSuiteAjax
{
    private $ulethlib = null;

    public function __construct()
    {
        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();
    }

    public function jenkinsCurlCall()
    {
        // error_log("\n**************************************************************************************");
        // error_log("\n**************************************************************************************");
        // error_log("\n***********************    Welcome to cURL Web Services    ***************************");
        // error_log("serverurl=> " . $serverurl);
        /**
         * Description
         * @param string $function_name
         * @param array of params
         * @param string pass in the token
         * @return string
         */
        $serverurl = 'http://parmenion.netsrv.uleth.ca:8080/jenkins/job/UMoodle-Nightly-Dev-All-Build-Selenium/api/json?pretty=true&depth=2';
        $token = 'cdfe4435935bc37af723a3b7beef0d06';
        $username = "david.lowe";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serverurl);
        curl_setopt($ch, CURLOPT_USERPWD, $username.":".$token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($ch);
        curl_close($ch);
        // error_log("\nDevSuite -> jenkinsCurlCall() -> Response is: " . print_r($response, 1));
        if ($response == "" || !$response) {
            die(json_encode(array("success" => "false", "msg" => "Sorry, but Jenkins is unavailable.......")));
        } else {
            die(json_encode(array("success" => "true", "data" => $response)));
        }
        // error_log(print_r($response), true);
        // echo $response;
        // return $response;

        // error_log(curl_error($ch));
        // error_log("\n\n");
        // error_log("\n\n");

        // error_log("\nCOMPLETED....\n\n");

    }

    /**
     * [updateJiraData checks if the data exists if yes then "Update" if no then "insert" into the DB]
     * @param  [jira data] $params [description]
     * @return ["INSERT" OR "UPDATE"]  [description]
     */
    public function updateJiraData($params = null)
    {
        global $DB;
        $jira_release = isset($params['new_jira_release']) ? $params['new_jira_release'] : null;
        $jira_start_date = isset($params['new_jira_start_date']) ? $params['new_jira_start_date'] : null;
        $jira_release_date = isset($params['new_jira_release_date']) ? $params['new_jira_release_date'] : null;
        $pass = isset($params['new_pass']) ? $params['new_pass'] : null;
        $abort = isset($params['new_abort']) ? $params['new_abort'] : null;
        $fail = isset($params['new_fail']) ? $params['new_fail'] : null;

        $jira_data = new stdClass();
        $jira_data->jira_release = $jira_release;
        $jira_data->jira_start_date = $jira_start_date;
        $jira_data->jira_release_date = $jira_release_date;
        $jira_data->func_passed_test = $pass;
        $jira_data->func_failed_test = $fail;
        $jira_data->func_aborted_test = $abort;

        //checks if the data already exists
        $get_jira_data = $DB->get_records_sql("SELECT id FROM {utools_developer_suite} WHERE jira_release = ?", array($jira_release));
        $arr = array_keys($get_jira_data); // remaps to regular array starting from 0

        // error_log(print_r($arr, 1));
        // error_log(sizeof($arr) . "*******************");
        if (!$get_jira_data) {
            // error_log("Doesn't exists------------");
            $jira_updated_data = $DB->insert_record('utools_developer_suite', $jira_data, true);
            die(json_encode(array("success" => "true", "msg" => "Jira has successfully inserted new information.")));
        } else {
            // error_log("Exists------------------");
            if (sizeof($arr) > 1) {
                die(json_encode(array("success" => "fail", "msg" => "Jira DB has repeating data")));
            } else {
                $jira_data->id =  $arr[0];
                // error_log(print_r($jira_data, 1));
                $jira_updated_data = $DB->update_record('utools_developer_suite', $jira_data);
                die(json_encode(array("success" => "true", "msg" => "Jira has successfully updated new information.")));
            }
        }
    }

    /**
     * [drawjiraData Gets all the data from the DB]
     * @param  [NULL] $params [description]
     * @return [JSON data]    [description]
     */
    public function drawjiraData($params = null)
    {
        global $DB;
        $draw_jira_data = $DB->get_records_sql(
            'SELECT *
            FROM mdl_utools_developer_suite'
        );
        die(json_encode(array("success" => "true", "data" => $draw_jira_data)));
    }
}
