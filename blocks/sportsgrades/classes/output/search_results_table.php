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
 * Table for displaying student search results
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class to display search results in a table
 */
class block_sportsgrades_search_results_table extends table_sql {
    
    /**
     * Constructor
     * @param string $uniqueid Unique id of table
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        
        // Define columns
        $this->define_columns([
            'username',
            'universal_id',
            'firstname',
            'lastname',
            'college',
            'major',
            'classification',
            'sports',
            'actions'
        ]);
        
        // Define headers
        $this->define_headers([
            get_string('result_username', 'block_sportsgrades'),
            get_string('result_universal_id', 'block_sportsgrades'),
            get_string('result_firstname', 'block_sportsgrades'),
            get_string('result_lastname', 'block_sportsgrades'),
            get_string('result_college', 'block_sportsgrades'),
            get_string('result_major', 'block_sportsgrades'),
            get_string('result_classification', 'block_sportsgrades'),
            get_string('result_sports', 'block_sportsgrades'),
            get_string('result_view_grades', 'block_sportsgrades')
        ]);
        
        $this->sortable(true, 'lastname', SORT_ASC);
        $this->no_sorting('sports');
        $this->no_sorting('actions');
        
        $this->column_class('actions', 'text-center');
    }
    
    /**
     * Format the sports column
     *
     * @param object $row
     * @return string HTML
     */
    public function col_sports($row) {
        if (empty($row->sports)) {
            return '';
        }
        
        $sports = unserialize($row->sports);
        $output = [];
        
        foreach ($sports as $sport) {
            $output[] = html_writer::tag('span', $sport->name, ['class' => 'badge badge-info m-1']);
        }
        
        return implode('', $output);
    }
    
    /**
     * Format the actions column
     *
     * @param object $row
     * @return string HTML
     */
    public function col_actions($row) {
        global $OUTPUT;
        
        $url = new moodle_url('/blocks/sportsgrades/view_grades.php', ['studentid' => $row->id]);
        
        return html_writer::link(
            $url,
            get_string('result_view_grades', 'block_sportsgrades'),
            ['class' => 'btn btn-primary btn-sm']
        );
    }
}
