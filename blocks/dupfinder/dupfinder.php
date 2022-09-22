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

// Authentication.
require_login();

if (!is_siteadmin()) {
    $helpers->redirect_to_url('/my');
}

$title = get_string('pluginname', 'block_dupfinder') . ': ' . get_string('dashboard', 'block_dupfinder');
$pagetitle = $title;
$sectiontitle = get_string('dashboard', 'block_dupfinder');
$url = new moodle_url('/blocks/dupfinder/dupfinder.php');
$context = \context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Navbar Bread Crumbs.
$PAGE->navbar->add(get_string('dfdashboard', 'block_dupfinder'), new moodle_url('dupfinder.php'));
$PAGE->requires->css(new moodle_url('/blocks/dupfinder/style.css'));
$output = $PAGE->get_renderer('block_dupfinder');


echo $output->header();
echo $output->heading($sectiontitle);

// Links.
$dashboardlinks = array(
    array(
        // The Mappinges View.
        'url' => $CFG->wwwroot . '/blocks/dupfinder/manual.php',
        'icon' => 'list',
        'lang' => get_string('manualtrigger', 'block_dupfinder')
    ),
    array(
        // The Settings Page.
        'url' => $CFG->wwwroot . '/admin/settings.php?section=blocksettingdupfinder',
        'icon' => 'cog',
        'lang' => get_string('settings', 'block_dupfinder')
    ),
);

$renderable = new \block_dupfinder\output\dashboard($dashboardlinks);
echo $output->render($renderable);
echo $output->footer();
