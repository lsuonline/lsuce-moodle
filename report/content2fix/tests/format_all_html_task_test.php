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
// GNU General Public License for the terms and conditions.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_content2fix;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for format_all_html_task.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_all_html_task_test extends \advanced_testcase {

    /**
     * Test ad-hoc task processes all entries and persists cleaned HTML.
     */
    public function test_task_processes_all_entries(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $malformed = '<p>Broken content</div>';
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $malformed,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $entries = $DB->get_records('report_content2fix');
        $this->assertCount(1, $entries);

        $before = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertSame($malformed, $before);

        $formattask = new task\format_all_html_task();
        $formattask->execute();

        $after = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertNotSame($malformed, $after);
        $this->assertFalse(local\html_scanner::is_malformed_html($after));
    }

    /**
     * Test ad-hoc task can be queued.
     */
    public function test_task_can_be_queued(): void {
        global $DB;
        $this->resetAfterTest();

        $task = new task\format_all_html_task();
        \core\task\manager::queue_adhoc_task($task);

        $tasks = \core\task\manager::get_adhoc_tasks(task\format_all_html_task::class);
        $this->assertCount(1, $tasks);
    }

    /**
     * Test task handles empty report table.
     */
    public function test_task_handles_empty_table(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $DB->delete_records('report_content2fix');

        $task = new task\format_all_html_task();
        $task->execute();

        $this->assertSame(0, $DB->count_records('report_content2fix'));
    }

    /**
     * Test task processes multiple entries.
     */
    public function test_task_processes_multiple_entries(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Valid</p>',
            'contentformat' => FORMAT_HTML,
            'intro' => '<div>Broken intro</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $DB->set_field('page', 'content', '<div>Broken content</p>', ['id' => $page->id]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $count = $DB->count_records('report_content2fix');
        $this->assertGreaterThanOrEqual(2, $count);

        $formattask = new task\format_all_html_task();
        $formattask->execute();

        $intro = $DB->get_field('page', 'intro', ['id' => $page->id]);
        $content = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertFalse(local\html_scanner::is_malformed_html($intro ?? ''));
        $this->assertFalse(local\html_scanner::is_malformed_html($content ?? ''));
    }

    /**
     * Test task handles custom_data with filtervalues as stdClass (from JSON decode).
     * When adhoc task custom_data is stored/restored via json_encode/decode, nested
     * structures become stdClass. The task must cast filtervalues to array.
     */
    public function test_task_handles_filtervalues_as_stdclass(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $malformed = '<p>Broken</div>';
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $malformed,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $this->assertGreaterThanOrEqual(1, $DB->count_records('report_content2fix'));

        $formattask = new task\format_all_html_task();
        $formattask->set_custom_data((object) [
            'filtervalues' => (object) [
                'malformed_content:component_operator' => '0',
            ],
        ]);

        $formattask->execute();

        $this->assertSame(0, $DB->count_records('report_content2fix'));
    }
}
