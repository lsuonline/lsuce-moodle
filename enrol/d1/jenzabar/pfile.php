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
// defined('MOODLE_INTERNAL') || die();

class pfile {

    private $bugfiles;
    public $pagenumber;
    public $pagesize;
    public $totalcount;
    public $restcalled;
    public $report;
    public $loadfile1;
    public $loadfile2;
    public $loadlog;
    public $groups;
    private $chooser;
    public $file1cm;
    public $file2cm;
    public $file1cv;
    public $file2cd;

    public function __construct(&$report, $rowbegin = 0, $rowend = 0, $extras = array()) {
        $this->bugfiles = get_config('enrol_d1', 'extradebug');
        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
        $this->rb = $rowbegin;
        $this->re = $rowend;

        $this->groups = array();
        
        $this->loadfile1 = "";
        $this->loadlog = "";
        if (!empty($extras['loadfile1'])) {
            $this->loadfile1 = $this->reportspath."/importer/pfile/".$extras['loadfile1']. ".csv";
            $this->loadlog = $this->reportspath."/importer/pfile/logs/".$extras['loadfile1'] . "_logs.txt";
        }
        if (!empty($extras['loadfile2'])) {
            $this->loadfile2 = $this->reportspath."/importer/pfile/".$extras['loadfile2']. ".csv";
            $this->loadlog = $this->reportspath."/importer/pfile/logs/".$extras['loadfile2'] . "_logs.txt";
        }
        !empty($extras['file1cm']) ? $this->file1cm = $extras['file1cm'] : null;
        !empty($extras['file2cm']) ? $this->file2cm = $extras['file2cm'] : null;
        !empty($extras['file1cv']) ? $this->file1cv = $extras['file1cv'] : null;
        !empty($extras['file2cd']) ? $this->file2cd = $extras['file2cd'] : null;

    }

    /**
     * Intitialize dodads here.
     * @param   @object   Data from the CSV file
     * @param   @object   Any extra tidbits
     * @return  @bool   return success or fail
     */
    // public function init($rowdata = "", $extras = array()) {
        
    // }
    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process($i) {

        $pstart = microtime(true);
        
        switch ($i) {
            case 1:
                // Extract enrollments
                $this->enroll_extractor();
                break;
            case 2:
                $this->course_extractor();
                break;

            case 3:
                $this->add_section_schedule_block();
                break;
            case 4:
                $this->quick_updater();
                break;
            case 5:
                $this->search_insert();
                break;
        }
        $pend = microtime(true);
        $this->report->timer("search", $pend - $pstart);

    }

    // public function post_process($result, $rowdata, $extras) {
    //     return;
    // }

    public function enroll_extractor() {

        // You create a list of courses and put the file here: pfile/course_list.csv
        $list = $this->report->reportspath."/importer/pfile/course_list.csv";
        $clist = array_map('str_getcsv', file($list));
        $clist_size = count($clist);
        
        // Load the courses file
        $allenrollments = array_values(array_diff(scandir($this->report->reportspath."/importer/student"), array('.', '..', '.DS_Store')));
        $allenrollment = $this->report->reportspath."/importer/student/".$allenrollments[0];

        // Load the new file
        $new_stuffies = $this->report->reportspath. "/importer/pfile/enroll_output.csv";
        $newstuff_handle = fopen($new_stuffies, "a");
        
        $notfound = array();
        $found_count = 0;
        $found = false;
        foreach ($clist as $cc) {
            if (($allenrollments_handle = fopen($allenrollment, "r")) !== FALSE) {
                while (($cdata = fgetcsv($allenrollments_handle, 5000, ",")) !== FALSE) {
                    if ($cdata[58] == $cc[0]) {
                        fputcsv($newstuff_handle, $cdata);
                        $found_count++;
                        break;
                    }
                }
                $notfound[] = $cc[0];
            }

            echo "\rExtracted: ". $found_count;
        }

        error_log("\n\n=========================================");
        foreach ($notfound as $missing) {
            error_log("Couldn't find in file: ". $missing);
        }
        error_log("\n=========================================\n");

        fclose($allenrollments_handle);
        fclose($newstuff_handle);
    }

    public function course_extractor() {

        // You create a list of courses and put the file here: pfile/course_list.csv
        $list = $this->report->reportspath."/importer/pfile/course_list.csv";
        $clist = array_map('str_getcsv', file($list));
        $clist_size = count($clist);
        // Load the courses file
        $allcoursefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/course"), array('.', '..', '.DS_Store')));
        $allcoursefile = $this->report->reportspath."/importer/course/".$allcoursefiles[0];

        // Load the new file
        $new_stuffies = $this->report->reportspath. "/importer/pfile/course_output.csv";
        $newstuff_handle = fopen($new_stuffies, "a");
        
        $notfound = array();
        $found_count = 0;
        $found = false;
        foreach ($clist as $cc) {
            if (($allcoursefile_handle = fopen($allcoursefile, "r")) !== FALSE) {
                while (($cdata = fgetcsv($allcoursefile_handle, 5000, ",")) !== FALSE) {
                    if ($cdata[4] == $cc[0]) {
                        fputcsv($newstuff_handle, $cdata);
                        $found_count++;
                        break;
                    }
                }
                $notfound[] = $cc[0];
            }

            echo "\rExtracted: ". $found_count. " of ". $clist_size;
        }

        error_log("\n\n=========================================");
        foreach ($notfound as $missing) {
            error_log("This section was not found: ". $missing);
        }
        error_log("\n=========================================\n");

        fclose($allcoursefile_handle);
        fclose($newstuff_handle);
    }

    public function add_section_schedule_block() {
        // Load the file to process
        $list = $this->report->reportspath."/importer/pfile/add_section_schedule_block.csv";
        $clist = array_map('str_getcsv', file($list));
        $clist_size = count($clist);
        
        // // Load the courses file
        // $allcoursefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/course"), array('.', '..', '.DS_Store')));
        // $allcoursefile = $this->report->reportspath."/importer/course/".$allcoursefiles[0];

        // Load the new file
        $new_stuffies = $this->report->reportspath. "/importer/pfile/add_section_schedule_block_output.csv";
        $newstuff_handle = fopen($new_stuffies, "a");
        $rowcount = 1; // match against the csv row numbers

        foreach ($clist as $cc) {

            if ($rowcount < $this->rb) {
                $rowcount++;
                continue;
            }

            if ($rowcount > $this->re && $this->re != 0) {
                break;
            }

            $rowcount++;
            // if (($allcoursefile_handle = fopen($allcoursefile, "r")) !== FALSE) {
            //     while (($cdata = fgetcsv($allcoursefile_handle, 5000, ",")) !== FALSE) {
            //         if ($cdata[4] == $cc[0]) {
            //             fputcsv($newstuff_handle, $cdata);
            //             $found_count++;
            //             break;
            //         }
            //     }
            //     $notfound[] = $cc[0];
            // }

            // error_log("\rWhat is cc: ". $cc[0]);
            $result = $this->update_course_section($cc);
            // $result = true;
            if ($result) {
                error_log("\e[0;32m".$cc[1]." - successfully updated");
            } else {
                error_log("\e[0;31mBooooo - ". $cc[1]. " (".$cc[0].") Failed to update");
            }
        }

        // fclose($allcoursefile_handle);
        fclose($newstuff_handle);   
    }

    /**
     * A quick way to update something
     * 
     * @param   @string   object id
     * @param   @string   custom section number
     * @param   @string   some value to update
     */
    public function quick_updater($file = "", $log = "") {
        // Load the file to process
        if ($this->loadfile1 != "" ) {
            $file_to_open = $this->loadfile1;
        } else {
            error_log("Sorry, there is no file to load....????");
        }
        
        $list = $this->report->reportspath."/importer/pfile/".$file_to_open;
        $clist = array_map('str_getcsv', file($list));
        $clist_size = count($clist);
        
        // // Load the courses file
        // $allcoursefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/course"), array('.', '..', '.DS_Store')));
        // $allcoursefile = $this->report->reportspath."/importer/course/".$allcoursefiles[0];

        // // Load the new file
        // $new_stuffies = $this->report->reportspath. "/importer/pfile/". $log_to_this_file;
        // $newstuff_handle = fopen($new_stuffies, "a");
        
        $rowcount = 1; // match against the csv row numbers
        foreach ($clist as $cc) {

            if ($rowcount < $this->rb) {
                $rowcount++;
                continue;
            }

            if ($rowcount > $this->re && $this->re != 0) {
                break;
            }

            $rowcount++;
            // if (($allcoursefile_handle = fopen($allcoursefile, "r")) !== FALSE) {
            //     while (($cdata = fgetcsv($allcoursefile_handle, 5000, ",")) !== FALSE) {
            //         if ($cdata[4] == $cc[0]) {
            //             fputcsv($newstuff_handle, $cdata);
            //             $found_count++;
            //             break;
            //         }
            //     }
            //     $notfound[] = $cc[0];
            // }

            // Course Sections
            // $result = $this->update_it($cc);

            // Course
            // $result = $this->update_it2($cc);

            // Student
            $result = $this->update_it3($cc);

            if ($result) {
                error_log("\e[0;32mRow: ". $rowcount. " ".$cc[0]." - successfully updated");
            } else {
                error_log("\e[0;31mRow: ". $rowcount. " Booooo - ". $cc[0]. " Failed to update");
            }
        }

        // fclose($allcoursefile_handle);
        // fclose($newstuff_handle);   
    }
    /*
    // binary search
    public function bin_search($objid) {
        // Set the left pointer to 0.
        $left = 0;
        // Set the right pointer to the length of the array -1.
        $right = count(self::$bundlelist2) - 1;

        while ($left <= $right) {
            // Set the initial midpoint to the rounded down value of half the length of the array.
            $midpoint = (int) floor(($left + $right) / 2);

            // error_log("bundlelist: ".self::$bundlelist2[$midpoint][0]." objid: ". $objid);
            // error_log("Is bundlelist obj less than objid: ".self::$bundlelist2[$midpoint][0]." < ". $objid);
            if (self::$bundlelist2[$midpoint][0] < $objid) {
                // The midpoint value is less than the value.
                $left = $midpoint + 1;
            } elseif (self::$bundlelist2[$midpoint][0] > $objid) {
                // The midpoint value is greater than the value.
                $right = $midpoint - 1;
            } else {
                // This is the key we are looking for.
                // return $midpoint;
                // error_log("HUZZZZAAAAAHHHHHH - FOUND IT.");
                // error_log("HUZZZZAAAAAHHHHHH - returning this: ". self::$bundlelist2[$midpoint][1]);

                return self::$bundlelist2[$midpoint][1];
            }
            // error_log("Loop.");
        }
        
        // die();
        // The value was not found.
        return false;

    }

    /**
     * A quick way to update something
     * 
     * @param   @string   object id
     * @param   @string   custom section number
     * @param   @string   some value to update
     */
    /*
    public function search_insert() {
        // Load the file to process
        
        // $insert_data_here = $this->reportspath. "/importer/reports/Failed_".$this->toprocess."_rows_". $thisfilename. ".csv";
        // $file2_handle = fopen($this->loadfile1, "a");

        $data_we_need = array_map('str_getcsv', file($this->loadfile2));
        

        if (($file1_handle = fopen($this->loadfile1, "r")) !== FALSE) {
            while (($rowdata = fgetcsv($file1_handle, 5000, ",")) !== FALSE) {
        
                if ($headerrow && $rb == 0) {
                    $headerrow = false;
                    $header = implode(",", $rowdata);
                    $header .= "\r\n";
                    $rowcount++;
                    continue;
                }

                if ($rowcount < $rb) {
                    $rowcount++;
                    continue;
                }

                if ($rowcount > $re && $re != 0) {
                    break;
                }

                error_log("\e[0;37mOn CSV Row: ". ($rowcount + 1). "   Processed: ". $totalcount ."   Average row time: ".
                    $this->report->average_time("row"). "   Total Time: ". $this->report->running_time());

                $rowcount++;
                $totalcount++;
                $reportcount++;

                // Write to the reports every X number of rows.
                // if ($this->report->rwc != 0 && $reportcount >= $this->report->rwc) {
                //     error_log("\nHave hit the threshold for reports, going to write to file and then continue.");
                //     $this->report->save_and_clear();
                //     $reportcount = 0;
                // }

                $pstart = microtime(true);
                $facker = "enrol_d1\jenzabar\\".$this->toprocess;
                $processobj = new $facker($this->report, $rowdata, $extras);
                // If any pre loaded process need to occur, add in init()
                $processobj->init($rowdata, $extras);

                // Process will return either an objectId or false
                $result = $processobj->process();

                if ($result == false || $result == "") {
                    // The failed result is stored in a failed list for this process.
                    // $body .= implode(",", $rowdata);
                    // $body .= "\r\n";
                    fputcsv($this->handles['error_handle'], $rowdata);
                    $pend = microtime(true);
                    $this->report->timer("row", $pend - $pstart);
                    $this->report->failed();
                    continue;
                }

                $post_result = $processobj->post_process($result, $rowdata, $extras);

                if ($this->toprocess == "student") {

                    // Are we needing a new csv with XNumbers?
                    if ($extras["genx"]) {
                        $rowdata[1] = $processobj->studenttemp->xnumber;
                        fputcsv($this->handles['xstudent'], $rowdata);
                    }
                    
                    if ($post_result == false || $post_result == "") {
                        fputcsv($this->handles['error_enrol_handle'], $rowdata);
                        $pend = microtime(true);
                        $this->report->timer("row", $pend - $pstart);
                        $this->report->failed();
                        continue;
                    }
                }

                $pend = microtime(true);
                $this->report->timer("row", $pend - $pstart);

                if (helpers::get_sig()) {
                    $this->report->finish();
                    break;
                }
            }
        }
        
        $list = $this->report->reportspath."/importer/pfile/".$file_to_open;
        $clist = array_map('str_getcsv', file($list));
        $clist_size = count($clist);
        
        // // Load the courses file
        // $allcoursefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/course"), array('.', '..', '.DS_Store')));
        // $allcoursefile = $this->report->reportspath."/importer/course/".$allcoursefiles[0];

        // // Load the new file
        // $new_stuffies = $this->report->reportspath. "/importer/pfile/". $log_to_this_file;
        // $newstuff_handle = fopen($new_stuffies, "a");
        
        $rowcount = 1; // match against the csv row numbers
        foreach ($clist as $cc) {

            if ($rowcount < $this->rb) {
                $rowcount++;
                continue;
            }

            if ($rowcount > $this->re && $this->re != 0) {
                break;
            }

            $rowcount++;
            // if (($allcoursefile_handle = fopen($allcoursefile, "r")) !== FALSE) {
            //     while (($cdata = fgetcsv($allcoursefile_handle, 5000, ",")) !== FALSE) {
            //         if ($cdata[4] == $cc[0]) {
            //             fputcsv($newstuff_handle, $cdata);
            //             $found_count++;
            //             break;
            //         }
            //     }
            //     $notfound[] = $cc[0];
            // }

            // Course Sections
            // $result = $this->update_it($cc);

            // Course
            // $result = $this->update_it2($cc);

            // Student
            $result = $this->update_it3($cc);

            if ($result) {
                error_log("\e[0;32mRow: ". $rowcount. " ".$cc[0]." - successfully updated");
            } else {
                error_log("\e[0;31mRow: ". $rowcount. " Booooo - ". $cc[0]. " Failed to update");
            }
        }

        // fclose($allcoursefile_handle);
        // fclose($newstuff_handle);   
    }
    */
    public function update_it($rowdata) {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/updateCourseSection\?_type=json';
        
        $request = new \stdClass();
        $request->updateCourseSectionRequestDetail = new \stdClass();
        $request->updateCourseSectionRequestDetail->courseSection = new \stdClass();
        $request->updateCourseSectionRequestDetail->courseSection->objectId = $rowdata[0];
        $request->updateCourseSectionRequestDetail->courseSection->sectionTitle = $rowdata[2];
        $request->updateCourseSectionRequestDetail->courseSection->associationMode = "update";


        // Schedules
        // $request->updateCourseSectionRequestDetail->courseSection->sectionSchedules = new \stdClass();
        // $request->updateCourseSectionRequestDetail->courseSection->sectionSchedules->associationMode = "update";
        // $request->updateCourseSectionRequestDetail->courseSection->sectionSchedules->sectionSchedule = new \stdClass();
        // $request->updateCourseSectionRequestDetail->courseSection->sectionSchedules->sectionSchedule->dateTimeTBA = true;
        // $request->updateCourseSectionRequestDetail->courseSection->sectionSchedules->sectionSchedule->associationMode = "create";
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        // error_log("is bugging on: ". $this->bugfiles);
        // This will write the results to a logging file.
        // if ($this->bugfiles) {
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
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

    public function update_it2($rowdata) {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateCourse?_type=json';
        
        $request = new \stdClass();
        $request->updateCourseRequestDetail = new \stdClass();
        $request->updateCourseRequestDetail->course = new \stdClass();
        $request->updateCourseRequestDetail->course->objectId = $rowdata[0];
        $request->updateCourseRequestDetail->course->name = $rowdata[2];
        $request->updateCourseRequestDetail->course->associationMode = "update";

        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
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
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            return false;
        }
    }

    public function get_group_obj_id($gc) {
        if (!$this->groups) {
            return false;
        }

        foreach ($this->groups as $g) {
            if ($g[0] == $gc) {
                return $g[1];
            }
        }
        return false;
    }
    public function store_group($g, $goid) {
        $this->groups[] = array($g, $goid);
    }
    // ------------------------------------------------------------------------
    // ------------------------------------------------------------------------
    public function update_it3($rowdata) {
        $groupobjectId = "";
        if (!$groupobjectId = $this->get_group_obj_id($rowdata[2])) {
            // Not stored locally so fetch from D1
            if ($gobjectId = $this->get_group($rowdata[2])) {
                $this->store_group($rowdata[2], $gobjectId);
                $groupobjectId = $gobjectId;
            } else {
                return false;
            }
        }
            
        error_log("User: ". $rowdata[0]. " has this for group: ". $rowdata[2]. " with objectId: ". $groupobjectId);
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        
        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $rowdata[0];

        $request->student->enrolmentGroups = new \stdClass();
        $request->student->enrolmentGroups->enrolmentGroup = new \stdClass();
        $request->student->enrolmentGroups->enrolmentGroup->groupNumber = $rowdata[2];
        $request->student->enrolmentGroups->enrolmentGroup->objectId = $groupobjectId;
        $request->student->enrolmentGroups->enrolmentGroup->associationMode = "create";

        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateStudentResult")) {

            if ($results->updateStudentResult->responseCode == "Success") {
                return true;
            } else {
                return false;
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            return false;
        }
    }

    public function get_group($gid) {

        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $level = "Medium";

        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewREST/getGroup?_type=json';
        // Set the POST body.
        $params->body = '{"getGroupRequestDetail": {"attributeValue": "'.$gid. '","matchOn": "groupNumber"}}';

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

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/pfile/logs/Group_search_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        if (property_exists($results, "SRSException")) {
            return false;
        } else if (property_exists($results, "getGroupResult")) {
            return $results->getGroupResult->group->objectId;
        }
    }

    // public function enroll_extractor() {
    //     // In the pfile folder, for this particular function, the needed file should be the 4 or more fees file
    //     // Load the student enrollments file
    //     $enrolfiles = array_values(array_diff(scandir($this->report->reportspath."/importer/student"), array('.', '..', '.DS_Store')));
    //     $enrolfile = $this->report->reportspath."/importer/student/".$enrolfiles[0];

    //     // Load the fees file
    //     $feefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/fee"), array('.', '..', '.DS_Store')));
    //     $feefile = $this->report->reportspath."/importer/fee/".$feefiles[0];

    //     // Load the new file
    //     $new_enrollments = $this->report->reportspath. "/importer/pfile/output.csv";
    //     $newenrol_handle = fopen($new_enrollments, "a");

    //     $fee_count = 0;
    //     $enrol_count = 0;

    //     $array = array();
    //     if (($fee_handle = fopen($feefile, "r")) !== FALSE) {
    //         while (($feedata = fgetcsv($fee_handle, 5000, ",")) !== FALSE) {
    //             $fee_course = $feedata[4];
    //             $enrol_count = 0;
    //             $fee_count++;

    //             if (($enrol_handle = fopen($enrolfile, "r")) !== FALSE) {
    //                 while (($enroldata = fgetcsv($enrol_handle, 5000, ",")) !== FALSE) {
    //                     if ($enroldata[58] == $fee_course) {
    //                         fputcsv($newenrol_handle, $enroldata);
    //                         $enrol_count++;
    //                         // echo "\rRow: ". $fee_count. " Course: ". $fee_course;
    //                     }
    //                 }
    //             }

    //             error_log("Row: ". $fee_count. " Course: ". $fee_course. " found: ". $enrol_count);
    //         }
    //     }

    //     fclose($newenrol_handle);
    //     fclose($fee_handle);
    //     fclose($enrol_handle);
    // }

}
