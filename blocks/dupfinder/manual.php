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

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->dirroot . '/blocks/dupfinder/helpers.php');

// Authentication.
require_login();
if (!is_siteadmin()) {
    $helpers->redirect_to_url($CFG->wwwroot);
}

$context = \context_system::instance();

$pageparams = [
    'runmanual' => optional_param('runmanual', 0, PARAM_INT),
    'emailadmins' => optional_param('emailadmins', 0, PARAM_INT),
];

// Setup the page.
$title = get_string('pluginname', 'block_dupfinder') . ': ' . get_string('manualtrigger', 'block_dupfinder');
$pagetitle = $title;
$sectiontitle = get_string('manualtrigger', 'block_dupfinder');
$url = new moodle_url($CFG->wwwroot . '/blocks/dupfinder/manual.php', $pageparams);
$worky = null;
$df = new helpers();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Navbar Bread Crumbs.
$PAGE->navbar->add(get_string('dfdashboard', 'block_dupfinder'), new moodle_url('dupfinder.php'));
$PAGE->navbar->add(get_string('manual', 'block_dupfinder'), new moodle_url('manual.php'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/dupfinder/style.css'));

$output = $PAGE->get_renderer('block_dupfinder');

// View the Duplicates, if any.
echo $output->header();

$dupes = null;


if ($pageparams['runmanual'] == 1) {
    $starttime = microtime(true);
    $dupes = array();

    // $xml = $df->gettestdata();
    $data = $df->getdata();
    $xml = $df->objectify($data);
    if (empty($xml)) {
        \core\notification::warning(get_string('xmlissues', 'block_dupfinder'));        
    } else {
        $dupes = $df->finddupes($xml);
        if ($dupes) {
            if ($pageparams['emailadmins'] == 1) {
                $emailsuccess = $df->emailduplicates($dupes);
                if ($emailsuccess) {
                    \core\notification::success(get_string('emailsent', 'block_dupfinder'));
                }
            }
        } else {
            \core\notification::success(get_string('nodupsfound', 'block_dupfinder'));
        }
    }

    $elapsedtime = round(microtime(true) - $starttime, 3);
    mtrace(PHP_EOL. "\nThis entire process took " . $elapsedtime . " seconds.". PHP_EOL);
} else {
    $dupes = array();
}

$renderable = new \block_dupfinder\output\manual_view($dupes, true);
echo $output->render($renderable);
echo $output->footer();
