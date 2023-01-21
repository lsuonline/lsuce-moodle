<?php
// This file is part of Moodle - http://moodle.org/
//
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
 *
 * @package    enrol_d1 - Jenzabar/CSV Import Tool
 * @copyright  2022 onwards Louisiana State University
 * @copyright  2022 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace enrol_d1\jenzabar;
// defined('MOODLE_INTERNAL') || die();

class bundle {

    private $bugfiles;
    public $pagenumber;
    public $pagesize;
    public $totalcount;
    public $restcalled;
    public $report;
    public $settofuture;
    public $convertbundle;

    public function __construct(&$report, $dataobj, $extras) {
        $this->bugfiles = get_config('enrol_d1', 'extradebug');
        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
        // error_log("bundle construct -> what is extras: ". print_r($extras, 1));
        !empty($extras['convertbundle']) ? $this->convertbundle = true : false;

        $this->eb = new \stdClass();
        $this->eb->enrollStudentInBundleRequestDetail = new \stdClass();
        $this->eb->enrollStudentInBundleRequestDetail->matchOn = $dataobj[1];
        $this->eb->enrollStudentInBundleRequestDetail->attributeValue = $dataobj[2];
        $this->eb->enrollStudentInBundleRequestDetail->bundleProfileCode = $dataobj[3];
        $this->eb->enrollStudentInBundleRequestDetail->enrollmentDate = $this->fix_for_d1_date($dataobj[4]);
        $this->eb->enrollStudentInBundleRequestDetail->groupPayor = $dataobj[5];
        !empty($dataobj[6]) ? $this->eb->enrollStudentInBundleRequestDetail->groupCorporateContract = $dataobj[6] : null;
        $this->eb->enrollStudentInBundleRequestDetail->paymentAmount = $dataobj[7];
        $this->eb->enrollStudentInBundleRequestDetail->outstandingAmount = $dataobj[8];

        !empty($dataobj[9]) ? $this->eb->enrollStudentInBundleRequestDetail->invoiceDueDate = $dataobj[9] : null;
        !empty($dataobj[10]) ? $this->eb->enrollStudentInBundleRequestDetail->transactionBasketComments = $dataobj[10] : null;
        !empty($dataobj[11]) ? $this->eb->enrollStudentInBundleRequestDetail->enforceEnrollmentRules = $dataobj[11] : null;
        !empty($dataobj[12]) ? $this->eb->enrollStudentInBundleRequestDetail->enforceProfileHolds = $dataobj[12] : null;
        /*
        {
            "enrollStudentInBundleRequestDetail": {
                "attributeValue": "X001658",
                "bundleProfileCode": "BU0070",
                "enrollmentDate": "14 Aug 2019",
                "matchOn": "studentNumber",
                "outstandingAmount": "79",
                "paymentAmount": "0"
            }
        }*/
    }

    /**
     * Intitialize dodads here.
     * @param   @object   Data from the CSV file
     * @param   @object   Any extra tidbits
     * @return  @bool   return success or fail
     */
    public function init($rowdata = "", $extras = array()) {

    }

    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process() {

        $processresult = false;
        if ($this->convertbundle) {
            $this->convert_bundle();
        } else {
            $pstart = microtime(true);

            $result = $this->enroll_bundle();
            if ($result['callsuccess'] == true) {
                error_log("\e[0;32mStudent: ". $this->eb->enrollStudentInBundleRequestDetail->attributeValue. " Successfully enrol in: ".
                    $this->eb->enrollStudentInBundleRequestDetail->bundleProfileCode);
                $processresult = true;
            } else {

                if(strpos($result['msg'], "is already enrolled in bundle") !== false){
                    error_log("\e[0;34mNOTICE: ". $result['errorCode']. " Msg: ". $result['msg']);
                } else {
                    error_log("\e[0;31mStudent: ". $this->eb->enrollStudentInBundleRequestDetail->attributeValue. " failed to enrol in: ".
                        $this->eb->enrollStudentInBundleRequestDetail->bundleProfileCode);
                    error_log("\e[0;31mError: ". $result['errorCode']. " Msg: ". $result['msg']);
                }
            }

            $pend = microtime(true);
            $this->report->timer("addup", $pend - $pstart);
            return $processresult;
        }
    }

    public function post_process($result, $rowdata, $extras) {
        return;
    }

    public function fix_for_d1_date($csvdate) {
        // From Scott's file: May/29/2019
        $newdatetime = \DateTime::createFromFormat('M/j/Y', trim($csvdate));
        return $newdatetime->format('j M Y');
    }

    /* Enrol a bundle
     *
     * @return  @bool
     */
    public function enroll_bundle() {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $level = "Medium";
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewREST/enrollStudentInBundle?_type=json';
        $params->body = json_encode($this->eb);

        // error_log("\n============================================================");
        // error_log("The bundle to send is: ". $params->body);
        // return;
        /* {
            "enrollStudentInBundleRequestDetail": {
                "attributeValue": "X001658",
                "bundleProfileCode": "BU0070",
                "enrollmentDate": "14 Aug 2019",
                "matchOn": "studentNumber",
                "outstandingAmount": "79",
                "paymentAmount": "0"
            }
        } */

        $results = helpers::curly($params);
        /* Return Example:
        {
            "enrollStudentInBundleResult": {
                "status": "OK",
                "responseCode": "Success",
                "basketNumber": 20,
                "studentInfo": {
                    "firstName": "Morgan",
                    "lastName": "Ashman",
                    "objectId": 1430401,
                    "personNumber": "X000024",
                    "preferredEmail": "morgan.ashman@gmail.com",
                    "schoolPersonnelNumber": "",
                    "status": "Active",
                    "userName": "mashman13"
                }
            }
        }
        */
        // error_log("What is the curl results: ". print_r($results, 1));

        if (empty($results)) {
            return array(
                "callsuccess" => false,
                "result" => false,
                "msg" => "Failed to communicate with D1",
                "errorCode" => "Error",
                "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                "sce" => "enrol"
            );
        }

        $header = helpers::log_header();

        // This will write the results to a logging file.
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Bundle_Enroll_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        if (property_exists($results, "SRSException")) {
            return array(
                "callsuccess" => false,
                "result" => $results,
                "msg" => $results->SRSException->message,
                "errorCode" => isset($results->SRSException->errorCode) ? $results->SRSException->errorCode : $results->SRSException->cause,
                "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                "wsc" => "enrol"
            );
        } else if (property_exists($results, "enrollStudentInBundleResult")) {
            return array(
                "callsuccess" => true,
                "result" => $results,
                "msg" => "",
                "errorCode" => "",
                "wsc" => "enrol"
            );
        }
    }

    /* Search for a student in D1 to see if they exist or not
     * level can be either: Ignore, Shortest, Short, Medium, Long, Full, Privileged
     * @param   @object   The student object from the file
     * @return  @object   return web service result
     */
    public function search_student($overwrite_body = false) {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $level = "Medium";
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/searchStudent?informationLevel='. $level. "&_type=json";

        // Set the POST body.
        // $params->body = '{"searchStudentRequestDetail": {"studentSearchCriteria": {"telephoneNumber": "1234","isYouth": "Y"}}';

        // search params to use:
        // userName
        // objectId
        // schoolId
        // studentNumber

        // $params->body = '{"searchStudentRequestDetail": {"studentSearchCriteria": {"loginId": '.$this->student->loginId.'}}}';
        if ($overwrite_body) {
            $params->body = $overwrite_body;
        } else {
            // $params->body = '{"searchStudentRequestDetail": {"studentSearchCriteria": {"email": '. $this->student->student->emails->email->emailAddress.'}}}';
            $params->body = '{"searchStudentRequestDetail": {"studentSearchCriteria": {'.
            '"searchType": "begin_with",'.
            '"email": "'. $this->student->student->emails->email->emailAddress.'"}}}';
        }

        $results = helpers::curly($params);
        if (empty($results)) {
            return array(
                "callsuccess" => false,
                "result" => false,
                "msg" => "Failed to communicate with D1",
                "errorCode" => "Error",
                "sce" => "search"
            );
        }

        $header = helpers::log_header();

        // This will write the results to a logging file.
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Student_Search_Bundle_Results.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        if (property_exists($results, "SRSException")) {
            return array(
                "callsuccess" => false,
                "result" => $results,
                "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                "sce" => "search"
            );
        } else if (property_exists($results, "student")) {
            // Student has been found.
            return array(
                "callsuccess" => true,
                "result" => $results,
                "objectId" => $results->student->objectId,
                "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                "sce" => "search"
            );
            return $results->student->objectId;

        } else if (property_exists($results, "searchStudentResult")) {
            error_log("SS -->> METHOD 2 YOOOOOOOOOOOO");
            if ($results->searchStudentResult->paginationResponse->totalCount == 0) {
                return array(
                    "callsuccess" => true,
                    "result" => $results,
                    "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                    "sce" => "search"
                );
            }
            if ($results->searchStudentResult->paginationResponse->totalCount > 1) {
                return array(
                    "callsuccess" => true,
                    "result" => $results,
                    "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                    "sce" => "search"
                );
            }
            return array(
                "callsuccess" => true,
                "result" => $results,
                "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                "objectId" => $results->searchStudentResult->students->student->objectId,
                "sce" => "search"
            );
        } else {
            error_log("SS -->> WARNING WARNING D1 has changed their API and has broken everything.  <<--||");
        }
    }

    /** 
     * This will convert the bundle from X###### to object id
     */
    public function convert_bundle() {
        $search_this = '{"searchStudentRequestDetail": {"studentSearchCriteria": {"'.
            $this->eb->enrollStudentInBundleRequestDetail->matchOn.'": "'.
            $this->eb->enrollStudentInBundleRequestDetail->attributeValue.'"}}}';

        $result = $this->search_student($search_this);
        $path_to_save = $this->report->reportspath. "/importer/bundle_enroll_converted.csv";
        $body = $result['objectId'].",".$this->eb->enrollStudentInBundleRequestDetail->bundleProfileCode."\r\n";
        file_put_contents(
            $path_to_save,
            $body,
            FILE_APPEND
        );
    }
}
