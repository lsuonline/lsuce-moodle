<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //================================================================================
    //current url
    
    // Title w/ Header
    $settings->add(
        new admin_setting_heading(
            'block_landing_block_current_heading',
            get_string('landing_block_current_heading', 'block_landing_block'),
            ''
        )
    );
    // Checkbox
    $settings->add(new admin_setting_configcheckbox(
        'block_landing_block_showCurrUrl',
        get_string('showCurr', 'block_landing_block'),
        get_string('labelshowcurr', 'block_landing_block'),
        1
    ));
    // Title
    $settings->add(new admin_setting_configtext(
        'block_landing_block_current_title', // grayed out part under title
        get_string('current_title', 'block_landing_block'), // title
        get_string('current_title_details', 'block_landing_block'), // details below title
        '', // default
        PARAM_TEXT
    ));
    // URL
    $settings->add(new admin_setting_configtext(
        'block_landing_block_currentUrl',
        get_string('currUrl', 'block_landing_block'),
        get_string('labelcurrUrl', 'block_landing_block'),
        '',
        PARAM_TEXT
    ));

    //================================================================================
    //longterm
    // Title w/ Header
    $settings->add(
        new admin_setting_heading(
            'block_landing_block_longterm_heading',
            get_string('landing_block_longterm_heading', 'block_landing_block'),
            ''
        )
    );
    // Checkbox
    $settings->add(new admin_setting_configcheckbox(
        'block_landing_block_showLongTermUrl',
        get_string('showLongTerm', 'block_landing_block'),
        get_string('labelShowLongTerm', 'block_landing_block'),
        1
    ));
    // Title
    $settings->add(new admin_setting_configtext(
        'block_landing_block_longterm_title', // grayed out part under title
        get_string('longterm_title', 'block_landing_block'), // title
        get_string('longterm_title_details', 'block_landing_block'), // details below title
        '', // default
        PARAM_TEXT
    ));
    // URL
    $settings->add(new admin_setting_configtext(
        'block_landing_block_longTermUrl',
        get_string('longTermUrl', 'block_landing_block'),
        get_string('labelLongTermUrl', 'block_landing_block'),
        '',
        PARAM_TEXT
    ));

    //================================================================================
    //past
    // Title w/ Header
    $settings->add(
        new admin_setting_heading(
            'block_landing_block_previous_heading',
            get_string('landing_block_previous_heading', 'block_landing_block'),
            ''
        )
    );
    // Checkbox
    $settings->add(new admin_setting_configcheckbox(
        'block_landing_block_showPastUrl',
        get_string('showPast', 'block_landing_block'),
        get_string('labelShowPast', 'block_landing_block'),
        1
    ));
    // Title
    $settings->add(new admin_setting_configtext(
        'block_landing_block_past_title', // grayed out part under title
        get_string('past_title', 'block_landing_block'), // title
        get_string('past_title_details', 'block_landing_block'), // details below title
        '', // default
        PARAM_TEXT
    ));
    // URL
    $settings->add(new admin_setting_configtext(
        'block_landing_block_pastUrl',
        get_string('pastUrl', 'block_landing_block'),
        get_string('labelPastUrl', 'block_landing_block'),
        '',
        PARAM_TEXT
    ));

    //================================================================================
    //future
    // Title w/ Header
    $settings->add(
        new admin_setting_heading(
            'block_landing_block_future_heading',
            get_string('landing_block_future_heading', 'block_landing_block'),
            ''
        )
    );
    // Checkbox
    $settings->add(new admin_setting_configcheckbox(
        'block_landing_block_showFutureUrl',
        get_string('showFuture', 'block_landing_block'),
        get_string('labelshowFuture', 'block_landing_block'),
        1
    ));
    // Title
    $settings->add(new admin_setting_configtext(
        'block_landing_block_future_title', // grayed out part under title
        get_string('future_title', 'block_landing_block'), // title
        get_string('future_title_details', 'block_landing_block'), // details below title
        '', // default
        PARAM_TEXT
    ));
    // URL
    $settings->add(new admin_setting_configtext(
        'block_landing_block_futureUrl',
        get_string('futureUrl', 'block_landing_block'),
        get_string('labelFutureUrl', 'block_landing_block'),
        '',
        PARAM_TEXT
    ));

    //================================================================================
    // Title w/ Header
    $settings->add(
        new admin_setting_heading(
            'block_landing_block_miscellaneous_heading',
            get_string('landing_block_miscellaneous_heading', 'block_landing_block'),
            ''
        )
    );
    // Use AJAX, Checkbox
    $settings->add(new admin_setting_configcheckbox(
        'block_landing_block_use_ajax_to_load',
        get_string('landing_block_use_ajax_to_load', 'block_landing_block'),
        get_string('landing_block_use_ajax_to_load_details', 'block_landing_block'),
        1
    ));

    // Use the new Course Overview display.
    $settings->add(new admin_setting_configcheckbox(
        'block_landing_block_use_new_course_overview',
        get_string('landing_block_use_new_course_overview', 'block_landing_block'),
        get_string('landing_block_use_new_course_overview_details', 'block_landing_block'),
        0
    ));
    
    //post timeout settings
    $settings->add(new admin_setting_configtext(
        'block_landing_block_postimeout',
        get_string('postTimeout', 'block_landing_block'),
        get_string('labelPostTimeout', 'block_landing_block'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'block_landing_block_secret',
        get_string('secret', 'block_landing_block'),
        get_string('labelsecret', 'block_landing_block'),
        '',
        PARAM_TEXT
    ));

    $settings->add(
        new admin_setting_configtextarea(
            'block_landing_block_message_no_courses',
            get_string('block_landing_block_message_no_courses_title', 'block_landing_block'),
            get_string('block_landing_block_message_no_courses_help', 'block_landing_block'),
            'You are currently not registered in any course OR your Instructor has not opened your course for access yet.',
            PARAM_TEXT
        )
    );

    // Landing Block Version to help identify different versions of theme
    $settings->add(
        new admin_setting_configtext(
            'block_landing_block_version',
            get_string('lb_version', 'block_landing_block'),
            get_string('lb_version_help', 'block_landing_block'),
            '2',
            PARAM_TEXT
        )
    );

    // Landing Block Version to help identify different versions of theme
    $settings->add(
        new admin_setting_configcheckbox(
            'block_landing_block_simple_view',
            get_string('lb_simple_view', 'block_landing_block'),
            get_string('lb_simple_view_help', 'block_landing_block'),
            1
        )
    );
}
