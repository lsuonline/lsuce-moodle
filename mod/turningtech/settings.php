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
 * The admin settings page for the turningtech module
 * @package    mod_turningtech
 * @copyright  2012 Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once($CFG->dirroot . '/mod/turningtech/lib.php');
defined('MOODLE_INTERNAL') || die;
if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");
    // Select device ID format.
    $settings->add(new admin_setting_configselect('turningtech_encryption_format',
                     get_string('encryptionformat', 'turningtech'),
                     get_string('encryptionformatdescription', 'turningtech'),
                     TURNINGTECH_ENCRYPTION_FORMAT_ECB, turningtech_get_encryption_format_options()));
}