<?php

defined('MOODLE_INTERNAL') or die();

if ($ADMIN->fulltree) {
    require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
    ues::require_daos();

    $defaults = array('sec_number', 'credit_hours');
    $user_meta = array_merge($defaults, ues_user::get_meta_names());

    $options = array_combine($user_meta, $user_meta);

    $_s = ues::gen_str('block_ues_people');

    $settings->add(new admin_setting_configmultiselect('block_ues_people/outputs',
        $_s('outputs'), $_s('outputs_desc'), $defaults, $options));
}
