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
 * UES Dupe Finder
 *
 * @package   block_dupfinder
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards Robert Russo, David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// global $CFG;
require_once($CFG->dirroot . '/blocks/dupfinder/helpers.php');


// Authentication.
require_login();
if (!is_siteadmin()) {
    $helpers->redirect_to_url($CFG->wwwroot);
}

$context = \context_system::instance();

$pageparams = [
    'runmanual' => optional_param('runmanual', 0, PARAM_INT),
];

// Setup the page.
$title = get_string('pluginname', 'block_dupfinder') . ': ' . get_string('manualtrigger', 'block_dupfinder');
$pagetitle = $title;
$sectiontitle = get_string('manualtrigger', 'block_dupfinder');
// $enablewideview = (bool)get_config('moodle', "block_dupfinder_enable_wide_view");
$url = new moodle_url($CFG->wwwroot . '/blocks/dupfinder/manual.php', $pageparams);
$worky = null;
$df = new helpers();

// Are we looking at the form to add/update or the list?
// $viewform = false;
// if ($pageparams['vform'] == 1) {
    // Ok then, we are looking at the FORM.
    // $viewform = true;
// }
// ------------------------------------------------------------------------
// If we want to push any data to javascript then we can add it here.
// $initialload = array(
//     "wwwroot" => $CFG->wwwroot,
//     "xe_form" => "mappings",
//     "xe_viewform" => $viewform,
//     "settings" => array(
//         "xes_autocomplete" => get_config('moodle', "block_dupfinder_enable_form_auto")
//     )
// );
// $initialload = json_encode($initialload, JSON_HEX_APOS | JSON_HEX_QUOT);
// $xtras = "<script>window.__SERVER__=true</script>".
//     "<script>window.__INITIAL_STATE__='".$initialload."'</script>";
// ------------------------------------------------------------------------

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Navbar Bread Crumbs.
$PAGE->navbar->add(get_string('dfdashboard', 'block_dupfinder'), new moodle_url('dupfinder.php'));
$PAGE->navbar->add(get_string('manual', 'block_dupfinder'), new moodle_url('manual.php'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/dupfinder/style.css'));
// if ($enablewideview) {
    // $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/lsuxe/xestyles/style.css'));
// }
// $PAGE->requires->js_call_amd('block_lsuxe/main', 'init');
$output = $PAGE->get_renderer('block_dupfinder');

// View the Duplicates, if any.
echo $output->header();
// If the sent action is delete then the user just deleted a row, let's process it.

// $userstarttime = microtime(true);

$dupes = null;

if ($pageparams['runmanual'] == 1) {
    $starttime = microtime(true);
    $xml = $df->gettestdata();
    $dupes = $df->finddupes($xml);
    // error_log("\n -------------------------------- \n");
    // error_log("\n manual.php -> do we have dupes: ". print_r($dupes, 1));
    // error_log("\n -------------------------------- \n");
    $elapsedtime = round(microtime(true) - $starttime, 3);
    mtrace(PHP_EOL. "This entire process took " . $elapsedtime . " seconds.". PHP_EOL);
} else {
    $dupes = array();
}

// mtrace("User #$count ($user->username) took " . $userelapsedtime . " seconds to process.\n");



// echo $xtras;
$renderable = new \block_dupfinder\output\manual_view($dupes, true);
echo $output->render($renderable);
echo $output->footer();
