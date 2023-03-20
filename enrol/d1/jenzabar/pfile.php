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
    public $swiopt;
    public $thisfilename;
    public $found_empty;
    public $cc_match;
    public $cc_count;
    public $cc_mismatch;
    public $cc_failed;
    public $course_counter;

    public function __construct(&$report, $rowbegin = 0, $rowend = 0, $extras = array()) {
        $this->bugfiles = get_config('enrol_d1', 'extradebug');
        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
        $this->rb = $rowbegin;
        $this->re = $rowend;
        $this->found_empty = false;
        if (!empty($extras['thisfilename'])) {
            $this->thisfilename = $extras['thisfilename'];
        }
        $this->groups = array();
        
        $this->loadfile1 = "";
        $this->loadlog = "";
        if (!empty($extras['loadfile1'])) {
            $this->loadfile1 = $this->report->reportspath."/importer/pfile/".$extras['loadfile1']. ".csv";
            $this->loadlog = $this->report->reportspath."/importer/pfile/logs/".$extras['loadfile1'] . "_logs.txt";
        }
        if (!empty($extras['loadfile2'])) {
            $this->loadfile2 = $this->report->reportspath."/importer/pfile/".$extras['loadfile2']. ".csv";
            $this->loadlog = $this->report->reportspath."/importer/pfile/logs/".$extras['loadfile2'] . "_logs.txt";
        }
        !empty($extras['file1cm']) ? $this->file1cm = $extras['file1cm'] : null;
        !empty($extras['file2cm']) ? $this->file2cm = $extras['file2cm'] : null;
        !empty($extras['file1cv']) ? $this->file1cv = $extras['file1cv'] : null;
        !empty($extras['file2cd']) ? $this->file2cd = $extras['file2cd'] : null;
        !empty($extras['swiopt']) ? $this->swiopt = $extras['swiopt'] : null;

        $this->cc_match = 0;
        $this->cc_mismatch = 0;
        $this->cc_failed = 0;
        $this->course_counter = array();

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
                $this->section_extractor();
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
            case 6:
                $this->bundle_checker();
                break;
            case 7:
                $this->enroll_compare();
                break;
            case 8:
                $this->d1_moodle_compare();
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

    public function section_extractor() {

        error_log("-----------  section_extractor START -------------");
        // You create a list of courses and put the file here: pfile/course_list.csv
        $list = $this->report->reportspath."/importer/pfile/course_list.csv";
        $clist = array_map('str_getcsv', file($list));
        $clist_size = count($clist);
        // Load the courses file
        $allcoursefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/section"), array('.', '..', '.DS_Store')));
        $allcoursefile = $this->report->reportspath."/importer/section/".$allcoursefiles[0];
        error_log("This is the all sections file: ". $allcoursefile);
        // Load the new file
        $new_stuffies = $this->report->reportspath. "/importer/pfile/course_output.csv";
        $newstuff_handle = fopen($new_stuffies, "a");
        
        $notfound = array();
        $found_count = 0;
        foreach ($clist as $cc) {
            if (($allcoursefile_handle = fopen($allcoursefile, "r")) !== FALSE) {                
                $found = false;
                while (($cdata = fgetcsv($allcoursefile_handle, 5000, ",")) !== FALSE) {
                    if ($cdata[4] == $cc[0]) {
                        $found = true;
                        fputcsv($newstuff_handle, $cdata);
                        $found_count++;
                        break;
                    }
                }
                if (!$found) {
                    $notfound[] = $cc[0];
                }
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
        
        // $list = $this->report->reportspath."/importer/pfile/".$file_to_open;
        // $clist = array_map('str_getcsv', file($file_to_open));
        // $clist_size = count($clist);
        
        // // Load the courses file
        // $allcoursefiles = array_values(array_diff(scandir($this->report->reportspath."/importer/course"), array('.', '..', '.DS_Store')));
        // $allcoursefile = $this->report->reportspath."/importer/course/".$allcoursefiles[0];

        // // Load the new file
        $missing_bits = $this->report->reportspath. "/importer/pfile/".$this->thisfilename.".csv";
        $bits_handle = fopen($missing_bits, "a");

        $rowcount = 0; // match against the csv row numbers
        $totalcount = 1;
        if (($main_handle = fopen($file_to_open, "r")) !== FALSE) {
            while (($cc = fgetcsv($main_handle, 5000, ",")) !== FALSE) {
                // reset found empty
                $this->found_empty = false;
                
                if ($rowcount < $this->rb) {
                    $rowcount++;
                    continue;
                }

                if ($rowcount > $this->re && $this->re != 0) {
                    break;
                }
                
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
                // error_log("\e[0;37m----------------------------------------------------");
                // echo("\r\e[0;37mOn CSV Row: ". $rowcount);
                error_log("\e[0;37mOn CSV Row: ". ($rowcount + 1). "   Processed: ". $totalcount ."   Average row time: ".
                        $this->report->average_time("row"). "   Total Time: ". $this->report->running_time());
                $pstart = microtime(true);
                switch ($this->swiopt) {
                    case 1:
                        // Course Sections
                        $result = $this->update_it($cc);
                        break;
                    case 2:
                        // Course
                        $result = $this->update_course($cc);
                        break;

                    case 3:
                        // Student
                        // $result = $this->update_it3($cc);
                        $result = $this->update_student($cc);
                        break;
                    case 4:
                        $result = $this->drop_student($cc);    
                        break;
                    case 5:
                        $result = $this->update_email($cc);    
                        break;
                    case 6:
                        $result = $this->update_fack($cc);    
                        break;
                    case 7:
                        $result = $this->course_checker($cc);    
                        break;

                    case 8:
                        $result = $this->course_counter($cc);    
                        break;
                    case 9:
                        $result = $this->drop_bundle($cc);    
                        break;
                    case 10:
                        $result = $this->unenroll_cert($cc);    
                        break;
                    case 11:
                        $result = $this->enroll_cert($cc);    
                        break;
                }
                $pend = microtime(true);
                $this->report->timer("row", $pend - $pstart);
                // Bundle
                // $result = $this->bundle_checker($cc);
                // $result = $this->unenrol_bundler($cc);

                
                
                
                if ($result['success']) {
                    error_log($result['msg']);
                    // error_log("\e[0;32mXorO: ". $cc[1]. " - ". $result['msg']);
                } else {
                    error_log($result['msg']);
                    // error_log("\e[0;31mRow: ". $rowcount. " Booooo - ". $cc[1]. " failed to update.\n". $result['msg']);
                    $this->report->failed();
                }

                
                // fputcsv($bits_handle, (array)$result);
                // if ($result['success']) {

                //     foreach ($result['data'] as $foobar) {
                //         $foobar->csn = trim($cc[2]);
                //         fputcsv($bits_handle, (array)$foobar);
                //     }
                // } else {
                //     error_log("\e[0;31m CSN: ". $cc[2]. " failed to search.\n". $result['msg']);
                // }
                // if ($this->found_empty) {
                //     fputcsv($bits_handle, (array)$result);
                // }
                $rowcount++;
                $totalcount++;
            }
        }

        // foreach ($this->course_counter as $cck => $ccv) {
        //     // error_log("This course---->>> ". $cck." <<--has: ". $ccv);
        //     fputcsv($bits_handle, array($cck, $ccv));
        // }
        // This is for course checker
        // error_log("****************************************************************");
        // error_log($this->cc_match. " sections matched.");
        // error_log($this->cc_mismatch. " sections mismatched.");
        // error_log($this->cc_failed. " sections are MIA.");
        // error_log($this->cc_count. " sections in total were looked at.");
        // error_log("****************************************************************");
        
        $this->report->finish();
        fclose($bits_handle);
        fclose($main_handle);
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
        
        // $insert_data_here = $this->report->reportspath. "/importer/reports/Failed_".$this->toprocess."_rows_". $thisfilename. ".csv";
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

    public function update_course($rowdata) {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateCourse?_type=json';
        // reset the REST checker.
        $this->restcalled = false;
        $search_result = $this->search_course($rowdata[0]);
        $cobjid = 0;
        if ($search_result['success']) {
            $cobjid = $search_result['data'];
        } else {
            error_log("ERROR: ". $search_result['msg']);
        }
        // return;

        $request = new \stdClass();
        $request->updateCourseRequestDetail = new \stdClass();
        $request->updateCourseRequestDetail->course = new \stdClass();
        $request->updateCourseRequestDetail->course->objectId = $cobjid;
        // $request->updateCourseRequestDetail->course->objectStatusCode = "Active";
        $request->updateCourseRequestDetail->course->objectStatusCode = "Inactive";
        // $request->updateCourseRequestDetail->course->associationMode = "update";
        // "objectStatusCode": "Active",
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        error_log("What is the course update WS result: ". print_r($results, true));

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->loadlog;
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
            
            error_log("*** ERROR *** in update for (".$rowdata[0]."): ". $error_code . " - ". $results->SRSException->message);
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

    public function bundle_checker($rowdata) {
        
        if ($this->loadfile2 != "" ) {
            $file_to_open = $this->loadfile2;
        } else {
            error_log("Sorry, there is no file to load....????");
        }

        // $list = $this->report->reportspath."/importer/pfile/".$file_to_open;
        $clist = array_map('str_getcsv', file($file_to_open));
        $clist_size = count($clist);
        
        $rowcount = 1; // match against the csv row numbers
        foreach ($clist as $cc) {
            
            if ($rowdata[0] == $cc[0]) {
                $foundit = true;
                error_log("\e[0;31mXnum - ".$rowdata[0]." from bad list matches. BAD BU=".$rowdata[1]." and Correct BU=".$cc[1]);
                return false;
                // break;
            }
        }

        // if ($bb = $this->search_bundle($rowdata[0])) {
        //     if (property_exists($bb->bundleEnrollmentActivities, "bundleEnrollmentActivity")) {
        //         error_log("\e[0;31mUhh ohhhh, there is stuff in here.");
        //         error_log("\e[0;31m". print_r($bb, 1));
        //         return false;
        //     } else {
        //         return true;
        //     }

        // }
        /*
        // First check and see if the Xnumber from the WRONG file exists in Right
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
        */
    }



    public function unenrol_bundler($rr) {

        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/dropStudentFromBundle?_type=json';
        
        $request = new \stdClass();
        $request->dropStudentFromBundleRequestDetail = new \stdClass();
        $request->dropStudentFromBundleRequestDetail->attributeValue = $rr[0];
        $request->dropStudentFromBundleRequestDetail->bundleProfileCode = $rr[1];
        $request->dropStudentFromBundleRequestDetail->matchOn = "studentNumber";

        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "dropStudentFromBundleRequestDetail")) {

            if ($results->dropStudentFromBundleRequestDetail->responseCode == "Success") {
                error_log("\e[0;32mStudent ".$rr[0]. " was removed from bundle: ". $rr[1]);
                return true;
            } else {
                return false;
            }
        } else if (property_exists($results, "SRSException")) {
            // $error_code = "";
            /*
                "SRSException": {
                    "message": "[Student X061081 is not enrolled in bundle BU0005.]",
                    "errorCode": "SYS0001"
                }
            */
            // $findme = "is not enrolled in bundle";
            // $pos = strpos($results->SRSException->message, $findme);

            error_log("\e[0;31m".$results->SRSException->message);
            // // if ($pos) {
            // } else if (property_exists($results->SRSException, "cause")) {
            //     error_log("\n\e[0;31m".$results->SRSException->message);
            //     $error_code = "EXCEPTION";
            // }
            
            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            return false;
        }

    }
    public function search_bundle($xnum) {
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/getStudentBundleEnrollments?_type=json';
        
        $request = new \stdClass();
        $request->getStudentBundleEnrollmentsRequestDetail = new \stdClass();
        $request->getStudentBundleEnrollmentsRequestDetail->attributeValue = $xnum;
        $request->getStudentBundleEnrollmentsRequestDetail->includeDroppedBundles = "Y";
        $request->getStudentBundleEnrollmentsRequestDetail->includeEnrolledBundles = "Y";
        $request->getStudentBundleEnrollmentsRequestDetail->matchOn = "studentNumber";

        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "getStudentBundleEnrollmentsResult")) {

            if ($results->getStudentBundleEnrollmentsResult->responseCode == "Success") {
                return $results->getStudentBundleEnrollmentsResult;
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
                "success" => false,
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

    public function update_email($ss) {

        if ($this->email_comm_meth($ss, "Mail")) {
            if ($this->email_delete($ss)) {
                if ($this->email_create($ss)) {
                    if ($this->email_comm_meth($ss, "Email")) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function process_address($ss, $d1ss) {
        
        $addr = $d1ss['result'];
        $error_msg = "";
        $success = false;
        $other = "";
        $homeContact = "";
        $typeCode = "Contact";
        $final_result = array();

        if ($d1ss['success']) {
            if (gettype($addr->addresses->address) == "array") {
                if (count($addr->addresses->address) > 2) {
                    $error_msg .= "\e[0;31m *****".$ss[1]. " has more than 2 mailing addresses *****\n";
                }
                if ($addr->addresses->address[0]->typeCode == "Other") {
                    $other = $addr->addresses->address[0];
                    $homeContact = $addr->addresses->address[1];
                } else if ($addr->addresses->address[1]->typeCode == "Other") {
                    $other = $addr->addresses->address[1];
                    $homeContact = $addr->addresses->address[0];
                }
                $typeCode = $homeContact->typeCode;
                $remove_result = $this->remove_other($ss, $other, $homeContact);
                $error_msg .= $remove_result['msg'];
                $success = $remove_result['success'];
                
            } else {
                if ($addr->addresses->address->typeCode == "Other") {
                    $error_msg .= "\e[0;31m *****".$ss[1]. " has ONLY 1 mailing addresses and it's 'Other' *****\n";
                } else {
                    $error_msg .= "\e[0;32m".$ss[1]. " no change needed for preferred address\n";
                }
                $typeCode = $addr->addresses->address->typeCode;
                // error_log("What is the address typeCode: ". $addr->addresses->address->typeCode);
            }

            $update_result = $this->update_address($ss, $typeCode);
            $error_msg .= $update_result['msg'];
            $success = $update_result['success'];
        }

        $final_result['success'] = $success;
        $final_result['msg'] = $error_msg;
        $final_result['foundempty'] = $update_result['foundempty'];
        return $final_result;
    }

    public function update_student($ss) {
        $success = true;
        $msg = "";
        $foundempty = false;

        $d1s = $this->get_student($ss);
        $d1ss = $d1s['result'];

        $found_any = false;

        if (property_exists($d1ss->contactMethods, "contactMethod")) {
            if (count((array)$d1ss->contactMethods->contactMethod) == 1 && $d1ss->contactMethods->contactMethod == "Any") {
                $found_any = true;
            } else {
                if (count((array)$d1ss->contactMethods->contactMethod) == 1 && $d1ss->contactMethods->contactMethod != "Any") {
                    $dcm = $this->delete_contact_method($ss, $d1ss->contactMethods->contactMethod);
                    if (!$dcm['success']) {
                        $success = false;
                    }
                    $msg .= $dcm['msg'];
                } else {
                    // There can be multiple
                    foreach ($d1ss->contactMethods->contactMethod as $con) {
                        if ($con == "Any") {
                            $found_any = true;
                            continue;
                        }
                        // Not "Any" so remove it.
                        $dcm = $this->delete_contact_method($ss, $con);
                        if (!$dcm['success']) {
                            $success = false;
                        }
                        $msg .= $dcm['msg'];
                    }
                }
            }
        }
        
        $process_result = $this->process_address($ss, $d1s);
        $this->found_empty = $process_result['foundempty'];

        $msg .= $process_result['msg'];
        if ($success != false) {
            // if it has previously failed we don't want to flip back to true
            $success = $process_result['success'];
        }
        // if ($process_result['success'])
        // error_log($process_result['msg']);

        // At this point all but Any should be removed, if any isn't there then create it.
        if (!$found_any) {

            $ccm = $this->create_contact_method($ss);
            if (!$ccm['success']) {
                $success = false;
            }
            $msg .= $ccm['msg'];
        }

        return array (
            "success" => $success,
            "msg" => $msg,
            "foundempty" => $process_result['foundempty']
        );
        // https://www.youtube.com/watch?v=kCc8FmEb1nY

        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================


        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================
        // ====================================================================================

    }

    public function drop_student($ss) {
        
        // $ss[0] matchOn
        // $ss[1] attributeValue
        // $ss[2] courseNumber
        // $ss[3] sectionNumber
        // $ss[4] dropReason
        // $ss[5] Custom_Section_Number
        
        // {
        //     "dropStudentFromSectionRequestDetail":{
        //          "attributeValue":"X012148",
        //          "customSectionNumber":"blah_blah_blah",
        //          "dropReason":"Xtra Fee Fix",
        //          "matchOn":"studentNumber",
        //     }
        // }

        $request = new \stdClass();
        $request->dropStudentFromSectionRequestDetail = new \stdClass();
        $request->dropStudentFromSectionRequestDetail->attributeValue = $ss[1];

        $request->dropStudentFromSectionRequestDetail->courseNumber = $ss[2];
        $request->dropStudentFromSectionRequestDetail->sectionNumber = str_pad($ss[3], 3, '0', STR_PAD_LEFT);
        $request->dropStudentFromSectionRequestDetail->dropReason = $ss[4];
        $request->dropStudentFromSectionRequestDetail->customSectionNumber = $ss[5];
        $request->dropStudentFromSectionRequestDetail->matchOn = "studentNumber";

        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/dropStudentFromSection?_type=json';
        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "dropStudentFromSectionResult")) {

            if ($results->dropStudentFromSectionResult->responseCode == "Success") {
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
        } else {
            error_log("*** RED ALERT *** Web Services returned NULL, something is Borked");
            return false;
        }
    }

    public function email_comm_meth($ss, $email = "Email") {
        
        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[0];
        $request->student->communicationMethod = $email;
        $s = helpers::get_d1_settings();
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        $params->body = json_encode($request);
        $results = helpers::curly($params);
        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->loadlog;
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
            return false;
        }
    }

    public function email_delete($ss) {
        
        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[0];
        $request->student->emails = new \stdClass();
        $request->student->emails->email = new \stdClass();
        $request->student->emails->email->emailAddress = $ss[2];
        $request->student->associationMode = "delete";
        $s = helpers::get_d1_settings();
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        $params->body = json_encode($request);
        $results = helpers::curly($params);
        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->loadlog;
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
            return false;
        }
    }

    public function email_create($ss) {
        
        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[0];
        $request->student->emails = new \stdClass();
        $request->student->emails->email = new \stdClass();
        $request->student->emails->email->emailAddress = $ss[1];
        $request->student->emails->email->preferred = "true";
        $request->student->emails->email->typeCode = "Standard";
        $request->student->associationMode = "create";

        $s = helpers::get_d1_settings();
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        $params->body = json_encode($request);
        $results = helpers::curly($params);
        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->loadlog;
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
            return false;
        }
    }


    public function delete_contact_method($ss, $this_method = "None") {
        
        $s = helpers::get_d1_settings();
        
        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[1];
        $request->student->associationMode = "delete";
        
        $request->student->contactMethods = new \stdClass();
        $request->student->contactMethods->contactMethod = $this_method;
        
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateStudentResult")) {

            if ($results->updateStudentResult->responseCode == "Success") {
                return array(
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[1]. " successfully created 'Any' contact method.\n"
                );
            } else {
                return array(
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** FAILED to create 'Any' contact method.\n",
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            return array(
                "success" => false,
                "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message."\n"
            );
        }

    }

    public function drop_bundle($ss) {
        $s = helpers::get_d1_settings();
        
        $request = new \stdClass();
        $request->dropStudentFromBundleRequestDetail = new \stdClass();
        $request->dropStudentFromBundleRequestDetail->matchOn = "studentNumber";
        $request->dropStudentFromBundleRequestDetail->attributeValue = $ss[0];
        $request->dropStudentFromBundleRequestDetail->bundleProfileCode = $ss[1];
        $request->dropStudentFromBundleRequestDetail->dropReason = "Data Migration Cleanup";
        
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/dropStudentFromBundle?_type=json';
        
        $params->body = json_encode($request);
        
        // error_log("\nWould drop user: ". $ss[0]. " from: ". $ss[1]);
        // return array(
        //     "success" => true,
        //     "msg" => "\e[0;32m".$ss[0]. " successfully dropped bundle ".$ss[1]." \n"
        // );

        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "dropStudentFromBundleResult")) {

            if ($results->dropStudentFromBundleResult->responseCode == "Success") {
                return array(
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[0]. " successfully dropped bundle ".$ss[1]." \n"
                );
            } else {
                return array(
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[0]. " *** ERROR *** FAILED to drop bundle ".$ss[1]."\n",
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            // return false;
            return array(
                "success" => false,
                "msg" => "\e[0;31m".$ss[0]. " *** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message."\n"
            );
        }
    }

    public function unenroll_cert($ss) {
        $s = helpers::get_d1_settings();

        $request = new \stdClass();
        /*
        {
            "dropStudentFromCertificateRequestDetail": {
                "attributeValue": "X001873",
                "certificateCode": "MC_ACCT",
                "matchOn": "studentNumber"
            }
        }
        */
        $request->dropStudentFromCertificateRequestDetail = new \stdClass();
        $request->dropStudentFromCertificateRequestDetail->matchOn = "studentNumber";
        $request->dropStudentFromCertificateRequestDetail->attributeValue = $ss[1];
        $request->dropStudentFromCertificateRequestDetail->certificateCode = $ss[2];

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/dropStudentFromCertificate?_type=json';

        $params->body = json_encode($request);

        error_log("unenroll_cert func, What is XNumber: ". $ss[1]. " and cert code: ". $ss[2]);
        // return array(
        //     "success" => true,
        //     "msg" => "\e[0;32m".$ss[0]. " successfully dropped cert ".$ss[1]." \n"
        // );
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "dropStudentFromCertificateResult")) {

            if ($results->dropStudentFromCertificateResult->responseCode == "Success") {
                return array(
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[1]. " successfully dropped cert ".$ss[2]." \n"
                );
            } else {
                return array(
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** FAILED to drop cert ".$ss[2]."\n",
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }

            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            // return false;
            return array(
                "success" => false,
                "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message."\n"
            );
        }
    }

    public function enroll_cert($ss) {
        $s = helpers::get_d1_settings();

        $request = new \stdClass();
        /*
        {
            "dropStudentFromCertificateRequestDetail": {
                "attributeValue": "X001873",
                "certificateCode": "MC_ACCT",
                "matchOn": "studentNumber"
            }
        }
        */
        $request->enrollStudentInCertificateRequestDetail = new \stdClass();
        $request->enrollStudentInCertificateRequestDetail->matchOn = "studentNumber";
        $request->enrollStudentInCertificateRequestDetail->attributeValue = $ss[1];
        $request->enrollStudentInCertificateRequestDetail->certificateCode = $ss[2];

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/enrollStudentInCertificate?_type=json';

        $params->body = json_encode($request);

        // error_log("unenroll_cert func, What is XNumber: ". $ss[1]. " and cert code: ". $ss[2]);
        // return array(
        //     "success" => true,
        //     "msg" => "\e[0;32m".$ss[0]. " successfully dropped cert ".$ss[1]." \n"
        // );
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "enrollStudentInCertificateResult")) {

            if ($results->enrollStudentInCertificateResult->responseCode == "Success") {
                return array(
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[1]. " successfully enrolled cert ".$ss[2]." \n"
                );
            } else {
                return array(
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** FAILED to enroll cert ".$ss[2]."\n",
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }

            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            // return false;
            return array(
                "success" => false,
                "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message."\n"
            );
        }
    }

    public function create_contact_method($ss) {
        $s = helpers::get_d1_settings();
        
        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[1];
        $request->student->associationMode = "create";
        
        $request->student->contactMethods = new \stdClass();
        $request->student->contactMethods->contactMethod = "Any";
        
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateStudentResult")) {

            if ($results->updateStudentResult->responseCode == "Success") {
                return array(
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[1]. " successfully created 'Any' contact method.\n"
                );
            } else {
                return array(
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** FAILED to create 'Any' contact method.\n",
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            // return false;
            return array(
                "success" => false,
                "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message."\n"
            );
        }
    }

    public function update_address($ss, $typeCode) {
        $s = helpers::get_d1_settings();

        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[1];
        $request->student->associationMode = "update";
        
        // Address
        $request->student->addresses = new \stdClass();
        $request->student->addresses->address = new \stdClass();
        $request->student->addresses->address->typeCode = $typeCode;
        // $request->student->addresses->address->preferred = "Y";

            // "address": {
            //     "typeCode": "Contact",
            //     "preferred": "true",
            //     "city": "Baton Rouge",
            //     "country": "USA",
            //     "provinceState": "LA",
            //     "street1": "123456",
            //     "postalZip": "70802",
            //     "associationMode": "create"
            // }

        // ================================================
        // Country
        // ================================================
        // Country is not specified, let's fill it with USA and LA for State otherwise D1 fails.
        $foundempty = false;

        if (empty($ss[22]) || $ss[22] == "" || strtolower($ss[22]) == "na") {
            $request->student->addresses->address->country = "USA";
            $request->student->addresses->address->provinceState = "LA";
            $foundempty = true;

        // If it's the US then make sure it's set properly.
        } else if ($ss[22] == "USA" || $ss[22] == "U.S." || $ss[22] == "United States" || $ss[22] == "US") {
            $request->student->addresses->address->country = "USA";
            
            // Is the provinceState Null or Blank? Make it LA then.
            if (empty($ss[20]) || $ss[20] == "" || strtolower($ss[20]) == "na") {
                $request->student->addresses->address->provinceState = "LA";
                $foundempty = true;
            } else {
                // Sometimes they still enter the state wrong, so let's check
                if (helpers::validate_state($ss[20])) {
                    // Yes the entry is a valid state.
                    $request->student->addresses->address->provinceState = $ss[20];
                } else {
                    $request->student->addresses->address->provinceState = "LA";
                    $foundempty = true;
                }
            }

        } else {
            // Is the country field empty
            if(!empty($ss[22])) {
                $request->student->addresses->address->country = $ss[22];
            } else {
                $request->student->addresses->address->country = "XX";
                $foundempty = true;
            }
            // If it's not Canada then get the foreign info
            if ($ss[22] != "Canada") {
                // We are out of Canada and USA.
                $request->student->addresses->address->foreign = "Y";
                // Is the foreign field empty?
                if (empty($ss[21])) {
                    $request->student->addresses->address->foreignState = $ss[20];
                    $foundempty = true;
                } else {
                    $request->student->addresses->address->foreignState = $ss[21];
                }
            } else {
                if(!empty($ss[20])) {
                    $request->student->addresses->address->provinceState = $ss[20];
                } else {
                    $request->student->addresses->address->provinceState = "XX";
                    $foundempty = true;
                }
            }
            
        }

        // // State - check if Canada or USA or Other.
        //     $request->student->addresses->address->country == "USA") {


        //     if (empty($ss[20]) || $ss[20] == "" || strtolower($ss[20]) == "na") {
        //         $request->student->addresses->address->provinceState = "LA";
        //     } else {
        //         $request->student->addresses->address->provinceState = $ss[20];
        //     }

        // } else {
        //     // We are out of Canada and USA.
        //     $request->student->addresses->address->foreign = "Y";
        //     !empty($ss[20]) ? $request->student->addresses->address->foreignState = $ss[20] : null;
        // }

        // Address Type Code.
        !empty($ss[16]) ? $request->student->addresses->address->typeCode = $ss[16] : "Contact";

        // The import has holes for the street, make sure a placeholder is there.
        if (empty($ss[17]) || $ss[17] == "" || strtolower($ss[17]) == "na") {
            $request->student->addresses->address->street1 = "123 Placeholder Fake Street";
            $foundempty = true;
        } else {
            // $request->student->addresses->address->street1 = helpers::alphaNumStr($ss[17]);
            $request->student->addresses->address->street1 = $ss[17];
        }

        // !empty($ss[18]) ? $request->student->addresses->address->street2 = helpers::alphaNumStr($ss[18]) : null;
        // 18 = Street 2
        !empty($ss[18]) ? $request->student->addresses->address->street2 = $ss[18] : null;

        // 19 = City
        if (empty($ss[19]) || $ss[19] == "" || strtolower($ss[19]) == "na") {
            $request->student->addresses->address->city = "Placeholder Fake City";
            $foundempty = true;
        } else {
            // $request->student->addresses->address->city = helpers::alphaNumStr($ss[19]);
            $request->student->addresses->address->city = $ss[19];
        }



        if (empty($ss[23]) || $ss[23] == "" || strtolower($ss[23]) == "na") {
            $request->student->addresses->address->postalZip = "70802";
            $foundempty = true;
        } else {
            $request->student->addresses->address->postalZip = $ss[23];
        }

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateStudentResult")) {

            if ($results->updateStudentResult->responseCode == "Success") {
                return array(
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[1]. " successfully updated users address.\n",
                    "foundempty" => $foundempty
                );
            } else {
                return array(
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** FAILED to update users address.\n",
                    "foundempty" => $foundempty
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            // return false;
            return array(
                "success" => false,
                "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** FAILED to update users address: ". $error_code."\n",
                "foundempty" => $foundempty
            );
        }
    }

    public function remove_other($ss, $other, $homeContact) {
        
        $s = helpers::get_d1_settings();

        $request = new \stdClass();
        $request->student = new \stdClass();
        $request->student->studentNumber = $ss[1];
        $request->student->associationMode = "replace";
        
        $request->student->addresses = new \stdClass();
        $request->student->addresses->address = new \stdClass();
        $request->student->addresses->address->typeCode = $homeContact->typeCode;
        $request->student->addresses->address->preferred = "Y";
        $request->student->addresses->address->associationMode = "update";

        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);

        $header = helpers::log_header();
        
        if (property_exists($results, "SRSException")) {
            // $path_to_save = $this->report->reportspath. "/importer/pfile/logs/".$this->loadlog;
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        if (property_exists($results, "updateStudentResult")) {

            if ($results->updateStudentResult->responseCode == "Success") {
                // return true;
                return array (
                    "success" => true,
                    "msg" => "\e[0;32m".$ss[1]. " successfully replaced preferred address.\n"
                );
            } else {
                return array (
                    "success" => false,
                    "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** Not sure what happened....?\n"
                );
            }
        } else if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            
            return array (
                "success" => false,
                "msg" => "\e[0;31m".$ss[1]. " *** ERROR *** failed to replace preferred address.". $error_code."\n"
            );
            // error_log("*** ERROR *** in update: ". $error_code . " - ". $results->SRSException->message);
            // return false;
        }
    }

    public function get_student($ss) {
    
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $level = "Medium";
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/student/studentNumber/'.$ss[1].'?_type=json&informationLevel=full';
        $results = helpers::curly($params, true);

        if (empty($results)) {
            return array(
                "success" => false,
            );
        }

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // Depending on the type of search there are multiple result sets.
        if (property_exists($results, "SRSException")) {
            return array(
                "success" => false,
                "result" => $results,
            );

        } else if (property_exists($results, "getStudentResult")) {
            // Student has been found.
            return array(
                "success" => true,
                "result" => $results->getStudentResult->student,
            );
        } else {
            error_log("SS -->> WARNING WARNING D1 has changed their API and has broken everything.  <<--||");
        }
    }


    public function course_counter(&$ss) {
        $cindex = trim($ss[58]);
        if (isset($this->course_counter[$cindex])) {
            $this->course_counter[$cindex]++;
        } else {
            // $this->course_counter[] = $cindex;
            $this->course_counter[$cindex] = 1;
        }
    }

    public function course_checker(&$ss) {

        $this->cc_count++;
        $d1_enrol_list = array();
        $s = helpers::get_d1_settings();
        /*

        $params = new \stdClass();
        $level = "Medium";
        $section = "";
        $courseNumber = "";
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/courseSection/objectId/'.$ss[0].'?informationLevel=Short&_type=json';
        $results = helpers::curly($params, true);

        if (empty($results)) {
            return array (
                "success" => false,
                "msg" => "\e[0;31mobjectId ".$ss[0]. " *** ERROR *** No Results from WS.\n"
            );
        }

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->loadlog;
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // Depending on the type of search there are multiple result sets.
        if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "errorCode")) {
                $error_code = $results->SRSException->errorCode;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            $this->cc_failed++;
            return array (
                "success" => false,
                "msg" => "\e[0;31mobjectId ".$ss[0]. " *** ERROR *** ". $error_code."\n"
            );

        } else if (property_exists($results, "getCourseSectionResult")) {
            // Student has been found.
            $section = $results->getCourseSectionResult->courseSection->code;
            $courseNumber = $results->getCourseSectionResult->courseSection->associatedCourse->courseNumber;
            
        } else {
            $this->cc_failed++;
            return array (
                "success" => false,
                "msg" => "\e[0;31mobjectId ".$ss[0]. " *** ERROR *** UNKNOWN\n"
            );
        }
        */

        // $cresult = $this->search_section(trim($ss[58]));



        $courseNumber = trim($ss[0]);
        $section = trim($ss[1]);
        $section = str_pad($section, 3, '0', STR_PAD_LEFT);
        // =================================================================================
        // =================================================================================
        // =================================================================================
        // =================================================================================
        // Now to get class list
        $params2 = new \stdClass();
        // $level = "Medium";
        $request = new \stdClass();
        $request->getClassListRequestDetail = new \stdClass();
        $request->getClassListRequestDetail->courseNumber = $courseNumber;
        $request->getClassListRequestDetail->sectionNumber = $section;
        $request->getClassListRequestDetail->overrideMaxResults = true;

        // Set the URL for the post command to get a list of the courses matching the parms.
        $params2->url = $s->wsurl.'/webservice/InternalViewRESTV2/getClassList?_type=json';
        $params2->body = json_encode($request);
        $results = helpers::curly($params2);
        // error_log("What are the results: ". print_r($results, true));
        
        
        // Depending on the type of search there are multiple result sets.
        if (property_exists($results, "SRSException")) {
            $error_code = "";
            if (property_exists($results->SRSException, "message")) {
                $error_code = $results->SRSException->message;
            } else if (property_exists($results->SRSException, "cause")) {
                $error_code = "EXCEPTION";
            }
            $this->cc_failed++;
            return array (
                "success" => false,
                "msg" => "\e[0;31mobjectId ".$ss[0]. " *** ERROR *** ". $error_code."\n"
            );
            
        } else if (property_exists($results, "getClassListResult")) {
            // Student List has been found.
            $this->found_empty = true;
            if (property_exists($results->getClassListResult->studentListItems, "studentListItem")) {
                // $scount = count($results->getClassListResult->studentListItems->studentListItem);
                // $totalcount = $results->getClassListResult->paginationResponse->totalCount;
                // if ($totalcount > 0) {
                    if (!is_array($results->getClassListResult->studentListItems->studentListItem)) {
                        
                        $this_stud = new \stdClass();
                        $this_stud->enrollmentStatus = $results->getClassListResult->studentListItems->studentListItem->enrollmentStatus;
                        $this_stud->studentFirstName = $results->getClassListResult->studentListItems->studentListItem->studentFirstName;
                        $this_stud->studentLastName = $results->getClassListResult->studentListItems->studentListItem->studentLastName;
                        $this_stud->studentPreferredEmail = $results->getClassListResult->studentListItems->studentListItem->studentPreferredEmail;
                        $this_stud->studentNumber = $results->getClassListResult->studentListItems->studentListItem->studentNumber;
                        $this_stud->studentId = $results->getClassListResult->studentListItems->studentListItem->studentId;
                        array_push($d1_enrol_list, $this_stud);
                        return array(
                            "success" => true,
                            "data" => $d1_enrol_list
                        );
                    }

                    foreach ($results->getClassListResult->studentListItems->studentListItem as $sli) {
                        $this_stud = new \stdClass();
                        $this_stud->enrollmentStatus = $sli->enrollmentStatus;
                        $this_stud->studentFirstName = $sli->studentFirstName;
                        $this_stud->studentLastName = $sli->studentLastName;
                        $this_stud->studentPreferredEmail = $sli->studentPreferredEmail;
                        $this_stud->studentNumber = $sli->studentNumber;
                        $this_stud->studentId = $sli->studentId;
                        array_push($d1_enrol_list, $this_stud);
                    }
            } else {
                $scount = 0;
            }
            
            return array(
                "success" => true,
                "data" => $d1_enrol_list
            );
            // return $d1_enrol_list;
            
        } else {
            $this->cc_failed++;
            error_log("SS -->> WARNING WARNING D1 has changed their API and has broken everything.  <<--||");
            return array (
                "success" => false,
                "msg" => "\e[0;31mobjectId ".$ss[0]. " *** ERROR *** UNKNOWN\n"
            );
        }
    }

    public function search_course($courseCode) {
        // Get the data needed.
        $s = helpers::get_d1_settings();
        
        error_log("Going to search for the course, what is the code: ". $courseCode);
        // Set the URL for the post command to get a list of the courses matching the parms.
        // https://lsuonlinews.destinyone.moderncampus.net/webservice/InternalViewREST/searchCourse?informationLevel=full&_type=json
        $params = new \stdClass();
        $params->url = $s->wsurl.'/webservice/InternalViewREST/searchCourse?informationLevel=full&_type=json';

        // {
        //     "searchCourseProfileRequestDetail": {
        //         "courseSectionSearchCriteria": {
        //             // "objectID": "1008108"
        //             "courseCode": "OLHERBS"
        //         }
        //     }
        // }
        $request = new \stdClass();
        $request->searchCourseProfileRequestDetail = new \stdClass();
        $request->searchCourseProfileRequestDetail->courseSectionSearchCriteria = new \stdClass();
        $request->searchCourseProfileRequestDetail->courseSectionSearchCriteria->courseCode = $courseCode;
        
        $params->body = json_encode($request);
        
        $results = helpers::curly($params);
        // This will write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Course_Search_FAIL_".$this->thisfilename.".txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // For course section search use.
        // searchCourseResult->courseProfiles->
        if (property_exists($results, "searchCourseResult")) {

            if ($this->restcalled == false) {
                $this->restcalled = true;
                $this->totalcount = $results->searchCourseResult->paginationResponse->totalCount;
            }

            if ($this->totalcount > 0 ) {

                if (!is_array($results->searchCourseResult->courseProfiles->courseProfile)) {
                    // only one result was returned and it's not an array but an object.
                    // $temp_section_code = $results->searchCourseResult->courseProfiles->courseProfile->code;
                    $temp_objectid = $results->searchCourseResult->courseProfiles->courseProfile->objectId;
                    // $temp_course_number = $results->searchCourseResult->courseProfiles->courseProfile->associatedCourse->courseNumber;
                    return array (
                        "success" => true,
                        "msg" => "\e[0;32m Found object id".$temp_objectid."\n",
                        "data" => $temp_objectid
                    );
                }

                foreach ($results->searchCourseResult->courseProfiles->courseProfile as $course) {
                    // What if customSectionNumber doesn't exist?? Do we have section number from the file????
                    // Try the course and 
                    if ($course->courseNumber == $courseCode) {
                        return array (
                            "success" => true,
                            "msg" => "\e[0;32m Found object id".$course->objectId."\n",
                            "data" => $course->objectId
                        );
                    }
                }
                return array (
                    "success" => false,
                    "msg" => "\e[0;31m Search Result had MULTIPLE results but didn't find a match.\n"
                );
                // If we have hit this point then it wasn't found.
                
            } else {
                return array (
                    "success" => false,
                    "msg" => "\e[0;31m Search Result had ZERO results.\n"
                );
            }
        } else if (property_exists($results, "SRSException")) {
            return array (
                "success" => false,
                "msg" => "\e[0;31m ERROR in searching for course.\n"
            );
        }

    }
    public function search_section($csn) {

        // Get the data needed.
        $s = helpers::get_d1_settings();
        $temp_section_code = "";
        $temp_course_number = "";
        $temp_objectid = "";
        $params = new \stdClass();
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/searchCourseSection?informationLevel=full&_type=json';

        if ($csn != "") {
            $search_criteria = '"advancedCriteria": {'.
                    '"customSectionNumber": "'.$csn.'"'.
                '}';
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
        // error_log("body output: ". $params_body);
        $results = helpers::curly($params);

        // This will write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Course_Search_FAIL_".$this->thisfilename.".txt";
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
                    $temp_section_code = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->code;
                    $temp_objectid = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->objectId;
                    $temp_course_number = $results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile->associatedCourse->courseNumber;
                    return array(
                        $temp_objectid,
                        $temp_course_number,
                        $temp_section_code
                    );
                }

                foreach ($results->SearchCourseSectionProfileResult->courseSectionProfiles->courseSectionProfile as $course) {
                    // What if customSectionNumber doesn't exist?? Do we have section number from the file????
                    // Try the course and 
                    if (isset($course->customSectionNumber) && $course->customSectionNumber == $csn) {
                        $temp_section_code = $course->code;
                        $temp_objectid = $course->objectId;
                        $temp_course_number = $course->associatedCourse->courseNumber;

                        return array(
                            $temp_objectid,
                            $temp_course_number,
                            $temp_section_code
                        );
                    }
                }

                // If we have hit this point then it wasn't found.
                // Is there a page 2?
                if (($this->pagenumber * $this->pagesize) < $this->totalcount) {
                    // Increase the page so we can see the next batch.
                    $this->pagenumber++;
                    return self::search_section($csn);
                }
            } else {
                return false;
            }
        } else if (property_exists($results, "SRSException")) {
            return false;
        }
    }

    
    /**
     * A quick way to update something
     * 
     * @param   @string   object id
     * @param   @string   custom section number
     * @param   @string   some value to update
     */
    public function enroll_compare($file = "", $log = "") {
        // Load the file to process
        if ($this->loadfile1 != "" ) {
            $file_to_open1 = $this->loadfile1;
        } else {
            error_log("Sorry, there is no file to load....????");
        }

        if ($this->loadfile2 != "" ) {
            $file_to_open2 = $this->loadfile2;
        } else {
            error_log("Sorry, there is no file to load....????");
        }

        $not_in_d1 = array();
        $not_in_csv = array();
        $course_not_in_d1 = array();
        $course_not_in_csv = array();
        $structure1 = array();
        $structure2 = array();
        // =================================================
        // Build the course list from file 1 (D1)
        if (($f1_handle = fopen($file_to_open1, "r")) !== FALSE) {
            while (($xx = fgetcsv($f1_handle, 5000, ",")) !== FALSE) {

                $cindex = trim($xx[6]);
                // build the obj
                $temp_enrol = new \stdClass();
                $temp_enrol->csn = $xx[6];
                $temp_enrol->status = $xx[0];
                $temp_enrol->firstName = $xx[1];
                $temp_enrol->lastName = $xx[2];
                $temp_enrol->email = $xx[3];
                $temp_enrol->xnumber = $xx[4];
                $temp_enrol->objectid = $xx[5];

                if (isset($structure1[$cindex])) {
                    array_push($structure1[$cindex], $temp_enrol);
                } else {
                    $structure1[$cindex] = array();
                    array_push($structure1[$cindex], $temp_enrol);
                }
            }
        }
        // =================================================
        // Build from ENROLL file 1 (CSV)
        if (($f2_handle = fopen($file_to_open2, "r")) !== FALSE) {
            while (($zz = fgetcsv($f2_handle, 5000, ",")) !== FALSE) {
                
                $cindex = trim($zz[58]);
                // build the obj
                $temp_enrol = new \stdClass();
                $temp_enrol->csn = $cindex;
                $temp_enrol->firstName = $zz[3];
                $temp_enrol->lastName = $zz[2];
                $temp_enrol->email = $zz[29];
                $temp_enrol->xnumber = $zz[1];
                // $temp_enrol->objectid = $zz[];

                if (isset($structure2[$cindex])) {
                    array_push($structure2[$cindex], $temp_enrol);
                } else {
                    $structure2[$cindex] = array();
                    array_push($structure2[$cindex], $temp_enrol);
                }
            }
        }

        // Check left to right
        foreach ($structure1 as $d1c => $d1cn) {
            if (array_key_exists($d1c, $structure2)) {
                // Found the course
                // d1c == ACCT 2001 001
                foreach ($d1cn as $stud1) {
                    // does $stud1 exist in S2
                    $foundit = false;
                    // How many objs in  struct 2
                    foreach ($structure2[$d1c] as $stucsv) {
                        if ($stud1->xnumber == $stucsv->xnumber) {
                            // found it, continue
                            $foundit = true;
                            break;
                        }
                    }

                    if (!$foundit) {
                        $not_in_csv[$d1c][] = $stud1;
                    }
                }
            } else {
                $course_not_in_csv[] = $d1c;
            }
        }

        // Check left to right
        foreach ($structure2 as $csvc => $csvcn) {
            if (array_key_exists($csvc, $structure1)) {
                // Found the course
                // csvc == ACCT 2001 001
                foreach ($csvcn as $stucsv) {
                    // does $stucsv exist in S2
                    $foundit = false;
                    foreach ($structure1[$csvc] as $stud1) {
                        if ($stucsv->xnumber == $stud1->xnumber) {
                            // found it, continue
                            $foundit = true;
                            break;
                        }
                    }

                    if (!$foundit) {
                        $not_in_d1[$csvc][] = $stucsv;
                    }
                }
            } else {
                $course_not_in_d1[] = $csvc;
            }
        }
        
        $allenrollments = array_values(array_diff(scandir($this->report->reportspath."/importer/student"), array('.', '..', '.DS_Store')));
        $allenrollment = $this->report->reportspath."/importer/student/".$allenrollments[0];

        // File for D1
        $d1fn = $this->report->reportspath. "/importer/pfile/ZZ_Enrollments_Not_In_D1.csv";
        $d1f_handle = fopen($d1fn, "w");
        $header1 = array("Custom Section Number", "First Name", "Last Name", "Email", "XNumber");
        fputcsv($d1f_handle, $header1);
        foreach ($not_in_d1 as $stupid => $dong) {
            // error("what is stupid: ". print_r($stupid, true));
            foreach ($dong as $mini_dong) {
                fputcsv($d1f_handle, (array)$mini_dong);
            }
        }
        
        // File for JZ CSV
        $jzfn = $this->report->reportspath. "/importer/pfile/ZZ_Enrollments_Not_In_JZ_Enroll_File.csv";
        $jzf_handle = fopen($jzfn, "w");
        $header2 = array("Custom Section Number", "Enroll Status", "First Name", "Last Name", "Email", "XNumber", "ObjectId");
        fputcsv($jzf_handle, $header2);
        foreach ($not_in_csv as $dummy => $face) {
            // error("what is dummy: ". $dummy. " and face: ".print_r($stupid, true));
            foreach ($face as $butt_face) {
                fputcsv($jzf_handle, (array)$butt_face);
            }
            
        }
        // File for Course Diff
        $cdfn = $this->report->reportspath. "/importer/pfile/ZZ_Enroll_Diff_Course_Diff.csv";
        $cdf_handle = fopen($cdfn, "w");
        
        $nid1_count = count($course_not_in_d1);
        $nicsv_count = count($course_not_in_csv);
        $header3 = array("In Sections File Not Found in D1", "Not Found in Enroll File");
        fputcsv($cdf_handle, $header3);
        
        $highest = $nid1_count > $nicsv_count ? $nid1_count : $nicsv_count;

        for ($i = 0; $i < $highest; $i++) {
            $temper = array(
                !empty($course_not_in_d1[$i]) ? $course_not_in_d1[$i] : "",
                !empty($course_not_in_csv[$i]) ? $course_not_in_csv[$i] : ""
            );
            fputcsv($cdf_handle, $temper);
        }
        
        fclose($d1f_handle);
        fclose($jzf_handle);
        fclose($cdf_handle);


        fclose($f1_handle);
        fclose($f2_handle);
    }

    public function d1_moodle_compare() {

        // This should be the D1 Archive export
        if ($this->loadfile1 != "" ) {
            $file_to_open1 = $this->loadfile1;
        } else {
            error_log("Sorry, there is no file to load....????");
        }

        // File to to be the moodle user export
        if ($this->loadfile2 != "" ) {
            $file_to_open2 = $this->loadfile2;
        } else {
            error_log("Sorry, there is no file to load....????");
        }

        $mm = $this->report->reportspath. "/importer/pfile/mismatchers.csv";
        $mm_handle = fopen($mm, "w");

        $mismatch = array();
        $emailnotfound = 0;
        $matchcount = 0;
        $mismatchcount = 0;

        $mm_counter = 0;
        $zz_counter = 0;
        // =================================================

        if (($f1_handle = fopen($file_to_open1, "r")) !== FALSE) {
            while (($xx = fgetcsv($f1_handle, 5000, ",")) !== FALSE) {
                //  $xx[3] is email
                //  $xx[4] is loginid
                $mm_counter++;
                $foundemail = false;
                $usernamesmatch = false;

                if (($f2_handle = fopen($file_to_open2, "r")) !== FALSE) {
                    while (($zz = fgetcsv($f2_handle, 5000, ",")) !== FALSE) {
                        // $zz[12] is email
                        // $zz[7] is username
                        $zz_counter++;

                        // Are the emails the same?
                        if ($xx[3] == $zz[12]) {
                            $foundemail = true;

                            // are usernames the same?
                            if ($xx[4] == $zz[7]) {
                                $usernamesmatch = true;
                                $matchcount++;
                            } else {
                                $temp = new \stdClass();
                                $temp->d1_email = $xx[3];
                                $temp->d1_username = $xx[4];
                                $temp->mood_email = $zz[12];
                                $temp->mood_username = $zz[7];
                                array_push($mismatch, $temp);
                                $mismatchcount++;

                                fputcsv($mm_handle, (array)$temp);
                            }
                            break;
                        }
                    }
                    fclose($f2_handle);
                }

                if (!$foundemail) {
                    $emailnotfound++;
                    error_log("Could not find ". $xx[3]. " in the moodle file");
                }
            }
        }

        error_log("----------------  Done processing files  ----------------");
        error_log("This many matches: ". $matchcount);
        error_log("This many mismatches: ". $mismatchcount);
        error_log("Email not found: ". $emailnotfound);
    }
}
