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
 * @package   local_cas_help_links
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

if (is_siteadmin()) {

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once($CFG->libdir . '/adminlib.php');

    // Create the new settings page
    $settings = new admin_settingpage('local_cas_help_links', 'CAS Help Links Settings');

    // Create 
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_cas_help_links/show_links_global',
        get_string('setting_show_links_global_title', 'local_cas_help_links'),
        get_string('setting_show_links_global_description', 'local_cas_help_links'),
        0
    ));

    // default_help_link
    $settings->add( new admin_setting_configtext(
        'local_cas_help_links/default_help_link',
        get_string('setting_default_help_link_title', 'local_cas_help_links'),
        get_string('setting_default_help_link_description', 'local_cas_help_links'),
        get_string('setting_default_help_link_default', 'local_cas_help_links'),
        PARAM_URL
    ));
}
