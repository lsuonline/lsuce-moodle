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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class select_courses_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $sectionsbycourse = $this->_customdata['sectionsbycourse'] ?? [];

        // Get the count of sections
        $sectioncount = array_sum(array_map('count', $sectionsbycourse));

        $courses = array_keys($sectionsbycourse);

        $mform->addElement('header',
            'wdsprefs:selectcoursesheader',
            get_string('wdsprefs:selectcoursesheader', 'block_wdsprefs'));

        // Add checkboxes for course selection
        foreach ($courses as $coursename) {

            // Sanitize course name.
            $sanitized = str_replace([' ', '/'], ['_', '-'], $coursename);

            $mform->addElement('advcheckbox', 'selectedcourses_' . $sanitized, '', $coursename);
        }

        $mform->addElement('select',
            'shellcount',
            get_string('wdsprefs:shellcount', 'block_wdsprefs'),
            array_combine(range(1, $sectioncount), range(1, $sectioncount)));

        $mform->setDefault('shellcount', 1);

        $this->add_action_buttons(true, get_string('continue'));
    }
}
