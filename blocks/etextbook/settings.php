<?php
$settings->add(new admin_setting_heading(
    'headerconfig',
    get_string('headerconfig', 'block_etextbook'),
    get_string('descconfig', 'block_etextbook')
));

$settings->add(new admin_setting_configtext(
    'etextbook/Library_link',
    get_string('labellibrarylink', 'block_etextbook'),
    get_string('desclibrarylink', 'block_etextbook'),
    get_string('desclibrarylink', 'block_etextbook')
));

$settings->add(new admin_setting_configtext(
    'etextbook/Library_admin',
    get_string('email_report_to', 'block_etextbook'),
    get_string('email_report_to', 'block_etextbook'),
    get_string('email_report_to', 'block_etextbook')
));