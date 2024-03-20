<?php<?php
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
 * This page allows department administrators to build new evaluations for their
 * departments.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Parameters ----- //
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page

$searchstring = optional_param('search', false, PARAM_RAW);
$courseid = optional_param('cid', false, PARAM_INT);
$dept = optional_param('dept', false, PARAM_TEXT);
$context = context_system::instance();


// ----- Breadcrumbs ----- //
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('evaluations.php'));
//If a department was selected the create a link the the department selection page
if ($dept) {
    $PAGE->navbar->add(get_string('nav_ev_course', 'local_evaluations'), new moodle_url('evaluations.php?dept='.$dept));
}
// ----- Stuff ----- //
//If dept isn't specified here it will still redirect properly to the dept selection
//page.
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . $dept);
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_ev_course', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_course', 'local_evaluations'));
$PAGE->set_pagelayout('standard');

// ----- Security ------ //
require_login();

//Give a list of departments to choose from if they have not already specified one.
if (!$dept) {
    echo $OUTPUT->header();
    echo printHeaderBar("Evaluation Manager", hasAdminAccess());

    //Get a list of all departments that the user is an administrator for.
    $department_list = get_departments();
    $your_administrations = $DB->get_records(
        'department_administrators',
        array('userid' => $USER->id)
    );

    $your_depts = array();
    if (count($department_list) > 0) {
        foreach ($your_administrations as $administration) {
            if (isset($department_list[$administration->department])) {
                $your_depts[$administration->department] = $department_list[$administration->department];
            }
        }
    }

    echo '<ul class="list-group">';
    foreach ($your_depts as $code => $dept) {
        echo '<li class="list-group-item"><a href="evaluations.php?dept=' . $code . '">' . $dept . '</a></li>';
    }
    echo '</ul>';

    echo $OUTPUT->footer();
    die();
} else {
    //If a department is specified and the user is not an admin for that department
    //then print an error
    if (!is_dept_admin($dept, $USER)) {
        print_error(get_string('restricted', 'local_evaluations'));
    }
}

// ----- Output ----- //
echo $OUTPUT->header();

echo printHeaderBar("Evaluation Manager", hasAdminAccess());

//Get the list of courses depending on which way they called this page.
if ($courseid !== false) {
    //If the specified a single course id then the coruse list will only be that course
    $sql = "SELECT * from {course} c WHERE c.id = $courseid";
    $courses = $DB->get_records_sql($sql);
} elseif ($searchstring !== false) {
    //If search terms were entered then only look for courses with those terms.
    $searchterms = explode(" ", $searchstring);
    $courses = get_courses_search($searchterms, "fullname ASC", 0, 50, $totalcount);
} else {
    //If nothing was specified then we look at all courses in the system.
    //$totalcount = $DB->count_records('course');
    $totalcount = $DB->count_records_select('course', "fullname LIKE '%".$dept."%'");
    
    $url = new moodle_url($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . $dept, array('perpage' => $perpage));
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
    
    // $search_this = array('fullname' => $dept."-");
    $search_this = array('fullname' => $dept);
    $courses = get_courses_search($search_this, "fullname ASC", $page, $perpage, $totalcount);
    // $courses = get_courses($dept);
}

//Start outputing the evaluation list table. (Holds all courses)
//Should be switched at some point so that each course gets it's own table =/
echo '<table width="95%" cellpadding="1" style="text-align: center;">';

foreach ($courses as $course) {
    //Make sure that the course is part of this department and the user is not
    //an instructor for this course.

    // $is_envigilator = $DB->get_records('department_administrators', array('userid'=>$USER->id, 'department'=>$dept));
    $course_context = context_course::instance($course->id);
    $is_envigilator = $DB->get_record_select('department_administrators', "userid=:uid AND department=:dept", array('uid'=>$USER->id, 'dept'=>$dept), 'userid,department');
    // var_dump($is_envigilator);
    // $is_envigilator = $DB->get_record('department_administrators', array('userid'=>$USER->id, 'department'=>$dept));
    /*
    error_log("What is dept: ".$dept." and what is user->id: ".$USER->id);
    $env_sql = "SELECT * FROM mdl_department_administrators WHERE userid='".$USER->id."' AND department='".$dept."'";
    error_log("What is env_sql: ".$env_sql);
    $is_envigilator = $DB->get_record_sql($env_sql);
    error_log("What is is_envigilator 2: ".$is_envigilator);
    */

    $am_i_enrolled = is_enrolled($course_context, $USER->id, 'moodle/course:update', true);
    if (!$is_envigilator) {
        // print_error("No access, simply hit back....");
        print_error(get_string('restricted', 'local_evaluations'));
    }

    $teachers_class = false;
    if ($am_i_enrolled) {
        $teachers_class = true;
    }

    echo '<tr>';
    //Generate a link to the create a new evaluation form.
    
    if ($teachers_class) {
        echo "<td colspan=8><b>$course->fullname </b><br>".get_string('denied_viewing', 'local_evaluations')."</td";
    } else {
        $url = html_writer::link(
            "$CFG->wwwroot/local/evaluations/evaluation.php" . "?cid=$course->id&dept=$dept",
            get_string('new_single_evaluation', 'local_evaluations')
        );
        echo "<td colspan=8><b>$course->fullname </b><br> $url </td";
    }

    echo '</tr>';

    //Output the table header.
    table_header();

    //Print a list of all evaluations for this course.
    $evals = $DB->get_records('evaluations', array('course' => $course->id, 'deleted' => 0));
    print_evaluations($evals);

    //Create a space between each course =/
    echo '<tr><td><br></td></tr>';
}

//Close the massive table
echo '</tr></table>';

//Create a search field.
echo "
     <center> <form action='$PAGE->url' method='post'>
      Search: <input type='text' name ='search'/>
      <input type='submit' />
      </form></center>
   ";

echo $OUTPUT->footer();

// ------ Functions ------ //

/**
 * Print out the table header row.
 */
function table_header() {
    echo '<tr>';
    echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('status_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('delete_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('force_s_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('force_c_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('response_count', 'local_evaluations') . '</th>';
    echo '</tr>';
}

/**
 * Prints the html table rows for each evaluation.
 * 
 * @param stdClass[] $evals An array of evaluations pulled from the database.
 */
function print_evaluations($evals) {
    global $CFG, $dept;
    if ($evals == null || count($evals) == 0) {
        //If there were no evaluations let the users know.
        echo '<tr><td colspan=8>' . get_string('none', 'local_evaluations') . '</td></tr>';
        return;
    }

    foreach ($evals as $eval) {

        //create empty greyed out spans for force_ delete, start and complete
        $force_delete = '<span class="unavaliable_action">' . get_string('delete',
                        'local_evaluations') . '</span>';
        $force_start = '<span class="unavaliable_action">' . get_string('start',
                        'local_evaluations') . '</span>';
        $force_complete = '<span class="unavaliable_action">' . get_string('complete',
                        'local_evaluations') . '</span>';
        $reponses = 0;
        //Create the onclick confirm messages for delete, start and complete.
        $base = 'evaluation_action.php?dept=' . $dept . '&action=';
        $deleteconfirm = 'onclick="return confirm(\'' . get_string('confirm_delete',
                        'local_evaluations') . '\');"';
        $startconfirm = 'onclick="return confirm(\'' . get_string('confirm_start',
                        'local_evaluations') . '\');"';
        $completeconfirm = 'onclick="return confirm(\'' . get_string('confirm_complete',
                        'local_evaluations') . '\');"';

        switch (eval_check_status($eval)) {
            case EVAL_STATUS_PRESTART:
                //If it hasnt started replace the greyed out label with a href button for delete and start. Attach comfirm scripts.
                $force_delete = '<a href="' . $base . 'delete&eval_id=' . $eval->id . '" ' . $deleteconfirm . '>' . get_string('delete',
                                'local_evaluations') . '</a>';
                $force_start = '<a href="' . $base . 'force_start&eval_id=' . $eval->id . '" ' . $startconfirm . '>' . get_string('start',
                                'local_evaluations') . '</a>';
                break;
            case EVAL_STATUS_INPROGRESS:
                //If it is in progress replace the greyed out label with a href button for force_complete. Attach comfirm script.
                $force_complete = '<a href="' . $base . 'force_complete&eval_id=' . $eval->id . '" ' . $completeconfirm . '>' . get_string('complete',
                                'local_evaluations') . '</a>';
                break;
            case EVAL_STATUS_COMPLETE:
                //Leave greyed out.
                break;
        }

        //output the evaluation row.
        echo '<tr>';
        
        $href = $CFG->wwwroot . '/local/evaluations/evaluation.php?eval_id=' . $eval->id . '&dept=' . $dept;
        echo "<td><a href='$href'>" . $eval->name . "</a></td>";

        echo '<td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
        echo '<td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
        echo '<td>' . get_eval_status($eval) . '</td>';
        echo '<td>' . $force_delete . '</td>';
        echo '<td>' . $force_start . '</td>';
        echo '<td>' . $force_complete . '</td>';
        echo '<td>' . get_eval_reponses_count($eval->id) . '</td>';

        echo '</tr>';
    }
}
