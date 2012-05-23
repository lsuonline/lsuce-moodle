<?php

if ($ADMIN->fulltree) {
    $_s = function($key, $a=null) { return get_string($key, 'block_post_grades', $a); };

    $period_url = new moodle_url('/blocks/post_grades/posting_periods.php');
    $reset_url = new moodle_url('/blocks/post_grades/reset.php');

    $a = new stdClass;
    $a->period_url = $period_url->out();
    $a->reset_url = $reset_url->out();

    $settings->add(new admin_setting_heading('post_grades_header',
        '', $_s('header_help', $a)));

    $settings->add(new admin_setting_configtext('block_post_grades/domino_application_url',
        $_s('domino_application_url'), '', ''));

    $settings->add(new admin_setting_configtext('block_post_grades/mylsu_gradesheet_url',
        $_s('mylsu_gradesheet_url'), '', ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/https_protocol',
        $_s('https_protocol'), $_s('https_protocol_desc'), 0));
}
