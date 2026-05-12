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
use moodle_url;
use stdClass;
use table_sql;

/**
 * Shared course-identity columns and status rendering for Backadel {@see \table_sql} tables.
 *
 * {@see \flexible_table} exposes {@see \flexible_table::$uniqueid} and {@see \flexible_table::$pagesize}
 * as public properties; this constructor sets {@see \flexible_table::$pagesize} to 30. Subclasses implement
 * {@see self::setup_sql()} and may override {@see self::define_base_columns()} to prepend or append columns.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_backadel_table extends table_sql {

    /**
     * Maps a normalized status code to a Bootstrap badge CSS class suffix (e.g. badge-warning).
     *
     * Keys are uppercase status tokens stored for display.
     */
    protected const STATUS_BADGE_MAP = [
        'BACKUP'  => 'badge-warning',
        'SUCCESS' => 'badge-success',
        'FAIL'    => 'badge-danger',
        'DELETED' => 'badge-secondary',
    ];

    /**
     * @param string $uniqueid Stable id for this table (session URL params).
     * @return void
     */
    public function __construct(string $uniqueid) {
        parent::__construct($uniqueid);
        $this->pagesize = 30;
        $this->sortable(true, null, SORT_ASC);
        $this->collapsible(false);
        $this->initialbars(false);
        $this->define_base_columns();
        $this->setup_sql();
    }

    /**
     * Course short name linked to the course view (CSV/export returns plain text).
     *
     * @param stdClass $row
     * @return string
     */
    public function col_shortname(stdClass $row): string {
        $name = (string) ($row->shortname ?? '');
        if ($this->is_downloading()) {
            return $name;
        }
        $courseid = (int) ($row->id ?? 0);
        if ($courseid <= 0) {
            return html_writer::span(format_string($name));
        }
        return html_writer::link(
            new moodle_url('/course/view.php', ['id' => $courseid]),
            format_string($name),
            ['title' => get_string('course')]
        );
    }

    /**
     * Registers the shared shortname, fullname, and category columns.
     *
     * @return void
     */
    protected function define_base_columns(): void {
        // Use 'coursefullname' not 'fullname' — flexible_table treats 'fullname' as a user profile
        // column and shows "First name / Last name" with the alpha-initial filter bar.
        $this->define_columns(['shortname', 'coursefullname', 'category']);
        $this->define_headers([
            get_string('table_col_shortname', 'block_backadel'),
            get_string('table_col_fullname', 'block_backadel'),
            get_string('table_col_category', 'block_backadel'),
        ]);
    }

    /**
     * Course full name (aliased as coursefullname in SQL to avoid flexible_table user-column magic).
     *
     * @param stdClass $row
     * @return string
     */
    public function col_coursefullname(stdClass $row): string {
        return format_string($row->fullname ?? '');
    }

    /**
     * Subclass hook: must call {@see self::set_sql()} (and optionally {@see self::set_count_sql()}).
     *
     * @return void
     */
    abstract protected function setup_sql(): void;

    /**
     * Render a status cell as a Bootstrap badge.
     *
     * @param stdClass $row Row from the query; must expose a `status` property (string).
     * @return string HTML fragment.
     */
    public function col_status(stdClass $row): string {
        $status = strtoupper((string) ($row->status ?? ''));
        $badgeclass = self::STATUS_BADGE_MAP[$status] ?? 'badge-secondary';
        $stringkey = match ($status) {
            'BACKUP'  => 'results_status_backup',
            'SUCCESS' => 'results_status_success',
            'FAIL'    => 'results_status_failed',
            'DELETED' => 'results_status_deleted',
            default   => null,
        };
        if ($stringkey !== null) {
            $label = get_string($stringkey, 'block_backadel');
        } else if ($status !== '') {
            $label = format_string($status);
        } else {
            $label = get_string('none');
        }
        return html_writer::tag('span', $label, ['class' => 'badge ' . $badgeclass]);
    }

    /**
     * Formats the last-modified cell for the current user.
     *
     * @param stdClass $row Row object exposing a `timemodified` property (unix timestamp or string).
     * @return string Localised date or an empty string when unset.
     */
    public function col_timemodified(stdClass $row): string {
        $raw = $row->timemodified ?? 0;
        $ts = is_string($raw) ? (int) strtotime($raw) : (int) $raw;
        if ($ts <= 0) {
            return '';
        }
        return userdate($ts);
    }

    /**
     * Row actions (buttons/links). Override in concrete tables.
     *
     * @param stdClass $row
     * @return string HTML fragment; empty by default.
     */
    public function col_actions(stdClass $row): string {
        return '';
    }
}
