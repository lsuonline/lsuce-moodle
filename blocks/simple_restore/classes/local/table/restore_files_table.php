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

namespace block_simple_restore\local\table;

defined('MOODLE_INTERNAL') || die();

use flexible_table;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Flexible table listing semester (Backadel filesystem) backup files for Simple Restore.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_files_table extends flexible_table {

    /** @var int Course id (target restore course). */
    private int $courseid;

    /** @var string Course short name (passed for future use / filtering context). */
    private string $shortname;

    /** @var int Restore-to selector forwarded to the modal trigger. */
    private int $restoreto;

    /** @var stdClass[] Normalized row objects for paging/output. */
    private array $filerows = [];

    /**
     * @param string $uniqueid Stable table id (session preferences key).
     * @param int $courseid Target course id.
     * @param string $shortname Course short name.
     * @param int $restoreto Restore-to selector (0 = current course, 2 = system / archive).
     */
    public function __construct(string $uniqueid, int $courseid, string $shortname, int $restoreto = 0) {
        parent::__construct($uniqueid);
        $this->courseid = $courseid;
        $this->shortname = $shortname;
        $this->restoreto = $restoreto;

        $this->define_columns([
            'year', 'semester', 'dept', 'filename',
            'coursetype', 'status',
            'filesize', 'modified', 'action',
        ]);
        $this->define_headers([
            get_string('table_col_year',       'block_simple_restore'),
            get_string('table_col_semester',   'block_simple_restore'),
            get_string('table_col_dept',       'block_simple_restore'),
            get_string('table_col_filename',   'block_simple_restore'),
            get_string('table_col_coursetype', 'block_simple_restore'),
            get_string('table_col_status',     'block_simple_restore'),
            get_string('table_col_filesize',   'block_simple_restore'),
            get_string('table_col_modified',   'block_simple_restore'),
            get_string('table_col_action',     'block_simple_restore'),
        ]);
        $this->sortable(false);
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable table-sm w-100');
        // Hide lower-priority columns on small screens.
        $this->column_class('year',     'd-none d-sm-table-cell');
        $this->column_class('dept',     'd-none d-md-table-cell');
        $this->column_class('status',   'd-none d-xl-table-cell');
        $this->column_class('filesize', 'd-none d-lg-table-cell');
        $this->column_class('modified', 'd-none d-lg-table-cell');
    }

    /**
     * Load row data from filesystem descriptors (arrays or objects).
     *
     * Expected keys per row: filename, size (optional), filesize (optional), modified or timemodified, filepath (optional).
     * When $source is 'catalogue', rows are expected to carry an integer id which is stored as catalogue_id.
     *
     * @param array  $files  Raw file rows from {@see simple_restore_utils::backadel_backups()} or catalogue query.
     * @param string $source Source identifier: 'semester_backadel' (filesystem) or 'catalogue' (DB catalogue).
     */
    public function populate(array $files, string $source = 'semester_backadel'): void {
        $this->filerows = [];
        foreach ($files as $file) {
            $row = is_array($file) ? (object) $file : $file;
            $normalized = new stdClass();
            $normalized->filename   = (string) ($row->filename ?? '');
            $normalized->filepath   = (string) ($row->filepath ?? $normalized->filename);
            $normalized->size       = $row->size ?? null;
            $normalized->filesize   = $row->filesize ?? null;
            $normalized->modified   = (int) ($row->modified ?? $row->timemodified ?? 0);
            // Catalogue metadata (empty string for semester_backadel rows — renders as em-dash).
            $normalized->year       = (string) ($row->year ?? '');
            $normalized->semester   = (string) ($row->semester ?? '');
            $normalized->dept       = (string) ($row->dept ?? '');
            $normalized->coursetype = (string) ($row->coursetype ?? '');
            $normalized->status     = (string) ($row->status ?? '');
            $normalized->pattern    = (string) ($row->pattern ?? '');
            // For catalogue rows the integer row ID is passed as $row->id.
            $rawid = $row->id ?? 0;
            $normalized->catalogue_id = ($source === 'catalogue' && is_int($rawid) && $rawid > 0)
                ? $rawid : 0;
            $this->filerows[] = $normalized;
        }
    }

    /**
     * Build a table from the Backadel catalogue rows (available backups for a course shortname).
     *
     * @param string $uniqueid Stable table id (session preferences key).
     * @param int $courseid Target course id.
     * @param string $shortname Course short name (matched against catalogue.shortname).
     * @param int $restoreto Restore-to selector (0 = current course, 2 = system / archive).
     */
    public static function from_catalogue(string $uniqueid, int $courseid, string $shortname, int $restoreto = 0): self {
        global $DB;

        $table = new self($uniqueid, $courseid, $shortname, $restoreto);

        if (get_config('block_backadel', 'catalogue_fallback_scandir')) {
            // Fallback: populate from filesystem e.g. simple_restore_utils::backadel_backups() matching $shortname.
            $table->populate([]);
            return $table;
        }

        $likesql = $DB->sql_like('shortname', ':sn', false, true, false);
        $sql = "SELECT filename, filepath, filepath_full, file_size, backup_ts
                  FROM {block_backadel_catalogue}
                 WHERE $likesql AND status = :st
              ORDER BY backup_ts DESC";
        $records = $DB->get_records_sql($sql, ['sn' => $DB->sql_like_escape($shortname), 'st' => 'available']);

        $files = [];
        if ($records) {
            foreach ($records as $row) {
                $filepath = '';
                if (isset($row->filepath_full) && (string) $row->filepath_full !== '') {
                    $filepath = (string) $row->filepath_full;
                } else {
                    $filepath = (string) ($row->filepath ?? '');
                }
                $files[] = (object) [
                    'filename' => (string) ($row->filename ?? ''),
                    'filesize' => $row->file_size ?? 0,
                    'modified' => (int) ($row->backup_ts ?? 0),
                    'filepath' => $filepath,
                ];
            }
        }

        $table->populate($files);
        return $table;
    }

    /**
     * @param stdClass $row
     * @return string Plain filename (restore link is in the action column).
     */
    public function col_filename(stdClass $row): string {
        return s($row->filename ?? '');
    }

    /**
     * @param stdClass $row
     * @return string Academic year (e.g. 2022) or em-dash for non-catalogue rows.
     */
    public function col_year(stdClass $row): string {
        $v = (string) ($row->year ?? '');
        return $v !== '' ? s($v) : html_writer::tag('span', '—', ['class' => 'text-muted']);
    }

    /**
     * @param stdClass $row
     * @return string Semester name or em-dash for non-catalogue rows.
     */
    public function col_semester(stdClass $row): string {
        $v = (string) ($row->semester ?? '');
        return $v !== '' ? format_string($v) : html_writer::tag('span', '—', ['class' => 'text-muted']);
    }

    /**
     * @param stdClass $row
     * @return string Department code or em-dash for non-catalogue rows.
     */
    public function col_dept(stdClass $row): string {
        $v = (string) ($row->dept ?? '');
        return $v !== '' ? format_string($v) : html_writer::tag('span', '—', ['class' => 'text-muted']);
    }

    /**
     * @param stdClass $row
     * @return string Bootstrap badge for course type, or em-dash if unknown.
     */
    public function col_coursetype(stdClass $row): string {
        $type = strtolower((string) ($row->coursetype ?? ''));
        if ($type === '') {
            return html_writer::tag('span', '—', ['class' => 'text-muted']);
        }
        $label = match ($type) {
            'teaching'  => get_string('coursetype_teaching',  'block_backadel'),
            'blueprint' => get_string('coursetype_blueprint', 'block_backadel'),
            'other'     => get_string('coursetype_other',     'block_backadel'),
            default     => format_string($type),
        };
        $badge = match ($type) {
            'blueprint' => 'badge bg-info text-white',
            'teaching'  => 'badge bg-secondary text-white',
            'other'     => 'badge bg-light text-dark border',
            default     => 'badge bg-secondary text-white',
        };
        return html_writer::tag('span', $label, ['class' => $badge]);
    }

    /**
     * @param stdClass $row
     * @return string Bootstrap badge for backup status, or em-dash if absent.
     */
    public function col_status(stdClass $row): string {
        $status = strtolower((string) ($row->status ?? ''));
        if ($status === '') {
            return html_writer::tag('span', '—', ['class' => 'text-muted']);
        }
        [$badge, $key] = match ($status) {
            'available' => ['badge bg-success text-white',   'status_available'],
            'missing'   => ['badge bg-warning text-dark',    'status_missing'],
            'archived'  => ['badge bg-secondary text-white', 'status_archived'],
            default     => ['badge bg-secondary text-white', null],
        };
        $label = $key !== null
            ? get_string($key, 'block_simple_restore')
            : format_string($status);
        return html_writer::tag('span', $label, ['class' => $badge]);
    }

    /**
     * @param stdClass $row
     * @return string Human-readable size.
     */
    public function col_filesize(stdClass $row): string {
        $bytes = (int) ($row->size ?? $row->filesize ?? 0);
        return display_size($bytes);
    }

    /**
     * @param stdClass $row
     * @return string Localised modified date/time.
     */
    public function col_modified(stdClass $row): string {
        $ts = (int) ($row->modified ?? $row->timemodified ?? 0);
        return userdate($ts);
    }

    /**
     * @param stdClass $row
     * @return string Restore control opening the confirm modal.
     */
    public function col_action(stdClass $row): string {
        $filename = (string) ($row->filename ?? '');
        $catalogueid = (int) ($row->catalogue_id ?? 0);
        $attrs = [
            'class'           => 'btn btn-primary',
            'data-action'     => 'restore-confirm',
            'data-courseid'   => (string) $this->courseid,
            'data-filename'   => $filename,
            'data-restore-to' => (string) $this->restoreto,
        ];
        if ($catalogueid > 0) {
            $attrs['data-catalogue-id'] = (string) $catalogueid;
        }
        $restorelink = html_writer::link('#', get_string('restore_action', 'block_simple_restore'), $attrs);

        if ($catalogueid > 0) {
            $downloadurl = new moodle_url('/blocks/backadel/download.php', [
                'fileid'   => $catalogueid,
                'courseid' => $this->courseid,
                'sesskey'  => sesskey(),
            ]);
            $downloadlink = html_writer::link(
                $downloadurl,
                get_string('download_action', 'block_simple_restore'),
                ['class' => 'btn btn-sm btn-secondary ms-3']
            );
            return $restorelink . $downloadlink;
        }

        return $restorelink;
    }

    /**
     * Run setup, emit up to $pagesize rows for the current page, and finish the table.
     *
     * @param int $pagesize Rows per page.
     */
    public function setup_and_out(int $pagesize): void {
        global $PAGE;
        $this->define_baseurl($PAGE->url);
        $total = count($this->filerows);
        $this->pagesize($pagesize, $total);
        $this->setup();

        $start = (int) $this->get_page_start();
        $slice = array_slice($this->filerows, $start, $pagesize);

        foreach ($slice as $row) {
            $formattedrow = $this->format_row($row);
            $this->add_data_keyed($formattedrow);
        }

        $this->finish_output();
    }
}
