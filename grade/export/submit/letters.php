<?php
/**
 *
 *    Grade submit Step 1 - Check Scale
 *
 *  List of grade letters.
 *
 * Most code copied from existing letters code /grade/edit/letters/index.php
 *
 * Basically I only left in the code to display the letters, and removed code relating to editing them.
 *
 *    This is meant to be included from index.php
 */
require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->libdir.'/gradelib.php';


//ID is the COURSE ID. Need to convert to a CONTEXTID
$courseid = required_param('id', PARAM_INT); // course id
$context = context_course::instance($courseid);
$contextid=$context->id;

//$PAGE->set_url('/grade/edit/letter/index.php', array('id' => $contextid)); // removed-kingjj

list($context, $course, $cm) = get_context_info_array($contextid);
$contextid = null;//now we have a context object throw away the $contextid from the params

if (!has_capability('moodle/grade:manage', $context) and !has_capability('moodle/grade:manageletters', $context)) {
    print_error('nopermissiontoviewletergrade');
}

$returnurl = null;
$editparam = null;

if ($context->contextlevel == CONTEXT_COURSE) {
    $PAGE->set_pagelayout('standard');//calling this here to make blocks display

//    require_login($context->instanceid, false, $cm); // removed-kingjj

    $admin = false;
    $returnurl = $CFG->wwwroot.'/grade/edit/letter/index.php?id='.$context->id;
    $editparam = '&edit=1';

    $proceedurl = $CFG->wwwroot.'/grade/export/submit/index.php?id='.$courseid;
    $proceedparam = '&step=2';

    $gpr = new grade_plugin_return(array('type'=>'edit', 'plugin'=>'letter', 'courseid'=>$course->id));
} else {
    print_error('invalidcourselevel');
}

$strgrades = get_string('grades');
$pagename  = get_string('letters', 'grades');

$letters = grade_get_letters($context);
$num = count($letters) + 3;

//viewing the letters


$data = array();

$max = 100;
foreach ($letters as $boundary => $letter) {
    $line = array();
    $line[] = format_float($max, 2).' %';
    $line[] = format_float($boundary, 2).' %';
    $line[] = format_string($letter);
    $data[] = $line;
    $max = $boundary - 0.01;
}
print "<h2>". get_string('letters_title', 'gradeexport_submit')."</h2>";
print get_string('letters_description', 'gradeexport_submit');
//print_grade_page_head($COURSE->id, 'letter', 'view', get_string('gradeletters', 'grades'));

$stredit = get_string('editgradeletters', 'grades');
$editlink = html_writer::nonempty_tag('div', html_writer::link($returnurl.$editparam, $stredit), array('class'=>'mdl-align'));

echo $editlink;

$table = new html_table();
$table->head  = array(get_string('max', 'grades'), get_string('min', 'grades'), get_string('letter', 'grades'));
$table->size  = array('30%', '30%', '40%');
$table->align = array('left', 'left', 'left');
$table->width = '30%';
$table->data  = $data;
$table->tablealign  = 'center';
echo html_writer::table($table);

echo $editlink;
echo get_string('letters_edit_note', 'gradeexport_submit');


$strproceed = get_string('letters_proceed', 'gradeexport_submit');
// $proceedlink = html_writer::nonempty_tag('div', html_writer::link($proceedurl.$proceedparam, $strproceed), array('class'=>'mdl-align'));
$btnCSS = "btn btn-primary";
$exportStartDate = strtotime($CFG->gradeexport_submit_start_date);// "1521571951";  //
$exportEndDate =  strtotime($CFG->gradeexport_submit_end_date); // "1522435951"; //
$currentDate = strtotime(date('Y/n/j H:i'));

$wst = trim(date('M-j-Y H:i' ,$exportStartDate));
$wet = trim(date('M-j-Y H:i' ,$exportEndDate));

$wst_a = explode(" ", $wst. "");
$wet_a = explode(" ", $wet. "");

$wordyStartTime = "";
$wordyEndTime = "";

// ====================================================
// ====================================================
// Let's break the time down and get am or pm.
// Also if it's noon let's say noon as it can be confusing.
function breakdownTime($da_time) {
    $time_array = explode(":", $da_time);
    $am_pm = "";
    if ((int)$time_array[0] < 12) {

        $am_pm = "AM";
    } else {
        $am_pm = "PM";
    }
    $add_noon = "";
    if (($da_time . $am_pm) == "12:00PM") {
        $add_noon = " (noon)";
    }

    return " ". $da_time . $am_pm . $add_noon;
}
// ====================================================
// ====================================================
if ($wst_a[1] == '00:00') {
    $wordyStartTime = $wst_a[0];
} else {
    $wordyStartTime = $wst_a[0] . breakdownTime($wst_a[1]);
}

if ($wet_a[1] == '00:00') {
    $wordyEndTime = $wet_a[0];
} else {
    $wordyEndTime = $wet_a[0] . breakdownTime($wet_a[1]);
}

// error_log("\n\n=======================================\n");
// error_log("\nwordyStartTime: ". $wordyStartTime);
// error_log("\nwordyEndTime: ". $wordyEndTime);
// error_log("\n=======================================\n\n\n");

if (!($currentDate >= $exportStartDate && $currentDate <= $exportEndDate)) {
    $btnCSS .= " disabled";
    echo '</br><div class="alert alert-danger" role="alert"><center>Grade submissions open on <b>'. $wordyStartTime .'</b> and will close on <b>'. $wordyEndTime .'</b></center></div>';
} else {
    echo '</br><div class="alert alert-warning" role="alert"><center>Grade submissions close on <b>'. $wordyEndTime .'</b></center></div>';
}


$proceedlink = '<div class="mdl-align"><a href="'.$proceedurl.$proceedparam.'" role="button" class="'.$btnCSS.'">'.$strproceed.'&nbsp;&nbsp;<i class="fa fa-forward"></i></a></div>';

echo $proceedlink;

echo $OUTPUT->footer();
