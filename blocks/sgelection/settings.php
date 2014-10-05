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
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {
    $settings->add(
            new admin_setting_configtext(
                    'block_sgelection/facadv',
                    get_string('facadv', 'block_sgelection'),
                    get_string('facadv_desc', 'block_sgelection'),
                    '',
                    PARAM_ALPHANUM)
            );
    $settings->add(
            new admin_setting_configtext(
                    'block_sgelection/earliest_start',
                    get_string('earliest_start', 'block_sgelection'),
                    get_string('earliest_start_desc', 'block_sgelection'),
                    14,
                    PARAM_INT)
            );
    $settings->add(
            new admin_setting_configtext(
                    'block_sgelection/census_window',
                    get_string('census_window', 'block_sgelection'),
                    get_string('census_window_desc', 'block_sgelection'),
                    24,
                    PARAM_INT)
            );
    $settings->add(
            new admin_setting_configtext(
                    'block_sgelection/latest_end',
                    get_string('latest_end', 'block_sgelection'),
                    get_string('latest_end_desc', 'block_sgelection'),
                    14,
                    PARAM_INT)
            );
}
