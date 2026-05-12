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

use context_system;
use core_text;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Paginated Backadel backup catalogue listing ({@see \table_sql}).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue_table extends base_backadel_table {

    /** @var array|null Filter values ({@see \backadel_catalogue_to_where()}) */
    private ?array $filters;

    /**
     * @param string $uniqueid Stable id for this table (session URL params).
     * @param array|null $filters Optional filter array with keys q, year, semester, status, source, pattern.
     */
    public function __construct(string $uniqueid, ?array $filters = null) {
        $this->filters = $filters;
        parent::__construct($uniqueid);
        $this->sortable(true, 'backup_ts', SORT_DESC);
        $this->no_sorting('pattern');
        $this->no_sorting('actions');
    }

    #[\Override]
    protected function define_base_columns(): void {
        $this->define_columns([
            'filename',
            'shortname',
            'semester',
            'dept',
            'course_num',
            'source',
            'backup_ts',
            'file_size',
            'status',
            'pattern',
            'actions',
        ]);
        $this->define_headers([
            get_string('table_col_filename', 'block_backadel'),
            get_string('table_col_fullname', 'block_backadel'),
            get_string('catalogue_col_semester', 'block_backadel'),
            get_string('catalogue_col_dept', 'block_backadel'),
            get_string('catalogue_col_course_num', 'block_backadel'),
            get_string('catalogue_col_source', 'block_backadel'),
            get_string('catalogue_col_backup_date', 'block_backadel'),
            get_string('catalogue_col_size', 'block_backadel'),
            get_string('table_col_status', 'block_backadel'),
            get_string('catalogue_col_pattern', 'block_backadel'),
            get_string('catalogue_col_actions', 'block_backadel'),
        ]);
    }

    #[\Override]
    protected function setup_sql(): void {
        [$where, $params] = \backadel_catalogue_to_where($this->filters ?? []);

        $fields = 'c.id, c.filename, c.shortname, c.semester, c.dept, c.course_num, '
            . 'c.source, c.backup_ts, c.file_size, c.status, c.pattern, c.year, '
            . 'c.instructors, c.filepath_full, '
            . '(SELECT bc.courseid FROM {block_backadel_courses} bc '
            . 'WHERE bc.filename = c.filename AND bc.courseid IS NOT NULL '
            . 'ORDER BY bc.id DESC LIMIT 1) AS catalogued_courseid, '
            . 'COALESCE('
            . '(SELECT co.id FROM {course} co WHERE co.id = ('
            . 'SELECT bc2.courseid FROM {block_backadel_courses} bc2 '
            . 'WHERE bc2.filename = c.filename AND bc2.courseid IS NOT NULL '
            . 'ORDER BY bc2.id DESC LIMIT 1)), '
            . '(SELECT co3.id FROM {course} co3 '
            . 'WHERE co3.shortname = c.shortname AND c.shortname IS NOT NULL AND c.shortname <> \'\' '
            . 'ORDER BY co3.id ASC LIMIT 1)'
            . ') AS live_courseid';

        $from = '{block_backadel_catalogue} c';

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql('SELECT COUNT(1) FROM {block_backadel_catalogue} c WHERE ' . $where, $params);
    }

    #[\Override]
    public function get_sql_sort(): string {
        global $DB;

        $sort = parent::get_sql_sort();
        if ($sort !== '' && stripos($sort, 'backup_ts') !== false) {
            return $sort . ', ' . $DB->sql_order_by_null('filename');
        }
        return $sort;
    }

    /**
     * Basename of the backup file.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_filename(stdClass $row): string {
        $filename = (string) ($row->filename ?? '');
        if ($this->is_downloading()) {
            return $filename;
        }
        if (core_text::strlen($filename) <= 45) {
            return format_string($filename);
        }
        $display = core_text::substr($filename, 0, 22) . '…' . core_text::substr($filename, -20);
        return html_writer::tag('span', format_string($display), [
            'title'          => $filename,
            'data-toggle'    => 'tooltip',
            'data-placement' => 'top',
        ]);
    }

    #[\Override]
    public function col_shortname(stdClass $row): string {
        $name = (string) ($row->shortname ?? '');
        $display = $name !== '' ? $name : '—';
        return format_string($display);
    }

    /**
     * @param stdClass $row
     * @return string
     */
    public function col_semester(stdClass $row): string {
        $v = $row->semester ?? '';
        $display = ($v !== null && $v !== '') ? (string) $v : '—';
        return format_string($display);
    }

    /**
     * @param stdClass $row
     * @return string
     */
    public function col_dept(stdClass $row): string {
        $v = $row->dept ?? '';
        $display = ($v !== null && $v !== '') ? (string) $v : '—';
        return format_string($display);
    }

    /**
     * @param stdClass $row
     * @return string
     */
    public function col_course_num(stdClass $row): string {
        $v = $row->course_num ?? '';
        $display = ($v !== null && $v !== '') ? (string) $v : '—';
        return format_string($display);
    }

    /**
     * Catalogue row source (legacy vs current).
     *
     * @param stdClass $row
     * @return string
     */
    public function col_source(stdClass $row): string {
        $src = format_string((string) ($row->source ?? ''));
        if ($this->is_downloading()) {
            return $src;
        }
        return html_writer::tag('span', $src, ['class' => 'badge bg-info text-white']);
    }

    /**
     * Parsed backup timestamp from filename.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_backup_ts(stdClass $row): string {
        $ts = (int) ($row->backup_ts ?? 0);
        if ($ts <= 0) {
            return '—';
        }
        return userdate($ts);
    }

    /**
     * File size on disk when known.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_file_size(stdClass $row): string {
        if ($row->file_size === null) {
            return '';
        }
        return display_size((int) $row->file_size);
    }

    #[\Override]
    public function col_status(stdClass $row): string {
        $status = strtolower((string) ($row->status ?? ''));
        [$badgeclasses, $stringkey] = match ($status) {
            'available' => ['badge bg-success text-white', 'catalogue_status_available'],
            'missing' => ['badge bg-warning text-dark', 'catalogue_status_missing'],
            'archived' => ['badge bg-secondary text-white', 'catalogue_status_archived'],
            default => ['badge bg-secondary text-white', null],
        };
        if ($stringkey !== null) {
            $label = get_string($stringkey, 'block_backadel');
        } else if ($status !== '') {
            $label = format_string($status);
        } else {
            $label = get_string('none');
        }
        if ($this->is_downloading()) {
            return $label;
        }
        return html_writer::tag('span', $label, ['class' => $badgeclasses]);
    }

    /**
     * Filename parser pattern key.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_pattern(stdClass $row): string {
        $pattern = (string) ($row->pattern ?? '');
        if ($this->is_downloading()) {
            return $pattern;
        }
        return html_writer::tag('small', format_string($pattern), ['class' => 'text-muted']);
    }

    /**
     * Row actions: restore, live course link, instructors modal, download.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_actions(stdClass $row): string {
        if ($this->is_downloading()) {
            return '';
        }

        $syscontext = context_system::instance();
        $canmanage = has_capability('block/backadel:managebackups', $syscontext);

        $status = strtolower((string) ($row->status ?? ''));
        $fragments = [];

        if ($canmanage) {
            $year = isset($row->year) ? (int) $row->year : (int) date('Y');
            $restoreparams = [
                'action'  => 'stage',
                'fileid'  => (int) $row->id,
                'year'    => $year,
                'sesskey' => sesskey(),
            ];
            $restoreurl = new moodle_url('/blocks/backadel/restore.php', $restoreparams);
            if ($status === 'available') {
                $fragments[] = html_writer::link(
                    $restoreurl,
                    get_string('catalogue_action_restore', 'block_backadel'),
                    ['class' => 'btn btn-sm btn-primary']
                );
            } else {
                $fragments[] = html_writer::tag(
                    'span',
                    get_string('catalogue_action_restore_disabled', 'block_backadel'),
                    [
                        'class'             => 'btn btn-sm btn-secondary disabled',
                        'role'              => 'button',
                        'aria-disabled'     => 'true',
                        'data-bs-toggle'    => 'tooltip',
                        'data-bs-placement' => 'top',
                        'title'             => get_string('catalogue_restore_disabled_tooltip', 'block_backadel'),
                    ]
                );
            }
        }

        $livecourseid = isset($row->live_courseid) ? (int) $row->live_courseid : 0;
        if ($livecourseid > 0) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $livecourseid]);
            $fragments[] = html_writer::link(
                $courseurl,
                get_string('catalogue_action_goto_course', 'block_backadel'),
                ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']
            );
        }

        if ($canmanage) {
            $pairs = $this->resolve_instructor_pairs_for_row($row);
            if ($pairs !== []) {
                $json = json_encode($pairs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                $fragments[] = html_writer::tag(
                    'button',
                    get_string('catalogue_action_instructors', 'block_backadel'),
                    [
                        'type'               => 'button',
                        'class'              => 'btn btn-sm btn-outline-info',
                        'data-action'        => 'show-instructors',
                        'data-instructors'   => $json,
                        'data-catalogue-id'  => (string) ((int) $row->id),
                    ]
                );
            }

            if ($status === 'available') {
                $downloadurl = new moodle_url('/blocks/backadel/download.php', [
                    'fileid'  => (int) $row->id,
                    'sesskey' => sesskey(),
                ]);
                $fragments[] = html_writer::link(
                    $downloadurl,
                    get_string('catalogue_action_download', 'block_backadel'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );
            }
        }

        if ($fragments === []) {
            return '';
        }

        return html_writer::div(implode(' ', $fragments), 'd-flex flex-wrap gap-1 catalogue-actions');
    }

    /**
     * Decode catalogue instructors JSON and resolve Moodle full names.
     *
     * @param stdClass $row Catalogue row with optional instructors property.
     * @return array<int, array{username: string, fullname: string}>
     */
    private function resolve_instructor_pairs_for_row(stdClass $row): array {
        global $DB;

        $raw = $row->instructors ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $ordered = [];
        $seen = [];
        foreach ($decoded as $item) {
            if (!is_string($item) || trim($item) === '') {
                continue;
            }
            $lc = core_text::strtolower($item);
            if (isset($seen[$lc])) {
                continue;
            }
            $seen[$lc] = true;
            $ordered[] = $item;
        }

        if ($ordered === []) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($ordered, SQL_PARAMS_NAMED, 'inu');
        $namefields = implode(', ', \core_user\fields::get_name_fields());
        $sql = "SELECT id, username, $namefields FROM {user} WHERE deleted = 0 AND username $insql";
        $records = $DB->get_records_sql($sql, $params);

        $bylcname = [];
        foreach ($records as $u) {
            $bylcname[core_text::strtolower($u->username)] = $u;
        }

        $pairs = [];
        foreach ($decoded as $item) {
            if (!is_string($item) || trim($item) === '') {
                continue;
            }
            $lc = core_text::strtolower($item);
            if (isset($bylcname[$lc])) {
                $obj = $bylcname[$lc];
                $pairs[] = [
                    'username' => $obj->username,
                    'fullname' => fullname($obj),
                ];
            } else {
                $pairs[] = [
                    'username' => $item,
                    'fullname' => $item,
                ];
            }
        }

        return $pairs;
    }
}
