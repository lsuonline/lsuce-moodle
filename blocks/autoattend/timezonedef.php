<?php

defined('MOODLE_INTERNAL') || die();


global $USER, $CFG;


$TIME_OFFSET = 0;
if (property_exists($CFG, 'use_timeoffset')) {
	if ($CFG->use_timeoffset) {
		//
		$ver = jbxl_get_moodle_version();
		if ($ver>=2.7) {
			$TIME_OFFSET = $CFG->timezone*ONE_HOUR_TIME;
		}
		else {
			if (jbxl_is_admin($USER->id)) {
				if ($USER->timezone!=99) {
					$TIME_OFFSET = $USER->timezone*ONE_HOUR_TIME;
				}
				else if ($CFG->timezone!=99) {
					$TIME_OFFSET = $CFG->timezone*ONE_HOUR_TIME;
				}
			}
		}
	}
}


//
$OMITTED_DAYS = array('0'=>'Sun','1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat');
