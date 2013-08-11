<?php

defined('MOODLE_INTERNAL') || die;


if ($ADMIN->fulltree) {
	$settings->add(
		new admin_setting_heading(
			'csv_enrol/heading',
			'Setttings',
			'Select the type of field that will be used to identify each user.'
		)
	);
	
	$options = array(0 => 'username', 1 => 'email', 2 => 'idnumber');
	
	$settings->add(new admin_setting_configselect('csv_enrol/field',
			get_string('field', 'block_csv_enrol'), get_string('fielddesc', 'block_csv_enrol'),
			'', $options));
}


