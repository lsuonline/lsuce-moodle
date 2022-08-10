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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lsuxe\form;

use block_lsuxe\controllers\form_controller;
use block_lsuxe\form\groupform_autocomplete;
require_once($CFG->dirroot . '/blocks/lsuxe/lib.php');

\MoodleQuickForm::registerElementType('groupform_autocomplete',
    $CFG->dirroot . '/blocks/lsuxe/classes/form/groupform_autocomplete.php',
    '\\block_lsuxe\\form\\groupform_autocomplete');

class mappings_form extends \moodleform {
    /*
     * Moodle form definition
     */
    public function definition() {

        $mappingsctrl = new form_controller("moodles");
        $helpers = new \lsuxe_helpers();
        $moodleinstances = $mappingsctrl->get_records_by_prop("url", true);
        $formupdating = false;
        if (isset($this->_customdata->id)) {
            $formupdating = true;
        }

        // Get data for the form
        $mform =& $this->_form;

        // This is for styling purposes, not using.....yet.
        // $mform->addElement('html', '<span class="lsuxe_course_picker">');
        // --------------------------------
        // Course Shortname.
        $options = array('multiple' => false);
        $courseselect = $mform->addElement(
            'course',
            'srccourseshortname',
            get_string('srccourseshortname', 'block_lsuxe'),
            $options,
            array('ajax' => 'block_lsuxe/course_search')
        );

        if (isset($this->_customdata->shortname)) {
            $courseselect->setValue($this->_customdata->courseid);
        }
        
        // ----------------------------------------------------------------
        // ------------------- Source Group  ------------------------------
        // Override for manual group name entry.
        $mform->addElement(
            'advcheckbox',
            'selectgroupentry',
            get_string('manualgroupentry', 'block_lsuxe'),
        );
        $mform->setDefault('selectgroupentry', 0);

        // Select Source Group Name, there could be MULTIPLE groups to choose form 
        // ** NOTE ** this must also match in the form_events.js code where the group form resets
        if (isset($this->_customdata->groupname)) {
            $defaultgroupselect = array($this->_customdata->groupid => $this->_customdata->groupname);
        } else {
            $defaultgroupselect = array("Please search for a course first");
        }

        // --------------------------------
        $mform->addElement(
            'select',
            'srccoursegroupnameselect',
            get_string('srccoursegroupname', 'block_lsuxe'),
            $defaultgroupselect,
        );

        // --------------------------------
        // Manual Source Group Name.
        $mform->addElement(
            'text',
            'srccoursegroupnametext',
            get_string('srccoursegroupname', 'block_lsuxe'),
        );
        $mform->setType(
            'srccoursegroupnametext',
            PARAM_TEXT
        );
        // if (isset($this->_customdata->groupname)) {
        //     $mform->setDefault('srccoursegroupnametext', $this->_customdata->groupname);
        // }

        // --------------------------------
        // Moodle removes any select items that are added via AJAX. In order to save this
        // the value will be stored in this hidden input.
        $mform->addElement('hidden', 'srccoursegroupname');
        $mform->setType('srccoursegroupname', PARAM_TEXT);
        if (isset($this->_customdata->groupname)) {
            $mform->setDefault('srccoursegroupname', $this->_customdata->groupname);
        } else {
            $mform->setDefault('srccoursegroupname', "");
        }

        // --------------------------------
        $mform->addElement('hidden', 'srccoursegroupid');
        $mform->setType('srccoursegroupid', PARAM_INT);
        if (isset($this->_customdata->groupname)) {
            $mform->setDefault('srccoursegroupid', $this->_customdata->groupid);
        } else {
            $mform->setDefault('srccoursegroupid', "");
        }

        $mform->disabledIf('srccoursegroupnameselect', 'selectgroupentry', 'checked');
        $mform->disabledIf('srccoursegroupnametext', 'selectgroupentry', 'notchecked');
        
        $mform->hideIf('srccoursegroupnameselect', 'selectgroupentry', 'checked');
        $mform->hideIf('srccoursegroupnametext', 'selectgroupentry', 'notchecked');
        // ----------------------------------------------------------------
        // ----------------------------------------------------------------

        // --------------------------------
        // Moodle Instance.
        $mform->addElement(
            'select',
            'available_moodle_instances',
            get_string('destmoodleinstance', 'block_lsuxe'),
            $moodleinstances,
            []
        );
        if (isset($this->_customdata->destgroupprefix)) {
            $mform->setDefault('available_moodle_instances', $this->_customdata->destmoodleid);
        }

        // --------------------------------
        // Destination Course Group name autocomplete
        $mform->addElement(
            'groupform_autocomplete',
            // 'autocomplete',
            'destcourseshortname',
            get_string('destcourseshortname', 'block_lsuxe'),
            // $deez_attributes
        );
        // $mform->setType(
        //     'destcourseshortname',
        //     PARAM_TEXT
        // );
        if (isset($this->_customdata->destcourseshortname)) {
            $mform->setDefault('destcourseshortname', $this->_customdata->destcourseshortname);
        }

        // --------------------------------
        // Destination Course Group name manual entry
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

        // --------------------------------
        // Interval.
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

        // --------------------------------
        // Hidden Elements.
        // For Page control list or view form.
        $mform->addElement('hidden', 'vform');
        $mform->setType('vform', PARAM_INT); 
        $mform->setConstant('vform', 1);

        // Record id which is used to update.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT); 
        if ($formupdating) {
            $mform->setDefault('id', $this->_customdata->id);
        }

        // --------------------------------
        // Buttons!
        // The button can either be Save or Update for the submit action.
        $thissubmitbutton = $formupdating ? get_string('savechanges', 'block_lsuxe') : get_string('savemapping', 'block_lsuxe');
        $buttons = [
            $mform->createElement('submit', 'send', $thissubmitbutton),
            $mform->createElement('button', 'verifysource', get_string('verifysrccourse', 'block_lsuxe')),
            $mform->createElement('button', 'verifydest', get_string('verifydestcourse', 'block_lsuxe')),
        ];

        $mform->addGroup($buttons, 'actions', '&nbsp;', [' '], false);
        // $mform->addElement('html', '</span>');
    }

    /*
     * Moodle form validation
     */
    public function validation($data, $files) {
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
