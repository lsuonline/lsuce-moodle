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
 * External function: return live migration status + backup counts for the block widget.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// externallib.php (Moodle 3.x shim) is intentionally omitted —
// core_external\external_api (Moodle 4+) is autoloaded.

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Returns the current migration / backup status for the Backadel sidebar widget.
 */
class get_migration_status extends external_api {

    /**
     * Parameters definition: no inputs.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Return pending/failed backup counts and the current "running" state.
     *
     * @return array
     */
    public static function execute(): array {
        global $DB;

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/backadel:managebackups', $context);

        $pending = (int) $DB->count_records_select('block_backadel_statuses', "status='SUCCESS'");
        $failed  = (int) $DB->count_records_select('block_backadel_statuses', "status='FAIL'");

        $running   = get_config('block_backadel', 'running');
        $isrunning = !empty($running);
        $elapsed   = '';
        if ($isrunning) {
            $secs = (int) round(time() - (int) $running);
            $elapsed = self::seconds_to_human($secs);
        }

        return [
            'pending_count' => $pending,
            'failed_count'  => $failed,
            'is_running'    => $isrunning,
            'elapsed_human' => $elapsed,
        ];
    }

    /**
     * Returns definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'pending_count' => new external_value(PARAM_INT,  'Successful (pending-delete) backup count'),
            'failed_count'  => new external_value(PARAM_INT,  'Failed backup count'),
            'is_running'    => new external_value(PARAM_BOOL, 'Whether a migration / backup job is currently running'),
            'elapsed_human' => new external_value(PARAM_TEXT, 'Human-readable elapsed time since the job started'),
        ]);
    }

    /**
     * Convert a number of seconds to a human-readable string.
     * Duplicates block_backadel::seconds2human() to avoid loading the
     * non-namespaced block class (which requires block_base infrastructure).
     *
     * @param int $secondsrun
     * @return string
     */
    private static function seconds_to_human(int $secondsrun): string {
        $months = (int) floor($secondsrun / 2592000);
        $days   = (int) floor(($secondsrun % 2592000) / 86400);
        $hours  = (int) floor(($secondsrun % 86400) / 3600);
        $mins   = (int) floor(($secondsrun % 3600) / 60);
        $secs   = $secondsrun % 60;

        $parts = [];
        if ($months > 0) {
            $parts[] = $months . ' months';
        }
        if ($days > 0) {
            $parts[] = $days . ' days';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' hours';
        }
        if ($mins > 0) {
            $parts[] = $mins . ' minutes';
        }
        $parts[] = $secs . ' seconds';

        return implode(', ', $parts);
    }
}
