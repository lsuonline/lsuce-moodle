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
 *
 * @package    block_ues_reprocess
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ues_reprocess\form;
// use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/blocks/ues_reprocess/classes/repall.php');

// \MoodleQuickForm::registerElementType(
//     'ues_course',
//     $CFG->dirroot . '/blocks/ues_reprocess/classes/form/groupform_autocomplete.php',
//     '\\ues_reprocess\\form\\groupform_autocomplete'
// );

class repall_form extends \moodleform {

    public function definition() {
        global $CFG, $USER, $COURSE;

        $m =& $this->_form;

        $testing = get_config('moodle', "ues_reprocess_turn_on_testing");

        $repall = new \repall();

        $semesters = $repall->get_semesters();
        $depts = $repall->get_departments();

        $semlist = array_merge(array(0 => "Select Semester"), $semesters);
        $deplist = array_merge(array("start" => "Select Department"), $depts);
        // ----------------------------
        // Year.
        $years = $repall->get_year();
        $options = array_values(array(999 => "Select Year") + $years);
        
        $select1 = $m->addElement(
            'select',
            'ues_year',
            'Year',
            $options
        );

        // ----------------------------
        // Semester.
        $select2 = $m->addElement(
            'select',
            'ues_semesters',
            get_string('ues_semester_title', 'block_ues_reprocess'),
            $semlist
        );

        // ----------------------------
        // Departments.
        $m->addElement(
            'select',
            'ues_departments',
            get_string('seldepart', 'block_ues_reprocess'),
            $deplist
        );

        // ----------------------------
        // Courses.
        $m->addElement(
            'select',
            'ues_courses',
            get_string('selcourse', 'block_ues_reprocess'),
            array("start" => "Select Course")
        );
        $m->setDefault('ues_courses', array("start" => "Select Course"));
        
        if ($testing) {
            $m->addElement(
                'checkbox',
                'ues_checkbox',
                get_string('unenrolcheck', 'block_ues_reprocess')
            );
        }
        // $m->setDefault('ues_courses', array("start" => "Select Course"));
        // $m->addRule('ues_courses', null, 'required', null, 'client');
        // $m->disabledIf('ues_courses', 'ues_departments', 'noitemselected');

        // ----------------------------
        // Sections.
        /*
        $m->addElement(
            'select',
            'ues_sections',
            // TODO: make below a get_string to be used here and in disabled if
            "Select Sections",
            array("start" => "Section")
        );
        $m->setDefault('ues_sections', array("start" => "Select Section"));
        */

        $m->addElement('hidden', 'vform');
        $m->setType('vform', PARAM_INT);
        $m->setConstant('vform', 1);

        $m->addElement('hidden', 'ues_courses_h');
        $m->setType('ues_courses_h', PARAM_INT);
        
        // ----------------------------
        $buttons = array(
            $m->createElement('submit', 'reprocess', get_string('reprocess', 'block_ues_reprocess')),
            $m->createElement('cancel')
        );

        $m->addGroup($buttons, 'subgroup', '', array(' '), false);
        $m->closeHeaderBefore('subgroup');
    }
}
