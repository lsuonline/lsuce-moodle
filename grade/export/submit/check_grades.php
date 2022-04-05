<?php
/*
    Grade Submit Step 2 - Check grades
    This file is meant to be included from index.php.
*/

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/querylib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/grader/lib.php';

// require_once $CFG->dirroot.'/grade/export/submit/create_column.php';

require_once('grade_report_banner.php');


$courseid = required_param('id', PARAM_INT); // course id

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid, 'page'=>0));
$context = context_course::instance($courseid);

$report = new grade_report_banner($courseid, $gpr, $context, 0, 0);

if (!$report->hasFinalGrades()) {
    $report->set_pref('studentsperpage', 999);    //set high to ensure all grades are included.
    // final grades MUST be loaded after the processing
    $report->load_users();
    
    $users = $report->users;

    //The column doesn't exist yet. We have to create it.
    $colName = $report->getFinalGradeColumnName();
    
    //Now hide and lock the Course Total grade item.
    //CategoryID=null, ItemType=course
    $DB->set_field("grade_items", "locked", time(), array("courseid"=>$courseid,'categoryid'=>null,'itemtype'=>'course'));
    $DB->set_field("grade_items", "hidden", 1, array("courseid"=>$courseid,'categoryid'=>null,'itemtype'=>'course'));

    $grades = array();

    foreach ($users as $user) {
        $course_grade = grade_get_course_grade($user->id, $courseid);
        
        if ($course_grade->grade == null) {
            // stupid piece of crap sometimes the first entry get's a null for a grade
            // trying again........
            $course_grade = grade_get_course_grade($user->id, $courseid);
        }

        $grades[$user->id] = new stdClass;
        $grades[$user->id]->userid = $user->id;
        $grades[$user->id]->rawgrade = $course_grade->grade;
        $grades[$user->id]->dategraded = $course_grade->dategraded;
    }

    //now create the new column
    $use_mod_or_manual = "mod";
    $use_item_mmodule = "assign";
    // $use_item_mmodule = "";

    $grade_item = new grade_item(array(
        // $grade_item = new column_insert(array(
        'courseid' => $courseid,
        'itemname' => $colName,
        'itemtype' => $use_mod_or_manual,
        'itemmodule' => $use_item_mmodule,
        'itemnumber' => 0,
        'gradetype' => 1,
        // 'gradetype' => GRADE_DISPLAY_TYPE_LETTER, // GRADE_DISPLAY_TYPE_LETTER (used to be 1)
        'grademax' => 100,
        'grademin' => 0,
        'gradepass' => 0,
        'multfactor' => 1,
        'plusfactor' => 0,
        'aggregationcoef' => 0,
        'display' => 3,
        'needsupdate' => 0,
        'iteminfo' => null,
        'idnumber' => null,
        'outcomeid' => null,
        'decimals' => null)
    );
        
    $grade_item->insert();
    $grade_item->needsupdate = 0;
    $grade_item->update();
    
    //Now hide and lock the Course Total grade item.
    //CategoryID=null, ItemType=course
    $DB->set_field("grade_items", "locked", time(), array("courseid"=>$courseid,'categoryid'=>null,'itemtype'=>'course'));
    $DB->set_field("grade_items", "hidden", 1, array("courseid"=>$courseid,'categoryid'=>null,'itemtype'=>'course'));

    // error_log("\nUpdating the grades and column: ". $colName);
    $params = array('itemtype' => 'mod','courseid' => $courseid, 'itemname' => $colName);
    // 201603 4th param breaks if there are no components listed
    $result = grade_update('system', $courseid, $use_mod_or_manual, $use_item_mmodule, null, 0, $grades, $params);
    error_log("\nFinished creating Final Grades column");


}

$report = new grade_report_banner($courseid, $gpr, $context, 0, 0);

// final grades MUST be loaded after the processing
$report->load_users();
$numusers = $report->get_numusers();
$report->load_final_grades();


echo $report->group_selector;
echo '<div class="clearer"></div>';
// echo $report->get_toggles_html();

//show warnings if any
// foreach($warnings as $warning) {
//     echo $OUTPUT->notification($warning);
// }

$studentsperpage = $report->get_pref('studentsperpage');
// Don't use paging if studentsperpage is empty or 0 at course AND site levels
if (!empty($studentsperpage)) {
    echo $OUTPUT->paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
}

//Hack to force it to NOT use fixed students as it messes up our display of things
if (!isset($CFG->grade_report_fixedstudents)) {
    $CFG->grade_report_fixedstudents = false;
}

$fixedStudents = $CFG->grade_report_fixedstudents;
$CFG->grade_report_fixedstudents = false;
$reporthtml = $report->get_grade_table();
$CFG->grade_report_fixedstudents = $fixedStudents;

//hack to get the popup grade hover to look right :-(
echo '<style type="text/css">
.graderreportoverlay {
    background-color: #EEE;
    border: 1px solid black;
    padding: 10px;
}
</style>';

echo "<h2>".get_string('check_grades_title', 'gradeexport_submit')."</h2>";
echo get_string('check_grades_description', 'gradeexport_submit');
echo $reporthtml;

// let's get the id of the final grades column.....
$show_me_the_id = $DB->get_record('grade_items', array('itemname' => '[Final Grades]', 'courseid' => $courseid));
if (isset($show_me_the_id->id)) {
    // error_log("What is show_me_the_id obj: ". print_r($show_me_the_id, 1));
    $final_grades_id = $show_me_the_id->id;
} else {
    error_log("There is NO show_me_the_id obj");
}

$missingGrades = $report->hasMissingGrades();
if ($missingGrades) {
    echo get_string('missing_grades_note', 'gradeexport_submit');
}


$html_output = '<div class="row-fluid">
    <div class="col-12">
    <div class="mdl-align">
        <a href="javascript:void(0);" 
            class="btn btn-danger" 
            data-final_grades_courseid="' . $courseid . '" 
            data-final_grades_id="' . $final_grades_id . '" 
            id="delete_final_grades_export">
            <i class="fa fa-trash"></i>&nbsp;&nbsp;' .
            get_string('reset_final_grades', 'gradeexport_submit').'
        </a>
    </div>
    </div>
</div>';

if (!$missingGrades) {
    $proceedurl = $CFG->wwwroot.'/grade/export/submit/index.php?id='.$courseid;
    $proceedparam = '&step=3';
    $strproceed = get_string('check_grades_proceed', 'gradeexport_submit');
    $proceedlink = '<div class="float-right"><a href="'.$proceedurl.$proceedparam.'" class="btn btn-primary">'.$strproceed.'&nbsp;&nbsp;<i class="fa fa-forward"></i></a></div>';
    // echo $proceedlink;
}


$html_output .= '<br><hr>
<div class="row-fluid">
    <div class="col-12">
        <a href="'.$CFG->wwwroot.'/grade/export/submit/index.php?id='.$courseid.'" class="btn btn-warning">
            <i class="fa fa-backward"></i>&nbsp;&nbsp;'.get_string('enter_grades', 'gradeexport_submit').'
        </a>'.
        $proceedlink .
    '</div>
</div>';

echo $html_output;
// prints paging bar at bottom for large pages
if (!empty($studentsperpage) && $studentsperpage >= 20) {
    echo $OUTPUT->paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
}


echo $OUTPUT->footer();
