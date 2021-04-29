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
 * Version information for the report_biosigcourseconstruct plugin.
 *
 * @package    report_biosigcourseconstruct
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$plugin->component = 'report_biosigcourseconstruct';
$plugin->version = 2020031300;
$plugin->requires  = 2017110800;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.0';
$plugin->dependencies = array(
    'mod_biosigid' => 2020031300,
    'quizaccess_biosigid' => 2020031300
);
