<?php

if ($ADMIN->fulltree) {
    require_once $CFG->dirroot . '/blocks/post_grades/settinglib.php';
    require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
    ues::require_daos();

    $_s = ues::gen_str('block_post_grades');

    $period_url = new moodle_url('/blocks/post_grades/posting_periods.php');
    $reset_url = new moodle_url('/blocks/post_grades/reset.php');

    $a = new stdClass;
    $a->period_url = $period_url->out();
    $a->reset_url = $reset_url->out();

    $settings->add(new admin_setting_heading('block_post_grades_header',
        '', $_s('header_help', $a)));

    $settings->add(new admin_setting_configtext('block_post_grades/domino_application_url',
        $_s('domino_application_url'), '', ''));

    $settings->add(new admin_setting_configtext('block_post_grades/mylsu_gradesheet_url',
        $_s('mylsu_gradesheet_url'), '', ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/https_protocol',
        $_s('https_protocol'), $_s('https_protocol_desc'), 0));

    // -----------  Begin LAW work  -------------

    $settings->add(new admin_setting_heading('block_post_grades_law_header',
        $_s('law_heading'), ''));

    $settings->add(new admin_setting_configtext('block_post_grades/law_domino_application_url',
        $_s('law_domino'), '', ''));

    $settings->add(new admin_setting_configtext('block_post_grades/law_mylsu_gradesheet_url',
        $_s('law_mylsu_gradesheet_url'), '', ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/law_quick_edit_compliance',
        $_s('law_quick_edit_compliance'), $_s('law_quick_edit_compliance_help'), 0));

    $options = $DB->get_records_menu('scale', array(), 'name ASC', 'id, name');

    if ($options) {
        $settings->add(new admin_setting_configselect('block_post_grades/scale',
            $_s('law_scale'), $_s('law_scale_help'), key($options), $options));
    }

    // ------------ Large Course Settings -------------
    $settings->add(new admin_setting_heading('block_post_grades_law_large_header',
        $_s('large_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/large_required',
        $_s('required'), $_s('required_help'), 1));

    $settings->add(new admin_setting_configtext('block_post_grades/large_mean',
        $_s('mean'), '', "2.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/large_mean_range',
        $_s('point_range'), '', "2.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/large_median',
        $_s('median'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/large_median_range',
        $_s('point_range'), '', "0.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/number_students',
        $_s('number_students'), '', "31"));

    $values = array(
        'high_pass' => array(
            'value' => "3.8", 'lower' => "5", 'upper' => "10"),
        'pass' => array(
            'value' => "3.5", 'lower' => "15", 'upper' => "25"),
        'fail' => array(
            'value' => "2.4", 'lower' => "10", 'upper' => "20")
        );

    foreach ($values as $name => $value) {
        $settings->add(new admin_setting_heading("block_post_grades_law_$name",
            $_s($name), ''));

        foreach ($value as $key => $default) {
            if ($key == 'value')
                $str = "{$name}_{$key}";
            else if ($key == 'lower')
                $str = "lower_percent";
            else $str = "upper_percent";

            $settings->add(new admin_setting_configtext("block_post_grades/{$name}_{$key}",
                $_s($str), '', $default));
        }
    }

    // ------------ Mid-sized Settings ------------

    $settings->add(new admin_setting_heading('block_post_grades_law_mid_header',
        $_s('mid_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/mid_required',
        $_s('required'), $_s('required_help'), 1));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_mean',
        $_s('mean'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_mean_range',
        $_s('point_range'), '', "0.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_median',
        $_s('median'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_median_range',
        $_s('point_range'), '', "0.2"));

    // ------------ Small-sized Settings -------------

    $settings->add(new admin_setting_heading('block_post_grades_law_small_header',
        $_s('small_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/small_required',
        $_s('required'), $_s('required_help'), 0));

    $settings->add(new admin_setting_configtext('block_post_grades/small_median',
        $_s('median'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/small_median_range',
        $_s('point_range'), '', "0.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/number_students_less',
        $_s('number_students_less'), '', 15));

    // ------------ Seminar Settings ------------

    $settings->add(new admin_setting_heading('block_post_grades_law_sem_header',
        $_s('sem_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/sem_required',
        $_s('required'), $_s('required_help'), 0));

    $settings->add(new admin_setting_configtext('block_post_grades/sem_median',
        $_s('median'), '', "3.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/sem_median_range',
        $_s('point_range'), '', "0.2"));

    // ------------- Course Settings ------------

    $filters = ues::where()->department->equal('LAW');

    $courses = ues_course::get_all($filters);

    if (!empty($courses)) {
        $settings->add(new admin_setting_heading(
            'block_post_grades_law_extra_settings',
            $_s('law_extra'), '')
        );

        $to_name = function($course) { return "$course"; };

        $exceptions = new admin_setting_configmultiselect(
            'block_post_grades/exceptions',
            $_s('law_exceptions'), $_s('law_exceptions_help'),
            array(), array_map($to_name, $courses)
        );

        post_grade_settings_callbacks::$exceptions = $exceptions;
        $exceptions->set_updatedcallback('post_grade_exceptions_callback');

        $settings->add($exceptions);

        $filters->cou_number->less_equal(5300);

        $courses = ues_course::get_all($filters);

        $legal_writing =  new admin_setting_configmultiselect(
            'block_post_grades/legal_writing',
            $_s('law_legal_writing'), $_s('law_legal_writing_help'),
            array(), array_map($to_name, $courses)
        );

        post_grade_settings_callbacks::$legal_writing = $legal_writing;
        $legal_writing->set_updatedcallback('post_grade_legal_writing_callback');

        $settings->add($legal_writing);
    }
}
