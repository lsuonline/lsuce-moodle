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
 * Tiny accordion settings file.
 *
 * @package     tiny_accordion
 * @copyright   2026 Catalyst IT Australia
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'tiny_accordion_settings',
        new lang_string('pluginname', 'tiny_accordion')
    );

    if ($ADMIN->fulltree) {
        // Show or hide both accordion toolbar icons.
        $settings->add(new admin_setting_configcheckbox(
            'tiny_accordion/showtoolbaricons',
            new lang_string('settings_showtoolbaricons', 'tiny_accordion'),
            new lang_string('settings_showtoolbaricons_desc', 'tiny_accordion'),
            1
        ));

        // Show or hide the remove accordion icon only.
        $settings->add(new admin_setting_configcheckbox(
            'tiny_accordion/showremoveicon',
            new lang_string('settings_showremoveicon', 'tiny_accordion'),
            new lang_string('settings_showremoveicon_desc', 'tiny_accordion'),
            1
        ));

        // Select which toolbar group the accordion icons appear in.
        $settings->add(new admin_setting_configselect(
            'tiny_accordion/toolbargroup',
            new lang_string('settings_toolbargroup', 'tiny_accordion'),
            new lang_string('settings_toolbargroup_desc', 'tiny_accordion'),
            'content',
            [
                'advanced'    => new lang_string('settings_toolbargroup_advanced', 'tiny_accordion'),
                'content'     => new lang_string('settings_toolbargroup_content', 'tiny_accordion'),
                'formatting'  => new lang_string('settings_toolbargroup_formatting', 'tiny_accordion'),
                'lists'       => new lang_string('settings_toolbargroup_lists', 'tiny_accordion'),
                'indentation' => new lang_string('settings_toolbargroup_indentation', 'tiny_accordion'),
                'view'        => new lang_string('settings_toolbargroup_view', 'tiny_accordion'),
            ]
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tiny_accordion/allowinlinestyle',
            new lang_string('settings_allowinlinestyle', 'tiny_accordion'),
            new lang_string('settings_allowinlinestyle_desc', 'tiny_accordion'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'tiny_accordion/classprefixallowlist',
            new lang_string('settings_classprefixallowlist', 'tiny_accordion'),
            new lang_string('settings_classprefixallowlist_desc', 'tiny_accordion'),
            '',
            PARAM_RAW
        ));
    }
}
