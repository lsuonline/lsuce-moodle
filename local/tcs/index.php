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
include_once('lib/TcsLib.php');
include_once('lib/Stats.php');
include_once('lib/Pages.php');
// include_once('lib/TemplateSettings.php');

global $DB, $USER;

// error_log("\n\n===============>>>>>   INDEX.PHP START   <<<<<======================\n\n");

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
$PAGE->set_pagelayout("local");
$PAGE->set_url($CFG->wwwroot . '/local/tcs/index.php');

// $PAGE->force_theme("uleth_v2");

//Create the breadcrumbs

// Transitioning from uleth_v1 to uleth_v2
// once we have transitioned to the new theme this can be removed
if ($CFG->local_tcs_auto_comp_click_finish == 0 || $CFG->local_tcs_auto_comp_click_finish == '') {
    // NEW THEME
    $enter_to_finish = "0";
} else {
    $enter_to_finish = "1";
}

// This is for the heart beat. How often to we want to ping the server to update stats.......?
if ($CFG->local_tcs_dash_refresh_rate == 0 || $CFG->local_tcs_dash_refresh_rate == '') {
    // NEW THEME
    $dash_refresh_rate = "0";
} else {
    $dash_refresh_rate = $CFG->local_tcs_dash_refresh_rate;
}

// Are we using the old theme while going through transition? If set to 0 then we are
if ($CFG->local_tcs_theme_use_old == 0 || $CFG->local_tcs_theme_use_old == '') {
    // NEW THEME
    $using_old_theme = "0";
} else {
    // OLD THEME
    // $PAGE->navbar->ignore_active();
    $PAGE->navbar->add(get_string('tcs_title', 'local_tcs'), new moodle_url('index.php'));

    //Set page headers.
    $PAGE->set_title(get_string('tcs_title', 'local_tcs'));
    $using_old_theme = "1";
}


$PAGE->set_heading(get_string('tcs_title', 'local_tcs'));

// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/app.f10102d366958aed281ff21bc3e76e5e.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/style.css'));

// $this->page->requires->js_function_call('window.requirejs.config', [(object) [
//     'paths' => (object) [
//         'editable' => new moodle_url($CFG->wwwroot . '/local/tcs/js/bootstrap-editable')
//     ],
//     'shim' => (object) [
//         'editable' => (object) [
//             'exports' => 'editable'
//         ],
//     ],
// ]]);
// $PAGE->requires->jquery_plugin('ui');
// $PAGE->requires->jquery_plugin('ui-css');
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/css/jquery-ui.css'));

$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/main.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/animate.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/scheduler.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/modal_enterstudent.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/fullcalendar.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/jqueryui-editable.css'));

// bootstrap-editable
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/bootstrap-editable.css'));

// DateTimePicker
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/datetimepicker/bootstrap-datetimepicker.css'));

// jquery.autocomplete
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/jquery.autocomplete/main.css'));

// Alertify 
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/iziToast/iziToast.css'));

// bootstrap-table
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/bootstrap-table/bootstrap-table.css'));


// autocomplete
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/autocomplete/autoComplete.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/autocomplete/main.css'));

// FullCalendar Timeline
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/fullcalendar/fullcalendar_core.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/fullcalendar/fullcalendar_timeline.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/fullcalendar/fullcalendar_resource_timeline.css'));

// SkedTape Scheduler
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/skedTape/jquery.skedTape.css'));

// TimeScheduler
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/timescheduler/skezzy_calendar.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/timescheduler/skezzy_timeline.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/timescheduler/skezzy_timeline_styling.css'));

// TUI Calendar/Scheduler
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/tui/tui-calendar.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/tui/tui-date-picker.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/tui/tui-time-picker.css'));

// $PAGE->requires->js_call_amd('local_tcs/config', 'init');

// This works but the documentation sucks
// https://onewaytech.github.io/vue2-datatable/doc/#/en/getting-started
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/vue-datatable-component.css'));

// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/vuetable-ratiw.css'));

// This works for datatables.net
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/jquery.dataTables.css'));
// $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/tcs/styles/dataTables.bootstrap4.css'));

// $statslib = new TemplateSettings();
$tcslib = new TcsLib();
$statslib = new Stats();
$pageslib = new Pages();

// Are we an admin user here or proctor?......or random stranger.
$in_sub = in_array(getremoteaddr(), explode(',', $CFG->local_tcs_subnet_list)) ? true : false;
$is_admin = false;
$is_tc_admin = false;

if (($tcslib->checkAdminUser() && $in_sub ) || $tcslib->isSystemAdmin()) {
    $is_admin = true;
} else if ($USER->username && $DB->get_record('local_tcms_user_admin', array('username'=> $USER->username))) {
    //check if this user is already in the record
    $is_tc_admin = true;
}

// =============================================
// ============     Is Admin?     ==============
$extras = array($is_admin);
// $PAGE->requires->js_call_amd(new moodle_url($CFG->wwwroot . 'local_tcs/main'), 'init', $extras);
$PAGE->requires->js_call_amd('local_tcs/main', 'init', $extras);
// =============================================

// Template from here:
// Prior to Jan 20 2020: https://www.codeply.com/go/KrUO8QpyXP
// This one is ok....but not using: https://startbootstrap.com/previews/sb-admin-2/
// This one is ok....but not using: https://dashboardpack.com/live-demo-free/?livedemo=329 (ArchitectUI HTML Dashboard Free)

// =============================================
// ==========   Redirect Check    ==============
$list_of_pages = $pageslib->getPages();
// error_log("\nWhat is the list of pages BEFORE: ". print_r($list_of_pages, 1));

// $page_dashboard_redirect = $page_examlist_redirect = $page_scheduler_redirect = $page_examreqs_redirect = $page_useroverride_redirect = $page_examlogs_redirect = $page_settings_redirect = $page_stats_redirect = $page_printpass_redirect = $page_useradmin_redirect = false;
if (isset($_REQUEST['page'])) {
    // error_log("\n\nWe have a page to redirect to: ". print_r($_REQUEST, 1). "\n\n");
    $redirect_page = "page_".$_REQUEST['page'];
} else {
    $redirect_page = "page_dashboard";
}

$list_of_pages[$redirect_page] = true;
// error_log("\nWhat is the list of pages AFTER: ". print_r($list_of_pages, 1));
// error_log("\nWhat is the page: ". $redirect_page. " and is it true: ". $list_of_pages[$redirect_page]);

// =============================================
// ==========   Initial State     ==============
// Let's have initial state set here, pass var's to init call.

$template_version = 1.0;
$stats_hash = 0;

$initial_state = array(
    // "table_data" => $studAjax->getCheckedInStudentsTest(array("local_call" => true))
    // "user_data" => $studAjax->loadUsers(array("local_call" => true))
    "user_hash" => $tcslib->getSetting("user_hash"),
    "dash_hash" => $tcslib->getSetting("dash_hash"),
    // "s_table_hash" => $tcslib->getSetting("s_table_hash"),
    "template_version" => $template_version,
    "stats_hash" => $stats_hash,
    "redirect_page" => $redirect_page,
    "list_of_pages" => $list_of_pages,
    "enter_to_finish" => $enter_to_finish,
    "dash_refresh_rate" => $dash_refresh_rate,
    "is_admin" => $is_admin,
    "is_tc_admin" => $is_tc_admin
);

// error_log("\n\ntcs/index.php -> What is the initial state to load: ". print_r($initial_state["stats"], 1));
// =============================================


// $PAGE->requires->js('/dist/build.js');

// $PAGE->requires->js('/local/utools/js/grid.widget.js');
// make sure user is logged in.
require_login();

/*
error_log("\n\nURL Getter START ====>\n\n");

$actual_link1 = "http://". $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$actual_link2 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

error_log("\n\nWhat is link 1: ". $actual_link1);
error_log("\n\nWhat is link 2: ". $actual_link2);

function url_origin( $s, $use_forwarded_host = false )
{
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    error_log("\nBuilding the URL bits, what is ssl: ". $ssl. "\n");
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    error_log("\nBuilding the URL bits, what is sp: ". $sp. "\n");
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    error_log("\nBuilding the URL bits, what is protocol: ". $protocol. "\n");
    $port     = $s['SERVER_PORT'];
    error_log("\nBuilding the URL bits, what is port: ". $port. "\n");
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    error_log("\nBuilding the URL bits, what is port: ". $port. "\n");
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    error_log("\nBuilding the URL bits, what is host: ". $host. "\n");
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    error_log("\nBuilding the URL bits, what is host: ". $host. "\n");
    error_log("\n\n==========================\n\n");
    return $protocol . '://' . $host;
}

function full_url( $s, $use_forwarded_host = false )
{
    return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}

$absolute_url = full_url( $_SERVER );
error_log("\n\nWhat is link 3: ". $absolute_url. "\n\n");

error_log("\n\nWhat is link 4: ". $tcslib->getCurrentSite(). "\n\n");
error_log("\n\nWhat is link 5: ". $tcslib->getCurrentTerm(). "\n\n");
error_log("\n\nWhat is link 6: ". $tcslib->getRawURL(). "\n\n");
error_log("\n\nWhat is request 7: ". print_r($_REQUEST, 1). "\n\n");
error_log("\n\nWhat is post 7: ". print_r($_POST, 1). "\n\n");
error_log("\n\nWhat is get 7: ". print_r($_GET, 1). "\n\n");
*/



echo $OUTPUT->header();
// $initial_state = str_replace("\u0022","\\\\\"",json_encode( $initial_state,JSON_HEX_QUOT)); 
// JSON_HEX_APOS - single quote
// JSON_HEX_QUOT - double quote
$initial_state = json_encode($initial_state, JSON_HEX_APOS|JSON_HEX_QUOT);
// need to get all the students in the TCS at this moment
// error_log("\nindex.php -> What is initial_state after json encoding: ". $initial_state);

echo "<script>window.__SERVER__=true</script>".
    "<script>window.__INITIAL_STATE__='".$initial_state."'</script>";


$tcs_title = get_string('tcs_title', 'local_tcs');

// Get the current stats 
// $stats = $statslib->getDashboardData();
$stats = $statslib->getStats();
$smallstats = $statslib->getRoomStats();

// error_log("\n\nWhat is the partial that will be used: ". $redirect_page. "\n\n");
// error_log("\n\nWhat is is_admin: ". $is_admin. "\n\n");
// error_log("\n\nWhat is is_tc_admin: ". $is_tc_admin. "\n\n");


// $redirect_page = "local_tcs/" . $redirect_page;
// error_log("\n\nWhat is the list of pages: ". print_r($list_of_pages, 1). "\n\n");
$templatecontext = [
    "output" => $OUTPUT,
    "stats" => $stats,
    "smallstats" => $smallstats,
    "flatnavigation" => $PAGE->flatnav,
    "is_admin" => $is_admin,
    "is_tc_admin" => $is_tc_admin,
    "tcs_title" => $tcs_title,
    "use_old_theme" => $using_old_theme,
    "settings_link" => $CFG->wwwroot,
    "redirect_page" => $redirect_page,
    "list_of_pages" => $list_of_pages,


    "page_dashboard" => $list_of_pages["page_dashboard"],
    "page_examlist" => $list_of_pages["page_examlist"],
    "page_scheduler" => $list_of_pages["page_scheduler"],
    "page_examreqs" => $list_of_pages["page_examreqs"],
    "page_useroverride" => $list_of_pages["page_useroverride"],
    "page_examlogs" => $list_of_pages["page_examlogs"],
    "page_settings" => $list_of_pages["page_settings"],
    "page_stats" => $list_of_pages["page_stats"],
    "page_printpass" => $list_of_pages["page_printpass"],
    "page_useradmin" => $list_of_pages["page_useradmin"]
    // "header" => $OUTPUT->header
];


echo $OUTPUT->render_from_template('local_tcs/main', $templatecontext);

echo $OUTPUT->footer();
// error_log("\nindex.php -> FINISHED");
