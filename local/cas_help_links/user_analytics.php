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
 
// defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');

global $PAGE, $CFG, $USER;

$selected_course_id = optional_param('course', 0, PARAM_INT);

$context = context_system::instance();

$PAGE->set_url($CFG->wwwroot . '/local/cas_help_links/user_analytics.php');
$PAGE->set_context($context);

require_login();

$user_id = $USER->id;

$userCourseIds = \local_cas_help_links_utility::get_teacher_course_selection_array($user_id, true);

// if a specific course has been selected, make sure it belongs to this auth user teacher!
if ($selected_course_id && ! in_array($selected_course_id, $userCourseIds)) {
    var_dump('no');die; // @TODO - change this for reals
} else {
    if ( ! count($userCourseIds)) {
        var_dump('you have no courses!');die;
    }
}

// require_capability('local/cas_help_links:editcategorysettings', $context);

//////////////////////////////////////////////////////////
/// 
/// RENDER PAGE
///
//////////////////////////////////////////////////////////

// get all data
list($weeks, $userTotals, $clickTotals) = \local_cas_help_links_logger::get_teacher_current_semester_usage_data($user_id, $selected_course_id);

// PAGE RENDERING STUFF
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/style.css"));

$output = $PAGE->get_renderer('local_cas_help_links');

echo $output->header();
echo $output->heading(get_string('analytics_heading', 'local_cas_help_links'));
echo $output->action_link('user_settings.php', get_string('user_settings_link_label', 'local_cas_help_links'));

echo $output->single_select('user_analytics.php', 'course', \local_cas_help_links_utility::get_teacher_course_selection_array($user_id), $selected_course_id, ['' => 'All my courses'], 'course-select', []);

echo $output->semester_usage_chart();

$PAGE->requires->js_call_amd('local_cas_help_links/semesterUsageChart', 'initialise', array(
    $weeks, 
    $userTotals,
    $clickTotals,
));

echo $output->footer();