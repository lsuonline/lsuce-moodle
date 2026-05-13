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

use block_backadel\local\instructor_resolver;
use stdClass;

/**
 * Periodically retries instructor resolution for unresolved teacher rows.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reresolve_teachers extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_reresolve_teachers', 'block_backadel');
    }

    public function execute(): void {
        global $DB;

        $sql = 'SELECT * FROM {block_backadel_teachers}
                 WHERE resolved = :resolved
              ORDER BY timecreated ASC';
        $rows = $DB->get_records_sql($sql, ['resolved' => 0], 0, 500);

        $resolver = new instructor_resolver();
        $resolvedcount = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $user = $resolver->resolve((string) $row->username);
            if ($user === null) {
                $skipped++;
                continue;
            }

            $update = new stdClass();
            $update->id = $row->id;
            $update->userid = (int) $user->id;
            $update->resolved = 1;
            $update->resolvedvia = 'username';

            $DB->update_record('block_backadel_teachers', $update);
            $resolvedcount++;
        }

        mtrace(sprintf(
            'block_backadel reresolve_teachers: resolved %d row(s), skipped %d row(s)',
            $resolvedcount,
            $skipped,
        ));
    }
}
