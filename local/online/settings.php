<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ues_lib = $CFG->dirroot . '/enrol/ues/publiclib.php';

    if (file_exists($ues_lib)) {
        require_once $ues_lib;
        ues::require_extensions();

        require_once dirname(__FILE__) . '/provider.php';

        $provider = new online_enrollment_provider(false);

        $reprocessurl = new moodle_url('/local/online/reprocess.php');

        $a = new stdClass;
        $a->reprocessurl = $reprocessurl->out(false);

        $settings = new admin_settingpage('local_online', $provider->get_name());
        $settings->add(
            new admin_setting_heading('local_online_header', '',
            get_string('pluginname_desc', 'local_online', $a))
        );

        $provider->settings($settings);

        $ADMIN->add('localplugins', $settings);
    }
}
