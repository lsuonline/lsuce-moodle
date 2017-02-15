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
 * Form for local cas_help_links
 *
 * @package    local_cas_help_links
 * @copyright  2016, William C. Mazilly, Robert Russo
 * @copyright  2016, Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class cas_delete_coursematch_form extends moodleform {

    function definition() {
        global $CFG, $DB, $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'id', $this->_customdata['coursematch_id']);

        $mform->addElement('html', '<p>' . $this->_customdata['coursematch_dept'] . ' ' . $this->_customdata['coursematch_number'] . '&nbsp;&nbsp;(<a href="' . $this->_customdata['coursematch_link'] . '" target="_blank">' . $this->_customdata['coursematch_link'] . '</a>)</p>');

        $mform->addElement('submit', 'submitdeletcoursematch', 'Delete', ['class' => 'pull-left']);
    }
}
