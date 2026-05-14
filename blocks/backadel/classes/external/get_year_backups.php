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
 * External function: list catalogue backups for a year.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\external;

use block_backadel\local\catalogue_allowlists;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use stdClass;

/**
 * Web service: catalogue rows for one catalogue year.
 */
class get_year_backups extends external_api {

    /**
     * Describe web service parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'year' => new external_value(PARAM_INT, 'Academic / catalogue year'),
            'source' => new external_value(PARAM_TEXT, 'Catalogue source filter', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Return schema: backups list wrapped in a single structure for the external API.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $rowstructure = new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Catalogue row id'),
            'filename' => new external_value(PARAM_TEXT, 'Backup file basename'),
            'shortname' => new external_value(PARAM_TEXT, 'Resolved course shortname', VALUE_OPTIONAL),
            'semester' => new external_value(PARAM_TEXT, 'Semester label', VALUE_OPTIONAL),
            'dept' => new external_value(PARAM_TEXT, 'Department code', VALUE_OPTIONAL),
            'course_num' => new external_value(PARAM_TEXT, 'Course number', VALUE_OPTIONAL),
            'source' => new external_value(PARAM_TEXT, 'Catalogue source key'),
            'backup_ts' => new external_value(PARAM_INT, 'Backup timestamp from filename'),
            'file_size' => new external_value(PARAM_INT, 'File size in bytes', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_TEXT, 'Row status'),
            'pattern' => new external_value(PARAM_TEXT, 'Filename parse pattern key'),
        ]);

        return new external_single_structure([
            'backups' => new external_multiple_structure($rowstructure, 'Catalogue backup rows'),
        ]);
    }

    /**
     * List catalogue entries for the given year (and optional source).
     *
     * @param int $year
     * @param string $source
     * @return array{backups: list<array<string, int|string|null>>}
     */
    public static function execute(int $year, string $source = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'year' => $year,
            'source' => $source,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/backadel:viewresults', $context);

        if ($params['source'] !== '' && !in_array($params['source'], catalogue_allowlists::VALID_SOURCES, true)) {
            throw new \invalid_parameter_exception('Invalid source value');
        }

        $sql = 'SELECT id, filename, shortname, semester, dept, course_num, source, backup_ts, file_size, status, pattern
                  FROM {block_backadel_catalogue}
                 WHERE year = :year';
        $queryparams = ['year' => $params['year']];

        if ($params['source'] !== '') {
            $sql .= ' AND source = :source';
            $queryparams['source'] = $params['source'];
        }

        $sql .= ' ORDER BY backup_ts DESC';

        $records = $DB->get_records_sql($sql, $queryparams, 0, 500);

        $backups = [];
        foreach ($records as $record) {
            $backups[] = self::export_row($record);
        }

        return ['backups' => $backups];
    }

    /**
     * Normalise one DB row for the web service payload.
     *
     * @param stdClass $record
     * @return array<string, int|string|null>
     */
    protected static function export_row(stdClass $record): array {
        $row = [
            'id' => (int) $record->id,
            'filename' => $record->filename,
            'source' => $record->source,
            'backup_ts' => (int) $record->backup_ts,
            'status' => $record->status,
            'pattern' => $record->pattern,
        ];

        if ($record->shortname !== null && $record->shortname !== '') {
            $row['shortname'] = $record->shortname;
        }
        if ($record->semester !== null && $record->semester !== '') {
            $row['semester'] = $record->semester;
        }
        if ($record->dept !== null && $record->dept !== '') {
            $row['dept'] = $record->dept;
        }
        if ($record->course_num !== null && $record->course_num !== '') {
            $row['course_num'] = $record->course_num;
        }
        if (isset($record->file_size) && $record->file_size !== null) {
            $row['file_size'] = (int) $record->file_size;
        }

        return $row;
    }
}
