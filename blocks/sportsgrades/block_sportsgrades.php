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
 * Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/sportsgrades/classes/search.php');

/**
 * Sports Grades block class
 */
class block_sportsgrades extends block_base {

    /**
     * Initialize the block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_sportsgrades');
    }

    /**
     * Block has its own configuration screen
     */
    public function has_config() {
        return true;
    }

    /**
     * Allow instances in multiple areas
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Return contents of the block
     */
    public function get_content() {
        global $CFG, $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        // Initialize content
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if user has access
        if (!$this->has_access()) {
            $this->content->text = get_string('noaccess', 'block_sportsgrades');
            return $this->content;
        }

        // Create a button to access the search page
        $searchurl = new moodle_url('/blocks/sportsgrades/view.php');
        $button = new single_button($searchurl, get_string('search_page_link', 'block_sportsgrades'), 'get');
        $button->add_action(new popup_action('click', $searchurl, 'sportsgradeswindow', array('height' => 800, 'width' => 1000)));
        
        $this->content->text .= html_writer::div($OUTPUT->render($button), 'text-center');

        return $this->content;
    }

    /**
     * Specify which pages types this block can be displayed on
     */
    public function applicable_formats() {
        return [
            'site' => true,
            'my' => true,
            'admin' => true,
        ];
    }

    /**
     * Check if the current user has access to this block
     * @return bool
     */
    protected function has_access() {
        global $USER, $DB;

        // Hardcoded access for now as requested
        $allowed_users = [
            'rrusso33',
            // Add more users as needed
        ];

        // Check if current user is in the allowed list
        if (in_array($USER->username, $allowed_users)) {
            return true;
        }

        // Alternative: Check for capabilities
        return has_capability('block/sportsgrades:view', context_system::instance());
    }
}
