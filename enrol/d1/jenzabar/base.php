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


// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

// defined('MOODLE_INTERNAL') || die();
$DateAndTime = date('D M j h:i:s a', time());
error_log("\n---------------------------------------- ");
error_log("Welcome to the Jenza processor - ". $DateAndTime);
error_log("---------------------------------------- \n");


use enrol_d1\jenzabar\processor;
use enrol_d1\jenzabar\helpers;
use enrol_d1\jenzabar\report;
use enrol_d1\jenzabar\pfile;

global $CFG;

require_once('processor.php');
require_once('helpers.php');
require_once('report.php');
require_once('pfile.php');


// ========================================================================
// ========================================================================
$shortopts  = "";
// General - will default to import whatever is in the student folder (students and/or enrollments)
$shortopts .= "b::";  // When processing a file, start at this row
$shortopts .= "e::";  // End at this row
$shortopts .= "n::";  // name the output files (currently uses timestamp which can change every time process starts)

// Students and Enrollments
$shortopts .= "a::";  // Trigger to import students
$shortopts .= "s::";  // Import Only Students
$shortopts .= "g::";  // Generate x numbers and export csv.
$shortopts .= "u::";  // Call the update function, add your changes there.

// Bundle
$shortopts .= "p::";  // Trigger to import bundle enrollments using p as package bundle/package ;-)
$shortopts .= "q::";  // Convert Bundle enrollments Jenza ID's to ObjectIds, folder: /importer/bundle

// Fees
$shortopts .= "f::";  // Trigger to import the more than 4 fees into courses
$shortopts .= "d::";  // Find duplicate fees
$shortopts .= "r::";  // Purge Fees and add temp fee
$shortopts .= "i::";  // Purge temp fee and add fees from csv

// Certificates
$shortopts .= "t::";  // Trigger to import enroll students in a certificate

// Course
$shortopts .= "v::";  // Update courses. This will update to the original status. File in folder: /importer/course/
$shortopts .= "w::";  // Set to true if you want everything to be Active and in Final_Approval.

// Course Section
$shortopts .= "c::";  // Run a course sectionupdate on all courses
$shortopts .= "x::";  // Set this flag to true if setting course enrollments to future date
$shortopts .= "o::";  // Set this flag to skip dates and add grade template code
$shortopts .= "y::";  // Set this flag to true for custom course update.
$shortopts .= "z::";  // Set this flag to unenroll all students in the course.
$shortopts .= "j::";  // Set to true if you want to count how many sections are NOT in Final Approval.

// General File Processing
$shortopts .= "m::";  // Do some file processing and pass in option number for which process to run.

// Help
$shortopts .= "h::";  // Optional value

$longopts  = array(
    "rb:",       // When processing a file, start at this row
    "re:",       // End at this row
    "cc:",       // Create the course
    "cs:",       // Create the course section
    "fees:",     // Import the more than 4 fees into courses
    "uc:",       // Run a course update on all courses
    "ucx:",      // Set this flag to true if setting course enrollments to future date
    "opt:",      // Switch for "quick_updater" function. Loops through a simple file calls the function based on value passed in. 
    "lf1:",      // lf - load file. Set this flag to true if setting course enrollments to future date
    "lf2:",      // lf - load file. Set this flag to true if setting course enrollments to future date
    "f1cm:",     // In this file what column to use to match with file 2? (if f1 has email and f2 has email then match it)
    "f2cm:",     // In this file what column to use to match with file 1?
    "f1cv:",     // Which column's value we wanting 
    "f2cd:",     // Which column we inserting that data??
    "help",      // Optional value
);

$options = getopt($shortopts, $longopts);
// $help = isset($options['h']) ? $options['h'] : $options['help'];
if (isset($options['h'])) {

    $help = helpers::get_help();
    echo $help;
    die;
}

// General
$rowbegin = $options['b'] ?? $options['rb'] ?? false;
$rowend = $options['e'] ?? $options['re'] ?? false;
$thisfilename = isset($options['n']) ? $options['n'] : false;
// Student/Enrollment
$stuenroll = isset($options['a']) ?? false;
$stuonly = isset($options['s']) ?? false;
$updstu = isset($options['u']) ?? false;
$genx = isset($options['g']) ?? false;
// Bundle
$bundle = isset($options['p']) ?? $options['bun'] ?? false;
$convertbundle = isset($options['q']) ?? false;
// Fees
$fees = isset($options['f']) ?? $options['fees'] ?? false;
$finddup = isset($options['d']) ?? false;
$purgeit = isset($options['i']) ?? false;
$restoreit = isset($options['r']) ?? false;
// Certificates
$cert = isset($options['t']) ?? false;

// Course
$ccv = isset($options['v']) ?? false;
$ccw = isset($options['w']) ?? false;
$cc = $options['cc'] ?? false;

// Course Section
$uc = isset($options['c']) ?? $options['uc'] ?? false;
$cs = $options['cs'] ?? false;
$ucx = isset($options['x']) ?? $options['ucx'] ?? false;
$uco = isset($options['o']) ?? false;
$ucy = isset($options['y']) ?? false;
$ucz = isset($options['z']) ?? false;
$ucj = isset($options['j']) ?? false;

// General File Processing
$pfile = $options['m'] ?? false;
$swiopt = $options['opt'] ?? false;
$loadfile1 = $options['lf1'] ?? false;
$loadfile2 = $options['lf2'] ?? false;

$file1cm = $options['f1cm'] ?? false;
$file2cm = $options['f2cm'] ?? false;
$file1cv = $options['f1cv'] ?? false;
$file2cd = $options['f2cd'] ?? false;

// Make sure the folders are present
helpers::check_dirs();

// Let's handle the Ctrl-c mechanism so we can stop whatever process
// and write do a final build for the reports so we don't lose any info.
declare(ticks = 1);
pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");

function signal_handler($signal) {
    switch($signal) {
        case SIGTERM:
            error_log("Process terminated by user, caught SIGTERM\n");
            helpers::set_sig(true);
            exit;
        case SIGKILL:
            error_log("Process terminated by user, caught SIGKILL\n");
            helpers::set_sig(true);
            exit;
        case SIGINT:
            error_log("Process terminated by user, caught SIGINT\n");
            helpers::set_sig(true);
            exit;
    }
}

helpers::set_token_start();

$token = helpers::get_token();

ini_set('memory_limit','256M');

if ($token == "" || empty($token)) {
    error_log("BASE -->> NO token, D1 might be unreachable - ABORTING!!!");
    die();
}

// This is used when testing files, if you don't want the file moved
// then set it to false.
$movefile = false;
// These are any flags/options for which ever process.
$extras = [
    "ucx" => $ucx,
    "uco" => $uco,
    "ucy" => $ucy,
    "ucz" => $ucz,
    "ccw" => $ccw,
    "ucj" => $ucj,
    "cc" => $cc,
    "cs" => $cs,
    "studentsonly" => $stuonly,
    "convertbundle" => $convertbundle,
    "updstu" => $updstu,
    "thisfilename" => $thisfilename,
    "genx" => $genx,
    "feedup" => $finddup,
    "purgeit" => $purgeit,
    "restoreit" => $restoreit,
    "pfile" => $pfile,
    "swiopt" => $swiopt,
    "loadfile1" => $loadfile1,
    "loadfile2" => $loadfile2,
    "file1cm" => $file1cm,
    "file2cm" => $file2cm,
    "file1cv" => $file1cv,
    "file2cd" => $file2cd
];

// Fees
if ($fees) {
    error_log("************************************** WARNING ***************************************");
    error_log("You should check the account codes and their corresponding objectId's on the Web UI.");
    error_log("************************************** WARNING ***************************************");
    $report = new report("fee");
    $feez = new processor($report, "fee");

    // Fees have a black list to run against, load and send
    // $feez_file = $report->reportspath."/importer/core_fee_black_list.csv";
    // $extras['feeblacklist'] = $feez->load_extra_files($feez_file, "index_black_list");
    
    $overallgarburatestart = microtime(true); 
    
    $feez->load();
    $d1ready = $feez->garburate($rowbegin, $rowend, $extras);
    
    $overallgarburateend = microtime(true); 
    $executiontime = ($overallgarburateend - $overallgarburatestart);

// Course
} else if ($ccv) {

    $report = new report("course");
    $course = new processor($report, "course");

    $overallgarburatestart = microtime(true);

    $course->load();
    $course->garburate($rowbegin, $rowend, $extras);

    $overallgarburateend = microtime(true);
    $executiontime = ($overallgarburateend - $overallgarburatestart);


// Course Section
} else if ($uc) {

    $report = new report("coursesection");
    $coursesection = new processor($report, "coursesection");

    $overallgarburatestart = microtime(true);

    $coursesection->load();
    $coursesection->garburate($rowbegin, $rowend, $extras);

    $overallgarburateend = microtime(true);
    $executiontime = ($overallgarburateend - $overallgarburatestart);

} else if ($cert) {

    $report = new report("cert");
    $cert = new processor($report, "cert");

    $overallgarburatestart = microtime(true);

    $cert->load();
    $cert->garburate($rowbegin, $rowend, $extras);

    $overallgarburateend = microtime(true);
    $executiontime = ($overallgarburateend - $overallgarburatestart);

} else if ($bundle) {

    $report = new report("bundle");
    $bundle = new processor($report, "bundle");

    $pstart = microtime(true);

    $bundle->load();
    $bundle->garburate($rowbegin, $rowend, $extras);

    $pend = microtime(true);
    $report->timer("overall", $pend - $pstart);

} else if ($pfile) {

    $report = new report("pfile");
    // want to load the fees file
    $pfileobj = new pfile($report, $rowbegin, $rowend, $extras);

    $pfileobj->process($pfile);
    $pstart = microtime(true);

    $pend = microtime(true);
    $report->timer("overall", $pend - $pstart);

} else if ($stuenroll) {

    $report = new report("student");
    $student = new processor($report, "student");

    $pstart = microtime(true);

    $count = $student->load();

    // Fees have a black list to run against, load and send
    // $student_file = $report->reportspath."/importer/core_bundle_enroll.csv";
    // helpers::load_bundle_list($student->load_extra_files($student_file));

    $d1ready = $student->garburate($rowbegin, $rowend, $extras);

    $pend = microtime(true);
    $report->timer("overall", $pend - $pstart);

} else {

    $help = helpers::get_help();
    echo $help;

    die();
}

$report->finish();
