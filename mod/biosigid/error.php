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
 * Used to display a message when an error occurs
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

$courseurl = new moodle_url('/course/view.php', array('id' => $id));
$url = $courseurl->out();

//Output a page containing some script to break out of frames and redirect them

echo '<html><body>';

$error = get_string('error', BIOSIGID_MODULE_NAME);
$clickhere = get_string('return_to_course', BIOSIGID_MODULE_NAME, (object)array('link' => $url));

echo "<p>{$error}</p>";

echo $clickhere;

echo '</body></html>';