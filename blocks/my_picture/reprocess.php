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
 * Connects to LSU web service for downloading and updating user photos
 *
 * @package    block_my_picture
 * @copyright  2008, Adam Zapletal, 2017, Robert Russo, Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Grab the requisite files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('lib.php');

require_login();

$_s = function($key) { return get_string($key, 'block_my_picture'); };

$header = $_s('reprocess_title');
$pluginname = $_s('pluginname');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/my_picture/reprocess.php');
$PAGE->navbar->add($pluginname);
$PAGE->navbar->add($header);
$PAGE->set_title($pluginname . ': ' . $header);
$PAGE->set_heading($SITE->shortname . ': ' . $pluginname);

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$result_map = array(
    0 => 'error_user',
    1 => 'badid_user',
    2 => 'success_user',
    3 => 'nopic_user'
);

// Force updating when user clicks reprocess
$force_update = true;
$result = mypic_update_picture($USER, $force_update);
$class = $result == 2 ? 'notifysuccess' : 'notifyproblem';

echo $OUTPUT->notification($_s($result_map[$result]), $class);

echo $OUTPUT->continue_button(new moodle_url('/my'));

echo $OUTPUT->footer();
