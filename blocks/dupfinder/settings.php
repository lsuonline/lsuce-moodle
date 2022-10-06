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
 *
 * @package    block_dupfinder
 * @copyright  2022 onwards Louisiana State University
 * @copyright  2022 Robert Russo
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // --------------------------------
    // Dashboard Link.
    $settings->add(
        new admin_setting_heading(
            'block_dupfinder_link_back_title',
            get_string('df_link_back_title', 'block_dupfinder'),
            ''
        )
    );

    // Add a heading.
    $settings->add(
        new admin_setting_heading(
            'block_dupfinder_settings',
            '',
            get_string('pluginname_desc', 'block_dupfinder')
        )
    );

    // DAS Webservice URL.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/url',
            get_string('df_url', 'block_dupfinder'),
            get_string('df_url_help', 'block_dupfinder'),
            '', PARAM_TEXT
        )
    );

    // DAS Webservice Username.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/username',
            get_string('df_username', 'block_dupfinder'),
            get_string('df_username_help', 'block_dupfinder'),
            '', PARAM_TEXT
        )
    );

    // DAS Webservice password.
    $settings->add(
        new admin_setting_configpasswordunmask(
            'block_dupfinder/password',
            get_string('df_password', 'block_dupfinder'),
            get_string('df_password_help', 'block_dupfinder'),
            '', PARAM_RAW
        )
    );

    // Debug files location.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/debugloc',
            get_string('df_debugloc', 'block_dupfinder'),
            get_string('df_debugloc_help', 'block_dupfinder'),
            $CFG->dataroot, PARAM_TEXT
        )
    );

    // Debug files location.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/semester',
            get_string('df_semester', 'block_dupfinder'),
            get_string('df_semester_help', 'block_dupfinder'),
            '', PARAM_TEXT
        )
    );

    // Debug files location.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/session',
            get_string('df_session', 'block_dupfinder'),
            get_string('df_session_help', 'block_dupfinder'),
            '', PARAM_TEXT
        )
    );

    // Debug files location.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/department',
            get_string('df_department', 'block_dupfinder'),
            get_string('df_department_help', 'block_dupfinder'),
            '', PARAM_TEXT
        )
    );

    // --------------------------------
    // Email Settings.
    $settings->add(
        new admin_setting_heading(
            'block_dupfinder_email_settings_title',
            get_string('df_email_settings', 'block_dupfinder'),
            ''
        )
    );

    // Check to email all admins.
    $settings->add(
        new admin_setting_configcheckbox(
            'block_dupfinder/emailalladmins',
            get_string('df_emailadmins_title', 'block_dupfinder'),
            get_string('df_emailadmins_desc', 'block_dupfinder'),
            1
        )
    );

    // Debug files location.
    $settings->add(
        new admin_setting_configtext(
            'block_dupfinder/emaillist',
            get_string('df_emaillist_title', 'block_dupfinder'),
            get_string('df_emaillist_desc', 'block_dupfinder'),
            '',
            PARAM_TEXT
        )
    );


}
