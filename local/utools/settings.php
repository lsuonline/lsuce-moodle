<?php

/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

defined('MOODLE_INTERNAL') || die;

// require_once('../../config.php');
// include_once('lib/UtoolsLib.php');

// add link to Site administration->development on main left panel
$ADMIN->add('development', new admin_externalpage(
    'localUofLTools',
    'UofL Tools',
    "$CFG->wwwroot/local/utools/index.php"
));

/* object for all uleth functions */
// $ulethlib = new UtoolsLib();
// if(!$ulethlib->checkAdminUser()){
//     echo $OUTPUT->header();
//     echo("<br>Sorry, you don't have access to this page.");
//     echo $OUTPUT->footer();
//     exit;
// }
//if ($hassiteconfig) { // needs this condition or there is error on login page
if ($ADMIN->fulltree) {
    $settings = new admin_settingpage(
        'local_utools',
        get_string('nav_ult_mn', 'local_utools'),
        'moodle/site:config',
        false
    );
    // $settings = new admin_settingpage('local_utools'.'utools Settings');
    $ADMIN->add('localplugins', $settings);

    
    // UofL Settings - Stupid link to get back -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading('local_utools_link_back_title', get_string('utools_link_back_title', 'local_utools'), '')
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_link_back_timer',
            get_string('LMB_refresh_timer', 'local_utools'),
            '(in milliseconds)',
            2000,
            PARAM_INT
        )
    );

    // UofL General Settings -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading('local_utools_general_settings_title', get_string('utools_general_settings_title', 'local_utools'), '')
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_use_js_ajax',
            get_string('local_utools_use_js_ajax_title', 'local_utools'),
            get_string('local_utools_use_js_ajax_help', 'local_utools'),
            1
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_LMB_refresh_timer',
            get_string('LMB_refresh_timer', 'local_utools'),
            '(in milliseconds)',
            2000,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_semester',
            get_string('utools_semester_title', 'local_utools'),
            get_string('utools_semester_help', 'local_utools'),
            '',
            PARAM_TEXT
        )
    );

    // UofL Settings - Web Services ------------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_utools_web_title',
            get_string('utools_web_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_web_token',
            get_string('utools_web_token', 'local_utools'),
            '',
            'replace_me',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_web_func',
            get_string('utools_web_func', 'local_utools'),
            '',
            'mod_assign_get_assignments_for_user',
            PARAM_TEXT
        )
    );
    
    // UofL Settings - Piwik -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_utools_piwik_title',
            get_string('local_utools_piwik_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_piwik_enabled',
            get_string('local_utools_piwik_enabled_title', 'local_utools'),
            get_string('local_utools_piwik_enabled_help', 'local_utools'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_piwik_interval_time',
            get_string('local_utools_piwik_interval_time_title', 'local_utools'),
            'For Ex: 30'.
            '<br>Number of seconds.',
            30,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_piwik_logging',
            get_string('local_utools_piwik_logging_title', 'local_utools'),
            get_string('local_utools_piwik_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );

    // UofL Settings - JMeter -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_utools_jmeter_title',
            get_string('local_utools_jmeter_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_jmeter_enabled',
            get_string('local_utools_jmeter_enabled_title', 'local_utools'),
            get_string('local_utools_jmeter_enabled_help', 'local_utools'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_jmeter_logging',
            get_string('local_utools_jmeter_logging_title', 'local_utools'),
            get_string('local_utools_jmeter_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );

    // UofL Settings - New Relic -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_utools_newrelic_title',
            get_string('local_utools_newrelic_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_newrelic_enabled',
            get_string('local_utools_newrelic_enabled_title', 'local_utools'),
            get_string('local_utools_newrelic_enabled_help', 'local_utools'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_newrelic_interval_time',
            get_string('local_utools_newrelic_interval_time_title', 'local_utools'),
            'For Ex: 30'.
            '<br>Number of seconds.',
            30,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_newrelic_logging',
            get_string('local_utools_newrelic_logging_title', 'local_utools'),
            get_string('local_utools_newrelic_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_newrelic_extra_auth_token',
            get_string('local_utools_newrelic_auth_token_title', 'local_utools'),
            get_string('local_utools_newrelic_auth_token_help', 'local_utools'),
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_newrelic_extra_server_ids',
            get_string('local_utools_newrelic_server_ids_title', 'local_utools'),
            get_string('local_utools_newrelic_server_ids_help', 'local_utools'),
            '',
            PARAM_TEXT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'local_utools_newrelic_extra_server_names',
            get_string('local_utools_newrelic_server_names_title', 'local_utools'),
            get_string('local_utools_newrelic_server_names_help', 'local_utools'),
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_newrelic_iframes',
            get_string('local_utools_newrelic_iframes_title', 'local_utools'),
            get_string('local_utools_newrelic_iframes_help', 'local_utools'),
            '',
            PARAM_RAW
        )
    );


    // UofL Settings - TCMS -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_utools_tcms_title',
            get_string('local_utools_tcms_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_tcms_enabled',
            get_string('local_utools_tcms_enabled_title', 'local_utools'),
            get_string('local_utools_tcms_enabled_help', 'local_utools'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_interval_time',
            get_string('local_utools_tcms_interval_time_title', 'local_utools'),
            'For Ex: 30'.
            '<br>Number of seconds.',
            30,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_logging',
            get_string('local_utools_tcms_logging_title', 'local_utools'),
            get_string('local_utools_tcms_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_extra_instance',
            get_string('local_utools_tcms_instance_title', 'local_utools'),
            get_string('local_utools_tcms_instance_help', 'local_utools'),
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_extra_hours_of_op_start',
            get_string('local_utools_tcms_hours_of_op_start_title', 'local_utools'),
            get_string('local_utools_tcms_hours_of_op_start_help', 'local_utools'),
            '9:00am',
            PARAM_TEXT
        )
    );
    
    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_extra_hours_of_op_stop',
            get_string('local_utools_tcms_hours_of_op_stop_title', 'local_utools'),
            get_string('local_utools_tcms_hours_of_op_stop_help', 'local_utools'),
            '9:00pm',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_total_exams_start',
            get_string('local_utools_tcms_total_exams_start_title', 'local_utools'),
            'For Ex: 2015-01-01'.
            '<br>Set the total exams start date',
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_tcms_total_exams_end',
            get_string('local_utools_tcms_total_exams_end_title', 'local_utools'),
            'For Ex: 2015-04-30'.
            '<br>Set the total exams end date',
            '',
            PARAM_TEXT
        )
    );

    // UofL Settings - Course Stat -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_utools_coursestat_title',
            get_string('local_utools_coursestat_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_coursestat_enabled',
            get_string('local_utools_coursestat_enabled_title', 'local_utools'),
            get_string('local_utools_coursestat_enabled_help', 'local_utools'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_coursestat_logging',
            get_string('local_utools_coursestat_logging_title', 'local_utools'),
            get_string('local_utools_coursestat_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );

    // $settings->add(
    //     new admin_setting_configtext(
    //         'local_utools_coursestat_interval_time',
    //         get_string('local_utools_coursestat_interval_time_title', 'local_utools'),
    //         'For Ex: 30'.
    //         '<br>Number of seconds.',
    //         30,
    //         PARAM_INT
    //     )
    // );
    //
    // UofL Settings - Developer Suite -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_utools_devsuite_title',
            get_string('local_utools_devsuite_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_utools_devsuite_enabled',
            get_string('local_utools_devsuite_enabled_title', 'local_utools'),
            get_string('local_utools_devsuite_enabled_help', 'local_utools'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_devsuite_logging',
            get_string('local_utools_devsuite_logging_title', 'local_utools'),
            get_string('local_utools_devsuite_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );

    // Utools Settings - Logging --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_utools_logging_main_title',
            get_string('local_utools_logging_main_title', 'local_utools'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_utools_logging',
            get_string('local_utools_logging', 'local_utools'),
            get_string('local_utools_logging_help', 'local_utools'),
            0,
            PARAM_INT
        )
    );
}
