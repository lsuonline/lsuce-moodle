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

use html_writer;
use stdClass;

/**
 * Paginated Backadel course search results (table_sql).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results_table extends base_backadel_table {

    /** @var array|null Filter values from the search form ({@see \backadel_search_to_where()}) */
    private ?array $filters;

    /**
     * @param string $uniqueid Stable id for this table (session URL params).
     * @param array|null $filters Optional filter array with keys q, category, status, coursetype, semester.
     */
    public function __construct(string $uniqueid, ?array $filters = null) {
        $this->filters = $filters;
        parent::__construct($uniqueid);
    }

    #[\Override]
    protected function define_base_columns(): void {
        parent::define_base_columns();
        $this->define_columns(array_merge(array_keys($this->columns), ['status', 'actions']));
        $this->define_headers(array_merge($this->headers, [
            get_string('table_col_status', 'block_backadel'),
            get_string('table_col_actions', 'block_backadel'),
        ]));
        $this->no_sorting('actions');
    }

    #[\Override]
    protected function setup_sql(): void {
        global $PAGE;

        $this->define_baseurl($PAGE->url);

        [$where, $params] = \backadel_search_to_where($this->filters ?? []);

        // Correlated subquery picks the most-recent status row per course, avoiding
        // duplicate id values that would crash get_records_sql() when a course has
        // multiple rows in block_backadel_statuses.
        $fields = 'co.id, co.shortname, co.fullname, cat.name AS category, '
            . '(SELECT ba.status FROM {block_backadel_statuses} ba '
            . ' WHERE ba.coursesid = co.id ORDER BY ba.id DESC LIMIT 1) AS status';
        $from = '{course} co '
            . 'JOIN {course_categories} cat ON cat.id = co.category';

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql(
            'SELECT COUNT(1) FROM {course} co '
                . 'JOIN {course_categories} cat ON cat.id = co.category WHERE ' . $where,
            $params
        );
    }

    #[\Override]
    public function col_actions(stdClass $row): string {
        if ($this->is_downloading()) {
            return '';
        }
        $courseid = (int) ($row->id ?? 0);
        if ($courseid < 2) {
            return '';
        }

        $raw = $row->status ?? null;
        $status = ($raw === null || $raw === '') ? null : strtoupper((string) $raw);

        if ($status === null || $status === 'DELETED') {
            return html_writer::tag('button', get_string('results_action_queue', 'block_backadel'), [
                'class' => 'btn btn-sm btn-primary',
                'type' => 'button',
                'data-action' => 'queue',
                'data-courseid' => (string) $courseid,
            ]);
        }
        if ($status === 'BACKUP') {
            return '';
        }
        if ($status === 'SUCCESS') {
            return html_writer::tag('button', get_string('results_action_delete', 'block_backadel'), [
                'class' => 'btn btn-sm btn-danger',
                'type' => 'button',
                'data-action' => 'delete',
                'data-courseid' => (string) $courseid,
            ]);
        }
        if ($status === 'FAIL') {
            return html_writer::tag('button', get_string('results_action_requeue', 'block_backadel'), [
                'class' => 'btn btn-sm btn-warning',
                'type' => 'button',
                'data-action' => 'requeue',
                'data-courseid' => (string) $courseid,
            ]);
        }
        return '';
    }
}
