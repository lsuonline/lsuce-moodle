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
 * Search form for Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Student search form
 */
class block_sportsgrades_search_form extends moodleform {
    
    /**
     * Form definition
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Basic search fields
        $mform->addElement('header', 'basic_search', get_string('search_title', 'block_sportsgrades'));
        
        $mform->addElement('text', 'universal_id', get_string('search_universal_id', 'block_sportsgrades'));
        $mform->setType('universal_id', PARAM_TEXT);
        
        $mform->addElement('text', 'username', get_string('search_username', 'block_sportsgrades'));
        $mform->setType('username', PARAM_TEXT);
        
        // Get list of sports from the database
        $sports = $DB->get_records('enrol_wds_sport', null, 'name ASC', 'id, code, name');
        $sport_options = ['' => get_string('search_sport_all', 'block_sportsgrades')];
        foreach ($sports as $sport) {
            $sport_options[$sport->code] = $sport->name;
        }
        
        $mform->addElement('select', 'sport', get_string('search_sport', 'block_sportsgrades'), $sport_options);
        
        // Advanced search fields
        $mform->addElement('header', 'advanced_search', get_string('search_advanced', 'block_sportsgrades'));
        $mform->setExpanded('advanced_search', false);
        
        $mform->addElement('text', 'firstname', get_string('search_firstname', 'block_sportsgrades'));
        $mform->setType('firstname', PARAM_TEXT);
        
        $mform->addElement('text', 'lastname', get_string('search_lastname', 'block_sportsgrades'));
        $mform->setType('lastname', PARAM_TEXT);
        
        $mform->addElement('text', 'major', get_string('search_major', 'block_sportsgrades'));
        $mform->setType('major', PARAM_TEXT);
        
        // Classifications
        $classifications = [
            '' => '',
            'FR' => 'Freshman',
            'SO' => 'Sophomore',
            'JR' => 'Junior',
            'SR' => 'Senior',
            'GR' => 'Graduate'
        ];
        
        $mform->addElement('select', 'classification', get_string('search_classification', 'block_sportsgrades'), $classifications);
        
        // Add action buttons
        $this->add_action_buttons(false, get_string('search_button', 'block_sportsgrades'));
    }
}
