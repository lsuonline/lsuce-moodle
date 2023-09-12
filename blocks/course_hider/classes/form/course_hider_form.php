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
 * Course Hider Tool
 *
 * @package   block_course_hider
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_course_hider\form;

use block_course_hider\controllers\form_controller;
// use block_course_hider\form\groupform_autocomplete;
use block_course_hider\models;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/blocks/course_hider/lib.php');
require_once($CFG->libdir . '/formslib.php');

class course_hider_form extends \moodleform {

    /*
     * Moodle form definition
     */
    public function definition() {
        global $CFG;

        $formupdating = false;
        if (isset($this->_customdata->id)) {
            $formupdating = true;
        }

        // Get data for the form.
        $mform =& $this->_form;
        $showexecute = false;
        if (isset($this->_customdata['btnpreview'])) {
        // if (property_exists($this->_customdata, 'btnpreview')) {
            $showexecute = $this->_customdata['btnpreview'];    
        }
        // isset($this->_customdata['btnpreview'])
        // For styling purposes, if needed.
        $mform->addElement('html', '<span class="course_hider_form_container">');
        
        // --------------------------------
        // Year.
        $years = \course_hider_helpers::getYears();
        $semtype = \course_hider_helpers::getSemesterType();
        $semester = \course_hider_helpers::getSemester();
        $section = \course_hider_helpers::getSemesterSection();

            $mform->addElement(
                'select',
                'ch_years',
                get_string('defaultyear2', 'block_course_hider'),
                $years,
                array('class' => 'ch_hider_form')
            );
            if (isset($this->_customdata->years)) {
                $mform->setDefault('ch_years', $this->_customdata->ch_years);
            }

            // --------------------------------
            // Semester Type.
            $mform->addElement(
                'select',
                'ch_semester_type',
                get_string('defaultsemestertype2', 'block_course_hider'),
                $semtype,
                array('class' => 'ch_hider_form')
            );
            if (isset($this->_customdata->ch_semester_types)) {
                $mform->setDefault('ch_semester_type', $this->_customdata->ch_semester);
            }

            // --------------------------------
            // Semester.
            $mform->addElement(
                'select',
                'ch_semester',
                get_string('defaultsemester2', 'block_course_hider'),
                $semester,
                array('class' => 'ch_hider_form')
            );
            if (isset($this->_customdata->semester)) {
                $mform->setDefault('ch_semester', $this->_customdata->ch_semester);
            }

            // --------------------------------
            // Semester Section.
            $mform->addElement(
                'select',
                'ch_semester_section',
                get_string('defaultsemestersection2', 'block_course_hider'),
                $section,
                array('class' => 'ch_hider_form')
            );
            if (isset($this->_customdata->semester)) {
                $mform->setDefault('ch_semester_section', $this->_customdata->ch_semester);
            }
            // 
            // --------------------------------
            // Show visible.
            $mform->addElement(
                'checkbox',
                'hiddenonly',
                get_string('hiddenonly', 'block_course_hider'),
                '',
                array('class' => 'ch_hider_form')
            );

        // --------------------------------
        // Spacer.
        $mform->addElement('html', '<hr>');

        // --------------------------------
        // Manually search.
        $mform->addElement(
            'text',
            'raw_input',
            get_string('raw_input', 'block_course_hider'),
            "Highly recommend to preview before executing"
        );

        $warning = get_string('raw_input_desc', 'block_course_hider');
        $mform->addElement('html', '<span class="ch-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span> '.$warning);
        $mform->setType(
            'raw_input',
            PARAM_TEXT
        );

        if (isset($this->_customdata->raw_input)) {
            $mform->setDefault('raw_input', $this->_customdata->raw_input);
        }

        // // --------------------------------
        // // Spacer.
        // $mform->addElement('html', '<hr>');

        // // --------------------------------
        // // Show visible.
        // $mform->addElement('checkbox', 'hiddenonly', get_string('hiddenonly', 'block_course_hider'));
        // --------------------------------
        // Hidden Elements.
        // For Page control list or view form.
        $mform->addElement('hidden', 'vpreview');
        $mform->setType('vpreview', PARAM_INT);
        if ($this->_customdata['btnpreview'] == 1) {
            $mform->setConstant('vpreview', 1);
        } else 
            $mform->setConstant('vpreview', 0);

        // --------------------------------
        // Buttons!
        // If using the autocomplete we don't need to verify the source and dest as it's an
        // autocomplete feature.
        // The button can either be Save or Update for the submit action.
        $mform->addElement('html', '<hr>');
        
        $buttons = [
            $mform->createElement('submit', 'preview', get_string('previewquery', 'block_course_hider')),
            $mform->createElement('submit', 'execute', get_string('executequery', 'block_course_hider'))
            // $mform->createElement('submit', 'execute', get_string('executequery', 'block_course_hider'), array('class' => 'btn btn-danger')),
        ];
        $mform->addGroup($buttons, 'actions', '&nbsp;', [' '], false);
        
        // Disable execute until it has been previewed.
        $mform->disabledIf('execute', 'vpreview', 'eq', 0);

        $mform->addElement('html', '</span>');
    }

    /**
     * Moodle form validation
     *
     * @param array $data  Data from the form.
     * @param array $files Any files in the form.
     *
     * @return array Errors returned for the required elements.
     */
    public function validation($data, $files) {
        $errors = [];

        // TODO: run validation checks.
        return;

        $enableautocomplete = (bool)get_config('moodle', "block_course_hider_enable_form_auto");

        // Check that we have at least one recipient.
        if (empty($data['samplecourse'])) {
            $errors['samplecourse'] = get_string('samplecourseverify', 'block_course_hider');
        } else {
            if (!$fuzzy->check_course_exists($data['samplecourse'], $enableautocomplete)) {
                $errors['samplecourse'] = get_string('formeerror', 'block_course_hider');
            }
        }

        if (empty($data['samplegroup'])) {
            $errors['samplegroup'] = get_string('samplegroupverify', 'block_course_hider');
        } else {
            if (!$fuzzy->check_group_exists($data['samplegroup'])) {
                $errors['samplegroup'] = get_string('formeerror', 'block_course_hider');
            }
        }

        return $errors;
    }
}
