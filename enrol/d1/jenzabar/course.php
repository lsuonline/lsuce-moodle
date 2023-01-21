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

class course {

    private $bugfiles;
    public $pagenumber;
    public $pagesize;
    public $totalcount;
    public $restcalled;
    public $report;
    public $settofuture;

    public function __construct(&$report, $cobj, $extras) {
        $this->bugfiles = get_config('enrol_d1', 'extradebug');
        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
            

        $this->alltrue = isset($extras['ccw']) ? $extras['ccw'] : false;

        // course_id
        // code
        // object_status_code
        // current_status_code

        // finalApprovalCourseStatus - True False
        // bypassApproval - ??
        // finalApprovalPublishingDate - dd MMM yyyy
        $this->course = new \stdClass();
        $this->course->coursecode = trim($cobj[1]);

        $this->cu = new \stdClass();
        $this->cu->updateCourseRequestDetail = new \stdClass();
        $this->cu->updateCourseRequestDetail->course = new \stdClass();
        $this->cu->updateCourseRequestDetail->course->objectId = $cobj[0];
        $this->cu->updateCourseRequestDetail->course->objectStatusCode = $cobj[2];
        // $this->cu->updateCourseRequestDetail->course->finalApprovalCourseStatus = $cobj[3];
        $this->cu->updateCourseRequestDetail->course->associationMode = "update";
    }

    /**
     * Intitialize dodads here.
     * @param   @object   Data from the CSV file
     * @param   @object   Any extra tidbits
     * @return  @bool   return success or fail
     */
    public function init($rowdata = "", $extras = array()) {
        if ($this->alltrue) {
            $this->cu->updateCourseRequestDetail->course->objectStatusCode = "Active";
            // $this->cu->updateCourseRequestDetail->course->finalApprovalCourseStatus = "True";
            // $this->cu->updateCourseRequestDetail->course->finalApprovalPublishingDate = "01 Jan 2023";
        }
    }
    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process() : bool {

        $pstart = microtime(true);

        // Run regular course date updates.
        $updated = $this->update_course();
            
        $pend = microtime(true);
        $this->report->timer("addup", $pend - $pstart);
            
        if ($updated) {
            error_log("\e[0;32mCourse ". $this->course->coursecode
                ." has been updated SUCCESSFULLY.");
            return true;
        } else {
            error_log("\e[0;31mERROR: ". $this->course->coursecode
                ." failed to update.");
            return false;
        }
        
    }

    public function post_process($result, $rowdata, $extras) {
        return;
    }

    /**
     * Update the course dates
     * @return  @bool
     */
    public function update_course() {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateCourse?_type=json';
        $params->body = json_encode($this->cu);
        // error_log("request body: \n\n". $params->body);

        // die();
        $results = helpers::curly($params);

        // error_log("curl results: \n\n". print_r($results, 1));

        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Course_Update_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateCourseResult")) {

            if ($results->updateCourseResult->responseCode == "Success") {
                return true;
            } else {
                return false;
            }
        } else if (property_exists($results, "SRSException")) {
            error_log("*** ERROR *** in update: ". $results->SRSException->errorCode. " - ". $results->SRSException->message);
            return false;
        }
    }

    /**
     * Search for a enrollment in D1 to see if they exist or not
     * level can be either: Ignore, Shortest, Short, Medium, Long, Full, Privileged
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     *
    public function search_course() {

        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/searchCourseSection?informationLevel=full&_type=json';

        if ($this->course->customSectionNumber != "") {
            $search_criteria = '"courseCode": "'.$this->course->courseNumber.'",'.
                '"advancedCriteria": {'.
                    '"customSectionNumber": "'.$this->course->customSectionNumber.'"'.
                '}';
        } else {
            $search_criteria = '"courseCode": "'.$this->course->courseNumber.'"';
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
                    $this->cu->updateCourseSectionRequestDetail->courseSection->code = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->code;
                    $this->cu->updateCourseSectionRequestDetail->courseSection->objectId = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->objectId;
                    return $this->cu->updateCourseSectionRequestDetail->courseSection->objectId;
                }
                error_log("***NOTICE*** multiple results found (". $this->totalcount.")");
                foreach ($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile as $course) {
                    // What if customSectionNumber doesn't exist?? Do we have section number from the file????
                    // Try the course and 
                    if (isset($course->customSectionNumber) && $course->customSectionNumber == $this->course->customSectionNumber) {
                        $this->cu->updateCourseSectionRequestDetail->courseSection->objectId = $course->objectId;
                        $this->cu->updateCourseSectionRequestDetail->courseSection->code = $course->code;
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
    */
}







































