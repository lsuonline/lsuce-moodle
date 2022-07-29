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
require_once($CFG->dirroot . '/blocks/lsuxe/lib.php');

class moodles_form extends \moodleform {

    /*
     * Moodle form definition
     */
    public function definition() {
        // element_name, type, contents, attr_collections, attributes = array()
        $mform =& $this->_form;

        // Will need this for the intervals
        $helpers = new \lsuxe_helpers();

        $mform->addElement(
            'text',
            'instanceurl',
            get_string('instanceurl', 'block_lsuxe'),
        );
        $mform->setType(
            'instanceurl',
            PARAM_TEXT
        );

        // ----------------
        $mform->addElement(
            'text',
            'instancetoken',
            get_string('instancetoken', 'block_lsuxe'),
        );
        $mform->setType(
            'instancetoken',
            PARAM_TEXT
        );

        // ----------------
        $intervals = $helpers->config_to_array('block_lsuxe_interval_list');
        $mform->addElement(
            'select',
            'defaultupdateinterval',
            get_string('defaultupdateinterval', 'block_lsuxe'),
            $intervals,
            []
        );

        // ----------------
        $mform->addElement(
            'date_selector',
            'tokenexpiration',
            get_string('tokenexpiration', 'block_lsuxe'),
        );

        // ----------------
        $mform->addElement(
            'checkbox',
            'enabletokenexpiration',
            get_string('tokenenable', 'block_lsuxe')
        );

        $mform->addElement('hidden', 'vform');
        $mform->setType('vform', PARAM_INT); 
        $mform->setConstant('vform', 1);

        // Buttons!
        $buttons = [
            $mform->createElement('submit', 'send', get_string('saveinstance', 'block_lsuxe')),
            $mform->createElement('button', 'verifysource', get_string('verifyinstance', 'block_lsuxe')),
        ];

        $mform->addGroup($buttons, 'actions', '&nbsp;', [' '], false);
    }

    /*
     * Moodle form validation
     */
    public function validation($data, $files) {
        $errors = [];

        // Check that we have at least one recipient.
        if (empty($data['instanceurl'])) {
            $errors['instanceurl'] = get_string('no_included_recipients_validation');
        }

        if (empty($data['instancetoken'])) {
            $errors['instancetoken'] = get_string('no_included_recipients_validation');
        }

        return $errors;
    }
}
