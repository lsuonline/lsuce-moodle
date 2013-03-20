<?php
 
class block_mhaairs_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
 
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
 
        // A sample string variable with a default value.
	$mform->addElement('select', 'config_block_mhaairs_link_type', get_string('linktype', 'block_mhaairs'), 
				array('mhcampus' => "MH Campus" , 'tegrity' => "Tegrity"));

        $mform->setDefault('config_block_mhaairs_link_type', 'tegrity');
        //$mform->setType('config_block_mhaairs_link_type', PARAM_MULTILANG);        
    }
}
 
?>
