<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Dustin Durrand http://oohoo.biz
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This page allows department administrators to decide what reports will be used
 * for comparisons when generating the reports.
 * 
 * This will typically occur when they change the standard questions and no longer want to 
 * use the stats from the old questions.
 */
global $USER;
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('classes/ComparisonReports.php');

// ----- Security ----- //
require_login();

// ----- Parameters ----- //
$searchstring = optional_param('search', null, PARAM_RAW);
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page
$clear = optional_param('clear', null, PARAM_RAW);
$dept = optional_param('dept', null, PARAM_TEXT);
$process_form = optional_param('process_selected', null, PARAM_RAW);

// ----- Reports Object ----- //
$reports = new ComparisonReports($dept, $page, $perpage);

if ($_SERVER['REQUEST_METHOD']=="GET") {
    // $function = $_GET['call'];
    $function = isset($_GET['call']) ? $_GET['call'] : 'empty';
    // error_log("What is the function in Get[call]: ".$function);

    if ($function != 'empty') {
        $reports->processGetRequest($function, $_GET);
    }
}

// ----- Security for Dept. Admin ----- //
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}
// ----- Breadcrumbs ----- //
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('admin.php'));
$PAGE->navbar->add(get_string('nav_course_setings', 'local_evaluations'), new moodle_url('admin.php?dept='.$dept));
$PAGE->navbar->add(get_string('nav_cs_mx', 'local_evaluations'), new moodle_url('coursecompare.php?dept='.$dept));


// ----- Page Setup ----- //
$context = context_system::instance();
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/coursecompare.php?dept=' . $dept);
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_cs_mx', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_cs_mx', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');

$PAGE->requires->js(new moodle_url('js/jquery-2.0.0.min.js'), true);
$PAGE->requires->js(new moodle_url('js/coursecompare.js'), true);

$PAGE->requires->js(new moodle_url('js/uleth.js'));
$PAGE->requires->js(new moodle_url('js/magnific.js'));
$PAGE->requires->js(new moodle_url('js/pnotify.js'));


$PAGE->set_pagelayout('standard');
$clearurl = $PAGE->url . '&clear=true';
$processurl = $PAGE->url . '&process_selected=true';
$stripped_url = substr($PAGE->url, 0, strrpos($PAGE->url, '?'));

echo $OUTPUT->header();


// ----- Handle Parameters ----- //
// clear all reports
// submit selected reports
if (isset($clear)) {
    //Remove all records from course compare if clear is selected
    $reports->purgeAllCompareRecords();
} else if (isset($process_form)) {
    // Submit the selected courses
    $reports->submitComparisonForm();
}

if (isset($dept) && $dept != "") {
    $dept_list = get_departments(); // locallib
    $this_course = " - ".$dept_list[$dept];
} else {
    $this_course = "";
}

// Print Info on page we are on
printHeaderBar("Course Evaluations ".$this_course, hasAdminAccess());

// ----- Search Requests ----- //
if (isset($searchstring)) {
    $searchterms = explode(" ", $searchstring);
    $courses = get_courses_search($searchterms, "fullname ASC", $page, $perpage, $totalcount);
} else {
    // $totalcount = $DB->count_records_select('course', "fullname LIKE '%".$dept."%'");
    // $totalcount = $DB->count_records_select('course', "category='".$dept."'");
    $courses = $DB->get_records('course', array('category' => $dept));
    $totalcount = count($courses);
    
    $url = new moodle_url($CFG->wwwroot . '/local/evaluations/coursecompare.php', array('perpage' => $perpage,'dept' => $dept));

    // $search_this = array('fullname' => $dept."-");
    // $courses = $reports->get_course_eval_search($search_this, $totalcount, "fullname ASC", $page, $perpage);
    
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
    if (!isset($_SESSION['list_of_selected'])) {
        $_SESSION['list_of_selected'] = array();
    }
    
}

// ----- Select All or All on Page ----- //
echo '<div class="row-fluid">';
    echo '<div class="col-md-6">';
        echo '<form action="#" id="eval_view_current_compares_form">
                <input type="hidden" name="dept" value="'.$dept.'"/>
                <button type="submit" class="btn-primary" id="eval_view_current_compares" value="View Group Compares" classs="pull-left"/>
                    <i class="fa fa-eye-open"></i> View Group Compares
                </button>
            </form>
            ';
    echo '</div>';

    echo '<div class="col-md-6">';

        echo '<form action="#" id="cc_allSelect" class="pull-right">
                <button type="submit" class="btn-primary"/>Select all '.$totalcount.' items
                </button>
                <input type="hidden" name="dept" value="'.$dept.'"/>
            </form>';

        echo '<form action="#" id="cc_pageOnlySelect" class="pull-right">
                <button type="submit" class="btn-primary"/>
                    Select all on page
                </button>
                <input type="hidden" name="page" value="'.$page.'"/>
                <input type="hidden" name="perpage" value="'.$perpage.'"/>
            </form>';
            
    echo '</div>';

echo '</div>';

echo '<form action="' . $PAGE->url . '" method="post" id="cc_pageOnlySelect_form">';
echo '<table width="95%" cellpadding="1" id="course_compare_reporting">';


// ----- Now Build the List of Courses ----- //

$list_count = 0;

foreach ($courses as $course) {
    
    $course_context = context_course::instance($course->id);
    $is_envigilator = $DB->get_record_select('department_administrators', "userid=:uid AND department=:dept", array('uid'=>$USER->id, 'dept'=>$dept), 'userid,department');
    $am_i_enrolled = is_enrolled($course_context, $USER->id, 'moodle/course:update', true);
    if (!$is_envigilator || $am_i_enrolled) {
        continue;
    }
    
    // get_records($table, array $conditions=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0)
    $evals = $DB->get_records('evaluations', array('course' => $course->id, 'deleted' => 0));
//        $evals = $DB->get_records('evaluations',
//                array('course' => $course->id, 'deleted' => 0), 'name');
    
    echo  '<tr>';
    if (isset($courseid)) {
        echo  '<td colspan=8><b>$course->fullname </b><br> $singleCourseUrl</td>';
    } else {
        echo  '<td colspan=8><b>' . $course->fullname . '</b></td>';
    }
    echo  '</tr>';

    $reports->tableHeader();

    if ($evals == null) {
        echo '<tr><td colspan=8>' . get_string('none', 'local_evaluations') . '</td></tr>';
        echo '<script>console.log("coursecompare.php => Stop the bus, the eval is NULL, NONE, ZILCH - courseid is: '.$course->id.'");</script>';
    } else {

        foreach ($evals as $eval) {
            //print_object($eval);
            $status = eval_check_status($eval);
            $reponses = 0;
            if ($status == 1) {
                $reponses = 0;
            } elseif ($status == 2) {
                $reponses = get_eval_reponses_count($eval->id);
            } elseif ($status == 3) {
                $reponses = get_eval_reponses_count($eval->id);
            }
            echo  '<tr>';
            echo  '<td>' . $eval->name . '</td>';
            echo  '<td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
            echo  '<td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
            echo  '<td>' . get_eval_status($eval) . '</td>';
            echo  '<td>' . $reponses . '</td>';
            echo  '<td>' . $reponses . '</td>';
            //gonna use these to like pass in the id's of the evals to compare

            $list_count_index = (($page * 10) + $list_count);

            if (in_array($course->id.'-'.$eval->id, $_SESSION['list_of_selected'])) {
                echo  '<td> <input type="checkbox" id="course_compare_eval" class="eval_id_'.$list_count_index.'_'.$page.'_'.$course->id.'-'.$eval->id.'" name="evalcheck" checked/></td>';
            } else {
                echo  '<td> <input type="checkbox" id="course_compare_eval" class="eval_id_'.$list_count_index.'_'.$page.'_'.$course->id.'-'.$eval->id.'" name="evalcheck" /></td>';
            }
       
            $list_count++;
            echo  '</tr>';

        }
    }
    echo  '<tr><td><br></td></tr>';
}

echo '</table>';
echo '</form>';

echo "<script>
        var php_list = ". json_encode($_SESSION['list_of_selected']).";
        console.log('coursecompare.php => What is php_list  --in compare file--: '+php_list);
    </script>";

echo "
    <div class='row-fluid'>
        <div class='span12'>
            <form action='$processurl' method='post' class='pull-right'>
                <button type='submit' class='btn btn-danger'>Compare Selected Courses</button>
            </form>
        </div>
    </div>";

            // Clear courses to compare: <button type='submit' class='btn btn-danger'><i class='icon-trash'></i>Clear</button>
// echo "
//     <form action='$clearurl' method='post'>
//             Clear courses to compare: <input type='submit' value='Clear'/>
//     </form>";

/*echo "<br><hr>
    <center> 
        <form action='".$stripped_url."' method='post'>
            Search: <input type='text' name='search'/>
            <input type='hidden' name='page' value=''/>
            <input type='hidden' name='perpage' value='10'/>
            <input type='hidden' name='dept' value=''/>
            <input type='submit' class='btn btn-primary'/>
        </form>
    </center>
    ";
*/
echo $reports->anyNotifications();
echo $reports->purgeNotifications();
    
echo $OUTPUT->footer();
