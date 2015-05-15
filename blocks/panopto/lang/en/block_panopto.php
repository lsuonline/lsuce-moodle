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
 * @package block_panopto
 * @copyright  Panopto 2009 - 2015 with contributions from Spenser Jones (sjones@ambrose.edu)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Panopto';
$string['panopto:addinstance'] = 'Add a new Panopto block';
$string['panopto:myaddinstance'] = 'Add a new Panopto block to my page';
$string['panopto:provision_course'] = 'Provision a course';
$string['panopto:provision_multiple'] = 'Provision multiple courses at once';
$string['panopto:provision_asteacher'] = 'Provision as a teacher';
$string['panopto:provision_aspublisher'] = 'Provision as a publisher';
$string['provision_courses'] = 'Provision Courses';
$string['provisioncourseselect'] = 'Select Courses to Provision.';
$string['provisioncourseselect_help'] = 'Multiple selections are possible by Ctrl-clicking (Windows) or Cmd-clicking (Mac).';
$string['unconfigured'] = 'Global configuration incomplete. Please contact your system administrator.';
$string['unprovisioned'] = 'This course has not yet been provisioned.';
$string['block_edit_error'] = 'Cannot configure block instance: ' . $string['unconfigured'];
$string['block_edit_header'] = 'Select the Panopto course to display in this block.';
$string['add_to_panopto'] = 'Add this course to Panopto (re-add to sync user lists)';
$string['or'] = 'OR';
$string['existing_course'] = 'Select an existing course:';
$string['block_global_instance_name'] = 'Moodle Instance Name';
$string['block_global_instance_description'] = 'This value is prefixed before usernames and course-names in Panopto.';
$string['block_global_hostname'] = 'Panopto Server Hostname';
$string['block_global_application_key'] = 'Application Key';
$string['block_global_add_courses'] = 'Add Moodle courses to Panopto';
$string['course'] = 'Course';
$string['no_course_selected'] = 'No Panopto course selected';
$string['error_retrieving'] = 'Error retrieving Panopto course.';
$string['live_sessions'] = 'Live Sessions';
$string['no_live_sessions'] = 'No Live Sessions';
$string['take_notes'] = 'Take Notes';
$string['watch_live'] = 'Watch Live';
$string['completed_recordings'] = 'Completed Recordings';
$string['no_completed_recordings'] = 'No Completed Recordings';
$string['show_all'] = 'Show All';
$string['podcast_feeds'] = 'Podcast Feeds';
$string['podcast_audio'] = 'Audio Podcast';
$string['podcast_video'] = 'Video Podcast';
$string['links'] = 'Links';
$string['course_settings'] = 'Course Settings';
$string['download_recorder'] = 'Download Recorder';
$string['show_all'] = 'Show All';
$string['show_less'] = 'Show Less';
$string['role_map_header'] = 'Change Panopto Role Mappings';
$string['role_map_info_text'] = "Choose which Panopto roles a user's Moodle role will map to. <br> Unmapped roles will be given the 'Viewer' role in Panopto.
 <br><br> ";
$string['block_panopto_async_tasks'] = 'Asynchronous enrolment sync';

/* End of file block_panopto.php */
