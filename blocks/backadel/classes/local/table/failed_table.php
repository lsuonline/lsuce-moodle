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

declare(strict_types=1);

namespace block_backadel\local\table;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use stdClass;

/**
 * Lists courses whose Backadel status is failed — replaces the {@see \html_table} in failed.php.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class failed_table extends base_backadel_table {

    /**
     * Adds status, last modified, and row actions.
     *
     * @return void
     */
    #[\Override]
    protected function define_base_columns(): void {
        parent::define_base_columns();
        $this->define_columns(array_merge(array_keys($this->columns), ['status', 'timemodified', 'actions']));
        $this->define_headers(array_merge($this->headers, [
            get_string('table_col_status', 'block_backadel'),
            get_string('table_col_modified', 'block_backadel'),
            get_string('table_col_actions', 'block_backadel'),
        ]));
        $this->no_sorting('actions');
    }

    /**
     * Builds the query for failed backup rows.
     *
     * @return void
     */
    #[\Override]
    protected function setup_sql(): void {
        global $PAGE;

        $this->define_baseurl($PAGE->url);

        // Timemodified comes from the course row until block_backadel_statuses tracks updates (MD-2189 schema).
        $fields = 'co.id, co.shortname, co.fullname, cat.name AS category, ba.status, '
            . 'co.timemodified AS timemodified';
        $from = '{course} co '
            . 'JOIN {course_categories} cat ON cat.id = co.category '
            . 'JOIN {block_backadel_statuses} ba ON ba.coursesid = co.id';
        $where = 'ba.status = :failed_status';
        $params = ['failed_status' => 'FAIL'];

        $this->set_sql($fields, $from, $where, $params);
    }

    /**
     * Placeholder control for Phase 2 re-queue modal wiring.
     *
     * @param stdClass $row
     * @return string
     */
    #[\Override]
    public function col_actions(stdClass $row): string {
        if ($this->is_downloading()) {
            return '';
        }
        $courseid = (int) ($row->id ?? 0);
        return html_writer::tag('a', get_string('results_action_requeue', 'block_backadel'), [
            'href' => '#',
            'class' => 'btn btn-sm btn-warning',
            'data-action' => 'requeue',
            'data-courseid' => (string) $courseid,
        ]);
    }
}
