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

global $CFG;

require_once('processor.php');
require_once('helpers.php');
require_once('report.php');


// ========================================================================
// ========================================================================
$shortopts  = "";
// General - will default to import whatever is in the student folder (students and/or enrollments)
$shortopts .= "b::";  // When processing a file, start at this row
$shortopts .= "e::";  // End at this row
$shortopts .= "w::";  // Write data to reports every X number of lines/loops/rows
$shortopts .= "n::";  // name the output files (currently uses timestamp which can change every time process starts)

// Students and Enrollments
$shortopts .= "s::";  // Import Only Students
$shortopts .= "u::";  // Call the update function, add your changes there.
$shortopts .= "g::";  // Generate x numbers and export csv.
// Bundle
$shortopts .= "p::";  // Import Bundle enrollments using p as package bundle/package ;-)
$shortopts .= "q::";  // Convert Bundle enrollments Jenza ID's to ObjectIds, folder: /importer/bundle
// Fees
$shortopts .= "f::";  // Import the more than 4 fees into courses
// Certificates
$shortopts .= "t::";  // Enroll students in a certificate
// Course
$shortopts .= "c::";  // Run a course update on all courses
$shortopts .= "x::";  // Set this flag to true if setting course enrollments to future date

// Help
$shortopts .= "h::";  // Optional value

$longopts  = array(
    "rb:",       // When processing a file, start at this row
    "re:",       // End at this row
    "fees:",     // Import the more than 4 fees into courses
    "uc:",       // Run a course update on all courses
    "ucx:",      // Set this flag to true if setting course enrollments to future date
    "help",      // Optional value
);

$options = getopt($shortopts, $longopts);
// $help = isset($options['h']) ? $options['h'] : $options['help'];

if (isset($options['h'])) {
    $help = <<<EOL
    No flags will run the default importer to import students and enrollments.

    The importer will process any file in the 'unprocessed' folder.
    Reports will be generated for successfully created students and enrolments as well 
    as the failed attempts to create a student or enrolment. Those reports will be in the importer/reports
    folder.

    Here are your options:

    General
      -b=[x],        Row begin when processing a large file
      -e=[x],        Row end when processing a large file
      -w,            Report Write Count, write data to reports every X number of lines/loops/rows
      -n,            Name the output files (currently uses timestamp which can change every time process starts)
                     Files are appended too. So if name exists then data will be appended
    Student/Enrollment
      -s,            Import students only, folder: /importer/unprocessed
      -u,            Find and update student (temp hack to update LSU MF ID)
      -g,            Get the XNumber and generate a CSV 

    Bundle
      -p,            Import Bundle enrollments, , folder: /importer/bundle
      -q,            Convert Bundle enrollments Jenza ID's to ObjectIds, folder: /importer/bundle

    Fees
      -f,            Import more than 4 fees file, folder: /importer/fees

    Certificates
      -t,            Enroll students in certificates, folder: /importer/cert

    Course  
      -c,            Update courses, folder: /importer/course/
      -x,            When updating courses set dates to far in the future.
      
      
      -h,     --help                  Display's the list of commands for this script
                                
    ******************************************************************************************************************\n\n
    EOL;
    echo $help;
    die;
}

$rowbegin = $options['b'] ?? $options['rb'] ?? false;
$rowend = $options['e'] ?? $options['re'] ?? false;
$rwc = isset($options['w']) ? $options['w'] : 1000;
$thisfilename = isset($options['n']) ? $options['n'] : false;

$stuonly = isset($options['s']) ?? false;
$updstu = isset($options['u']) ?? false;
$genx = isset($options['g']) ?? false;

$bundle = isset($options['p']) ?? $options['bun'] ?? false;
$convertbundle = isset($options['q']) ?? false;

$fees = isset($options['f']) ?? $options['fees'] ?? false;

$cert = isset($options['t']) ?? false;

$uc = isset($options['c']) ?? $options['uc'] ?? false;
$ucx = isset($options['x']) ?? $options['ucx'] ?? false;


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

error_log("BASE -->> What is the token: ". $token);
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
    "studentsonly" => $stuonly,
    "convertbundle" => $convertbundle,
    "updstu" => $updstu,
    "thisfilename" => $thisfilename,
    "genx" => $genx
];

if ($fees) {
    error_log("************************************** WARNING ***************************************");
    error_log("You should check the account codes and their corresponding objectId's on the Web UI.");
    error_log("************************************** WARNING ***************************************");
    $report = new report("fee", $rwc);
    $feez = new processor($report, "fee");

    // Fees have a black list to run against, load and send
    $feez_file = $report->reportspath."/importer/core_fee_black_list.csv";
    $extras['feeblacklist'] = $feez->load_extra_files($feez_file, "index_black_list");
    
    $overallgarburatestart = microtime(true); 
    
    $feez->load();
    $d1ready = $feez->garburate($rowbegin, $rowend, $extras);
    
    $overallgarburateend = microtime(true); 
    $executiontime = ($overallgarburateend - $overallgarburatestart);

} else if ($uc) {

    $report = new report("course", $rwc);
    $course = new processor($report, "course");

    $overallgarburatestart = microtime(true);

    $course->load();
    $course->garburate($rowbegin, $rowend, $extras);

    $overallgarburateend = microtime(true);
    $executiontime = ($overallgarburateend - $overallgarburatestart);

} else if ($cert) {

    $report = new report("cert", $rwc);
    $cert = new processor($report, "cert");

    $overallgarburatestart = microtime(true);

    $cert->load();
    $cert->garburate($rowbegin, $rowend, $extras);

    $overallgarburateend = microtime(true);
    $executiontime = ($overallgarburateend - $overallgarburatestart);

} else if ($bundle) {

    $report = new report("bundle", $rwc);
    $bundle = new processor($report, "bundle");

    $pstart = microtime(true);

    $bundle->load();
    $bundle->garburate($rowbegin, $rowend, $extras);

    $pend = microtime(true);
    $report->timer("overall", $pend - $pstart);

} else {

    $report = new report("student", $rwc);
    $student = new processor($report, "student");

    $pstart = microtime(true);

    // do {
        // $count is the number of files remaining in the unprocessed folder.
    $count = $student->load();

    // Fees have a black list to run against, load and send
    $student_file = $report->reportspath."/importer/core_bundle_enroll.csv";
    helpers::load_bundle_list($student->load_extra_files($student_file));

    $d1ready = $student->garburate($rowbegin, $rowend, $extras);

    $pend = microtime(true);
    $report->timer("overall", $pend - $pstart);

    // $report->clean();

    // if ($movefile) {
    //     $se->moveFile();
    // } else {
    //     $count = 0;
    // }
    // } while ($count != 0);
    error_log("+++++++++++++++++++++++  File Processing End  +++++++++++++++++++++++");
}

$report->finish();
