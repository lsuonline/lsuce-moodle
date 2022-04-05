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
 * @author      Dustin Durrand                                           **
 * @author      (Modified By) David Lowe                   				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */
/**
 * This page allows the global admin to assign users as department administrators.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('forms/administrator_form.php');
require_once('locallib.php');
include_once('classes/CourseEvalAjax.php');

$cea = new CourseEvalAjax();
// ----- Parameters ----- //
$dept = optional_param('dept', false, PARAM_TEXT);

// ----- Security ----- //
require_login();

// ----- Navigation ----- //
//build breadcrumbs
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('area_admins', 'local_evaluations'), new moodle_url('administration.php'));

if ($dept) {
    $PAGE->navbar->add(get_string('admin_add', 'local_evaluations'), new moodle_url(''));
}


// ----- Stuff ----- //
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/administrator.php');
$PAGE->set_heading(get_string('administration', 'local_evaluations'));
$PAGE->set_title(get_string('administration', 'local_evaluations'));

// $PAGE->requires->js(new moodle_url('js/courseeval.js')true));
// $PAGE->requires->js(new moodle_url('js/administration.js'));
$PAGE->requires->js(new moodle_url('js/jquery-2.0.0.min.js'), true);

$PAGE->requires->css(new moodle_url('css/styles.css'));
$PAGE->requires->js(new moodle_url('js/courseeval.js'), true);
$PAGE->requires->js(new moodle_url('js/administration.js'));
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

error_log("\n\nHello World");
error_log("\n\nWhat is dept: ". $dept);

echo '<nav class="navbar navbar-light bg-faded">
        <h3>UofL Course Evaluations'.$this_course.'</h3>
    </nav>';

// $PAGE->requires->js(new moodle_url('js/jquery_searchable.js'), true);

//Make sure the user is a global admin.
if (has_capability('local/evaluations:admin', $context)) {
    //If a department is specified then display the assignment form.
    if ($dept) {
        //Check if the addbutton was pressed and users were selected.
        if (array_key_exists('add', $_REQUEST) && array_key_exists('add_user', $_REQUEST)) {
            //If it was then we need to add each user as an admin if they aren't already.
            foreach ($_REQUEST['add_user'] as $userid) {
                //Check if the user is already an admin for this department.
                $records = $DB->get_records_select(
                    'department_administrators',
                    'userid = ' . $userid . ' AND department = \'' . $dept . '\''
                );
                
                //If no records were returned then the user is not an admin for
                //this department.
                if (empty($records)) {
                    $user = new stdClass();
                    $user->userid = $userid;
                    $user->department = $dept;
                    $DB->insert_record('department_administrators', $user);
                }
            }
        // Check if the remove button was pressed and users were selected.
        } elseif (array_key_exists('remove', $_REQUEST) && array_key_exists('remove_user', $_REQUEST)) {
            //For each user in the list of selected users remove them as admins
            //for this department.
            foreach ($_REQUEST['remove_user'] as $userid) {
                // Delete the record in the database.  If it doesnt exist this
                // function does nothing therefore we don't need to do a check.
                $DB->delete_records_select(
                    'department_administrators',
                    'userid = ' . $userid . ' AND department = \'' . $dept . '\''
                );
            }
        }

        //Show the added user lists.
        $mform = new admin_form($dept);
        $mform->display();
    } else {
        //If a department was not specified then create a list of departments to
        //choose from.

        // echo '<div class="row">';
        echo '<form class="form-horizontal" name="local_eval_admin_add_form" action="javascript:void(0);">';
            echo '<div class="form-group">';
                echo '<label for="local_eval_admin_add_dept" class="col-sm-2 control-label">Deptartment Name</label>';
                echo '<div class="col-sm-10">';
                    echo '<input class="form-control" type="text" name="dept" id="local_eval_admin_add_dept" placeholder="Deptartment Name"/>';
                echo '</div>';
            echo '</div>';
            
            echo '<div class="form-group">';
                echo '<label for="local_eval_admin_add_code" class="col-sm-2">Deptartment Code</label>';
                echo '<div class="col-sm-10">';
                    echo '<input class="form-control" type="text" name="code" id="local_eval_admin_add_code" placeholder="Deptartment Code"/>';
                echo '</div>';
            echo '</div>';

            
            echo '<div class="form-group">';
                echo '<div class="col-sm-offset-2 col-sm-10">';
                    echo '<button type="button" class="btn btn-primary" id="local_eval_admin_add_btn"><i class="fa fa-eye-plus"></i> Add Department</button>';
                echo '</div>';
            echo '</div>';
        echo '</form>';
        echo '<br></hr><br>';

        // echo '<div class="row-fluid">';
        //     echo '<div class="col-md-12">';
        //         echo '<button class="btn btn-danger pull-right"><i class="fa fa icon-trash"></i>Test Button</button>';
        //     echo '</div>';
        // echo '</div>';


        echo '<table class="table table-striped" id="local_eval_admin_dept_container">';
        $depts = get_departments();

        echo $cea->buildList($depts);

        echo '</table>';
    }
}

echo $OUTPUT->footer();
