<?php

require_once("../../../config.php");

global $CFG, $DB, $COURSE;
require_login();
$cid = required_param('cid', PARAM_INT);
$context = context_course::instance($cid);
$PAGE->set_context($context);
if (has_capability('block/rollsheet:viewblock', $context)) {
	require_once('../genpics/renderrollsheet.php');
	$PAGE->set_pagelayout('print');
	$PAGE->set_url('/blocks/rollsheet/print/pics.php');
	$logoEnabled = get_config('block_rollsheet', 'customlogoenabled');
	echo $OUTPUT->header();
	$usersPerPage = get_config('block_rollsheet', 'usersPerPage' );

	if($logoEnabled){
		printHeaderLogo();
	}
	$renderType = optional_param('rendertype', '', PARAM_TEXT);
	if(isset($renderType)){
		if($renderType == 'all' || $renderType == ''){
	                echo renderPicSheet($usersPerPage);
		} else if($renderType == 'group') {
			echo renderPicSheet($usersPerPage);
		}
	} else {
		renderPicSheet($usersPerPage);
	}

	echo $OUTPUT->footer();
	echo '<script>window.print();</script>'; 
} else { header("location: " . $CFG->wwwroot . "/course/view.php?id=" . $cid);
}
?>
