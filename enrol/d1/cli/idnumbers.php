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
 * @package    enrol_d1
 * @copyright  2022 onwards LSUOnline & Continuing Education
 * @copyright  2022 onwards Robert Russo
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * This file is used to update course idnumbers via CLI.
 *
 */

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

global $CFG, $DB;

require_once("$CFG->libdir/clilib.php");

// Require the magicness.
require_once('../classes/d1.php');

// Get the token.
$token = lsud1::get_token();
mtrace("Token: $token");

$ocats = get_config('local_d1', 'ocategories');
$pcats = get_config('local_d1', 'pcategories');

$pupdate = lsud1::update_pd_idnumbers($pcats);
$oupdate = lsud1::update_odl_idnumbers($ocats);
?>
