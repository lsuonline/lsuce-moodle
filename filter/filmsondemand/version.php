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
 * filmsondemand Video filter plugin.
 * @author    Pramod Ubbala (AGS -> Infobase)
 * @package    filter_filmsondemand
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2019 Infobase
 * @copyright based on work by Pramod Ubbala
 */

defined('MOODLE_INTERNAL') || die();

$plugin                     = new StdClass();
$plugin->component          = 'filter_filmsondemand';
$plugin->version			= 2018110800;
$plugin->requires           = 2017051500; // Moodle 3.3 is required.
$plugin->release			= 'v3.0-r2'; // This is our second revision for Moodle 3.0.x branch.
$plugin->maturity           = MATURITY_STABLE;
