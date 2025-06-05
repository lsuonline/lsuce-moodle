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
 * Search form template for Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sportsgrades\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Class containing data for the search form
 */
class search_form implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        $data = new stdClass();
        
        // Get list of sports from the database
        $sports = $DB->get_records('enrol_wds_sport', null, 'name ASC', 'id, code, name');
        
        $data->sports = array_values($sports);
        
        // Get classifications (Freshman, Sophomore, Junior, Senior, Graduate)
        $data->classifications = [
            ['id' => 'FR', 'name' => 'Freshman'],
            ['id' => 'SO', 'name' => 'Sophomore'],
            ['id' => 'JR', 'name' => 'Junior'],
            ['id' => 'SR', 'name' => 'Senior'],
            ['id' => 'GR', 'name' => 'Graduate']
        ];
        
        // Add strings for the template
        $data->str = [
            'search_title' => get_string('search_title', 'block_sportsgrades'),
            'search_universal_id' => get_string('search_universal_id', 'block_sportsgrades'),
            'search_username' => get_string('search_username', 'block_sportsgrades'),
            'search_firstname' => get_string('search_firstname', 'block_sportsgrades'),
            'search_lastname' => get_string('search_lastname', 'block_sportsgrades'),
            'search_major' => get_string('search_major', 'block_sportsgrades'),
            'search_classification' => get_string('search_classification', 'block_sportsgrades'),
            'search_sport' => get_string('search_sport', 'block_sportsgrades'),
            'search_sport_all' => get_string('search_sport_all', 'block_sportsgrades'),
            'search_button' => get_string('search_button', 'block_sportsgrades'),
            'search_advanced' => get_string('search_advanced', 'block_sportsgrades'),
        ];
        
        return $data;
    }
}
