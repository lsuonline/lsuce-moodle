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
 * @package    block_lsuxe Cross Enrollment
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lsuxe\form;

use block_lsuxe\controllers\form_controller;
require_once($CFG->dirroot . '/blocks/lsuxe/lib.php');
// use block_lsuxe\helpers;

class mappings_form extends \moodleform {
// class mappings_form extends \core\form\persistent {

    // protected static $persistentclass = 'block_lsuxe\\mappings_form';
    /*
     * Moodle form definition
     */
    public function definition() {

        $mappingsctrl = new form_controller("moodles");
        $helpers = new \lsuxe_helpers();
        $moodleinstances = $mappingsctrl->get_records_by_prop("url");
        // $records = $mappingsctrl->get_records();

        // Get data for the form
        $mform =& $this->_form;

        $options = array('multiple' => false);
        // $mform->addElement('course', 'mappedcourses', get_string('courses'), $options);
        $courseselect = $mform->addElement(
            'course',
            'srccourseshortname',
            get_string('srccourseshortname', 'block_lsuxe'),
            // $options
        );
        if (isset($this->_customdata->shortname)) {
            // $mform->setDefault('srccourseshortname', $this->_customdata->shortname);
            error_log("\n\n FUCK YA - shortname is set as: ". $this->_customdata->shortname);
            // $courseselect->setSelected($this->_customdata->shortname);
            $courseselect->setValue($this->_customdata->shortname);
            $courseselect->setMultiple(false);
        }
        // ----------------
        $mform->addElement(
            'text',
            'srccoursegroupname',
            get_string('srccoursegroupname', 'block_lsuxe'),
        );
        $mform->setType(
            'srccoursegroupname',
            PARAM_TEXT
        );
        if (isset($this->_customdata->groupname)) {
            $mform->setDefault('srccoursegroupname', $this->_customdata->groupname);
        }
        // ----------------
        $mform->addElement(
            'select',
            'available_moodle_instances',
            get_string('destmoodleinstance', 'block_lsuxe'),
            $moodleinstances,
            []
        );
        if (isset($this->_customdata->destgroupprefix)) {
            $mform->setDefault('available_moodle_instances', $this->_customdata->destmoodleid);
            // $mform->setValue('available_moodle_instances', $this->_customdata->destmoodleid);
        }
        // ----------------
        $mform->addElement(
            'text',
            'destcourseshortname',
            get_string('destcourseshortname', 'block_lsuxe'),
        );
        $mform->setType(
            'destcourseshortname',
            PARAM_TEXT
        );
        if (isset($this->_customdata->destcourseshortname)) {
            $mform->setDefault('destcourseshortname', $this->_customdata->destcourseshortname);
        }

        // ----------------
        $mform->addElement(
            'text',
            'destcoursegroupname',
            get_string('destcoursegroupname', 'block_lsuxe'),
        );
        $mform->setType(
            'destcoursegroupname',
            PARAM_TEXT
        );
        if (isset($this->_customdata->destgroupprefix)) {
            $mform->setDefault('destcoursegroupname', $this->_customdata->destgroupprefix);
        }

        // ----------------
        $intervals = $helpers->config_to_array('block_lsuxe_interval_list');
        $select = $mform->addElement(
            'select',
            'defaultupdateinterval',
            get_string('courseupdateinterval', 'block_lsuxe'),
            $intervals,
            []
        );
        if (isset($this->_customdata->updateinterval)) {
            $select->setSelected($this->_customdata->updateinterval);
        }
        // ----------------
        $mform->addElement('hidden', 'vform');
        $mform->setType('vform', PARAM_INT); 
        $mform->setConstant('vform', 1);

        // Buttons!
        $buttons = [
            $mform->createElement('submit', 'send', get_string('savemapping', 'block_lsuxe')),
            $mform->createElement('button', 'verifysource', get_string('verifysrccourse', 'block_lsuxe')),
            $mform->createElement('button', 'verifydest', get_string('verifydestcourse', 'block_lsuxe')),
        ];

        $mform->addGroup($buttons, 'actions', '&nbsp;', [' '], false);
    }

    /*
     * Moodle form validation
     */
    public function validation($data, $files) {
    // protected function extra_validation($data, $files, array &$errors) {
        $errors = [];

        // Check that we have at least one recipient.
        if (empty($data['srccourseshortname'])) {
            $errors['srccourseshortname'] = get_string('srccourseshortnameverify', 'block_lsuxe');
        }

        if (empty($data['srccoursegroupname'])) {
            $errors['srccoursegroupname'] = get_string('srccoursegroupnameverify', 'block_lsuxe');
        }

        if (empty($data['destcourseshortname'])) {
            $errors['destcourseshortname'] = get_string('destcourseshortnameverify', 'block_lsuxe');
        }

        if (empty($data['destcoursegroupname'])) {
            $errors['destcoursegroupname'] = get_string('destcoursegroupnameverify', 'block_lsuxe');
        }

        return $errors;
    }

    
}
