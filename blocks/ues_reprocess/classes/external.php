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
 * Reprocess All Tool
 * @package    block_ues_reprocess
 * @copyright  Louisiana State University
 * @copyright  The guy who did stuff: David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ues_reprocess;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/ues_reprocess/classes/repall.php');
/**
 * External API for AJAX calls.
 */
class external extends \external_api {

    /**
     * Returns parameter types for get_status function.
     *
     * @return \external_function_parameters Parameters
     */
    public static function get_courses_parameters(): \external_function_parameters {
        return new \external_function_parameters([
                'year' => new \external_value(PARAM_RAW, 'Year'),
                'semester' => new \external_value(PARAM_RAW, 'Semester'),
                'department' => new \external_value(PARAM_RAW, 'Department'),
                'update' => new \external_value(PARAM_RAW, 'Field to update'),
        ]);
    }

    /**
     * Returns result type for get_status function.
     *
     * @return \external_description Result type
     */
    public static function get_courses_returns(): \external_description {
        return new \external_single_structure([
            'data' => new \external_multiple_structure(
                new \external_single_structure([
                    'value' => new \external_value(PARAM_RAW, 'Choice value to return from the form.'),
                    'label' => new \external_value(PARAM_RAW, 'Choice name, to display the departments.'),
                ])
            ),
            'update' => new \external_value(PARAM_RAW, 'Field to update'),
            'csize' => new \external_value(PARAM_RAW, 'Estimated number of courses'),
        ]);
    }

    /**
     * Confirms that the get_status function is allowed from AJAX.
     *
     * @return bool True
     */
    public static function get_courses_is_allowed_from_ajax(): bool {
        return true;
    }

    /**
     * Get the list of sharable questions in a category.
     *
     * @param int $courseid the course whose question bank we are sharing from.
     * @param string $categoryidnumber the idnumber of the question category.
     *
     * @return array of arrays with two elements, keys value and label.
     */
    public static function get_courses($year, $semester, $department, $update): array {
        global $CFG, $USER;
        
        $repall = new \repall();
        $courses = $repall->get_courses($year, $semester, $department);
        
        $out = [];
        foreach ($courses as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }
        return array(
            'data' => $out,
            'update' => $update,
            'csize' => count($courses)
            // 'csize' => $repall->get_course_count($year, $semester, $department)
        );
    }
}