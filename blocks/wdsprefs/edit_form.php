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

class wdsprefs_cps_edit_form extends moodleform {

    /// The standard Moodle form stuff.
    public function definition() {
        $mform = $this->_form;

//TODO: ADD MORE SHIT.
        // Add the days prior item.
        $mform->addElement('text',
            'wdspref_daysprior',
            get_string('wdsprefs:daysprior', 'block_wdsprefs')
        );
        $mform->setType('wdspref_daysprior', PARAM_INT);
        $mform->addRule('wdspref_daysprior', null, 'required', null, 'client');

        // Add the days prior description.
        $mform->addElement('static',
            'wdspref_daysprior_desc',
            '',
            get_string('wdsprefs:daysprior_desc', 'block_wdsprefs')
        );

        // Add the action buttons.
        $this->add_action_buttons(true);
    }

    // Set the data from the preferences.
    public function set_data_from_preferences($userid) {

        // Build out the object.
        $data = new stdClass();

        // Build out the daysprior item with TODO: get default from enrol_workdaystudent.
        $data->wdspref_daysprior = get_user_preferences('wdspref_daysprior', '0', $userid);

        // Set the data.
        $this->set_data($data);
    }
}
