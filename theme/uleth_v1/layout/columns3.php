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
 * A two column layout for the uleth_v1 theme.
 *
 * @package   theme_uleth_v1
 * @copyright 2017 University of Lethbridge
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

$loggy = false;
$loggy_btn = false;

if (isloggedin()) {
    // $navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
    $loggy = true;
    $loggy_btn = false;
} else {
    $navdraweropen = false;
    $loggy = false;
    $loggy_btn = true;
}
$extraclasses = [];

// going to keep the nav draw closed and will beremoving the button in header template
$navdraweropen = false;
if ($navdraweropen) {
    $extraclasses[] = 'drawer-open-left';
}

$regionmainbox = 'col-12 col-lg-9';

// left block column
$sidepre = 'col-12 col-sm-4';

// middle block column
$regionmain = 'col-12 col-sm-8';

$sidepre2 = 'desktop-first-column';
$sidepost = 'col-12 col-lg-3 float-right uleth_right_block';


$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$pre_blockshtml = $OUTPUT->blocks('side-pre', $sidepre2);
$post_blockshtml = $OUTPUT->blocks('side-post', $sidepost);


// $sidepre = 'col-4 desktop-first-column';

// echo $OUTPUT->blocks('side-pre', $sidepre); 
// echo $OUTPUT->blocks('side-post', $sidepost); 

$hasblocks = strpos($pre_blockshtml, 'data-block=') !== false;
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();


$custom_top_links = explode(",", $PAGE->theme->settings->custom_header_links);
$custom_top_links_count = count($custom_top_links);

$teachingcentre_links = array();
for ($i = 0; $i < $custom_top_links_count; $i++) {
    if ($i % 2) {
        continue;
    }
    $teachingcentre_links[] = array("title" => trim($custom_top_links[$i]), "link" => trim($custom_top_links[$i + 1]));
}

$add_block_chunky_bit = $OUTPUT->getAddBlockChunky($PAGE);
if (isset($PAGE->theme->settings->terms_of_use)) {
    $terms_of_use = '<div id="terms-of-use"><a href="' . $PAGE->theme->settings->terms_of_use . '">Terms of Use</a></div>';
} else {
    $terms_of_use = '<div id="terms-of-use"><a href="../../terms_of_use.php">Terms of Use</a></div>';
}

/*
    **** NOTE **** 
    The white header and the blue header variables are in classes/output/core_renderer.php
    **************
*/

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    
    'regionmainbox' => $regionmainbox,
    'regionmain' => $regionmain,
    'sidepre' => $sidepre,
    'sidepost' => $sidepost,
    'sidepreblocks' => $pre_blockshtml,
    'sidepostblocks' => $post_blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'uleth_header_links' => $teachingcentre_links,
    'add_block_chunky_bit' => $add_block_chunky_bit,
    'is_logged' => $loggy,
    'show_loggy_btn' => $loggy_btn,
    'terms_of_use' => $terms_of_use
];

$templatecontext['flatnavigation'] = $PAGE->flatnav;


// $templatecontext['flatnavigation'] = $OUTPUT->getAddBlockChunky();

echo $OUTPUT->render_from_template('theme_uleth_v1/columns3', $templatecontext);
