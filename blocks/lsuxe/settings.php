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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Create the settings block.
$settings = new admin_settingpage($section, get_string('settings'));

// Make sure only admins see this one.
if ($ADMIN->fulltree) {
    // --------------------------------
    // LSUXE Settings Title.
    $settings->add(
        new admin_setting_heading(
            'block_lsuxe_interval_main_title',
            get_string('xe_interval_main_title', 'block_lsuxe'),
            ''
        )
    );

    // --------------------------------
    // Interval Settings.
    $settings->add(
        new admin_setting_configtextarea(
            'block_lsuxe_interval_list',
            get_string('xe_interval_list', 'block_lsuxe'),
            'List of Moodle instances',
            'Monthly 720
            Weekly 168
            Daily 24
            Hourly 1',
            PARAM_TEXT
        )
    );
}
