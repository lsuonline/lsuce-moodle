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

namespace block_backadel\task;

defined('MOODLE_INTERNAL') || die();

use block_backadel\local\migrator;

/**
 * Scheduled migration of configured filesystem directories into the Backadel catalogue.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_filesystem extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_migrate_filesystem', 'block_backadel');
    }

    public function execute(): void {
        global $CFG;

        // The admin setting block_backadel/path is a relative path (e.g. /backadel/) stored
        // relative to $CFG->dataroot.  Concatenate to get the absolute scan root.
        $relpath = get_config('block_backadel', 'path');
        if ($relpath === false || trim((string) $relpath) === '') {
            mtrace('block_backadel migrate_filesystem: backup path (block_backadel/path) not configured; skipping.');
            return;
        }
        $rootdir = rtrim($CFG->dataroot, '/') . '/' . ltrim(trim((string) $relpath), '/');

        $legacyraw = get_config('block_backadel', 'migration_extra_paths');
        $legacylines = [];
        if (is_string($legacyraw) && $legacyraw !== '') {
            $split = preg_split('/\R/', $legacyraw);
            $legacylines = is_array($split) ? $split : [];
        }

        $runs = [];
        $runs[] = [
            'dir' => $rootdir,
            'source' => 'backadel_current',
        ];

        foreach ($legacylines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $runs[] = [
                'dir' => $line,
                'source' => 'legacy_moodleus',
            ];
        }

        $migrator = new migrator();

        foreach ($runs as $run) {
            try {
                $inserted = $migrator->migrate_directory($run['dir'], $run['source']);
                mtrace(sprintf(
                    'block_backadel migrate_filesystem: migrated %d rows from %s (%s)',
                    $inserted,
                    $run['dir'],
                    $run['source'],
                ));
            } catch (\Throwable $e) {
                mtrace('block_backadel migrate_filesystem: error processing ' . $run['dir'] . ': ' . $e->getMessage());
            }
        }
    }
}
