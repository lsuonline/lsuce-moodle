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

    /**
     * Get the current content for a report entry from the source table.
     *
     * @param object $entry report_content2fix entry with comptable, compfield, rowid
     * @return string HTML content
     */
    public static function get_content_for_entry(object $entry): string {
        global $DB;

        $table = $entry->comptable ?? '';
        $field = $entry->compfield ?? '';
        $rowid = (int) ($entry->rowid ?? 0);

        if (!$table || !$field || !$rowid || !$DB->get_manager()->table_exists($table)) {
            return '';
        }
        $columns = $DB->get_columns($table);
        if (!isset($columns[$field])) {
            return '';
        }

        $row = $DB->get_record($table, ['id' => $rowid], $field);
        if (!$row || !isset($row->$field)) {
            return '';
        }
        return is_string($row->$field) ? $row->$field : '';
    }

    /**
     * Persist TinyMCE-edited content to the mod_* or course entity.
     * Clears course cache and does not remove report entries (event observer will re-evaluate).
     *
     * @param int $entryid report_content2fix.id
     * @param array $editordata Editor data with 'text' and 'format' keys
     * @return array{success: bool, message?: string}
     */
    public static function persist_tinymce_content(int $entryid, array $editordata): array {
        global $DB;

        $entry = $DB->get_record('report_content2fix', ['id' => $entryid]);
        if (!$entry) {
            return ['success' => false, 'message' => 'Entry not found'];
        }

        $content = $editordata['text'] ?? '';
        if (!is_string($content)) {
            return ['success' => false, 'message' => 'Invalid content'];
        }

        $table = $entry->comptable ?? '';
        $field = $entry->compfield ?? '';
        $rowid = (int) ($entry->rowid ?? 0);
        $formatfield = $field . 'format';

        if (!$DB->get_manager()->table_exists($table)) {
            return ['success' => false, 'message' => 'Table not found'];
        }
        $columns = $DB->get_columns($table);
        if (!isset($columns[$field]) || !isset($columns[$formatfield])) {
            return ['success' => false, 'message' => 'Field not found'];
        }

        $DB->update_record($table, (object) [
            'id' => $rowid,
            $field => $content,
        ]);

        $courseid = (int) ($entry->courseid ?? 0);
        if ($courseid > 0) {
            rebuild_course_cache($courseid, true);
        }

        static::trigger_content_updated_event($entry);

        return ['success' => true];
    }

    /**
     * Trigger the appropriate event after content was updated.
     *
     * @param object $entry report_content2fix entry
     */
    public static function trigger_content_updated_event(object $entry): void {
        if (!empty($entry->cmid)) {
            $cm = get_coursemodule_from_id('', $entry->cmid, 0, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);
            \core\event\course_module_updated::create_from_cm($cm, $context)->trigger();
        } elseif (!empty($entry->courseid) && in_array($entry->component ?? '', ['core_course', 'core_section'], true)) {
            $courseid = (int) $entry->courseid;
            $context = \context_course::instance($courseid);
            if ($entry->component === 'core_course') {
                $course = get_course($courseid);
                \core\event\course_updated::create([
                    'objectid' => $course->id,
                    'context' => $context,
                    'other' => ['shortname' => $course->shortname, 'fullname' => $course->fullname],
                ])->trigger();
            } elseif ($entry->component === 'core_section') {
                global $DB;
                $section = $DB->get_record('course_sections', ['id' => (int) $entry->rowid]);
                \core\event\course_section_updated::create([
                    'objectid' => (int) $entry->rowid,
                    'courseid' => $courseid,
                    'context' => $context,
                    'other' => ['sectionnum' => $section->section ?? 0],
                ])->trigger();
            }
        }
    }
}
