<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add main CLI runner page to Development.
    $ADMIN->add(
        'development',
        new admin_externalpage(
            'local_lsucli',
            get_string('pluginname', 'local_lsucli'),
            new moodle_url('/local/lsucli/index.php'),
            'moodle/site:config'
        )
    );

    // Add config page for help text editing.
    $ADMIN->add(
        'development',
        new admin_externalpage(
            'local_lsucli_config',
            get_string('confighelptext', 'local_lsucli'),
            new moodle_url('/local/lsucli/config.php'),
            'moodle/site:config'
        )
    );
}
