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
 * External function: get current state of the migrate_filesystem_adhoc task.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_backadel\external;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class get_migrate_status extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    public static function execute(): array {
        global $DB;

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/backadel:managemigration', $context);

        // A task with timestarted set older than 30 minutes is considered stale (process crashed).
        // Treat stale tasks as idle so the spinner does not hang indefinitely.
        $stalecutoff = time() - 1800;

        $tasks = $DB->get_records_select(
            'task_adhoc',
            $DB->sql_compare_text('classname') . ' = ' . $DB->sql_compare_text(':cls'),
            ['cls' => '\\block_backadel\\task\\migrate_filesystem_adhoc'],
            '',
            'id, timestarted'
        );

        $active = false;
        foreach ($tasks as $task) {
            // Count as active only if not yet started, or started within the last 30 minutes.
            if ($task->timestarted === null || (int)$task->timestarted >= $stalecutoff) {
                $active = true;
                break;
            }
        }

        return [
            'status'          => $active ? 'queued' : 'idle',
            'catalogue_count' => (int) $DB->count_records('block_backadel_catalogue'),
            'courses_count'   => (int) $DB->count_records('block_backadel_courses'),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'          => new external_value(PARAM_ALPHA, 'idle or queued'),
            'catalogue_count' => new external_value(PARAM_INT,   'Catalogue row count'),
            'courses_count'   => new external_value(PARAM_INT,   'Indexed courses count'),
        ]);
    }
}
