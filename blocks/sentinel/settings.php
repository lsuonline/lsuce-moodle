<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {

    $settings->add(
            new admin_setting_configtext(
                    'block_sentinel/excluded_courses',
                    get_string('excluded_courses', 'block_sentinel'),
                    get_string('excluded_courses_description', 'block_sentinel'),
                    ''
            )
    );
    
    $settings->add(
            new admin_setting_configtext(
                    'block_sentinel/landing_course',
                    get_string('landing_course', 'block_sentinel'),
                    get_string('landing_course_description', 'block_sentinel'),
                    ''
            )
    );
    
    $settings->add(
            new admin_setting_configtext(
                    'block_sentinel/clients',
                    get_string('clients', 'block_sentinel'),
                    get_string('clients_description', 'block_sentinel'),
                    ''
            )
    );
}
?>
