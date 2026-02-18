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

/**
 * Event observer for report_content2fix.
 *
 * Removes entries from report_content2fix when the associated course module
 * is updated or deleted, so the next scan can re-check or the entry is removed.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_observer {

    /**
     * Remove report_content2fix entries when a course module is updated.
     *
     * Content may have been fixed or changed; the next scheduled scan will re-check.
     *
     * @param \core\event\course_module_updated $event
     */
    public static function course_module_updated(\core\event\course_module_updated $event): void {
        global $DB;
        $cmid = $event->objectid;
        if ($cmid) {
            $DB->delete_records('report_content2fix', ['cmid' => $cmid]);
        }
    }

    /**
     * Remove report_content2fix entries when a course module is deleted.
     *
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        global $DB;
        $cmid = $event->objectid;
        if ($cmid) {
            $DB->delete_records('report_content2fix', ['cmid' => $cmid]);
        }
    }
}
