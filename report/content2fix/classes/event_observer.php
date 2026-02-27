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

namespace report_content2fix;

use report_content2fix\local\format_helper;
use report_content2fix\local\html_scanner;

/**
 * Event observer for report_content2fix.
 *
 * Re-evaluates entries when the associated course module, course, or section
 * is updated. Uses the scanner's logic to determine if content still differs
 * after clean_text: if it does, the entry persists; if not, it is removed.
 * Deleted course modules have their entries removed.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_observer {

    /**
     * Re-evaluate report_content2fix entries when a course module is updated.
     *
     * @param \core\event\course_module_updated $event
     */
    public static function course_module_updated(\core\event\course_module_updated $event): void {
        global $DB;
        $cmid = (int) $event->objectid;
        if ($cmid <= 0) {
            return;
        }
        $entries = $DB->get_records('report_content2fix', ['cmid' => $cmid]);
        foreach ($entries as $entry) {
            self::reevaluate_entry($entry);
        }
    }

    /**
     * Remove report_content2fix entries when a course module is deleted.
     *
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        global $DB;
        $cmid = (int) $event->objectid;
        if ($cmid > 0) {
            $DB->delete_records('report_content2fix', ['cmid' => $cmid]);
        }
    }

    /**
     * Re-evaluate report_content2fix entries for course intro when course is updated.
     *
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event): void {
        global $DB;
        $courseid = (int) $event->courseid;
        if ($courseid <= 0) {
            return;
        }
        $entries = $DB->get_records('report_content2fix', [
            'courseid' => $courseid,
            'component' => 'core_course',
        ]);
        foreach ($entries as $entry) {
            self::reevaluate_entry($entry);
        }
    }

    /**
     * Re-evaluate report_content2fix entries for section summary when section is updated.
     *
     * @param \core\event\course_section_updated $event
     */
    public static function course_section_updated(\core\event\course_section_updated $event): void {
        global $DB;
        $sectionid = (int) $event->objectid;
        if ($sectionid <= 0) {
            return;
        }
        $entries = $DB->get_records('report_content2fix', [
            'comptable' => 'course_sections',
            'rowid' => $sectionid,
        ]);
        foreach ($entries as $entry) {
            self::reevaluate_entry($entry);
        }
    }

    /**
     * Re-evaluate a single report entry using the scanner logic.
     *
     * @param object $entry report_content2fix record
     */
    protected static function reevaluate_entry(object $entry): void {
        global $DB;

        $html = format_helper::get_content_for_entry($entry);
        $analysis = html_scanner::analyse_html($html);

        if (!$analysis['differs']) {
            $DB->delete_records('report_content2fix', ['id' => $entry->id]);
            return;
        }

        $summary = self::summarise_issue($html);
        $DB->update_record('report_content2fix', (object) [
            'id' => $entry->id,
            'summary' => $summary,
            'htmlerrors' => implode("\n", $analysis['errors']),
            'malformedhtml' => $html,
            'timechecked' => time(),
        ]);
    }

    /**
     * Build a short summary string for malformed content.
     *
     * @param string $html
     * @return string
     */
    protected static function summarise_issue(string $html): string {
        $stripped = strip_tags($html);
        $stripped = preg_replace('/\s+/', ' ', $stripped);
        $stripped = trim($stripped);
        if ($stripped === '') {
            return get_string('malformed_no_text', 'report_content2fix');
        }
        return \core_text::substr($stripped, 0, 250);
    }
}
