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
 * This page prints a particular instance of turningtech
 *
 * @package    mod_turningtech
 * @copyright  Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use turningtech\event;
require('../../config.php');
require_once($CFG->dirroot . '/mod/turningtech/classes/event/ttlogs.php');
require_once($CFG->dirroot . '/mod/turningtech/locallib.php');
global $DB, $USER;
if (isloggedin()) {
    $sessioncookieval = ($USER->username);
    setcookie('TurningPointUserToken', base64_encode($sessioncookieval), time() + 3600);
    $value = clean_param($_COOKIE['TurningPointUserToken'], PARAM_TEXT);
} else {
$SESSION->wantsurl= $CFG->wwwroot . "/mod/turningtech/valdturning.php";
require_login(NULL,true, NULL, true, false);
}
// Print the page header.
$strturningtechs = get_string('modulenameplural', 'turningtech');
$strturningtech  = get_string('modulename', 'turningtech');

echo 'Welcome to '.$strturningtech;
// echo "The Value is ".$value;
// End.
