<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

require_once(dirname(dirname(dirname(__FILE__))). '/config.php');
global $DB, $USER;

// $page_builder = 'local_tcs/page_scheduler';
// $page_builder = 'local_tcs/page_examlogs';
$page_builder = 'local_tcs/page_stats';
// $page_builder = 'local_tcs/page_useradmin';

// $page_builder = 'local_tcs/page_examlist';

// include_once('lib/TCSLib.php');
// include_once('lib/StudentListAjax.php');
// include_once('lib/Stats.php');
// include_once('lib/TemplateSettings.php');



// error_log("\nWhat is this test1: ". dirname(__DIR__));
// error_log("\nWhat is this test2: ". __DIR__);
// error_log("\nWhat is this test3: ". __FILE__);
// error_log("\nWhat is this test4: ". dirname(dirname(__DIR__)));
// *********************************************************************
// https://github.com/felippe-regazio/php-hot-reloader
/*
require "lib/hotreloader.php";
$reloader = new HotReloader();
$reloader->setRoot(dirname(dirname(__DIR__)));
// $reloader->ignore([
//     "ignored.php"
//     ]); 
// $reloader->setWatchMode('includes');
$reloader->setWatchMode('added');
$reloader->add([
    // "filetoadd.php",
    "StudentListAjax.php",
    // "filetoadd.js",
    // "/amd/src",
    // "/lib"
    // "styles",
    // "template"
]);
$reloader->ignore([
    "lib",
    "cache",
    "config.php",
    "lang"
    // "filetoignore.php",
    // "filetoignore.js",
    // "path/folder/ignore"

  ]);
$reloader->currentConfig();
$reloader->init();
*/
// *********************************************************************

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/tcs/builder.php');

//Create the breadcrumbs
$PAGE->navbar->add(get_string('tcs_title', 'local_tcs'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('tcs_comp_builder_title', 'local_tcs'), new moodle_url('builder.php'));
//Set page headers.
$PAGE->set_title(get_string('tcs_title', 'local_tcs'));
$PAGE->set_heading(get_string('tcs_comp_builder_title', 'local_tcs'));

// Main CSS
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/main.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/builder.css'));

// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/app.f10102d366958aed281ff21bc3e76e5e.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/style.css'));

// DatePicker
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/datepicker/bootstrap-datepicker.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/datetimepicker/tempusdominus-bootstrap-4.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/datetimepicker/bootstrap-datetimepicker.css'));

// jquery.autocomplete
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/jquery.autocomplete/main.css'));


// TimeScheduler
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/timescheduler/skezzy_calendar.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/timescheduler/skezzy_timeline.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/timescheduler/skezzy_timeline_styling.css'));

// bootstrap-table
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/bootstrap-table/bootstrap-table.css'));


require_login();


echo $OUTPUT->header();
// $initial_state = str_replace("\u0022","\\\\\"",json_encode( $initial_state,JSON_HEX_QUOT)); 
// JSON_HEX_APOS - single quote
// JSON_HEX_QUOT - double quote
// $initial_state = json_encode($initial_state, JSON_HEX_APOS|JSON_HEX_QUOT);
// need to get all the students in the TCS at this moment
// error_log("\nindex.php -> What is initial_state after json encoding: ". $initial_state);

// echo "<script>window.__SERVER__=true</script>".
//     "<script>window.__INITIAL_STATE__='".$initial_state."'</script>";

$templatecontext = [
    'output' => $OUTPUT,
    "flatnavigation" => $PAGE->flatnav,
];

echo $OUTPUT->render_from_template($page_builder, $templatecontext);
echo $OUTPUT->footer();
