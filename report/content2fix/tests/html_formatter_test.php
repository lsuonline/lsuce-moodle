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
 * Unit tests for html_formatter (format_html and format_and_persist_entry).
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_formatter_test extends \advanced_testcase {

    /**
     * Test format_html repairs malformed HTML structure.
     */
    public function test_format_html_repairs_malformed(): void {
        $this->resetAfterTest();

        $malformed = '<p>Intro with unclosed tag</div>';
        $repaired = local\html_formatter::format_html($malformed);

        $this->assertNotSame($malformed, $repaired, 'format_html should alter malformed HTML.');
        $this->assertTrue(
            local\html_scanner::is_malformed_html($malformed),
            'Input should be detected as malformed.'
        );
        $this->assertFalse(
            local\html_scanner::is_malformed_html($repaired),
            'Output should be well-formed after format_html.'
        );
    }

    /**
     * Test format_html preserves iframe tags (clean_text would remove them).
     */
    public function test_format_html_preserves_iframe(): void {
        $this->resetAfterTest();

        $html = '<p>Before</p><iframe src="https://example.com/embed" title="Video"></iframe><p>After</p>';
        $repaired = local\html_formatter::format_html($html);

        $this->assertStringContainsString('<iframe', $repaired);
        $this->assertStringContainsString('src="https://example.com/embed"', $repaired);
        $this->assertStringContainsString('</iframe>', $repaired);
        $this->assertStringContainsString('Before', $repaired);
        $this->assertStringContainsString('After', $repaired);
    }

    /**
     * Test format_html preserves script tags (clean_text would remove them).
     */
    public function test_format_html_preserves_script(): void {
        $this->resetAfterTest();

        $html = '<p>Text</p><script>console.log("hello");</script><p>More</p>';
        $repaired = local\html_formatter::format_html($html);

        $this->assertStringContainsString('<script', $repaired);
        $this->assertStringContainsString('console.log', $repaired);
        $this->assertStringContainsString('</script>', $repaired);
        $this->assertStringContainsString('Text', $repaired);
        $this->assertStringContainsString('More', $repaired);
    }

    /**
     * Test format_html repairs misplaced ul/ol (ul as sibling after li - move into preceding li).
     */
    public function test_format_html_repairs_misplaced_ul_inside_ol(): void {
        $this->resetAfterTest();

        $malformed = '<ol>
<li>Read Chapters 1 and 2</li>
<li>Read and view the materials</li>
<ul>
<li>Complete the guided reading activity</li>
</ul>
<li>Complete the Reflective Reading</li>
</ol>';

        $repaired = local\html_formatter::format_html($malformed);

        $this->assertFalse(
            local\html_scanner::is_malformed_html($repaired),
            'Output should be well-formed HTML after repair.'
        );
        $this->assertStringContainsString('Read Chapters 1 and 2', $repaired);
        $this->assertStringContainsString('Read and view the materials', $repaired);
        $this->assertStringContainsString('Complete the guided reading activity', $repaired);
        $this->assertStringContainsString('Complete the Reflective Reading', $repaired);
        $this->assertMatchesRegularExpression(
            '/Read and view the materials\s*<ul>/s',
            $repaired,
            'The ul should be nested inside the second li.'
        );
    }

    /**
     * Test format_html repairs invalid list structure (orphan li, mixed ol/ul).
     */
    public function test_format_html_repairs_invalid_list_structure(): void {
        $this->resetAfterTest();

        $invalid = '<ol>
<li>Watch the Module 1 Introduction Video</li>
<li>Read Chapters 1 and 2 in the textbook</li>
<li>Read and view the materials in the Module 1 Resources book</li>
</ol><ul>
<li>Complete the guided reading activity</li>
<li>Complete the Introducing Social Psychology Quiz (AEA)</li>
<li>Complete the Social Learning and Social Cognition Quiz</li>
<li>Complete the Module 1 Discussion Forum</li>
</ul>
<li>View the Workforce Skills page and complete the accompanying activities</li>';

        $repaired = local\html_formatter::format_html($invalid);

        $this->assertFalse(
            local\html_scanner::is_malformed_html($repaired),
            'Output should be well-formed HTML after repair.'
        );
        $this->assertStringContainsString('Watch the Module 1 Introduction Video', $repaired);
        $this->assertStringContainsString('Complete the guided reading activity', $repaired);
        $this->assertStringContainsString('View the Workforce Skills page', $repaired);
        $this->assertStringContainsString('<ol>', $repaired);
        $this->assertStringContainsString('<ul>', $repaired);
        $this->assertStringContainsString('<li>', $repaired);
    }

    /**
     * Test format_html preserves valid HTML (may still normalize slightly).
     */
    public function test_format_html_valid_html(): void {
        $this->resetAfterTest();

        $valid = '<p>Valid content</p>';
        $cleaned = local\html_formatter::format_html($valid);

        $this->assertStringContainsString('Valid content', $cleaned);
    }

    /**
     * Test format_and_persist_entry updates source table when content changes.
     */
    public function test_format_and_persist_entry_updates_source(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $malformed = '<p>Broken tag</div>';
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $malformed,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $entry = $DB->get_record('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertNotNull($entry);

        $before = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertSame($malformed, $before);

        $changed = local\html_formatter::format_and_persist_entry($entry);
        $this->assertTrue($changed);

        $after = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertNotSame($malformed, $after);
        $this->assertFalse(local\html_scanner::is_malformed_html($after));
    }

    /**
     * Test format_and_persist_entry removes the row from report_content2fix when formatting succeeds.
     */
    public function test_format_and_persist_entry_removes_report_row(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $malformed = '<p>Broken tag</div>';
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $malformed,
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $entry = $DB->get_record('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertNotNull($entry);
        $entryid = $entry->id;

        $changed = local\html_formatter::format_and_persist_entry($entry);
        $this->assertTrue($changed);

        $this->assertFalse(
            $DB->record_exists('report_content2fix', ['id' => $entryid]),
            'Report row should be removed after HTML is successfully formatted.'
        );
    }

    /**
     * Test format_and_persist_entry returns false when content unchanged by clean_text.
     */
    public function test_format_and_persist_entry_returns_false_when_unchanged(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Broken</div>',
            'contentformat' => FORMAT_HTML,
        ]);

        $task = new task\scan_malformed_html_task();
        $task->execute();

        $entry = $DB->get_record('report_content2fix', ['rowid' => $page->id, 'compfield' => 'content']);
        $this->assertNotNull($entry);

        $first = local\html_formatter::format_and_persist_entry($entry);
        $this->assertTrue($first);
        // Row is removed after successful format; construct entry for already-cleaned content.
        $entryforsecond = (object) [
            'component' => 'mod_page',
            'comptable' => 'page',
            'compfield' => 'content',
            'rowid' => $page->id,
        ];
        $second = local\html_formatter::format_and_persist_entry($entryforsecond);
        $this->assertFalse($second, 'Second call on already-cleaned content should not update.');
    }

    /**
     * Test format_and_persist_entry returns false when format is not FORMAT_HTML.
     */
    public function test_format_and_persist_entry_skips_non_html(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            $this->markTestSkipped('report_content2fix table not installed.');
        }

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Plain text format</p>',
            'contentformat' => FORMAT_PLAIN,
        ]);

        $entry = (object) [
            'component' => 'mod_page',
            'comptable' => 'page',
            'compfield' => 'content',
            'rowid' => $page->id,
        ];

        $changed = local\html_formatter::format_and_persist_entry($entry);
        $this->assertFalse($changed);
    }
}
