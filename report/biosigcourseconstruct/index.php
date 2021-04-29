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
 * locallib code for the report_biosigcourseconstruct plugin.
 *
 * @package    report_biosigcourseconstruct
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/report/biosigcourseconstruct/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

// Get URL parameters.
$generate = optional_param('generate', 0, PARAM_SAFEDIR);
$category = optional_param('category', 0, PARAM_SAFEDIR);
$showteachers = optional_param('showteachers', 0, PARAM_SAFEDIR);
$showtitles = optional_param('showtitles', 1, PARAM_SAFEDIR);
$showcourses = optional_param('showcourses', 0, PARAM_SAFEDIR);

// Print the header & check permissions.
admin_externalpage_setup('biosigcourseconstruct', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

// Log.
//\report_biosigcourseconstruct\event\report_viewed::create(array('other' => array('requestedqtype' => $requestedqtype)))->trigger();

// generate the filter form
generate_filter_form($category, $showteachers, $showtitles, $showcourses);

if ($generate) {

    $courses = get_all_courses($category);

    output_summary($courses);

    if (count($courses) > 0) {
        if ($showteachers) {
            $teachers = get_teachers($courses);

            foreach ($teachers as $teacher) {

                $c = [];
                foreach($teacher->courses as $cid) {
                    if (array_key_exists($cid, $courses)) {
                        $c[] = $courses[$cid];
                    }
                }

                $course_count = count($c);
                $biosig_count = biosig_course_count($c);

                if (($showcourses == 0 || ($showcourses == 1 && $biosig_count) || ($showcourses == 2 && $course_count != $biosig_count))) {
                    echo $OUTPUT->box_start();
                    echo $OUTPUT->heading($teacher->name);
                    output_course_table($c, $showtitles, $showcourses);
                    echo $OUTPUT->box_end();
                }
            }
        } else {
            output_course_table($courses, $showtitles, $showcourses);
        }
    } else {
        echo get_string('noresults', 'report_biosigcourseconstruct');
    }
} else {

	$version_string = "mod: " . get_config('mod_biosigid', 'version');
	$version_string .= ", quiz: " . get_config('quizaccess_biosigid', 'version');
	$version_string .= ", report: " . get_config('report_biosigcourseconstruct', 'version');
	echo "<small>" . $version_string . "</small>";

}

// Footer.
echo $OUTPUT->footer();
