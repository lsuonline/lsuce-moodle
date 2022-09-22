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
 * UES Dupe Finder
 *
 * @package   block_dupfinder
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards Robert Russo, David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Link to get back.
$string['df_link_back_title'] = '<a href="'.$CFG->wwwroot.'/blocks/dupfinder/dupfinder.php">Back to Dup Finder Dashboard</a>';

// Block.
$string['pluginname'] = 'UES Dupe Finder';
$string['pluginname_desc'] = 'UES Duplicate User Search';
$string['get_dupes'] = 'Get duplicates';

// Configuration.
$string['df_username'] = 'DAS Username';
$string['df_username_help'] = 'Web Services username for DAS';
$string['df_password'] = 'DAS Password';
$string['df_password_help'] = 'Web Services password for the above username for DAS';
$string['df_semester'] = 'Semester Code';
$string['df_semester_help'] = 'The LSU DAS semester code used by the mainframe, example "20231S".';
$string['df_department'] = 'Department Code';
$string['df_department_help'] = 'The LSU DAS department code, example "HIST".';
$string['df_session'] = 'Session';
$string['df_session_help'] = 'The session code, example "C"';
$string['df_debugloc'] = 'Debug Files Location';
$string['df_debugloc_help'] = 'File storage area for extra debugging. XML enrollment files stored here.';


// Tasks.
$string['df_fixer'] = 'UES Find and Fix Duplicate Enrollments';

// Links.
$string['dashboard'] = 'Dashboard';
$string['manualtrigger'] = 'Manual Trigger';
$string['manual'] = 'Manual';
$string['settings'] = 'Settings';
$string['dfdashboard'] = 'DF Dashboard';
