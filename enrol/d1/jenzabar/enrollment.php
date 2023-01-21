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

require_once('helpers.php');

class enrollment {

    private $enrollment;
    private $bugfiles;
    public $pagenumber;
    public $pagesize;
    public $totalcount;

    public function __construct(&$report, $studobjid = false, $jenzaenrollment = "", $bundle_code = false, $matchon = "objectId") {

        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
        $this->bugfiles = get_config('enrol_d1', 'extradebug');

        if ($studobjid == false) {
            error_log("\n\n");
            error_log("\n ----------- ENROLLMENT FAIL FAIL FAIL - there is no student obj id  ----------- \n");
            error_log("\n\n");
            return false;
        }

        $this->enrollmenttemp = new \stdClass();

        // Data for the report, not D1.
        $this->enrollmenttemp->enrollmentEndDate = $jenzaenrollment[74];
        !empty($jenzaenrollment[7]) ? $this->enrollmenttemp->jenzaid = $jenzaenrollment[7] : null;

        $this->enrollment = new \stdClass();
        $this->enrollment->enrollStudentInSectionRequestDetail = new \stdClass();

        // These are "Mandatory"
        $this->enrollment->enrollStudentInSectionRequestDetail->attributeValue = $studobjid;
        $this->enrollment->enrollStudentInSectionRequestDetail->courseNumber = $jenzaenrollment[56];
        $this->enrollment->enrollStudentInSectionRequestDetail->matchOn = $matchon;
        $this->enrollment->enrollStudentInSectionRequestDetail->customSectionNumber = $jenzaenrollment[58];
        $this->enrollment->enrollStudentInSectionRequestDetail->paymentAmount = $jenzaenrollment[70];

        if ($bundle_code) {
            $this->enrollment->enrollStudentInSectionRequestDetail->bundleCode = $bundle_code;
        }

        !empty($jenzaenrollment[57]) ? $this->enrollment->enrollStudentInSectionRequestDetail->sectionNumber = str_pad($jenzaenrollment[57], 3, '0', STR_PAD_LEFT) : "";
        !empty($jenzaenrollment[60]) ? $this->enrollment->enrollStudentInSectionRequestDetail->academicUnits = $jenzaenrollment[60] : "";

        // Optional
        !empty($jenzaenrollment[67]) ? $this->enrollment->enrollStudentInSectionRequestDetail->enrollmentDate = $jenzaenrollment[67] : null;

        !empty($jenzastudent[69]) ? $this->enrollment->enrollStudentInSectionRequestDetail->groupPayor = $jenzastudent[69] : null;
        !empty($jenzastudent[70]) ? $this->enrollment->enrollStudentInSectionRequestDetail->paymentAmount = $jenzastudent[70] : null;
        !empty($jenzastudent[71]) ? $this->enrollment->enrollStudentInSectionRequestDetail->outstandingAmount = $jenzastudent[71] : null;
        !empty($jenzastudent[72]) ? $this->enrollment->enrollStudentInSectionRequestDetail->invoiceDueDate = helpers::fix_for_d1_date($jenzastudent[72]) : null;

        !empty($jenzaenrollment[73]) ? $this->enrollment->enrollStudentInSectionRequestDetail->transactionBasketComments = $jenzaenrollment[73] : null;
    }

    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process() {

        // do a search for the course?
        $pstart = microtime(true);

        if ($this->enrollment->enrollStudentInSectionRequestDetail->courseNumber == ""
            && $this->enrollment->enrollStudentInSectionRequestDetail->customSectionNumber == "") {
            error_log("PE -->> CSV had NO Course listed or Custom Section Number, nothing to ENROLL!");
            return;
            // The course number and custom section don't exist, so abort the search course
        }

        // ---------- SEARCH ------------
        $foundcourse = $this->search_course();

        $pend = microtime(true);
        $this->report->timer("search", $pend - $pstart);
        
        if ($foundcourse) {
            $pstart = microtime(true);

            // ---------- ENROLL ------------
            $webreturn = $this->enroll_in_section($foundcourse);
            $pend = microtime(true);
            $this->report->timer("addup", $pend - $pstart);

        } else {
            return false;
        }

        if ($webreturn['callsuccess'] == true) {
            error_log("\e[0;32m".$this->enrollment->enrollStudentInSectionRequestDetail->attributeValue. " was successfully enrolled in ".
                $this->enrollment->enrollStudentInSectionRequestDetail->courseNumber.
                " - ". $this->enrollment->enrollStudentInSectionRequestDetail->customSectionNumber);
            return true;
        } else {

            $msg = "\n\e[0;31m".$this->enrollment->enrollStudentInSectionRequestDetail->attributeValue. " failed to enrol";
            $msg .= " \nError: ". $webreturn['errorCode']. " - ".$webreturn['msg'];

            error_log($msg);

            // If the user is already enrolled then don't add to failed enrollments list.
            $findme = "is already enrolled to section";
            $pos = strpos($webreturn['msg'], $findme);
            if ($pos === false) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function post_process($result, $rowdata, $extras) {
        return;
    }

    /**
     * Search for a enrollment in D1 to see if they exist or not
     * level can be either: Ignore, Shortest, Short, Medium, Long, Full, Privileged
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    public function search_course() {

        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/searchCourseSection?informationLevel=full&_type=json';

        if ($this->enrollment->enrollStudentInSectionRequestDetail->customSectionNumber != "") {
            $search_criteria = '"courseCode": "'.$this->enrollment->enrollStudentInSectionRequestDetail->courseNumber.'",'.
                '"advancedCriteria": {'.
                    '"customSectionNumber": "'.$this->enrollment->enrollStudentInSectionRequestDetail->customSectionNumber.'"'.
                '}';
        } else {
            $search_criteria = '"courseCode": "'.$this->enrollment->enrollStudentInSectionRequestDetail->courseNumber.'"';
        }
        $params->body = '{'.
            '"searchCourseSectionProfileRequestDetail": {'.
                '"paginationConstruct": {'.
                    '"pageNumber":'.$this->pagenumber.','.
                    '"pageSize":'.$this->pagesize.
                '},'.
                '"courseSectionSearchCriteria": {'.
                    $search_criteria.
                '}'.
            '}'.
        '}';

        $results = helpers::curly($params);

        // This will write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Course_Search_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "SearchCourseSectionProfileResult")) {

            if ($this->restcalled == false) {
                $this->restcalled = true;
                $this->totalcount = $results->SearchCourseSectionProfileResult->paginationResponse->totalCount;
            }

            if ($this->totalcount > 0) {
                if (!is_array($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile)) {
                    // only one result was returned and it's not an array but an object.
                    $this->enrollment->enrollStudentInSectionRequestDetail->sectionNumber = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->code;
                    return $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->objectId;
                }

                foreach ($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile as $course) {
                    // What if customSectionNumber doesn't exist?? Do we have section number from the file????
                    // Try the course and 
                    if (isset($course->customSectionNumber) && $course->customSectionNumber == $this->enrollment->enrollStudentInSectionRequestDetail->customSectionNumber) {
                        $this->enrollment->enrollStudentInSectionRequestDetail->sectionNumber = $course->code;
                        return $course->objectId;
                    }
                }

                // If we have hit this point then it wasn't found.
                // Is there a page 2?
                if (($this->pagenumber * $this->pagesize) < $this->totalcount) {
                    // Increase the page so we can see the next batch.
                    $this->pagenumber++;
                    return self::search_course();
                }
            } else {
                return false;
            }
        } else if (property_exists($results, "SRSException")) {
            return false;
        }
    }

    /**
     * Create the enrollment if they do not exist
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    public function enroll_in_section($courseobjid) {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        // Set the URL for the post command to get a list of the courses matching the parms.

        $params->url = $s->wsurl.'/webservice/InternalViewREST/enrollStudentInSection?_type=json';
        $params->body = json_encode($this->enrollment);

        $results = helpers::curly($params);

        if (empty($results)) {
            return array(
                "callsuccess" => false,
                "result" => $results,
                "requestdata" => $this->eb->enrollStudentInBundleRequestDetail,
                "msg" => "ERROR: NULL",
                "errorCode" => "The web request returned null!!",
                "sce" => "enroll"
            );
        }

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Student_Enroll_FAIL.txt";
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
                "requestdata" => $this->enrollment->enrollStudentInSectionRequestDetail,
                "msg" => $results->SRSException->message,
                "errorCode" => $results->SRSException->errorCode,
                "sce" => "enroll"
            );
        } else {
            return array(
                "callsuccess" => true,
                "result" => $results,
                "requestdata" => $this->enrollment->enrollStudentInSectionRequestDetail,
                "sce" => "enroll"
            );
        }
    }
}
