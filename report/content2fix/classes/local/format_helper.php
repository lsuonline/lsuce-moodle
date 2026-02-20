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

namespace report_content2fix\local;

use report_content2fix\task\format_all_html_task;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for queueing and applying HTML formatting actions.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_helper {
    /**
     * Decide whether the "format filtered entries" task can be queued.
     *
     * @param bool $isadmin User is site admin.
     * @param bool $canfixfiltered User has report/content2fix:fixfiltered.
     * @param int $filtereddistinctcoursecount Distinct courses in filtered dataset.
     * @return bool
     */
    public static function can_queue_filtered_format(
        bool $isadmin,
        bool $canfixfiltered,
        int $filtereddistinctcoursecount
    ): bool {
        if (!$isadmin) {
            return false;
        }

        return $canfixfiltered || ($filtereddistinctcoursecount === 1);
    }

    /**
     * Queue adhoc task to format entries matching current report filters.
     *
     * @param array $filtervalues Report filter values.
     */
    public static function queue_filtered_format_task(array $filtervalues): void {
        $task = new format_all_html_task();
        $task->set_custom_data((object) ['filtervalues' => $filtervalues]);
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Format and persist one report entry.
     *
     * @param int $id report_content2fix.id
     * @return stdClass|null Entry that was changed, or null if not found/unchanged.
     */
    public static function format_single_entry(int $id): ?stdClass {
        global $DB;

        $entry = $DB->get_record('report_content2fix', ['id' => $id]);
        if (!$entry) {
            return null;
        }

        $changed = html_formatter::format_and_persist_entry($entry);
        return $changed ? $entry : null;
    }
}
