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
 *
 * @package    block_ues_reprocess
 * @copyright  Louisiana State University
 * @copyright  The guy who did stuff: David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/ues_reprocess/classes/repall.php');

// Authentication.
require_login();

if (!is_siteadmin()) {
    error_log("\n\n NOT ADMIN!!!!!");
    $helpers->redirect_to_url($CFG->wwwroot);
}

$context = \context_system::instance();

$pageparams = [
    'vform' => optional_param('vform', 0, PARAM_INT),
    'ues_courses_h' => optional_param('ues_courses_h', 0, PARAM_INT),
];

// Setup the page.
$title = get_string('pluginname', 'block_ues_reprocess') . ': ' . get_string('settings', 'block_ues_reprocess');
$pagetitle = $title;
$sectiontitle = get_string('reprocessselected', 'block_ues_reprocess');
$enablewideview = (bool)get_config('moodle', "block_ues_reprocess_enable_wide_view");
$url = new moodle_url($CFG->wwwroot . '/blocks/ues_reprocess/reprocess_all.php', $pageparams);
$worky = null;

$repall = new \repall();

// Are we looking at the form to view or run?
$pageparams['sent_action'] = "update";

$viewform = $pageparams['vform'] == 1 ? false : true;

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Navbar Bread Crumbs.
$PAGE->navbar->add(get_string('reprocess', 'block_ues_reprocess'), new moodle_url('reprocess_all.php'));
$PAGE->requires->js_call_amd('block_ues_reprocess/repall', 'init');
// $PAGE->requires->js_call_amd('block_ues_reprocess/main', 'init');
$output = $PAGE->get_renderer('block_ues_reprocess');

echo $output->header();
echo "<div class='row'><div class='col-6'>";
$mform = new \block_ues_reprocess\form\repall_form();

if ($viewform == true) {

    $fromform = $mform->get_data();

    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/blocks/ues_reprocess/reprocess_all.php');
    } else {
        // This branch is executed if the form is submitted but the data doesn't
        // validate and the form should be redisplayed or on the first display of the form.
        $mform->set_data($fromform);
    }
    
    echo $output->heading($sectiontitle);
    $mform->display();
    // End the first col.

    echo "</div'>";

} else {

    // View the Mappings.
    $fromform = $mform->get_data();
    $repall->run_it_all($fromform);
    // End the first col.
    echo "</div'>";
}

echo '</div>';
echo '<div class="col-6">';
echo '<div class="wacka">Approximately <span id="repall_estimator_course">0</span> courses to be reprocessed.';
echo '<div class="wacka">Potentially this much time to process: <span id="repall_estimator_time">0</span>';
echo '</div>';

// End the row.
echo "</div>";
echo $output->footer();
