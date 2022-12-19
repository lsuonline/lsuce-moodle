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

class fee {

    private $bugfiles;
    public $pagenumber;
    public $pagesize;
    public $totalcount;

    public function __construct(&$report, $jenzafee = "",$extras) {

        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
        $this->bugfiles = get_config('enrol_d1', 'extradebug');

        // Fee Black List
        $this->fbl = $extras['feeblacklist'];

        $this->course = new \stdClass();
        // Represent the student object according to D1.
        $this->course->coursenumber = trim($jenzafee[1]);
        $this->course->coursetitle = $jenzafee[2];
        $this->course->customSectionNumber = trim($jenzafee[4]);
        $this->course->sectionNumber = "";
        $this->course->objectId = "";
        $this->course->result = "";
        $this->course->fees = array();

        $this->accountcodes = array();

        $this->accountcodes["DEF-LB"] = 1026530;
        $this->accountcodes["PG000787"] = 1007511;
        $this->accountcodes["FS0002"] = 1007513;
        $this->accountcodes["LA002476"] = 1022863;
        $this->accountcodes["PG000788"] = 1007512;
        $this->accountcodes["PG007181"] = 1007514;
        $this->accountcodes["PG000786"] = 1007510;
        $this->accountcodes["PG000810"] = 1007503;
        $this->accountcodes["AG0002"] = 1022460;
        $this->accountcodes["PG000802"] = 1007415;
        $this->accountcodes["PG000807"] = 1007417;
        $this->accountcodes["GR-00009903"] = 1022461;
        $this->accountcodes["GR-00002010"] = 1023518;
        $this->accountcodes["PG000801"] = 1007414;
        $this->accountcodes["PG000803"] = 1007416;
        $this->accountcodes["REG-AR"] = 1026529;
        $this->accountcodes["PG000797"] = 1007505;
        $this->accountcodes["PG000799"] = 1022459;
        $this->accountcodes["PG000800"] = 1007504;
        $this->accountcodes["PG009110"] = 1022458;
        $this->accountcodes["PG000784"] = 1022464;
        $this->accountcodes["PG000789"] = 1022465;
        $this->accountcodes["GR00002010"] = 1022463;

        error_log("\n". $this->course->coursetitle. " - ". $this->course->coursenumber.
            " (".$this->course->customSectionNumber.")");
    }

    public function init($rowdata = "", $extras = array()) {

        // The column starting points for each Fee
        $fee_indexes = array(35, 39, 43, 47, 169, 173, 177, 181, 185);
        foreach ($fee_indexes as $fi) {
            // Name is REQUIRED so let's check for that.
            if ($rowdata[$fi] == "" || $rowdata[$fi+1] == "") {
                continue;
            }

            $temp1 = new \stdClass();
            $temp1->ffa = $rowdata[$fi];
            $temp1->ffn = $rowdata[$fi+1];
            $temp1->rgl = $rowdata[$fi+2];
            $temp1->pam = $rowdata[$fi+3];
            $this->course->fees[] = $temp1;
        }

        error_log("There are ".count($this->course->fees)." fees in the CSV file");
        // 39 - Flat Fee Amount 2,
        // 40 - Flat Fee Name 2,
        // 41 - Fee Revenue GL Account 2,
        // 42 - Fee Payment Account Mapping 2,
        // 43 - Flat Fee Amount 3,
        // 44 - Flat Fee Name 3,
        // ....
        // .... so on and so on.....
    }

    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process() {

        $pstart = microtime(true);
        $searchattempt = 0;
        $foundcourse = null;
        while ($searchattempt <= 5) {
            $foundcourse = $this->search_course();
            if ($foundcourse != null) {
                $searchattempt = 0;
                break;
            }
            error_log("\nWARNING - Search failed and returned null for: ".
                $this->course->customSectionNumber ." trying again.........");

            $searchattempt++;
        }

        if ($searchattempt >= 5) {
            error_log("\nWHOOOOOOAAAAAAAA - Have tried searching the course section: ".
                $this->course->customSectionNumber ." 5 TIMES and it failed.");
            return false;
        }

        $pend = microtime(true);
        $this->report->timer("search", $pend - $pstart);

        if ($foundcourse) {
            // Is this course in the black list?
            if ($this->in_black_list()) {
                error_log("\nHUZZZAAAHHHHH - This course is in the black list, aborting this course fee import!");
                return true;
            }

            // Find the missing fees from D1
            error_log("There are ".count($this->course->d1fees)." D1 fees.");
            $missing = $this->find_missing_fees2($this->course->d1fees, $this->course->fees);
            if (count($missing) === 0) {
                // list is empty.
                error_log("Fees seem to match up with D1");
                return true;
            } else {
                error_log("Will need to add ". count($missing). " fees to D1");
            }
            // Build a list of structured web service calls for the fees.
            $built = $this->build_fee_list($missing);
            $fee_failed = false;
            foreach ($built as $addme) {

                $pstart = microtime(true);
                $addresult = $this->add_fee($addme);
                $pend = microtime(true);
                $this->report->timer("addup", $pend - $pstart);

                if ($addresult['callsuccess'] == false) {
                    $fee_failed = true;
                }
            }
            // Add the fees to D1
            if ($fee_failed) {
                return false;
            } else {
                return true;
            }

        } else {
            error_log("FEE -->> Course search FAILED, adding to failed list.");
            return false;
        }
    }

    public function post_process($result, $rowdata, $extras) {
        return;
    }

    public function transform_fees($d1fees) {

        $fee_list = array();
        if (!is_array($d1fees)) {
            $tempfee = new \stdClass();
            $tempfee->ffa = $d1fees->amount;
            $tempfee->ffn = $d1fees->name;
            $tempfee->rgl = $d1fees->revenueGLAccount->code;
            $tempfee->pam = $d1fees->accountMapping->code;
            $fee_list[] = $tempfee;
            return $fee_list;
        }

        foreach ($d1fees as $dfee) {
            $tempfee = new \stdClass();
            $tempfee->ffa = $dfee->amount;
            $tempfee->ffn = $dfee->name;
            $tempfee->rgl = $dfee->revenueGLAccount->code;
            $tempfee->pam = $dfee->accountMapping->code;
            $fee_list[] = $tempfee;
        }
        return $fee_list;
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

        if ($this->course->customSectionNumber != "") {
            $search_criteria = '"courseCode": "'.$this->course->coursenumber.'",'.
                '"advancedCriteria": {'.
                    '"customSectionNumber": "'.$this->course->customSectionNumber.'"'.
                '}';
        } else {
            $search_criteria = '"courseCode": "'.$this->course->coursenumber.'"';
        }

        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/searchCourseSection?informationLevel=full&_type=json';
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

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Fee_SearchCourse_FAIL.txt";
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
                    $this->course->sectionNumber = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->code;
                    $this->course->objectId = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->objectId;
                    $this->course->result = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile;
                    $this->course->d1fees = $this->transform_fees($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->courseSectionFees->courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->tuitionFeeItems->tuitionFeeItem);

                    return $this->course->objectId;
                }

                foreach ($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile as $course) {
                    // What if customSectionNumber doesn't exist?? Do we have section number from the file????
                    // Try the course and
                    // if (isset($this->enrollment->enrollStudentInSectionRequestDetail->customNumber) &&
                    //     $this->enrollment->enrollStudentInSectionRequestDetail->sectionNumber)
                    if (isset($course->customSectionNumber) && $course->customSectionNumber == $this->course->customSectionNumber) {
                        $this->course->sectionNumber = $course->code;
                        $this->course->objectId = $course->objectId;
                        $this->course->result = $course;
                        $this->course->d1fees = $this->transform_fees($course->courseSectionFees->courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->tuitionFeeItems->tuitionFeeItem);
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

    public function in_black_list() {
        // The course in the fees export is in format ACCT 2001
        // The blacklist is ACCT in column 1 and 2001 in column 2.
        $found = false;
        $courseexplode = explode(" ", $this->course->coursenumber);

        if (array_key_exists($courseexplode[0], $this->fbl)) {
            foreach ($this->fbl[$courseexplode[0]] as $bk) {
                // Does the course number match?
                if($courseexplode[1] != $bk[0]) {
                    continue;
                }
                // Now check the section number
                if($bk[2] == $this->course->sectionNumber) {
                    $found = true;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Compare two objects (active record models) and return the difference. It wil skip ID from both objects as
     * it will be obviously different
     * Note: make sure that the attributes of the first object are present in the second object, otherwise
     * this routine will give exception.
     *
     * @param object $object1
     * @param object $object2
     * 
     * @return array difference array in key-value pair, empty array if there is no difference
     */
    public static function compareTwoObjects($object1, $object2) {

        $differences = [];
        foreach($object1->attributes as $key => $value) {
            if ($key =='id') {
                continue;
            }
            if ($object1->$key != $object2->$key) {
                $differences[$key] = array (
                    'old' => $object1->$key,
                    'new' => $object2->$key
                );
            }
        }
        return $differences;
    }

    /**
     * Add the fees from the file.
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    // a = d1
    // b = csv (csv has more)
    public function find_missing_fees2($a, $b) {

        $a = json_decode(json_encode($a));
        $b = json_decode(json_encode($b));

        // *** NOTE *** the first parameter has to be the CSV file as it'll have ALL the fees.
        // The second param will take away the dupes.
        $diff = array_diff_key($b, $a);

        return $diff;
    }

    public function find_missing_fees() {
        error_log("\n-------------- Find Missing Fees (FMF) --------------");
        $missing_fees = array();
        error_log("\nFMF: THERE ARE ". count($this->course->d1fees)." FEES IN D1.");
        foreach($this->course->fees as $csvfee) {
            // Is this fee in D1??
            $found = false;
            if (!isset($this->course->d1fees)) {
                // there are no fees at all, so we'll send em all.
                error_log("\nFMF: THERE ARE NO FEES IN D1, returning.....");
                return $this->course->fees;
            }

            foreach($this->course->d1fees as $dfee) {
                if($dfee->amount == $csvfee->ffa && $dfee->name == $csvfee->ffn && $dfee->revenueGLAccount->code == $csvfee->rgl) {
                    // Ok, the fee in the csv is already on D1
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing_fees[] = $csvfee;
            }
        }
        error_log("\nFMF: How many are missing: ". count($missing_fees));

        return $missing_fees;
    }

    /**
     * Add the fees from the file.
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    public function build_fee_list($missing = false) {

        $list_of_fees = array();
        foreach ($missing as $fee) {
            $update = new \stdClass();
            $update->updateCourseSectionRequestDetail = new \stdClass();
            $update->updateCourseSectionRequestDetail->courseSection = new \stdClass();
            $update->updateCourseSectionRequestDetail->courseSection->objectId = $this->course->objectId;
            $update->updateCourseSectionRequestDetail->courseSection->code = $this->course->sectionNumber;
            $update->updateCourseSectionRequestDetail->courseSection->courseSectionFees = new \stdClass();
            // $update->updateCourseSectionRequestDetail->courseSection->courseSectionFees->courseSectionFees = null;

            $courseSectionFee = new \stdClass();
            $courseSectionFee->objectId = $this->course->result->courseSectionFees->courseSectionFee->objectId;
            $courseSectionFee->associationMode = "update";
            $courseSectionFee->flatFeeTuitionProfile = new \stdClass();
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile = new \stdClass();
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->objectId = $this->course->result->courseSectionFees->courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->objectId;
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->associationMode = "update";

            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees =  new \stdClass();
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee =  new \stdClass();
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->associationMode = "update";
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->objectId =  $this->course->result->courseSectionFees->courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->objectId;
            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->printCode =  $this->course->result->courseSectionFees->courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->printCode;

            $tuitionFeeItems =  new \stdClass();
            $tuitionFeeItems->tuitionFeeItem =  new \stdClass();
            $tuitionFeeItems->tuitionFeeItem->amount = $fee->ffa;
            $tuitionFeeItems->tuitionFeeItem->discountable = true;
            $tuitionFeeItems->tuitionFeeItem->name = $fee->ffn;
            $tuitionFeeItems->tuitionFeeItem->surchargeable = false;
            $tuitionFeeItems->tuitionFeeItem->associationMode = "create";
            $tuitionFeeItems->tuitionFeeItem->accountMapping =  new \stdClass();
            $tuitionFeeItems->tuitionFeeItem->accountMapping->code = $fee->pam;
            $tuitionFeeItems->tuitionFeeItem->accountMapping->objectId = 1026531;
            $tuitionFeeItems->tuitionFeeItem->revenueGLAccount = new \stdClass();
            $tuitionFeeItems->tuitionFeeItem->revenueGLAccount->code = $fee->rgl;
            $tuitionFeeItems->tuitionFeeItem->revenueGLAccount->objectId = $this->accountcodes[$fee->rgl];

            $courseSectionFee->flatFeeTuitionProfile->associatedTuitionProfile->tuitionFees->tuitionFee->tuitionFeeItems = $tuitionFeeItems;
            $update->updateCourseSectionRequestDetail->courseSection->courseSectionFees->courseSectionFee = $courseSectionFee;

            $list_of_fees[] = json_encode($update);

        } // end of foreach
        return $list_of_fees;
    }

     /**
     * Add the fees from the file.
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    public function add_fee($feebody = false) {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/updateCourseSection\?_type=json';

        $params->body = $feebody;

        $results = helpers::curly($params);
        /* Produces this below
        Add Fee -->> what are the results: stdClass Object
        (
            [updateCourseSectionResult] => stdClass Object
                (
                    [status] => OK
                    [responseCode] => Success
                    [code] => 005
                    [courseCode] => OLMART
                    [customSectionNumber] => OLMART.(1)
                    [objectId] => 1110631
                    [version] => 57
                )
        )
        */
        if (empty($results)) {
            return array(
                "callsuccess" => false,
                "result" => false,
                "msg" => "Failed to communicate with D1",
                "errorCode" => "Error",
                "sce" => "enrol"
            );
        }

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Add_Fee_FAIL.txt";
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
                "requestdata" => $this->course,
                "sce" => "add"
            );
        } else {
            return array(
                "callsuccess" => true,
                "result" => $results,
                "requestdata" => $this->course,
                "sce" => "add"
            );
        }
    }

    /**
     * Update the enrollment record
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    public function update($enrollmentobj) {

    }

    /**
     * Update the enrollment record
     * @param   @object   The enrollment object from the file
     * @return  @object   return web service result
     */
    public function delete($enrollmentobj) {

    }
}
