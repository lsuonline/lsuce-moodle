<?php
$capabilities = array(
    'block/csv_enrol:uploadcsv' => array(
        'riskbitmask'  => RISK_PERSONAL | RISK_MANAGETRUST,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'          => CAP_ALLOW
        )
    ),
	'block/csv_enrol:addinstance' => array(
		'riskbitmask'  => RISK_PERSONAL | RISK_MANAGETRUST,
		'captype'      => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'archetypes'   => array(
			'teacher'        => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager'          => CAP_ALLOW
		)
	)	
);
