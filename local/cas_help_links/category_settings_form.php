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

class cas_cat_form extends moodleform {

    function definition() {
        global $CFG, $DB, $OUTPUT;
        $mform = $this->_form;
        $attributes = array(
            'class' => 'cas-display-toggle'
        );
        $lattributes = array(
            'class' => 'url-input'
        );
        $catheader = get_string('category_header', 'local_cas_help_links');
        $hidecatlinks = get_string('hide_category_links', 'local_cas_help_links');

        $categories = $this->_customdata['categorySettingsData'];
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('html', '<p class="error-notification-header alert alert-error">' . get_string('submit_error', 'local_cas_help_links') .'</p>');
        
        $mform->addElement('header', 'category_preferences', $catheader);
        
        foreach ($categories as $category) {
            // "hide" checkbox
            $mform->addElement('advcheckbox', $category['display_input_name'], $hidecatlinks, null, $attributes, array(0, 1));
            $mform->setDefault($category['display_input_name'], $category['hide_link']);
            
            // url input
            $mform->addElement('text', $category['link_input_name'], $category['category_name'], $lattributes);
            $mform->disabledIf($category['link_input_name'], $category['display_input_name'], 'checked');
            $mform->setDefault($category['link_input_name'], $category['link_url']);
            $mform->setType($category['link_input_name'], PARAM_TEXT);
        }

        $this->add_action_buttons();
    }
}
