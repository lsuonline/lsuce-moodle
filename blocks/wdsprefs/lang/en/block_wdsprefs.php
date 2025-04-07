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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin stuff.
$string['wdsprefs:pluginname'] = 'WDS Preferences';

// Basic stuff.
$string['wdsprefs:cancel'] = 'Preference modification cancelled.';
$string['wdsprefs:error'] = 'Error saving preferences!';
$string['wdsprefs:saveprefs'] = 'Save Preferences';
$string['wdsprefs:success'] = 'Preferences saved successfully!';

// Link stuff.
$string['wdsprefs:course'] = 'Course preferences';
$string['wdsprefs:crosslist'] = 'Cross listing';
$string['wdsprefs:split'] = 'Split courses';
$string['wdsprefs:schedule'] = 'Course schedule';
$string['wdsprefs:teamteach'] = 'Team teaching';
$string['wdsprefs:unwant'] = 'Unwanted sections';
$string['wdsprefs:user'] = 'User preferences';

$string['help_course_preferences'] = 'course creation and enrollment preferences.';
$string['help_unwanted_sections'] = 'unwanted section enrollments.';
$string['help_split_courses'] = 'splitting a multi-section course into multiple course shells.';
$string['help_cross_listing'] = 'crosslisting courses and sections into a single course shell.';
$string['help_team_teaching'] = 'team teaching assignments.';

$string['help_course_preferences_help'] = 'Set your course creation and enrollment date preferences.';
$string['help_unwanted_sections_help'] = 'Remove unwanted course sections from your Moodle course shells.';
$string['help_split_courses_help'] = 'Split courses into multiple course shells with anywhere from one course shell to one section per course shell.';
$string['help_cross_listing_help'] = 'Merge multiple courses and their sections into one single course shell. Enrollments will be kept up and post grades for all cross listed course sections directly from this shell.';
$string['help_team_teaching_help'] = 'Invite another professor and their section\'s roster into your course shell. Enrollment will be kept and grade posting permitted from this course shell.';

// WDS Page strings.
$string['wdsprefs:format'] = 'Course Format';
$string['wdsprefs:cdaysprior'] = 'Create Days Prior';
$string['wdsprefs:cdaysprior_help'] = 'Number of days prior to the semester starting to <strong>create courses</strong>.';
$string['wdsprefs:cdaysprior_desc'] = 'Number of days prior to the semester starting to <strong>create courses</strong>.';
$string['wdsprefs:edaysprior'] = 'Enroll Days Prior';
$string['wdsprefs:edaysprior_help'] = 'Number of days prior to the semester starting to <strong>enroll students</strong>.';
$string['wdsprefs:edaysprior_desc'] = 'Number of days prior to the semester starting to <strong>enroll students</strong>.';
$string['wdsprefs:courselimit'] = 'Course Limit';
$string['wdsprefs:courselimit_help'] = 'Only create courses below this threshold.';
$string['wdsprefs:courselimit_desc'] = 'Only create courses below this threshold.';

// Unwant Page Strings.
$string['wdsprefs:period'] = 'Academic Period';
