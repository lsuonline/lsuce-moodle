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

/// Add settings for this module to the $settings object (it's already defined)
defined('MOODLE_INTERNAL') || die;

include_once('admin_setting_configdate.php'); //custom date control
global $PAGE;

if ($ADMIN->fulltree) {

    // $coursenode = $this->page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)
    /*
    error_log("\n\n----COURSE ADMIN NODE------------------------------------------------------------------------\n");
    $coursenode = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
    error_log("\nWhat is coursenode: ". print_r($coursenode, 1));
    error_log("\n\n----DONE------------------------------------------------------------------------\n");
    $da_path = new moodle_url('/grade/export/submit/index.php', array('id' => $PAGE->course->id));
    $grade_submit_node = navigation_node::create(
        "Whuzzzzzuuuuuupppppp",
        $da_path,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/competencies', '')
    );
    $coursenode->add_node($grade_submit_node, 'wanker');

    $coursenode->add(get_string('unenrolme', 'core_enrol', $shortname), $unenrollink, navigation_node::TYPE_SETTING, null, 'unenrolself', new pix_icon('i/user', ''));
    // ------------------------------------------------------------------------------------------
    $settingsnode = navigation_node::create(
        $title,
        $path,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/competencies', '')
    );
    if (isset($settingsnode)) {
        $navigation->add_node($settingsnode);
    }
    // ------------------------------------------------------------------------------------------
    // ------------------------------------------------------------------------------------------
    ($coursenode = $this->page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE))

    $modchoosertoggle = navigation_node::create(
        $modchoosertogglestring,
        $modchoosertoggleurl,
        navigation_node::TYPE_SETTING,
        null,
        'modchoosertoggle'
    );
    $coursenode->add_node($modchoosertoggle, 'turneditingonoff');


    // ------------------------------------------------------------------------------------------
    // ------------------------------------------------------------------------------------------
    // $coursenode = $settings->get('courseadmin');
    // if ($coursenode) {
    //     $coursenode->add('XXXXXXXXXXX----->Text to display', 'URL to go to');
    // }
    */


    $settings->add(new admin_setting_configdate(
        'gradeexport_submit_start_date',
        'Start Date',
        'Day to allow submitting of grades.',
        null,
        null,
        'gradeexport_submit'
    ));
    
    $settings->add(new admin_setting_configdate(
        'gradeexport_submit_end_date',
        'End Date',
        'Day to stop allowing submitting of grades.',
        null,
        null,
        'gradeexport_submit'
    ));

    // POST URL
    $settings->add(new admin_setting_configtext(
        'gradeexport_webservice_url',
        get_string('settings_webservice_url_title', 'gradeexport_submit'),
        get_string('settings_webservice_url_description', 'gradeexport_submit'),
        '',
        PARAM_TEXT,
        60
    ));
    // GET URL
    $settings->add(new admin_setting_configtext(
        'gradeexport_webservice_url_get',
        get_string('settings_webservice_url_get_title', 'gradeexport_submit'),
        get_string('settings_webservice_url_get_description', 'gradeexport_submit'),
        '',
        PARAM_TEXT,
        60
    ));

    // GET URL Dangler
    $settings->add(new admin_setting_configtext(
        'gradeexport_webservice_url_get_dangler',
        get_string('settings_webservice_url_get_dangler_title', 'gradeexport_submit'),
        get_string('settings_webservice_url_get_dangler_description', 'gradeexport_submit'),
        '',
        PARAM_TEXT,
        60
    ));
    
    $settings->add(new admin_setting_configtext(
        'gradeexport_webservice_username',
        get_string('settings_webservice_username_title', 'gradeexport_submit'),
        get_string('settings_webservice_username_description', 'gradeexport_submit'),
        '',
        PARAM_TEXT,
        60
    ));
    
    $settings->add(new admin_setting_configpasswordunmask(
        'gradeexport_webservice_password',
        get_string('settings_webservice_password_title', 'gradeexport_submit'),
        get_string('settings_webservice_password_description', 'gradeexport_submit'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'gradeexport_datasource',
        get_string('settings_datasource_title', 'gradeexport_submit'),
        get_string('settings_datasource_description', 'gradeexport_submit'),
        'Uleth Moodle',
        PARAM_TEXT,
        60
    ));

    $settings->add(new admin_setting_configtext(
        'gradeexport_institution',
        get_string('settings_institution_title', 'gradeexport_submit'),
        get_string('settings_institution_description', 'gradeexport_submit'),
        'BANNER University',
        PARAM_TEXT,
        60
    ));

    $settings->add(new admin_setting_configtext(
        'gradeexport_final_grades_column',
        get_string('settings_final_grades_column_title', 'gradeexport_submit'),
        get_string('settings_final_grades_column_description', 'gradeexport_submit'),
        'Final Grades',
        PARAM_TEXT,
        60
    ));


    $settings->add(new admin_setting_configtext(
        'gradeexport_notification_emails',
        get_string('notification_email_addresses_title', 'gradeexport_submit'),
        get_string('notification_email_addresses_description', 'gradeexport_submit'),
        '',
        PARAM_TEXT,
        60
    ));
    

    $settings->add(new admin_setting_configtext(
        'gradeexport_from_email',
        get_string('settings_from_email_title', 'gradeexport_submit'),
        get_string('settings_from_email_description', 'gradeexport_submit'),
        'teachingsupport@uleth.ca',
        PARAM_TEXT,
        60
    ));
}
