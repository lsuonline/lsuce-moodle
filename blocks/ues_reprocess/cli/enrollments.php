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
 * @package    ues_reprocess
 * @copyright  2024 onwards LSUOnline & Continuing Education
 * @copyright  2024 onwards Robert Russo
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

// Include the main Moodle config.
require(__DIR__ . '/../../../config.php');
require_once('workdaystudent.php');

// Get settings.
$s = workdaystudent::get_settings();

// Get the sections.
$sections = workdaystudent::get_current_sections($s);

$numgrabbed = count($sections);
mtrace("Fetched $numgrabbed sections.");

// Get the formatted date to grab enrollments for X days prior.
$xdays = 30;
// Build the date.
$date = new DateTime();
// Set the timezone.
$date->setTimezone(new DateTimeZone('America/Chicago'));
// Modify the date.
$date->modify('-' . $xdays . ' days');
// Set the time to midnight.
$date->setTime(0, 0);
// Set the fdate varuiable for use.
$fdate = $date->format('Y-m-d\TH:i:s');

// Set up some timing.
$processstart = microtime(true);

foreach ($sections as $section) {
    if ($section->section_listing_id == "LSUAM_Listing_CHEM1201_002-LEC-SP_LSUAM_SPRING_2024") {
        $enrollments = workdaystudent::get_section_enrollments($s, $section, $fdate);
        var_dump($enrollments);
        die();
    }
}

$processend = microtime(true);
$processtime = round($processend - $processstart, 2);
mtrace("Processing $numgrabbed sections took $processtime seconds.");
