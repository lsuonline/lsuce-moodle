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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo, David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once('publiclib.php');
    require_once('settingslib.php');
    
    // Using the public lib for string generation.
    $b = 'block_wdsprefs';
    $s = wds::gen_str($b);
    $_m = wds::gen_str('moodle');

    $settings->add(new admin_setting_heading(
        'block_wdsprefs_settings',
        '',
        $s('pluginname_desc')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_wdsprefs/course_severed',
        $s('course_severed'),
        $s('course_severed_desc'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'block_wdsprefs/course_threshold',
        $s('course_threshold'),
        $s('course_threshold_desc'),
        '8000'
    ));

    $fieldcats = $DB->get_records_menu('user_info_category', null, '', 'id, name');

    if (!empty($fieldcats)) {
        $first = key($fieldcats);

        $settings->add(new admin_setting_configselect(
            'block_wdsprefs/user_field_catid',
            $s('user_field_category'),
            $s('user_field_category_desc'),
            $first,
            $fieldcats
        ));
    }

    $wdssettings = array(
        // 'setting',
        'creation',
        'unwant',
        'material',
        'split',
        // 'crosslist', Not sure if needed?
        // 'team_request' Not sure if needed?
    );

    foreach ($wdssettings as $setting) {
        $settings->add(new admin_setting_heading(
            'block_wdsprefs_'.$setting.'_settings',
            $s($setting),
            ''
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_wdsprefs/'.$setting,
            $s($setting). $s('enabled'),
            $s('enabled_desc'),
            1
        ));

        setting_cruncher::$setting($settings, $s);
    }
}
