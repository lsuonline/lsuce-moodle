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
 * Renderer for Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for the Sports Grades block
 */
class block_sportsgrades_renderer extends plugin_renderer_base {
    
    /**
     * Render the search form
     *
     * @param \block_sportsgrades\output\search_form $form The search form to render
     * @return string HTML
     */
    public function render_search_form(\block_sportsgrades\output\search_form $form) {
        $data = $form->export_for_template($this);
        return $this->render_from_template('block_sportsgrades/search_form', $data);
    }
    
    /**
     * Render search results
     *
     * @param array $results Search results
     * @return string HTML
     */
    public function render_search_results($results) {
        $data = [
            'results' => $results
        ];
        return $this->render_from_template('block_sportsgrades/search_results', $data);
    }
    
    /**
     * Render grade display
     *
     * @param array $grades Grade data
     * @param \stdClass $student Student data
     * @return string HTML
     */
    public function render_grade_display($grades, $student) {
        $data = [
            'student' => $student,
            'courses' => $grades['courses']
        ];
        return $this->render_from_template('block_sportsgrades/grade_display', $data);
    }
    
    /**
     * Render grade items
     *
     * @param array $course Course data
     * @param array $grade_items Grade items
     * @return string HTML
     */
    public function render_grade_items($course, $grade_items) {
        $data = [
            'course' => $course,
            'grade_items' => $grade_items
        ];
        return $this->render_from_template('block_sportsgrades/grade_items', $data);
    }
}
