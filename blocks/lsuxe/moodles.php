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
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Authentication.
require_login();
if (!is_siteadmin()) {
    $helpers->redirect_to_url('/my');
}

$context = \context_system::instance();

$pageparams = [
    'vform' => optional_param('vform', 0, PARAM_INT),
    'sort' => optional_param('sort', 'sent', PARAM_TEXT), // Field name.
    'dir' => optional_param('dir', 'desc', PARAM_TEXT), // Asc|desc.
    'page' => optional_param('page', 1, PARAM_INT),
    'per_page' => 10, // Adjust as necessary, maybe turn into real param?
    'sent_action' => optional_param('sentaction', 0, PARAM_TEXT),
    'sent_data' => optional_param('sentdata', 0, PARAM_INT),
];

// Setup the page.
$title = get_string('pluginname', 'block_lsuxe') . ': ' . get_string('moodles', 'block_lsuxe');
$pagetitle = $title;
$sectiontitle = get_string('newmmoodle', 'block_lsuxe');
$url = new moodle_url('/blocks/lsuxe/moodles.php', $pageparams);
$worky = null;

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Navbar Bread Crumbs
$PAGE->navbar->add(get_string('xedashboard', 'block_lsuxe'), new moodle_url('lsuxe.php'));
$PAGE->navbar->add(get_string('moodles', 'block_lsuxe'), new moodle_url('moodles.php'));
$PAGE->requires->css(new moodle_url('/blocks/lsuxe/style.css'));
$output = $PAGE->get_renderer('block_lsuxe');

// If the sent action is delete then the user just deleted a row, let's process it.
if ($pageparams['sent_action'] === "delete") {
    $worky = new \block_lsuxe\controllers\form_controller("moodles");
    $worky->delete_record((int)$pageparams['sent_data']);
    \core\notification::success(get_string('deletemoodle', 'block_lsuxe'));
}

// Create a Moodle Instance
if ($pageparams['vform'] == 1) {

    // Add New Mappings
    $mform = new \block_lsuxe\form\moodles_form();
    $fromform = $mform->get_data();

    if ($mform->is_cancelled()) {
        // If there is a cancel element on the form, and it was pressed,
        // then the `is_cancelled()` function will return true.
        // You can handle the cancel operation here.
        redirect($CFG->wwwroot . '/blocks/lsuxe/moodles.php');
    } else if ($fromform = $mform->get_data()) {
        // When the form is submitted, and the data is successfully validated,
        // the `get_data()` function will return the data posted in the form.
        $worky = $worky ?? new \block_lsuxe\controllers\form_controller("moodles");
        $worky->process_form($fromform);
    } else {
        // This branch is executed if the form is submitted but the data doesn't
        // validate and the form should be redisplayed or on the first display of the form.
        $mform->set_data($fromform);
    }

    echo $output->header();
    echo $output->heading($sectiontitle);
    $mform->display();

} else {

    // View the Moodle Instances
    echo $output->header();
    $renderable = new \block_lsuxe\output\moodles_view();
    echo $output->render($renderable);
}

echo $output->footer();
