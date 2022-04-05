<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

defined('MOODLE_INTERNAL') || die;

// add link to Site administration->development on main left panel
$ADMIN->add('development', new admin_externalpage(
    'localTCS',
    'TCS',
    "$CFG->wwwroot/local/tcs/index.php"
));

//if ($hassiteconfig) { // needs this condition or there is error on login page
if ($ADMIN->fulltree) {
    $settings = new admin_settingpage(
        'local_tcs',
        get_string('tcs_title', 'local_tcs'),
        'moodle/site:config',
        false
    );
    // $settings = new admin_settingpage('local_tcs'.'utools Settings');
    $ADMIN->add('localplugins', $settings);

    // TCS Settings - Stupid link to get back -------------------------------------------------------------------------------.
    $settings->add(
        new admin_setting_heading(
            'local_tcs_link_back_title',
            get_string('tcs_link_back_title', 'local_tcs'),
            ''
        )
    );

    // TCS Settings - Classroom Size --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_seat_size_main_title',
            get_string('local_tcs_seat_size_main_title', 'local_tcs'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_seat_size_count',
            get_string('local_tcs_seat_size_count', 'local_tcs'),
            '(number of seats)',
            45,
            PARAM_INT
        )
    );

    
    // TCS Settings - IP Subnets ------------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_subnet_main_title',
            get_string('local_tcs_subnet_main_title', 'local_tcs'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_subnet_list',
            get_string('local_tcs_subnet_list', 'local_tcs'),
            'All the ips that can access the TCS (comma seperated)',
            '142.66.30,142.66.112.53,142.66.112.52,127.0.0.1',
            PARAM_TEXT
        )
    );

    // TCS Settings - Quiz IP Restriction-----------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_quiz_ip_restriction_main_title',
            get_string('local_tcs_quiz_ip_restriction_main_title', 'local_tcs'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_quiz_ip_restriction',
            get_string('local_tcs_quiz_ip_restriction', 'local_tcs'),
            'The IP that can access the TCS (comma seperated)',
            '142.66.30',
            PARAM_TEXT
        )
    );

    // TCS Settings - Logging --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_logging_main_title',
            get_string('local_tcs_logging_main_title', 'local_tcs'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_logging',
            get_string('local_tcs_logging', 'local_tcs'),
            '(off to start)',
            0,
            PARAM_INT
        )
    );

    // TCS Settings - Theme --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_theme_main_title',
            get_string('local_tcs_theme_main_title', 'local_tcs'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_theme_use_old',
            get_string('local_tcs_theme_use_old', 'local_tcs'),
            '(off to start)',
            0,
            PARAM_INT
        )
    );


    // TCS Settings - Calendar Setting --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_calendar_main_title',
            get_string('local_tcs_calendar_main_title', 'local_tcs'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_calendar_range',
            get_string('local_tcs_calendar_range', 'local_tcs'),
            'Range for High, Medium and Low',
            '250-1000, 180-249, 0-179',
            PARAM_TEXT
        )
    );

    // TCS Settings - Room  --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_dashboard_settings_main_title',
            get_string('local_tcs_dashboard_settings_main_title', 'local_tcs'),
            ''
            )
        );
        
    $settings->add(
        new admin_setting_configtext(
            'local_tcs_dash_rooms',
            get_string('local_tcs_dash_rooms_title', 'local_tcs'),
            'How many rooms are open.',
            '5',
            PARAM_TEXT
        )
    );
            
    $settings->add(
        new admin_setting_configtext(
            'local_tcs_dash_refresh_rate',
            get_string('local_tcs_dash_refresh_rate_title', 'local_tcs'),
            'How often to refresh stats?',
            '8',
            PARAM_TEXT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'local_tcs_auto_comp_click_finish',
            get_string('local_tcs_auto_comp_click_finish_title', 'local_tcs'),
            'Hit enter to complete search?',
            '0',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_tcs_dash_stat_cards',
            get_string('local_tcs_dash_stat_cards_title', 'local_tcs'),
            'What Stat Cards do you want to see? <br>'. 
            'Student Count - 1<br>'.
            'Exams Today - 2<br>'.
            'Written Today - 3<br>'.
            'Written Semester - 4<br>',
            '1,2,3,4',
            PARAM_TEXT
        )
    );
    // TCS Settings - Random  --------------------------------------------------------------------------
    // Toggle this if we are running the old version still......
    // $settings->add(
    //     new admin_setting_configtext(
    //         'local_tcs_side_by_side',
    //         get_string('local_tcs_side_by_side_title', 'local_tcs'),
    //         'Are we running the old version as well?',
    //         '1',
    //         PARAM_INT
    //     )
    // );
    // TCS Settings - Queries  --------------------------------------------------------------------------
    $settings->add(
        new admin_setting_heading(
            'local_tcs_query_main_title',
            get_string('local_tcs_query_main_title', 'local_tcs'),
            ''
            )
        );
    // Query setting for fetch ALL exams
    $settings->add(
        new admin_setting_configtext(
            'local_tcs_query_iprestricted_exams',
            get_string('local_tcs_query_iprestricted_exams_title', 'local_tcs'),
            'Only show exams that have some form of IP restriction in place. (0 - if you want off)',
            '0',
            PARAM_INT
        )
    );
    // Query setting for fetch ALL exams
    $settings->add(
        new admin_setting_configtext(
            'local_tcs_query_closed_exams',
            get_string('local_tcs_query_closed_exams_title', 'local_tcs'),
            'Query for ALL exams in the Exam List including non ip restricted. (1 - include all expired exams)',
            '0',
            PARAM_INT
        )
    );
}
