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
 * Unit tests for scan_malformed_html_task (data gathering and expected findings).
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scan_malformed_html_task_test extends \advanced_testcase {

    /** @var string Malformed HTML used so we can assert exact match in report. */
    private const MALFORMED_CONTENT = '<p>Intro with unclosed tag</div>';

    /** @var string Expected summary after strip_tags and trim (task summarise_issue). */
    private const EXPECTED_SUMMARY = 'Intro with unclosed tag';

    /**
     * Test task finds malformed HTML in page content and stores expected data.
     */
    public function test_task_finds_malformed_html_and_stores_expected_data(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => self::MALFORMED_CONTENT,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $rows = $DB->get_records('report_content2fix', [], 'id ASC');
        $this->assertCount(1, $rows, 'Exactly one malformed entry should be recorded.');

        $row = reset($rows);
        $this->assertSame('mod_page', $row->component);
        $this->assertSame('page', $row->comptable);
        $this->assertSame('content', $row->compfield);
        $this->assertSame((int) $page->id, (int) $row->rowid);
        $this->assertSame((int) $course->id, (int) $row->courseid);
        $this->assertSame((int) $page->cmid, (int) $row->cmid);
        $this->assertSame(self::EXPECTED_SUMMARY, $row->summary, 'Stored summary must match expected from malformed HTML.');
        $this->assertNotEmpty($row->timechecked);
    }

    /**
     * Test task does not record valid HTML.
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
     * Test task does not record valid HTML with iframes (XML-strict validation would fail).
     */
    public function test_task_does_not_record_valid_html_with_iframe(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Watch the video</p><iframe src="https://example.com/video" title="Module intro"></iframe>',
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $this->assertSame(0, $DB->count_records('report_content2fix'));
    }

    /**
     * Test task does not record valid HTML with script tags (script content can break XML).
     */
    public function test_task_does_not_record_valid_html_with_script(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Before</p><script>if (x < 10) { alert("ok"); }</script><p>After</p>',
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $this->assertSame(0, $DB->count_records('report_content2fix'));
    }

    /**
     * Test task records invalid HTML structure (orphan li) - malformed HTML, not just malformed XML.
     */
    public function test_task_records_invalid_html_list_structure(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $invalid = '<ol><li>Item one</li></ol><ul><li>Item two</li></ul><li>Orphan item</li>';
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $invalid,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $rows = $DB->get_records('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertCount(1, $rows, 'Invalid HTML list structure (orphan li) must be recorded.');
    }

    /**
     * Test task records multiple malformed items (e.g. intro and content on same page).
     */
    public function test_task_records_multiple_malformed_fields(): void {
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
        $DB->set_field('page', 'intro', self::MALFORMED_CONTENT, ['id' => $page->id]);
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
     * Test that found malformed HTML in report is exactly the content we inserted.
     */
    public function test_found_malformed_html_matches_expected(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => self::MALFORMED_CONTENT,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $row = $DB->get_record('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertNotNull($row);
        $stored = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertSame(self::MALFORMED_CONTENT, $stored, 'Source content in DB must be unchanged.');
        $this->assertSame(self::EXPECTED_SUMMARY, $row->summary, 'Report summary must match expected from the malformed HTML we inserted.');
    }
}
