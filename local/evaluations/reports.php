<?php
/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				                 **
 * @author      (Modified By) David Lowe                                 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */
/**
 * Displays all the reports created from the completed evaluations.
 */

 
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('classes/ComparisonReports.php');

$searchstring = optional_param('search', null, PARAM_RAW);
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page
$dept = optional_param('dept', false, PARAM_TEXT);
$context = context_system::instance();

$perpage = 50;
// ----- create reports obj ----- //
$reports = new ComparisonReports($dept, $page, $perpage);

// ----- Security ----- //
require_login();

// ----- Breadcrumbs ----- //
//$string['nav_ev_mn'] = 'Evaluation Manager';
// $string['dept_selection'] = 'Department Selection';
// $PAGE->navbar->add('Course Evaluations', new moodle_url('index.php'));
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('reports.php'));

//If the department was specified then create a breadcrumb to the department selection.
if ($dept) {
    $PAGE->navbar->add(get_string('nav_reports', 'local_evaluations'), new moodle_url(''));
}

// ----- Stuff ----- //
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/reports.php');
$PAGE->set_title(get_string('nav_reports', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_reports', 'local_evaluations'));
$PAGE->requires->css(new moodle_url('/local/evaluations/styles/styles.css'));
// $PAGE->requires->js('/local/evaluations/js/coursecompare.js');

$PAGE->requires->js(new moodle_url('js/jquery-2.0.0.min.js'), true);
$PAGE->requires->js(new moodle_url('js/coursecompare.js'), true);

$PAGE->requires->js(new moodle_url('js/uleth.js'));
$PAGE->requires->js(new moodle_url('js/magnific.js'));
$PAGE->requires->js(new moodle_url('js/pnotify.js'));



$PAGE->set_pagelayout('standard');

// ----- Output ----- //
echo $OUTPUT->header();

if (isset($dept) && $dept != "") {
    $dept_list = get_departments();
    $this_course = " - ".$dept_list[$dept];
} else {
    $this_course = "";
}
echo printHeaderBar("Course Evaluation Reports".$this_course, hasAdminAccess());

// Print Info on page we are on

//If the department is not specified then create a list that l
if (!$dept) {
    $department_list = get_departments();
    $your_administrations = $DB->get_records('department_administrators', array('userid' => $USER->id));

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
        echo '<li class="list-group-item"><a href="reports.php?dept=' . $code . '">' . $dept . '</a></li>';
    }
    echo '</ul>';

    echo $OUTPUT->footer();
    die();
}

//If the user is NOT department admin then banish them forever.....or just error out
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

//Display all reports by course.
/* echo '<div class="row-fluid">
            <ul class="nav nav-tabs">
                <li><a href="#course_eval_reports_groups" data-toggle="tab">Group Evaluations</a></li>
                <li><a href="#course_eval_reports_individual" data-toggle="tab">Individual Evaluations</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="course_eval_reports_groups">';
                    echo $reports->getAllIndividualReportsHTML();
                echo '</div>
                <div class="tab-pane" id="course_eval_reports_individual">';
                    echo $reports->getAllIndividualReportsHTML();
                echo '</div>
            </div>
        </div>';
*/

$html_output = '<div class="row-fluid">
    <div class="accordion" role="tablist" aria-multiselectable="true" id="course_eval_accordion">
        <div class="card">
            <div class="card-header" role="tab" id="heading_group_reports">   
                <a data-toggle="collapse" role="tab" data-parent="#course_eval_accordion" href="#collapseOne">
                    Group Evaluations                           
                </a>
            </div>                   
            <div id="collapseOne" role="tabpanel" class="accordion-body show collapse in"  aria-labelledby="heading_group_reports">  
                <div class="card-block">';
                // collapsed is now either of the two below
                // $collapseIn = "show collapse in";
                // $collapseIn = "collapse";

                    $print_this_gr = $reports->getAllGroupReportsHTML();
                    if (strlen($print_this_gr) == 0) {
                        $html_output .= '<h3>No Group Reports have been calculated</h3>';
                    } else {
                        $html_output .= $print_this_gr;
                    }
                    $html_output .= '
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header" role="tab" id="heading_indi_reports">
                <a data-toggle="collapse" role="tab" data-parent="#course_eval_accordion" href="#collapseTwo">
                    Individual Evaluations
                </a>
            </div>
            <div id="collapseTwo" role="tabpanel" class="accordion-body collapse" aria-labelledby="heading_indi_reports">
                <div class="card-block">';
                    $html_output .= $reports->getAllIndividualReportsHTML();
                    
                    // error_log("\n\n");
                    // error_log("\nWhat is the length of indi reports: ". strlen($ind_reports));
                    // error_log("\nWhat is indi reports: ". $ind_reports);
                    // error_log("\n\n");
                    
                    // if (strlen($ind_reports) == 0) {
                    //     $html_output .= '<h3>There are no reports to show</h3>';
                    // } else {
                    //     $html_output .= $ind_reports;
                    // }
                    $html_output .= '
                </div>
            </div>
        </div>
    </div>
</div>
<br>';

//Insert a search form.
$searchURL = $CFG->wwwroot . '/local/evaluations/reports.php';
$html_output .= "
     <center> <form action='$searchURL' method='post'>
      Search: <input type='text' name ='search'/>
      <input type='submit' />
      </form></center>
   ";

echo $html_output;
echo $OUTPUT->footer();
