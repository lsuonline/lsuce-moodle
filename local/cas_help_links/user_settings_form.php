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
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/resourcelib.php');

class cas_form extends moodleform {

    function definition() {
        global $CFG, $DB, $OUTPUT;
        $mform = $this->_form;
        $attributes = array(
        'class' => 'cas-display-toggle'
        );

        /* A novel, if not sloppy hack to highlight input boxes
        if (isset($OUTPUT->linktestfail)) {
            $lattributes = array(
                'class' => 'url-input has-error'
            );
        } else {
            $lattributes = array(
                'class' => 'url-input'
            );
        }
        */

        $lattributes = array(
            'class' => 'url-input'
        );

        $pcourseheader = get_string('pcourse_header', 'local_cas_help_links');
        $hide_course_link = get_string('hide_course_link', 'local_cas_help_links');
        $pcategory_header = get_string('pcategory_header', 'local_cas_help_links');
        $user_header = get_string('user_header', 'local_cas_help_links');
        $hide_user_links = get_string('hide_user_links', 'local_cas_help_links');
        $my_default_link = get_string('my_default_link', 'local_cas_help_links');

        $courses = $this->_customdata['courseSettingsData'];
        $categories = $this->_customdata['categorySettingsData'];
        $userSettingsData = $this->_customdata['userSettingsData'];
        $mform->addElement('hidden', 'id', $userSettingsData['user_id']);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('html', '<div class="error-notification-header alert alert-error">' . get_string('submit_error', 'local_cas_help_links') .'</div>');

        $mform->addElement('header', 'personal_preferences', $pcourseheader);
        
        foreach ($courses as $course) {
            if ( !$course['link_id']) {
                $defaultlink = 'Inherited';
            } else {
                $defaultlink = $course['link_url']; 
            }

            // "hide" checkbox
            $mform->addElement('advcheckbox', $course['display_input_name'], $hide_course_link, null, $attributes, array(0, 1));
            $mform->setDefault($course['display_input_name'], $course['hide_link']);
            
            // url input
            $mform->addElement('text', $course['link_input_name'], $course['course_fullname'], $lattributes);
            $mform->disabledIf($course['link_input_name'], $course['display_input_name'], 'checked');
            $mform->setDefault($course['link_input_name'], $course['link_url']);
            $mform->setType($course['link_input_name'], PARAM_TEXT);
        }

        $mform->addElement('header', 'category_preferences', $pcategory_header);
        
        foreach ($categories as $category) {
            if ( !$category['link_id']) {
                $defaultlink = 'Inherited';
            } else {
                $defaultlink = $category['link_url'];
            }
            $hide_category_links = get_string('hide_category_links', 'local_cas_help_links', $category['category_name']);
            // "hide" checkbox
            $mform->addElement('advcheckbox', $category['display_input_name'], $hide_category_links, null, $attributes, array(0, 1));
            $mform->setDefault($category['display_input_name'], $category['hide_link']);
            
            // url input
            $mform->addElement('text', $category['link_input_name'], $category['category_name'], $lattributes);
            $mform->disabledIf($category['link_input_name'], $category['display_input_name'], 'checked');
            $mform->setDefault($category['link_input_name'], $category['link_url']);
            $mform->setType($category['link_input_name'], PARAM_TEXT);
        }

        $mform->addElement('header', 'user_preferences', $user_header);

        // "hide" checkbox
        $mform->addElement('advcheckbox', $userSettingsData['display_input_name'], $hide_user_links, null, $attributes, array(0, 1));
        $mform->setDefault($userSettingsData['display_input_name'], $userSettingsData['hide_link']);
        
        // url input
        $mform->addElement('text', $userSettingsData['link_input_name'], $my_default_link, $lattributes);
        $mform->disabledIf($userSettingsData['link_input_name'], $userSettingsData['display_input_name'], 'checked');
        $mform->setDefault($userSettingsData['link_input_name'], $userSettingsData['link_url']);
        $mform->setType($userSettingsData['link_input_name'], PARAM_TEXT);

        $this->add_action_buttons();
    }
}
