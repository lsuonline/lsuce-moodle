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

class coursesection {

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
        $this->settofuture = $extras["ucx"];
        $this->altupdate = $extras["ucy"];
        $this->unenroll = $extras["ucz"];
        $this->gtcode = $extras["uco"];
        

        $this->getinfo = $extras["ucj"];
        // If getinfo is true then store the curl result in var below.
        $this->searchresult = "";


        $this->course = new \stdClass();
        $this->course->customSectionNumber = trim($cobj[4]);
        $this->course->courseNumber = trim($cobj[1]);

        $this->cu = new \stdClass();
        $this->cu->updateCourseSectionRequestDetail = new \stdClass();
        $this->cu->updateCourseSectionRequestDetail->courseSection = new \stdClass();
        $this->cu->updateCourseSectionRequestDetail->courseSection->objectId = "";
        $this->cu->updateCourseSectionRequestDetail->courseSection->code = "";
        $this->cu->updateCourseSectionRequestDetail->courseSection->associationMode = "update";
    }

    /**
     * Intitialize dodads here.
     * @param   @object   Data from the CSV file
     * @param   @object   Any extra tidbits
     * @return  @bool   return success or fail
     */
    public function init($rowdata = "", $extras = array()) {
        // if the default of -c is used then include original dates.
        // if other functions or updates are needed and they are set then don't do anything with dates.
        if (!$this->altupdate) {
            // $this->cu->updateCourseSectionRequestDetail->courseSection->customSectionNumber = 
        // } else {
            // Get the CSV dates in
            $this->cu->updateCourseSectionRequestDetail->courseSection->svEnrollmentBeginDate = $this->fix_for_d1_date($rowdata[13]);
            $this->cu->updateCourseSectionRequestDetail->courseSection->svEnrollmentEndDate = $this->fix_for_d1_date($rowdata[14]);
            $this->cu->updateCourseSectionRequestDetail->courseSection->pvAvailabilityBeginDate = $this->fix_for_d1_date($rowdata[15]);
            $this->cu->updateCourseSectionRequestDetail->courseSection->pvAvailabilityEndDate = $this->fix_for_d1_date($rowdata[16]);
            $this->cu->updateCourseSectionRequestDetail->courseSection->pvEnrollmentBeginDate = $this->fix_for_d1_date($rowdata[17]);
            $this->cu->updateCourseSectionRequestDetail->courseSection->pvEnrollmentEndDate = $this->fix_for_d1_date($rowdata[18]);

        }

        if ($this->gtcode) {
            if (!empty($rowdata[58])) {
                $this->cu->updateCourseSectionRequestDetail->courseSection->gradingTemplateCode = $rowdata[58];
            }
        }
    }
    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process() : bool {
        $this->set_d1_date();

        $pstart = microtime(true);
        $objid = $this->search_course();
        
        if ($this->getinfo) {
            $this->get_info();
            return true;
        }
        $pend = microtime(true);
        $this->report->timer("search", $pend - $pstart);

        if ($objid) {

            $updated = false;
            $pstart = microtime(true);
            // Are we going to purge all enrollments in the course?
            if ($this->unenroll) {
                $this->purge_students();
            }

            // if ($this->gtcode) {
            //     if ($this->cu->updateCourseSectionRequestDetail->courseSection->gradingTemplateCode == "") {
            //         error_log("-------- WARNING: ".$this->course->customSectionNumber." has NO GRADE TEMPLATE CODE, skipping --------");
            //         return true;
            //     }
            // }
            // Are we running some course update?
            if ($this->altupdate) {
                $updated = $this->update_course_alt();
            } else {
                // Run regular course date updates.
                $updated = $this->update_course();
            }
            $pend = microtime(true);
            $this->report->timer("addup", $pend - $pstart);
            
            if ($updated) {
                error_log("\e[0;32mCourse Section ". $this->course->customSectionNumber
                    ." has been updated SUCCESSFULLY.");
                return true;
            } else {
                error_log("\e[0;31mERROR: ". $this->course->customSectionNumber
                    ." failed to update.");
                return false;
            }
        } else {
            error_log("\e[0;31mSearch for ". $this->course->customSectionNumber. " failed!");
            return false;
        }
    }

    public function post_process($result, $rowdata, $extras) {
        return;
    }

    public function fix_for_d1_date($csvdate) {
        // What is the csvdate: Jan/01/2010 00:00 AM
        $newdatetime = \DateTime::createFromFormat('M/j/Y h:i a', trim($csvdate));
        return $newdatetime->format('j M Y h:i:s a');
    }

    public function set_d1_date() {
        if ($this->settofuture) {

            $futuredatebegin = "1 Jan 2009 12:00:00 AM";
            $futuredateend = "1 Jan 2199 12:00:00 AM";

            $this->cu->updateCourseSectionRequestDetail->courseSection->svEnrollmentBeginDate = $futuredatebegin;
            $this->cu->updateCourseSectionRequestDetail->courseSection->svEnrollmentEndDate = $futuredateend;

            $this->cu->updateCourseSectionRequestDetail->courseSection->pvAvailabilityBeginDate = $futuredatebegin;
            $this->cu->updateCourseSectionRequestDetail->courseSection->pvAvailabilityEndDate = $futuredateend;

            $this->cu->updateCourseSectionRequestDetail->courseSection->pvEnrollmentBeginDate = $futuredatebegin;
            $this->cu->updateCourseSectionRequestDetail->courseSection->pvEnrollmentEndDate = $futuredateend;
        }
    }

    /**
     * Update the course dates
     * @return  @bool
     */
    public function update_course() {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/updateCourseSection\?_type=json';
        $params->body = json_encode($this->cu);

        // error_log("Would be sending this to update course: \n\n". $params->body);
        // die();
        $results = helpers::curly($params);

        $header = helpers::log_header();
        // error_log("is bugging on: ". $this->bugfiles);
        // This will write the results to a logging file.
        // if ($this->bugfiles) {
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/CourseSection_Update_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateCourseSectionResult")) {

            if ($results->updateCourseSectionResult->responseCode == "Success") {
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
     */
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
            $path_to_save = $this->report->reportspath. "/importer/logs/CourseSection_Search_FAIL.txt";
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
                    if ($this->getinfo) {
                        $this->searchresult = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile;
                    }
                    return $this->cu->updateCourseSectionRequestDetail->courseSection->objectId;
                }
                // error_log("***NOTICE*** multiple results found (". $this->totalcount.") for ". $this->course->customSectionNumber);
                foreach ($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile as $course) {
                    // What if customSectionNumber doesn't exist?? Do we have section number from the file????
                    // Try the course and 
                    if (isset($course->customSectionNumber) && $course->customSectionNumber == $this->course->customSectionNumber) {
                        $this->cu->updateCourseSectionRequestDetail->courseSection->objectId = $course->objectId;
                        $this->cu->updateCourseSectionRequestDetail->courseSection->code = $course->code;
                        if ($this->getinfo) {
                            $this->searchresult = $course;
                        }
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
     * Use this function to perform random updates for whatever.
     * Currently this will append "-Inactive" to customSectionNumber
     * @return  @bool
     */
    public function update_course_alt() {

        // Get the data needed.
        $s = helpers::get_d1_settings();

        $this->cu->updateCourseSectionRequestDetail->courseSection->customSectionNumber = $this->course->customSectionNumber . "-Inactive";

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/updateCourseSection\?_type=json';
        $params->body = json_encode($this->cu);

        $results = helpers::curly($params);

        $header = helpers::log_header();
        // error_log("is bugging on: ". $this->bugfiles);
        // This will write the results to a logging file.
        // if ($this->bugfiles) {
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/CourseSection_Update_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateCourseSectionResult")) {

            if ($results->updateCourseSectionResult->responseCode == "Success") {
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
     * Purge the students in a course.
     * @param   @object   The course object from the file
     * @return  @object   return web service result
     */
    public function purge_students() {
        $s = helpers::get_d1_settings();
        
        if ($studentlist = $this->get_students()) {

            error_log("How many students in this course: ". count($studentlist->getClassListResult->studentListItems->studentListItem));
            foreach ($studentlist->getClassListResult->studentListItems->studentListItem as $student) {

                $params = new \stdClass();
                $params->url = $s->wsurl.'/webservice/InternalViewREST/dropStudentFromSection?_type=json';
                $curlbody = array("dropStudentFromSectionRequestDetail" => array(
                    "attributeValue" => $student->studentNumber,
                    "courseNumber" => $this->course->courseNumber,
                    "dropReason" => "Xtra Fee Fix",
                    "matchOn" => "studentNumber",
                    "sectionNumber" => $this->cu->updateCourseSectionRequestDetail->courseSection->code
                ));
                $params->body = json_encode($curlbody);
                $results = helpers::curly($params);
                if (property_exists($results, "SRSException")) {
                    error_log("ERROR IN UNENROLLMENT for course ". $this->course->courseNumber. " ".
                        $this->cu->updateCourseSectionRequestDetail->courseSection->code.
                        " and for student: ". $student->studentNumber);
                } else {
                    error_log("Unenrolled ". $student->studentNumber. " from ". $this->course->courseNumber.
                        " ". $this->cu->updateCourseSectionRequestDetail->courseSection->code);
                }

            }
        } else {
            error_log("Getting students for the course ". $this->course->courseNumber. " ". 
                $this->cu->updateCourseSectionRequestDetail->courseSection->code. " failed in the curl call");
        }
    }

    public function get_students() {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/getClassList?_type=json';

        $curlbody = array("getClassListRequestDetail" => array(
            "courseNumber" => $this->course->courseNumber,
            "sectionNumber" => $this->cu->updateCourseSectionRequestDetail->courseSection->code
        ));
        

        $params->body = json_encode($curlbody);

        $results = helpers::curly($params);

        $header = helpers::log_header();
        // error_log("is bugging on: ". $this->bugfiles);
        // This will write the results to a logging file.
        // if ($this->bugfiles) {
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Get_All_Enrollments_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
            return false;
        }

        return $results;
    }

    public function get_info() {
        
        // error_log("What is the result: ". print_r($this->searchresult, 1));
        // $poop = $this->searchresult->sectionStatusCode;

        if ($this->searchresult->sectionStatusCode != "final_approval") {
            error_log("\e[0;31mApproval Check: ". $this->course->customSectionNumber. " is NOT in final final_approval, it's in: ". $this->searchresult->sectionStatusCode);
        }
    }
}







































