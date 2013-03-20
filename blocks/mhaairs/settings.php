<?php

$settings->add(new admin_setting_configcheckbox(
            'block_mhaairs_sslonly',
            get_string('mhaairs_sslonlylabel', 'block_mhaairs'),
            '',
            '0'
        ));
		
$settings->add(new admin_setting_configtext(
            'block_mhaairs_customer_number',
            get_string('mhaairs_customernumberlabel', 'block_mhaairs'),
            '',
            ''
        ));
		
$settings->add(new admin_setting_configtext(
            'block_mhaairs_shared_secret',
            get_string('mhaairs_secretlabel', 'block_mhaairs'),
            '',
            ''
        ));
		
$settings->add(new admin_setting_configtext(
            'block_mhaairs_base_address',
            get_string('mhaairs_baseaddresslabel', 'block_mhaairs'),
            '',
            'https://aairs-connectors.tegrity.com/sso/aairs/'
        ));

$settings->add(new admin_setting_configselect(
		   'block_mhaairs_default_link_type',				//setting id
		   get_string('mhaairs_defaultlinktypelabel', 
		   'block_mhaairs'),						//label
		   get_string('mhaairs_defaultlinktypelabel', 
		   'block_mhaairs'),
		   'tegrity',							//default value	 
		   array('mhcampus' => "MH Campus" , 'tegrity' => "Tegrity")    //select choices
		   ));

?>
