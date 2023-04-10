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
 * Local My Admin
 *
 * @package   local_myadmin
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


error_log("What is dirname(__FILE__) again??: ". dirname(__FILE__));

require_once(dirname(dirname(dirname(__FILE__))). '/config.php');
include_once('lib/MyAdminLib.php');
// include_once('lib/Stats.php');
include_once('lib/Pages.php');
// include_once('lib/TemplateSettings.php');

global $DB, $USER;

// *********************************************************************

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout("local");
$PAGE->set_url($CFG->wwwroot . '/local/myadmin/index.php');

//Create the breadcrumbs
$PAGE->set_heading(get_string('myadmin_title', 'local_myadmin'));


$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/myadmin/styles/main.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/myadmin/styles/animate.css'));

// $statslib = new TemplateSettings();
$myadminlib = new MyAdminLib();
// $statslib = new Stats();
$pageslib = new Pages();

// Are we an admin user here or proctor?......or random stranger.
// $in_sub = in_array(getremoteaddr(), explode(',', $CFG->local_myadmin_subnet_list)) ? true : false;
$is_admin = false;
$is_user = false;

// if (($myadminlib->checkAdminUser() && $in_sub ) || $myadminlib->isSystemAdmin()) {
// if (($myadminlib->checkAdminUser()) || $myadminlib->isSystemAdmin()) {
// TODO: checkAdminUser() is breaking for some dumb reason.
if ($myadminlib->isSystemAdmin()) {
    $is_admin = true;
// } else if ($USER->username && $DB->get_record('local_tcms_user_admin', array('username'=> $USER->username))) {
    //check if this user is already in the record
    // $is_user = true;
}

// =============================================
// ============     Is Admin?     ==============
$extras = array($is_admin);
// $PAGE->requires->js_call_amd(new moodle_url($CFG->wwwroot . 'local_myadmin/main'), 'init', $extras);
$PAGE->requires->js_call_amd('local_myadmin/main', 'init', $extras);
// =============================================


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
    "user_hash" => $myadminlib->getSetting("user_hash"),
    "dash_hash" => $myadminlib->getSetting("dash_hash"),
    // "s_table_hash" => $myadminlib->getSetting("s_table_hash"),
    "template_version" => $template_version,
    "stats_hash" => $stats_hash,
    "redirect_page" => $redirect_page,
    "list_of_pages" => $list_of_pages,
    "enter_to_finish" => $enter_to_finish,
    "dash_refresh_rate" => $dash_refresh_rate,
    "is_admin" => $is_admin,
    "is_user" => $is_user
);

// error_log("\n\n myadmin/index.php -> What is the initial state to load: ". print_r($initial_state["stats"], 1));
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

error_log("\n\nWhat is link 4: ". $myadminlib->getCurrentSite(). "\n\n");
error_log("\n\nWhat is link 5: ". $myadminlib->getCurrentTerm(). "\n\n");
error_log("\n\nWhat is link 6: ". $myadminlib->getRawURL(). "\n\n");
error_log("\n\nWhat is request 7: ". print_r($_REQUEST, 1). "\n\n");
error_log("\n\nWhat is post 7: ". print_r($_POST, 1). "\n\n");
error_log("\n\nWhat is get 7: ". print_r($_GET, 1). "\n\n");
*/



echo $OUTPUT->header();
// $initial_state = str_replace("\u0022","\\\\\"",json_encode( $initial_state,JSON_HEX_QUOT)); 
// JSON_HEX_APOS - single quote
// JSON_HEX_QUOT - double quote
$initial_state = json_encode($initial_state, JSON_HEX_APOS|JSON_HEX_QUOT);
// need to get all the students in the MyAdmin at this moment
// error_log("\nindex.php -> What is initial_state after json encoding: ". $initial_state);

echo "<script>window.__SERVER__=true</script>".
    "<script>window.__INITIAL_STATE__='".$initial_state."'</script>";


$myadmin_title = get_string('myadmin_title', 'local_myadmin');

// Get the current stats 
// $stats = $statslib->getDashboardData();
// $stats = $statslib->getStats();
// $smallstats = $statslib->getRoomStats();

// error_log("\n\nWhat is the partial that will be used: ". $redirect_page. "\n\n");
// error_log("\n\nWhat is is_admin: ". $is_admin. "\n\n");
// error_log("\n\nWhat is is_tc_admin: ". $is_tc_admin. "\n\n");


// $redirect_page = "local_myadmin/" . $redirect_page;
// error_log("\n\nWhat is the list of pages: ". print_r($list_of_pages, 1). "\n\n");
$templatecontext = [
    "output" => $OUTPUT,
    // "stats" => $stats,
    // "smallstats" => $smallstats,
    "flatnavigation" => $PAGE->flatnav,
    "is_admin" => $is_admin,
    "is_user" => $is_user,
    "myadmin_title" => $myadmin_title,
    // "use_old_theme" => $using_old_theme,
    "settings_link" => $CFG->wwwroot,
    "redirect_page" => $redirect_page,
    "list_of_pages" => $list_of_pages,


    // "page_dashboard" => $list_of_pages["page_dashboard"],
    // "page_examlist" => $list_of_pages["page_examlist"],
    // "page_scheduler" => $list_of_pages["page_scheduler"],
    // "page_examreqs" => $list_of_pages["page_examreqs"],
    // "page_useroverride" => $list_of_pages["page_useroverride"],
    // "page_examlogs" => $list_of_pages["page_examlogs"],
    // "page_settings" => $list_of_pages["page_settings"],
    // "page_stats" => $list_of_pages["page_stats"],
    // "page_printpass" => $list_of_pages["page_printpass"],
    // "page_useradmin" => $list_of_pages["page_useradmin"]
    // "header" => $OUTPUT->header
];


echo $OUTPUT->render_from_template('local_myadmin/main', $templatecontext);

echo $OUTPUT->footer();
// error_log("\nindex.php -> FINISHED");
