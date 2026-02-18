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

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for event observers (course_module_updated, course_module_deleted).
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_observer_test extends \advanced_testcase {

    /**
     * Insert a row into report_content2fix for a given cmid.
     *
     * @param int $cmid
     * @param int $courseid
     * @return \stdClass inserted record
     */
    protected function insert_report_row(int $cmid, int $courseid): \stdClass {
        global $DB;
        $row = (object) [
            'component' => 'mod_page',
            'comptable' => 'page',
            'compfield' => 'content',
            'rowid' => 1,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'summary' => 'Test malformed',
            'timechecked' => time(),
        ];
        $row->id = $DB->insert_record('report_content2fix', $row);
        return $row;
    }

    /**
     * Test course_module_updated observer removes entries for the given cmid.
     */
    public function test_course_module_updated_removes_entries(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id);

        $row = $this->insert_report_row((int) $cm->id, (int) $course->id);
        $this->assertTrue($DB->record_exists('report_content2fix', ['id' => $row->id]));

        $event = \core\event\course_module_updated::create_from_cm($cm);
        $event->trigger();

        $this->assertFalse($DB->record_exists('report_content2fix', ['id' => $row->id]));
        $this->assertFalse($DB->record_exists('report_content2fix', ['cmid' => $cm->id]));
    }

    /**
     * Test course_module_updated observer only removes entries for the affected cmid.
     */
    public function test_course_module_updated_removes_only_affected_cmid(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm1 = get_coursemodule_from_instance('page', $page1->id);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm2 = get_coursemodule_from_instance('page', $page2->id);

        $row1 = $this->insert_report_row((int) $cm1->id, (int) $course->id);
        $row2 = $this->insert_report_row((int) $cm2->id, (int) $course->id);

        $event = \core\event\course_module_updated::create_from_cm($cm1);
        $event->trigger();

        $this->assertFalse($DB->record_exists('report_content2fix', ['id' => $row1->id]));
        $this->assertTrue($DB->record_exists('report_content2fix', ['id' => $row2->id]));
    }

    /**
     * Test course_module_deleted observer removes entries for the given cmid.
     */
    public function test_course_module_deleted_removes_entries(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id);

        $row = $this->insert_report_row((int) $cm->id, (int) $course->id);
        $this->assertTrue($DB->record_exists('report_content2fix', ['id' => $row->id]));

        $context = \context_module::instance($cm->id);
        $event = \core\event\course_module_deleted::create([
            'courseid' => $course->id,
            'context' => $context,
            'objectid' => $cm->id,
            'other' => [
                'modulename' => 'page',
                'instanceid' => $page->id,
            ],
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->trigger();

        $this->assertFalse($DB->record_exists('report_content2fix', ['id' => $row->id]));
        $this->assertFalse($DB->record_exists('report_content2fix', ['cmid' => $cm->id]));
    }

    /**
     * Test course_module_deleted observer only removes entries for the affected cmid.
     */
    public function test_course_module_deleted_removes_only_affected_cmid(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm1 = get_coursemodule_from_instance('page', $page1->id);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm2 = get_coursemodule_from_instance('page', $page2->id);

        $row1 = $this->insert_report_row((int) $cm1->id, (int) $course->id);
        $row2 = $this->insert_report_row((int) $cm2->id, (int) $course->id);

        $context = \context_module::instance($cm1->id);
        $event = \core\event\course_module_deleted::create([
            'courseid' => $course->id,
            'context' => $context,
            'objectid' => $cm1->id,
            'other' => [
                'modulename' => 'page',
                'instanceid' => $page1->id,
            ],
        ]);
        $event->add_record_snapshot('course_modules', $cm1);
        $event->trigger();

        $this->assertFalse($DB->record_exists('report_content2fix', ['id' => $row1->id]));
        $this->assertTrue($DB->record_exists('report_content2fix', ['id' => $row2->id]));
    }
}
