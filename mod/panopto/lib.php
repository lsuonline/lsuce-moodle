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
 * Mandatory public API of panopto module
 *
 * @package    mod_panopto
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2015 Robert Russo and Louisiana State University {@link http://www.lsu.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in Panopto module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function panopto_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function panopto_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function panopto_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function panopto_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function panopto_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add panopto instance.
 * @param object $data
 * @param object $mform
 * @return int new panopto instance id
 */
function panopto_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/panopto/locallib.php');

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalpanopto = panopto_fix_submitted_panopto($data->externalpanopto);

    $data->timemodified = time();
    $data->id = $DB->insert_record('panopto', $data);

    return $data->id;
}

/**
 * Update panopto instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function panopto_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/panopto/locallib.php');

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalpanopto = panopto_fix_submitted_panopto($data->externalpanopto);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('panopto', $data);

    return true;
}

/**
 * Delete panopto instance.
 * @param int $id
 * @return bool true
 */
function panopto_delete_instance($id) {
    global $DB;

    if (!$panopto = $DB->get_record('panopto', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('panopto', array('id'=>$panopto->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function panopto_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/panopto/locallib.php');

    if (!$panopto = $DB->get_record('panopto', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, externalpanopto, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $panopto->name;

    $display = panopto_get_final_display_type($panopto);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullpanopto = "$CFG->wwwroot/mod/panopto/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($panopto->displayoptions) ? array() : unserialize($panopto->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullpanopto', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullpanopto = "$CFG->wwwroot/mod/panopto/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullpanopto'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('panopto', $panopto, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function panopto_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-panopto-*'=>get_string('page-mod-panopto-x', 'panopto'));
    return $module_pagetype;
}

/**
 * Export Panopto resource contents
 *
 * @return array of export file content
 */
function panopto_export_contents($cm, $basepanopto) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/panopto/locallib.php');
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $panopto = $DB->get_record('panopto', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fullpanopto = str_replace('&amp;', '&', panopto_get_full_panopto($panopto, $cm, $course));
    $ispanopto = clean_param($fullpanopto, PARAM_Panopto);
    if (empty($ispanopto)) {
        return null;
    }

    $panopto = array();
    $panopto['type'] = 'panopto';
    $panopto['timecreated']  = null;
    $panopto['timemodified'] = $panopto->timemodified;
    $panopto['sortorder']    = null;
    $panopto['userid']       = null;
    $panopto['author']       = null;
    $panopto['license']      = null;
    $contents[] = $panopto;

    return $contents;
}
