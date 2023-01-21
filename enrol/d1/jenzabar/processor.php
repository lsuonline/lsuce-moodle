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

use enrol_d1\jenzabar\file_loader;
use enrol_d1\jenzabar\coursesection;
use enrol_d1\jenzabar\course;
use enrol_d1\jenzabar\student;
use enrol_d1\jenzabar\bundle;
use enrol_d1\jenzabar\fee;
use enrol_d1\jenzabar\cert;

require_once('file_loader.php');
require_once('coursesection.php');
require_once('course.php');
require_once('student.php');
require_once('bundle.php');
require_once('fee.php');
require_once('cert.php');
require_once('helpers.php');

// defined('MOODLE_INTERNAL') || die();
class processor {
    public $token;
    public $filedata;
    public $donelist;
    public $reportspath;
    public $filecount;
    public $filename;
    public $handles;

    /**
     * Most processes go through this to loop through a CSV data sheet. 
     * 
     * @param   @object   $report - Is the general reporting tool
     * @param   @object   $processthis - Is the name of the class to create when processing rows
     * @param   @object   $token - this is uselss......so....should probably remove it.
     */
    public function __construct(&$report = "", $processthis = "", $token = "") {
        $this->token = $token;
        $this->donelist = array();
        $this->reportspath = $report->reportspath;
        $this->report = $report;
        $this->toprocess = $processthis;
        $this->handles = array();

        if (!file_exists($this->reportspath."/importer/".$processthis)) {
            mkdir($this->reportspath."/importer/".$processthis, 0777, true);
        }
    }

    public function load() {

        if (isset($this->filedata)) {
            unset($this->filedata);
        }

        $files = array_values(array_diff(scandir($this->reportspath."/importer/".$this->toprocess), array('.', '..', '.DS_Store')));
        $this_file = "/importer/".$this->toprocess."/".$files[0];
        error_log("Found and loading file: ". $this_file);

        // foreach ($files as $file) {
        //     error_log("What is this file: ". $file);
        // }
        $this->filecount = count($files);

        if ($this->filecount == 0) {
            error_log("Oooppss!! There are no files in this folder: ". $this->reportspath."/importer/".$this->toprocess);
            return 0;
        }

        $this->filename = $files[0];
        helpers::printMem();
        // $loadedfile = new file_loader($this->reportspath."/importer/unprocessed/".$this->filename);
        // $this->filedata = $loadedfile->fetchdata();
        unset($loadedfile);
        helpers::printMem();

        return $this->filecount;
    }

    /**
     * Some processes may require additional files to be used. Load it here, add any processing
     * functions below and return data to be used in processing CSV data.
     * @param   @string   The path to the file to open.
     * @param   @string   $caller - The calling function to run which shall be located here
     *                    AND below garburate(). [fees, certs, course]
     * @return  @object   return the data to be used.
     */
    public function load_extra_files($file, $caller = false) {

        $some_file_loaded = new file_loader($file);
        $some_data_set = $some_file_loaded->fetchdata();
        if ($caller) {
            return $this->$caller($some_data_set);
        }

        return $some_data_set;
    }

    public function moveFile() {
        // Processing is finished, move the file.
        $from = $this->reportspath."/importer/unprocessed/".$this->filename;
        $to = $this->reportspath."/importer/processed/".$this->filename;
        rename($from, $to);
    }


    public function garburate($rb = 0, $re = 0, $extras = false) {

        $headerrow = true;
        $header = "";
        $body = "";
        $totalcount = 0;
        $rowcount = 0;
        $reportcount = 0;
        $thisfilename = time();

        // Option to set file name 
        if ($extras['thisfilename'] != false) {
            $thisfilename = $extras['thisfilename'];
        }
        $file = $this->reportspath."/importer/".$this->toprocess."/".$this->filename;
        $error_file = $this->reportspath. "/importer/reports/Failed_".$this->toprocess."_rows_". $thisfilename. ".csv";
        $this->handles['error_handle'] = fopen($error_file, "a");

        if ($this->toprocess == "student") {
            $error_enrol_file = $this->reportspath. "/importer/reports/Failed_enrollment_rows_". $thisfilename. ".csv";
            $this->handles['error_enrol_handle'] = fopen($error_enrol_file, "a");

            if ($extras["genx"]) {
                $new_student_file = $this->reportspath. "/importer/reports/XStudent_rows_". $thisfilename. ".csv";
                $this->handles['xstudent'] = fopen($new_student_file, "a");
            }
        }

        $array = array();
        if (($this->handles['main_handle'] = fopen($file, "r")) !== FALSE) {
            while (($rowdata = fgetcsv($this->handles['main_handle'], 5000, ",")) !== FALSE) {

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

                // error_log("\n=====================================================================");
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

            foreach($this->handles as $handy) {
                fclose($handy);
            }
        }
        // Clean out filedata.
        $this->filedata = null;
    }

    // ====================================================================================
    // ====================================================================================
    /* Any unique functions specific to a process to be added below
    /**
     * For searching purposes let's index each course as it's own array of sections.
     * @param   @object   All the courses from the fee black list
     * @return  @null   results stored in class obj return web service result
     */
    public function index_black_list($blacklist) {
        $bl = array();

        // Drop the header.
        array_shift($blacklist);

        foreach ($blacklist as $blkcourse) {
            // Dept.    Num.    Ver.    Sec.    D1 Sec
            $bl[$blkcourse[0]][] = array($blkcourse[1], $blkcourse[3], $blkcourse[4]);
        }
        return $bl;
    }
}
