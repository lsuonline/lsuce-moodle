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
 * Private panopto module utility functions
 *
 * @package    mod_panopto
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2015 Robert Russo and Louisiana State University {@link http://www.lsu.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/panopto/lib.php");

/**
 * This methods does weak panopto validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $panopto
 * @return bool true is seems valid, false if definitely not valid Panopto
 */
function panopto_appears_valid_panopto($panopto) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $panopto)) {
        // note: this is not exact validation, we look for severely malformed Panoptos only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $panopto);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $panopto);
    }
}

/**
 * Fix common Panopto problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $panopto
 * @return string
 */
function panopto_fix_submitted_panopto($panopto) {
    $config = get_config('panopto');

    // note: empty panoptos are prevented in form validation
    $panopto = trim($panopto);

    // remove encoded entities - we want the raw URI here
    $panopto = html_entity_decode($panopto, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $panopto) and !preg_match('|^/|', $panopto)) {
        // invalid URI, try to fix it by making it normal link,
        // please note relative panopto links are not allowed
        $panopto = 'https://'.$panopto;
    }

    // check the link. If it's not a panopto link, forward them to the chosen panopto homepage, if it is, send them to the appropriate place.
    if(preg_match('/.+[V|v]iewer\.aspx(\?|%3f)id(=|%3d)(.+)/',$panopto)) {
        // ensure they get to the correct url including the authentication instance avoiding Panopto server's stupidity.
        $panopto = preg_replace('/.+[V|v]iewer\.aspx(\?|%3f)id(=|%3d)(.+)/','\3',$panopto);
        $panopto = $config->panoptoserver . '/Panopto/Pages/Auth/Login.aspx?instance=' . $config->authinstance . '&ReturnUrl=%2fPanopto%2fPages%2fViewer.aspx%3fid%3d' . $panopto;
    } else {
        // The link supplied makes no sense to us, let's send them to the main panopto page for the chose authentication instance.
        $panopto = $config->panoptoserver . '/Panopto/Pages/Auth/Login.aspx?instance=' . $config->authinstance;
    }
    return $panopto;
}

/**
 * Return full panopto link
 *
 * This function does not include any XSS protection.
 *
 * @param string $panopto
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string panopto with & encoded as &amp;
 */
function panopto_get_full_panopto($panopto, $cm, $course, $config=null) {

    // make sure there are no encoded entities, it is ok to do this twice
    $fullpanopto = html_entity_decode($panopto->externalpanopto, ENT_QUOTES, 'UTF-8');

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullpanopto) or preg_match('|^/|', $fullpanopto)) {
        // encode extra chars in Panoptos - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullpanopto = preg_replace_callback("/[^$allowed]/", 'panopto_filter_callback', $fullpanopto);
    } else {
        // encode special chars only
        $fullpanopto = str_replace('"', '%22', $fullpanopto);
        $fullpanopto = str_replace('\'', '%27', $fullpanopto);
        $fullpanopto = str_replace(' ', '%20', $fullpanopto);
        $fullpanopto = str_replace('<', '%3C', $fullpanopto);
        $fullpanopto = str_replace('>', '%3E', $fullpanopto);
    }

    // encode all & to &amp; entity
    $fullpanopto = str_replace('&', '&amp;', $fullpanopto);

    return $fullpanopto;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function panopto_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print panopto header.
 * @param object $panopto
 * @param object $cm
 * @param object $course
 * @return void
 */
function panopto_print_header($panopto, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$panopto->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($panopto);
    echo $OUTPUT->header();
}

/**
 * Print panopto heading.
 * @param object $panopto
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function panopto_print_heading($panopto, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($panopto->name), 2);
}

/**
 * Print panopto introduction.
 * @param object $panopto
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function panopto_print_intro($panopto, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($panopto->displayoptions) ? array() : unserialize($panopto->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($panopto->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'panoptointro');
            echo format_module_intro('panopto', $panopto, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display panopto frames.
 * @param object $panopto
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function panopto_display_frame($panopto, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        panopto_print_header($panopto, $cm, $course);
        panopto_print_heading($panopto, $cm, $course);
        panopto_print_intro($panopto, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('panopto');
        $context = context_module::instance($cm->id);
        $extepanopto = panopto_get_full_panopto($panopto, $cm, $course, $config);
        $navpanopto = "$CFG->wwwroot/mod/panopto/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($panopto->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','panopto'));
        $contentframetitle = format_string($panopto->name);
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navpanopto" title="$modulename"/>
    <frame src="$extepanopto" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print panopto info and link.
 * @param object $panopto
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function panopto_print_workaround($panopto, $cm, $course) {
    global $OUTPUT;

    panopto_print_header($panopto, $cm, $course);
    panopto_print_heading($panopto, $cm, $course, true);
    panopto_print_intro($panopto, $cm, $course, true);

    $fullpanopto = panopto_get_full_panopto($panopto, $cm, $course);

    $display = panopto_get_final_display_type($panopto);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullpanopto = addslashes_js($fullpanopto);
        $options = empty($panopto->displayoptions) ? array() : unserialize($panopto->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullpanopto', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="panoptoworkaround">';
    print_string('clicktoopen', 'panopto', "<a href=\"$fullpanopto\" $extra>$fullpanopto</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded panopto linlinkk.
 * @param object $panopto
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function panopto_display_embed($panopto, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $fullpanopto  = panopto_get_full_panopto($panopto, $cm, $course);
    $title    = $panopto->name;

    $link = html_writer::tag('a', $fullpanopto, array('href'=>str_replace('&amp;', '&', $fullpanopto)));
    $clicktoopen = get_string('clicktoopen', 'panopto', $link);
    $moodlepanopto = new moodle_url($fullpanopto);

    $extension = resourcelib_get_extension($panopto->externalpanopto);

    $mediarenderer = $PAGE->get_renderer('core', 'media');
    $embedoptions = array(
        core_media::OPTION_TRUSTED => true,
        core_media::OPTION_BLOCK => true
    );

        // Panopto icon at actual size
        $code = resourcelib_embed_general($fullpanopto, $title, $clicktoopen, NULL);

    panopto_print_header($panopto, $cm, $course);
    panopto_print_heading($panopto, $cm, $course);

    echo $code;

    panopto_print_intro($panopto, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $panopto
 * @return int display type constant
 */
function panopto_get_final_display_type($panopto) {
    global $CFG;

    if ($panopto->display != RESOURCELIB_DISPLAY_AUTO) {
        return $panopto->display;
    }

    // detect links to local moodle pages
    return RESOURCELIB_DISPLAY_OPEN;
}
