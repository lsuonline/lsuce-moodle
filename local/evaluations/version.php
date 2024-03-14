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
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Dustin Durrand http://oohoo.biz
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $plugin->version = 2013052709;  // The current module version (Date: YYYYMMDDXX)
// $plugin->version = 2014052301;  // The current module version (Date: YYYYMMDDXX)
$plugin->version = 2015102704;  // The current module version (Date: YYYYMMDDXX)
$plugin->cron = 1; // set cron to 5 mins
$plugin->component = 'local_evaluations';
