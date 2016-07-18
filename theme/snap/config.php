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
 * Theme config
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // SL - dec 2015 - Make sure editing sessions are not carried over between courses.
global $SESSION, $COURSE, $USER;
if (!defined('AJAX_SCRIPT')) {
    if (empty($SESSION->theme_snap_last_course) || $SESSION->theme_snap_last_course != $COURSE->id) {
        $USER->editing = 0;
        $SESSION->theme_snap_last_course = $COURSE->id;
    }
}

// Setup debugging html.
// This allows javascript to target debug messages and move them to footer.
if (!function_exists('xdebug_break')) {
    ini_set('error_prepend_string', '<div class="php-debug">');
    ini_set('error_append_string', '</div>');
}

$THEME->doctype = 'html5';
$THEME->yuicssmodules = array('cssgrids'); // This is required for joule grader.
$THEME->name = 'snap';
$THEME->parents = array();
$THEME->sheets = array('moodle', 'custom');
$THEME->supportscssoptimisation = false;

$THEME->editor_sheets = array('editor');

$THEME->plugins_exclude_sheets = array(
    'block' => array(
        'html'
    ),
);

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->layouts = array(
    'format_flexpage' => array(
        'file' => 'flexpage.php',
        'regions' => array('side-top', 'side-pre', 'main', 'side-main-box', 'side-post'),
        'defaultregion' => 'main',
        'options' => array('langmenu' => true),
    ),

    // Most backwards compatible layout without the blocks - this is the layout used by default.
    'base' => array(
        'file' => 'default.php',
        'regions' => array(),
    ),
    // Standard layout with blocks, this is recommended for most pages with general information.
    'standard' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'message' => array(
        'file' => 'default.php',
        'regions' => array(),
    ),
    // Main course page.
    'course' => array(
        'file' => 'course.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true),
    ),
    'coursecategory' => array(
        'file' => 'default.php',
        'regions' => array(),
    ),
    // Part of course, typical for modules - default page layout if $cm specified in require_login().
    'incourse' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // The site home page.
    'frontpage' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('nonavbar' => true),
    ),
    // Server administration pages.
    'admin' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // My dashboard page.
    'mydashboard' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true),
    ),
    // My public page.
    'mypublic' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'login' => array(
        'file' => 'login.php',
        'regions' => array(),
        'options' => array('langmenu' => true, 'nonavbar' => true),
    ),

    // Pages that appear in pop-up windows - no navigation, no blocks, no header.
    'popup' => array(
        'file' => 'embedded.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nocoursefooter' => true),
    ),
    // Embeded pages, like iframe/object embeded in moodleform - it needs as much space as possible.
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array()
    ),
    // Used during upgrade and install, and for the 'This site is undergoing maintenance' message.
    // This must not have any blocks, links, or API calls that would lead to database or cache interaction.
    // Please be extremely careful if you are modifying this layout.
    'maintenance' => array(
        'file' => 'maintenance.php',
        'regions' => array(),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => false),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => 'embedded.php',
        'regions' => array(),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // The pagelayout used for safebrowser and securewindow.
    'secure' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
);

$THEME->javascripts = array(
);

$THEME->javascripts_footer = array(
    'bootstrap',
    'snap',
    'course',
    'modernizer',
    'jquery.placeholder'
);

// Optionally load headroom only if required
if (empty($THEME->settings->fixheadertotopofpage)) {
    $THEME->javascripts_footer[] = 'headroom';
} else {
    $THEME->javascripts_footer[] = 'breadcrumb';
}

// Optionally load TweenMax only if required
if (!empty($THEME->settings->nextactivitymodaldialog)) {
    $THEME->javascripts_footer[] = 'completion';
    
    //TweenMax raises a minification error
    $THEME->javascripts_footer[] = 'TweenMax';
}

if (!empty($THEME->settings->csspostprocesstoggle)) {
    $THEME->csspostprocess = 'theme_snap_process_css';
}    
$THEME->hidefromselector = false;

// For use with Flexpage layouts.
$THEME->blockrtlmanipulations = array(
    'side-pre' => 'side-post',
    'side-post' => 'side-pre'
);
