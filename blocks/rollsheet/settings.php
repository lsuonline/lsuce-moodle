<?php
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/adminlib.php');

$uploaderLink = new moodle_url('/blocks/rollsheet/index.php');
 $settings->add(new admin_setting_configcheckbox('block_rollsheet/customlogoenabled',
     new lang_string('addcustomlogo', 'block_rollsheet'),
        new lang_string('addcustomlogodesc', 'block_rollsheet') . '<br><a href="'. $uploaderLink.'">Click here to Upload</a>', null,
         PARAM_INT));

     $settings->add(new admin_setting_configcheckbox('block_rollsheet/hidefromstudents',
     new lang_string('hidefromstudents', 'block_rollsheet'),
        new lang_string('hidefromstudents_desc', 'block_rollsheet') , null,
         PARAM_INT));
    
/*
	global $DB;
	$result = $DB->get_records('user_info_field');
  if($result){
   $settings->add(new admin_setting_configcheckbox('block_rollsheet/includecustomfield',
     new lang_string('customfield', 'block_rollsheet'),
        new lang_string('customfielddesc', 'block_rollsheet') , null,
         PARAM_INT));
    	$options = array();
    	foreach ($result as $item){
    		$options[$item->id] = $item->name;
    	}
    $settings->add(new admin_setting_configselect('block_rollsheet/customfieldselect', 
    				 new lang_string('customfieldselect', 'block_rollsheet'),
                       get_string('selectcustomfield', 'block_rollsheet'), null, $options));
  }
*/

    $settings->add(new admin_setting_configcheckbox('block_rollsheet/includecustomtextfield',
	new lang_string('includecustomtextfield', 'block_rollsheet'),
        new lang_string('includecustomtextfielddesc', 'block_rollsheet') , null, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_rollsheet/customtext', get_string('customtext', 'block_rollsheet'), get_string('customtextdesc', 'block_rollsheet'), null, PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('block_rollsheet/includeidfield',
    new lang_string('idfield', 'block_rollsheet'),
    new lang_string('idfielddesc', 'block_rollsheet') , null, PARAM_INT));


    $settings->add(new admin_setting_configtext('block_rollsheet/studentsPerPage', get_string('studentsPerPage', 'block_rollsheet'),null, null, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_rollsheet/numExtraFields', get_string('numExtraFields', 'block_rollsheet'),null, null, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_rollsheet/usersPerPage', get_string('usersPerPage', 'block_rollsheet'),null, null, PARAM_INT));
