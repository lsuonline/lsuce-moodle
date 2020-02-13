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
 * @package   local_cas_help_links
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$redirect_url = required_param('u', PARAM_URL);
$course_id = required_param('c', PARAM_INT);
$link_id = required_param('l', PARAM_INT);

$context = context_system::instance();

global $PAGE, $CFG, $USER;

$PAGE->set_url($CFG->wwwroot . '/local/cas_help_links/interstitial.php');
$PAGE->set_context($context);

require_login();

// HANDLE REDIRECT.

// If a URL was provided.
if ($redirect_url) {
    // Log link.
    \local_cas_help_links_logger::log_link_click($USER->id, $course_id, $link_id);
    // Redirect to the appropriate url.
    header('Location: ' . $redirect_url);
    die;
}

// Otherwise, redirect user back to where they came from.
header('Location: ' . $_SERVER['HTTP_REFERER']);
die;