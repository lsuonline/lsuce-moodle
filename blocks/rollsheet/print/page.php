<?php

require_once("../../../config.php");

global $CFG, $DB, $COURSE;
require_login();
$cid = required_param('cid', PARAM_INT);
$context = context_course::instance($cid);
$PAGE->set_context($context);
if (has_capability('block/rollsheet:viewblock', $context)) {
	require_once('../genlist/renderrollsheet.php');
	$PAGE->set_pagelayout('print');
	$PAGE->set_url('/blocks/rollsheet/print/page.php');
	$logoEnabled = get_config('block_rollsheet', 'customlogoenabled');
	echo $OUTPUT->header();
	$usersPerTable = get_config('block_rollsheet', 'studentsPerPage' );

	if($logoEnabled){
		printHeaderLogo();
	}
	$renderType = optional_param('rendertype', '', PARAM_TEXT);
	if(isset($renderType)){
		if($renderType == 'all' || $renderType == ''){
	                echo renderRollsheet($usersPerTable);
		} else if($renderType == 'group') {
			echo renderRollsheet($usersPerTable);
		}
	} else {
		renderRollsheet($usersPerTable);
	}

	echo $OUTPUT->footer();
	echo '<script>window.print();</script>'; 
} else { header("location: " . $CFG->wwwroot . "/course/view.php?id=" . $cid);
}
?>
