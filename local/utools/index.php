<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        utools
 * @author      David Lowe
 * @mentions    Masoum
 * ************************************************************************
 * ********************************************************************* */

require_once(dirname(dirname(dirname(__FILE__))). '/config.php');
include_once('lib/UtoolsLib.php');
include_once('lib/UtoolsAjax.php');
// include_once('lib/StudentListAjax.php');

global $DB, $USER;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/utools/index.php');

//Create the breadcrumbs
$PAGE->navbar->add('UofL Tools', new moodle_url('index.php'));
// error_log("\n\nCP - 4");

//Set page headers.
$PAGE->set_url($CFG->wwwroot . '/local/utools/index.php');
$PAGE->set_title(get_string('nav_ult_mn', 'local_utools'));
$PAGE->set_heading(get_string('nav_ult_mn', 'local_utools'));


$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/main.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/vue-easy-pie-chart.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/animate.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/fullcalendar.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/scheduler.css'));


// This works but the documentation sucks
// https://onewaytech.github.io/vue2-datatable/doc/#/en/getting-started
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/vue-datatable-component.css'));

// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/vuetable-ratiw.css'));

// This works for datatables.net
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/jquery.dataTables.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/utools/styles/dataTables.bootstrap4.css'));


$utoolslib = new UtoolsLib();
$utoolsajax = new UtoolsAjax();

// Are we an admin user here or proctor?......or random stranger.
// $in_sub = in_array(getremoteaddr(), explode(',', $CFG->local_utools_subnet_list)) ? true : false;
$is_admin = false;
$user_level = $utoolslib->checkUtoolsUserLevel(array('userid' => $USER->id));
// error_log("\n\nWhat is this level: ". print_r($user_level, 1));
error_log("\nUtools/index/ what is user level: ". $user_level);

if ($user_level == "hack") {
    // echo("<br>Sorry, you don't have access to this page.");
} else {
    if ($user_level == "Administrator") {
        $is_admin = true;
    }
    // echo $template->mainContentStart();

    // echo $template->printSideBar();

    // js/on_load.js (near the bottom), has the array of widgets to load
    // if (isset($CFG->local_utools_use_js_ajax) && $CFG->local_utools_use_js_ajax == "1") {
    //     echo $template->mainContent();
    // } else {
    //     echo $template->mainContent($load_this_widget);
    // }

    // echo $template->mainContentEnd();

}
    

// =============================================
// ============     Is Admin?     ==============
// $extras = array($is_admin);
$extras = array($is_admin);
$PAGE->requires->js_call_amd('local_utools/main', 'init', $extras);
// =============================================



// =============================================
// ==========   Initial State     ==============
// Let's have initial state set here, pass var's to init call.
// $studAjax = new StudentListAjax(); 
// $initial_state = array();

// error_log("\nWhat is the student data: ");
// error_log("\n". $studAjax->loadUsers(array("local_call" => true)). "\n");
$utools_settings = $utoolsajax->getUtoolsSettings();

$initial_state = array(
    // "user_data" => $studAjax->loadUsers(array("local_call" => true))
    "settings" => $utools_settings
);

// error_log("\n\ntcs/index.php -> What is the initial state to load: ". print_r($initial_state, 1));
// =============================================


// $PAGE->requires->js('/dist/build.js');

// $PAGE->requires->js('/local/utools/js/grid.widget.js');
// make sure user is logged in.
require_login();


echo $OUTPUT->header();
// $initial_state = str_replace("\u0022","\\\\\"",json_encode( $initial_state,JSON_HEX_QUOT)); 
// JSON_HEX_APOS - single quote
// JSON_HEX_QUOT - double quote
$initial_state = json_encode($initial_state, JSON_HEX_APOS|JSON_HEX_QUOT);
// need to get all the students in the TCMS at this moment
error_log("\nUtools/index.php -> What is initial_state after json encoding: ". $initial_state);

echo "<script>window.__SERVER__=true</script>".
    "<script>window.__INITIAL_STATE__='".$initial_state."'</script>";

$templatecontext = [
    'output' => $OUTPUT,
];

$templatecontext['flatnavigation'] = $PAGE->flatnav;
$templatecontext['is_admin'] = $is_admin;

error_log("\nUtools/index/ going to render main template now.........");

echo $OUTPUT->render_from_template('local_utools/main', $templatecontext);

echo $OUTPUT->footer();
