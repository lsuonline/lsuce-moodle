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
 * External function: set manual catalogue course type override.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\external;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_text;
use stdClass;

/**
 * Persist a manual course type on a catalogue row (or clear it).
 */
class set_coursetype_override extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'catalogueid' => new external_value(PARAM_INT, 'block_backadel_catalogue.id'),
            // Use PARAM_TEXT so empty string (clear override) is accepted; allowed values enforced in execute().
            'coursetype' => new external_value(PARAM_TEXT, 'New type: teaching | blueprint | other | (empty to clear)'),
            'note' => new external_value(PARAM_TEXT, 'Optional admin note', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'catalogueid' => new external_value(PARAM_INT, 'Row id echoed back'),
            'effective_type' => new external_value(PARAM_TEXT, 'Effective type after save (may be empty string if undetermined)'),
            'overridden' => new external_value(PARAM_BOOL, 'True when a manual override is now active'),
            'note' => new external_value(PARAM_TEXT, 'Saved note (empty string if none)', VALUE_OPTIONAL),
        ]);
    }

    /**
     * @param int $catalogueid
     * @param string $coursetype
     * @param string $note
     * @return array{catalogueid: int, effective_type: string, overridden: bool, note?: string}
     */
    public static function execute(int $catalogueid, string $coursetype, string $note = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'catalogueid' => $catalogueid,
            'coursetype' => $coursetype,
            'note' => $note,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/backadel:managebackups', $context);

        $ctype = strtolower(trim((string) $params['coursetype']));
        if (!in_array($ctype, ['teaching', 'blueprint', 'other', ''], true)) {
            throw new \invalid_parameter_exception(get_string('catalogue_override_invalid_type', 'block_backadel'));
        }

        $notetrim = trim((string) $params['note']);
        if (core_text::strlen($notetrim) > 1024) {
            throw new \invalid_parameter_exception(get_string('catalogue_override_note_too_long', 'block_backadel'));
        }

        $row = $DB->get_record('block_backadel_catalogue', ['id' => $params['catalogueid']], '*', MUST_EXIST);

        $update = new stdClass();
        $update->id = $row->id;
        if ($ctype !== '') {
            $update->coursetype_override = $ctype;
            $update->coursetype_override_note = ($notetrim !== '') ? $notetrim : null;
            $update->coursetype_override_by = $USER->id;
            $update->coursetype_override_ts = time();
        } else {
            $update->coursetype_override = null;
            $update->coursetype_override_note = null;
            $update->coursetype_override_by = null;
            $update->coursetype_override_ts = null;
        }
        $update->timemodified = time();
        $DB->update_record('block_backadel_catalogue', $update);

        $overridden = ($ctype !== '');
        if ($overridden) {
            $effectivetype = $ctype;
        } else {
            $effectivetype = self::auto_coursetype_for_filename((string) $row->filename);
        }

        $return = [
            'catalogueid' => (int) $row->id,
            'effective_type' => $effectivetype,
            'overridden' => $overridden,
        ];

        if ($overridden) {
            $return['note'] = $notetrim;
        }

        return $return;
    }

    /**
     * Latest auto-detected course type from {@see block_backadel_courses} for a filename.
     *
     * @param string $filename
     * @return string Lowercase type or '' if none.
     */
    private static function auto_coursetype_for_filename(string $filename): string {
        global $DB;

        if ($filename === '') {
            return '';
        }

        $rec = $DB->get_record_sql(
            "SELECT coursetype FROM {block_backadel_courses} WHERE filename = :fn ORDER BY id DESC",
            ['fn' => $filename],
            IGNORE_MULTIPLE
        );

        if (!$rec || $rec->coursetype === null) {
            return '';
        }

        return strtolower((string) $rec->coursetype);
    }
}
