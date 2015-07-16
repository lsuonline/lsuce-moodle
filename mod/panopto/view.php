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
 * Panopto module main user interface
 *
 * @package    mod_panopto
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2015 Robert Russo and Louisiana State University {@link http://www.lsu.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/panopto/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // Panopto instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $panopto = $DB->get_record('panopto', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('panopto', $panopto->id, $panopto->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('panopto', $id, 0, false, MUST_EXIST);
    $panopto = $DB->get_record('panopto', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/panopto:view', $context);

$params = array(
    'context' => $context,
    'objectid' => $panopto->id
);
$event = \mod_panopto\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('panopto', $panopto);
$event->trigger();

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/panopto/view.php', array('id' => $cm->id));

// Make sure Panopto exists before generating output - some older sites may contain empty panoptos
// Do not use PARAM_Panopto here, it is too strict and does not support general URIs!
$extpanopto = trim($panopto->externalpanopto);
if (empty($extpanopto) or $extpanopto === 'http://') {
    panopto_print_header($panopto, $cm, $course);
    panopto_print_heading($panopto, $cm, $course);
    panopto_print_intro($panopto, $cm, $course);
    notice(get_string('invalidstoredpanopto', 'panopto'), new moodle_panopto('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($extpanopto);

$displaytype = panopto_get_final_display_type($panopto);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // coming from course page or panopto index page,
    // the redirection is needed for completion tracking and logging
    $fullpanopto = str_replace('&amp;', '&', panopto_get_full_panopto($panopto, $cm, $course));

    if (!course_get_format($course)->has_view_page()) {
        // If course format does not have a view page, add redirection delay with a link to the edit page.
        // Otherwise teacher is redirected to the external Panopto without any possibility to edit activity or course settings.
        $editpanopto = null;
        if (has_capability('moodle/course:manageactivities', $context)) {
            $editpanopto = new moodle_panopto('/course/modedit.php', array('update' => $cm->id));
            $edittext = get_string('editthisactivity');
        } else if (has_capability('moodle/course:update', $context->get_course_context())) {
            $editpanopto = new moodle_panopto('/course/edit.php', array('id' => $course->id));
            $edittext = get_string('editcoursesettings');
        }
        if ($editpanopto) {
            redirect($fullpanopto, html_writer::link($editpanopto, $edittext)."<br/>".
                    get_string('pageshouldredirect'), 10);
        }
    }
    redirect($fullpanopto);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        panopto_display_embed($panopto, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        panopto_display_frame($panopto, $cm, $course);
        break;
    default:
        panopto_print_workaround($panopto, $cm, $course);
        break;
}
