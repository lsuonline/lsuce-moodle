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
 * Unit tests for scan_malformed_html_task (data gathering, clean_text diff logic).
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scan_malformed_html_task_test extends \advanced_testcase {

    /** @var string HTML that clean_text modifies (mismatched tags). */
    private const CONTENT_DIFFERS = '<p>Intro with unclosed tag</div>';

    /** @var string Expected summary after strip_tags and trim (task summarise_issue). */
    private const EXPECTED_SUMMARY = 'Intro with unclosed tag';

    /**
     * Test task finds content that clean_text would modify and stores expected data.
     */
    public function test_task_finds_content_differs_and_stores_expected_data(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => self::CONTENT_DIFFERS,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $rows = $DB->get_records('report_content2fix', [], 'id ASC');
        $this->assertCount(1, $rows, 'Exactly one entry should be recorded (content differs after clean_text).');

        $row = reset($rows);
        $this->assertSame('mod_page', $row->component);
        $this->assertSame('page', $row->comptable);
        $this->assertSame('content', $row->compfield);
        $this->assertSame((int) $page->id, (int) $row->rowid);
        $this->assertSame((int) $course->id, (int) $row->courseid);
        $this->assertSame((int) $page->cmid, (int) $row->cmid);
        $this->assertSame(self::EXPECTED_SUMMARY, $row->summary);
        $this->assertNotEmpty($row->htmlerrors, 'Message about clean_text diff should be stored.');
        $this->assertSame(self::CONTENT_DIFFERS, $row->malformedhtml);
        $this->assertNotEmpty($row->timechecked);
    }

    /**
     * Test task does not record valid HTML unchanged by clean_text.
     */
    public function test_task_does_not_record_valid_html(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Valid content</p>',
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $this->assertSame(0, $DB->count_records('report_content2fix'));
    }

    /**
     * Test task does not record HTML that clean_text does not modify.
     * Valid HTML with iframe - if clean_text preserves it, no record.
     */
    public function test_task_does_not_record_html_unchanged_by_clean(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $content = '<p>Watch the video</p><iframe src="https://example.com/video" title="Module intro"></iframe>';
        if (local\html_scanner::html_differs_after_clean($content)) {
            $this->markTestSkipped('clean_text modifies this HTML in this environment.');
        }

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $content,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $this->assertSame(0, $DB->count_records('report_content2fix'));
    }

    /**
     * Test task records content that clean_text would modify (e.g. script tags stripped).
     */
    public function test_task_records_content_modified_by_clean(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $content = '<p>Before</p><script>if (x < 10) { alert("ok"); }</script><p>After</p>';
        if (!local\html_scanner::html_differs_after_clean($content)) {
            $this->markTestSkipped('clean_text does not modify this HTML in this environment.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $content,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $this->assertGreaterThanOrEqual(1, $DB->count_records('report_content2fix', ['rowid' => $page->id]));
    }

    /**
     * Test task records invalid HTML structure when clean_text modifies it.
     */
    public function test_task_records_invalid_html_when_clean_modifies(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $invalid = '<ol><li>Item one</li></ol><ul><li>Item two</li></ul><li>Orphan item</li>';
        if (!local\html_scanner::html_differs_after_clean($invalid)) {
            $this->markTestSkipped('clean_text does not modify orphan li in this environment.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $invalid,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $rows = $DB->get_records('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertCount(1, $rows);
    }

    /**
     * Test task records multiple entries when content differs (intro and content).
     */
    public function test_task_records_multiple_differs_fields(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Valid content</p>',
            'contentformat' => FORMAT_HTML,
            'intro' => '',
            'introformat' => FORMAT_HTML,
        ]);
        $DB->set_field('page', 'intro', self::CONTENT_DIFFERS, ['id' => $page->id]);
        $DB->set_field('page', 'content', '<div>Broken</p>', ['id' => $page->id]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $rows = $DB->get_records('report_content2fix', [], 'compfield ASC');
        $this->assertCount(2, $rows);

        $byfield = [];
        foreach ($rows as $r) {
            $byfield[$r->compfield] = $r;
        }
        $this->assertArrayHasKey('intro', $byfield);
        $this->assertArrayHasKey('content', $byfield);
        $this->assertSame(self::EXPECTED_SUMMARY, $byfield['intro']->summary);
        $this->assertSame('Broken', $byfield['content']->summary);
        $this->assertSame((int) $page->id, (int) $byfield['intro']->rowid);
        $this->assertSame((int) $page->id, (int) $byfield['content']->rowid);
    }

    /**
     * Test that found content in report is exactly what we inserted.
     */
    public function test_found_content_matches_expected(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => self::CONTENT_DIFFERS,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $row = $DB->get_record('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertNotNull($row);
        $stored = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertSame(self::CONTENT_DIFFERS, $stored, 'Source content in DB must be unchanged.');
        $this->assertSame(self::EXPECTED_SUMMARY, $row->summary);
    }
}
