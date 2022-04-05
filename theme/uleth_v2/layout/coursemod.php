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
 * A two column layout for the boost theme.
 *
 * @package   theme_boost
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/* ******************************************************************************** */
/*  UofL          **************** NOTE ****************       

    To keep the Boost Layout as close to it's original form 
    everything must be marked to help with upgrades (diffing)
    START:  // ===================    UofL Start    ===================
    END:    // ===================    UofL End      ===================
    
/* ******************************************************************************** */


defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

if (isloggedin()) {
    $navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
} else {
    $navdraweropen = false;
}
$extraclasses = [];
if ($navdraweropen) {
    $extraclasses[] = 'drawer-open-left';
}
$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions();


// ===================    UofL Start    ===================
$custom_top_links = explode(",", $PAGE->theme->settings->custom_header_links);
$custom_top_links_count = count($custom_top_links);

$teachingcentre_links = array();
for ($i = 0; $i < $custom_top_links_count; $i++) {
    if ($i % 2) {
        continue;
    }
    // *** NOTE *** when switching to theme for the first time the links won't be set, need to avoid warning/error
    if (isset($custom_top_links[$i + 1])) {
        $teachingcentre_links[] = array("title" => trim($custom_top_links[$i]), "link" => trim($custom_top_links[$i + 1]));
    }
}
// ===================    UofL End      ===================
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    // ===================    UofL Start    ===================
    'uleth_header_links' => $teachingcentre_links
    // ===================    UofL End      ===================
];

$nav = $PAGE->flatnav;
$templatecontext['flatnavigation'] = $nav;
$templatecontext['firstcollectionlabel'] = $nav->get_collectionlabel();

echo "<script>console.log('<<----------- CourseMod -----  Template ****  ----->>');</script>";

// ===================    UofL Start    ===================
// echo $OUTPUT->render_from_template('theme_boost/columns2', $templatecontext);
echo $OUTPUT->render_from_template('theme_uleth_v2/coursemod', $templatecontext);
// ===================    UofL End      ===================
