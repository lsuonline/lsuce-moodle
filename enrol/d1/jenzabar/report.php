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

// defined('MOODLE_INTERNAL') || die();
namespace enrol_d1\jenzabar;

require_once('helpers.php');

class report {

    private $flist;
    private $overalltime;

    public $failcount;

    // report write count
    public $rwc;

    // Name of the report we are building
    public $reportname;

    public $rowcount;
    public $rowtotal;

    private $time_list;
    private $count_list;
    private $executiontime_list;

    private $filenames;

    public $reportspath;

    public function __construct($rname, $rwc = 1000) {
        $this->flist = array();
        $this->overalltime = 0;
        $this->failcount = 0;

        $this->rwc = $rwc;

        $this->reportname = $rname;
        $this->rowcount = 0;
        $this->rowtotal = 0;

        // overall time
        $this->overallcount = 0;
        $this->overalltime = 0;

        // Row Count and Time
        $this->rowcount = 0;
        $this->rowtime = 0;

        // Search Count and Time
        $this->searchcount = 0;
        $this->searchtime = 0;

        // Add/Create or Update Count and Time
        $this->addupcount = 0;
        $this->adduptime = 0;

        // Total Process Time
        $this->totaltime = time();

        $this->time_list = array();
        $this->count_list = array();
        $this->executiontime_list = array();

        $this->filenames = array();

        $this->reportspath = get_config('enrol_d1', 'debugfiles');
    }

    public function running_time() {

        $calc = time() - $this->totaltime;
        $hour = $calc / 3600 % 24;    // to get hours
        $minute = $calc / 60 % 60;    // to get minutes
        $second = $calc % 60;         // to get seconds

        $hrs = "Hr";
        $min = "Min";
        $sec = "Sec";

        return $hour. " ". $hrs. "  ". $minute. " ". $min. "  ". $second. " ". $sec;
        // return $hour. ":". $minute. ":". $second;
    }

    public function timer($section, $clocked) {
        // Example: all, row, update, create
        $count = $section."count";
        $time = $section."time";
        $this->$count = $this->$count + 1;
        $this->$time = $this->$time + $clocked;
    }

    public function failed() {
        $this->failcount++;
    }

    public function ostat($clocked) {
        $this->rowcount++;
        $this->rowtotal += $clocked;
    }

    public function average_time($section) {
        $count = $section."count";
        $time = $section."time";

        if ($this->$count == 0) {
            return 0;
        }
        return round($this->$time / $this->$count, 3);
    }

    public function save_and_clear() {
        error_log("----------------------------------------------------");
        error_log("-----------   Saving Reports   ---------------------");
        error_log("----------------------------------------------------");

        // Call finish reports
        $this->finish();

        // Now clean
        $this->flist = array();
    }

    public function finish() {

        error_log("\n\n");
        error_log("----------------------------------------------------");
        error_log("-----------   BUILD REPORTS   ----------------------");
        error_log("----------------------------------------------------");

        error_log("Success rate of ".number_format((($this->failcount / $this->rowcount) * 100), 2). "%, ".
            $this->failcount. "/".$this->rowcount." entries FAILED!!!");

        if ($this->searchcount > 0 && $this->searchtime > 0) {
            error_log("Number of web service calls for searching: ". $this->searchcount);
            error_log("Average time for searching: ". ($this->searchtime / $this->searchcount));
        }

        if ($this->addupcount > 0 && $this->adduptime > 0) {
            error_log("Number of web service calls for adding/creating/updating: ". $this->addupcount);
            error_log("Average time for adding/creating/updating: ". ($this->adduptime / $this->addupcount));
        }

        if ($this->rowtime > 0 && $this->rowcount > 0) {
            error_log("Number of rows processed: ". $this->rowcount);
            error_log("Average time to popcess a row: ". ($this->rowtime / $this->rowcount));
        }

        if ($this->overalltime > 0) {
            error_log("Overall time to run this process: ". $this->overalltime);
        }

        error_log("----------------------------------------------------");
        error_log("---------------------   END   ----------------------");
        error_log("----------------------------------------------------");
    }

    public function clean() {
        $this->flist = array();

        $this->overalltime = null;
        $this->rowcount = 0;
        $this->rowtotal = 0;
    }
}
