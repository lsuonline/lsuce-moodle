<?php
/*
    Grade Submit Step 3 - Final Send
    Info and button to submit the grades to Banner
    This file is meant to be included from index.php.
*/

require_once '../../../config.php';

$courseid = required_param('id', PARAM_INT); // course id

echo "<h2>".get_string('final_send_title', 'gradeexport_submit')."</h2>";
echo get_string('final_send_information', 'gradeexport_submit');

echo "<br>";
echo '<a href="'.$CFG->wwwroot.'/grade/export/submit/index.php?id='.$courseid.'&step=2" class="btn btn-primary"><i class="fa fa-backward"></i>&nbsp;&nbsp;'.get_string('back_one_step', 'gradeexport_submit')."</a>";
//print_r($report->grades);

$proceedurl = $CFG->wwwroot.'/grade/export/submit/index.php?id='.$courseid;
$proceedparam = '&step=4';
$strproceed = get_string('final_send_proceed', 'gradeexport_submit');
// $proceedlink = html_writer::nonempty_tag('div', html_writer::link($proceedurl.$proceedparam, $strproceed), array('class'=>'mdl-align'));
$proceedlink = '<div class="mdl-align"><a href="'.$proceedurl.$proceedparam.'" class="btn btn-success">'.$strproceed.'&nbsp;&nbsp;<i class="fa fa-cloud-upload"></i></a></div>';

echo $proceedlink;
echo $OUTPUT->footer();
