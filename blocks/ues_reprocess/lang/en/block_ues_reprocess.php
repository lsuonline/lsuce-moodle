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
 * @package    block_ues_reprocess
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Link back to page.
global $CFG;
$string['ues_repall_link_back_title'] = '<a href="'.$CFG->wwwroot.'/blocks/ues_reprocess/reprocess_all.php">Back to Reprocess Page</a>';

$string['pluginname'] = 'Reprocess Enrollment';
$string['settings'] = 'Reprocess Enrollment';
$string['reprocess'] = 'Reprocess';

$string['ues_reprocess:canreprocess'] = 'Allow UES enrollment reprocessing for courses';
$string['ues_reprocess:addinstance'] = 'Add UES Repocess block';
$string['ues_reprocess:myaddinstance'] = 'Add UES Repocess block';
$string['settings'] = 'Reprocess Enrollments';
$string['updatereprocessing'] = 'Update Reprocess Shnitzels';
$string['reprocessselected'] = 'Reprocess Specific Courses';

$string['not_supported'] = 'You have requested an unsupported reprocess type: {$a}';
$string['reprocess'] = 'Reprocess';
$string['reprocess_all_courses'] = 'Reprocess All Courses';
$string['reprocess_course'] = 'Reprocess Course';
$string['select'] = 'Select a course or section to be reprocessed.';
$string['none_found'] = 'No sections were found associated with this course. You can either wait for the section association to be restored tonight, or you can force reprocessing on section individually, by continuing.';
$string['cleanup'] = 'Initiating reprocessing cleanup ...';
$string['done'] = 'Done.';
$string['are_you_sure'] = 'Are you sure you want to reprocess the following sections?
    <ul>
    {$a}
    </ul>
';
$string['patience'] = 'Reprocessing can take a few minutes. Please be patient while the job finishes. Thank you.';

// Settings.
$string['ues_reprocess_link_back_title'] = 'Back to UES Repocess';
$string['ues_semester_title'] = 'Semester Options';
$string['ues_reprocess_semesters'] = 'Which Semester to Use';
$string['ues_task_title'] = 'Scheduled Task Settings';
$string['ues_task_year_title'] = 'Year to run';
$string['ues_task_year'] = 'Ex: 2024 (blank will default to current year)';
$string['ues_task_semester_title'] = 'Semester to run';
$string['ues_task_semester'] = 'Ex: 1S (blank will do all)';
$string['ues_task_department_title'] = 'Department to run';
$string['ues_task_department'] = 'Ex: ACCT (blank will do all)';
$string['semesters'] = 'Semester List';
$string['semesters_help'] = 'Select as many semesters as you\'d like to reprocess';
$string['categories'] = 'Course Categories';
$string['categories_help'] = 'Select as many categories as you\'d like to reprocess for the selected semester(s)';


$string['ues_reprocess_testing_title'] = 'Testing/Debugging';
$string['ues_testing_title'] = 'Check this to allow unenrollment option';
$string['ues_testing'] = '(this is for testing)';


// Form.
$string['seldepart'] = 'Select Department';
$string['selcourse'] = 'Select Course';
$string['unenrolcheck'] = 'Unenrol Students';
