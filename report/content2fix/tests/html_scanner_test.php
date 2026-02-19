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
 * Unit tests for html_scanner (malformed HTML detection and helpers).
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_scanner_test extends \advanced_testcase {

    /**
     * Test that valid HTML is not reported as malformed.
     */
    public function test_is_malformed_html_valid(): void {
        $this->resetAfterTest();

        $valid = [
            '<p>Simple paragraph</p>',
            '<div><p>Nested</p></div>',
            '<p>With <strong>formatting</strong></p>',
            '<ul><li>One</li><li>Two</li></ul>',
            '<ol><li>Item</li></ol>',
            '<table><tr><td>Cell</td></tr></table>',
            '<dl><dt>Term</dt><dd>Definition</dd></dl>',
            '<a href="http://example.com">Link</a>',
            '',  // Empty is not malformed.
            '   ',  // Whitespace-only is not malformed.
        ];
        foreach ($valid as $html) {
            $this->assertFalse(
                local\html_scanner::is_malformed_html($html),
                "Expected valid: " . \core_text::substr($html, 0, 50)
            );
        }
    }

    /**
     * Test that valid HTML with iframe is not reported as malformed.
     * Iframes would fail strict XML validation but are valid HTML.
     */
    public function test_is_malformed_html_valid_with_iframe(): void {
        $this->resetAfterTest();

        $html = '<p>Content</p><iframe src="https://example.com/embed" title="Video"></iframe><p>More</p>';
        $this->assertFalse(
            local\html_scanner::is_malformed_html($html),
            'Valid HTML with iframe must not be flagged as malformed.'
        );
    }

    /**
     * Test that valid HTML with script tag is not reported as malformed.
     * Script content with < and > can break XML validation but is valid HTML.
     */
    public function test_is_malformed_html_valid_with_script(): void {
        $this->resetAfterTest();

        $html = '<p>Before</p><script>if (a < b) { x = "test"; }</script><p>After</p>';
        $this->assertFalse(
            local\html_scanner::is_malformed_html($html),
            'Valid HTML with script (including < and > in JS) must not be flagged as malformed.'
        );
    }

    /**
     * Test that invalid HTML structure (orphan li) is detected as malformed.
     * This is valid in loose XML but invalid HTML - li must be inside ol or ul.
     */
    public function test_is_malformed_html_detects_invalid_list_structure(): void {
        $this->resetAfterTest();

        $invalid = '<ol><li>One</li></ol><ul><li>Two</li></ul><li>Orphan item</li>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($invalid),
            'Orphan li (invalid HTML structure) must be detected as malformed.'
        );
    }

    /**
     * Test that invalid table structure (orphan td, tr outside table) is detected.
     */
    public function test_is_malformed_html_detects_invalid_table_structure(): void {
        $this->resetAfterTest();

        $orphantd = '<table><tr><td>OK</td></tr></table><td>Orphan cell</td>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($orphantd),
            'Orphan td must be detected as malformed.'
        );

        $orphantr = '<tr><td>Orphan row</td></tr>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($orphantr),
            'Orphan tr (outside table) must be detected as malformed.'
        );
    }

    /**
     * Test that invalid dl structure (orphan dt, dd) is detected.
     */
    public function test_is_malformed_html_detects_invalid_dl_structure(): void {
        $this->resetAfterTest();

        $orphandt = '<dl><dt>Term</dt></dl><dt>Orphan term</dt>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($orphandt),
            'Orphan dt must be detected as malformed.'
        );

        $orphandd = '<dd>Orphan definition</dd>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($orphandd),
            'Orphan dd must be detected as malformed.'
        );
    }

    /**
     * Test that known malformed HTML is correctly detected.
     * Uses the same sample as scan_malformed_html_task_test so report findings match expectations.
     */
    public function test_is_malformed_html_malformed(): void {
        $this->resetAfterTest();

        $expected = '<p>Intro with unclosed tag</div>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($expected),
            'Mismatched closing tag must be detected as malformed.'
        );
    }

    /**
     * Test that the same malformed HTML string used in task tests is detected.
     * This ensures "expected malformed" in task test matches scanner behaviour.
     */
    public function test_expected_malformed_sample_detected(): void {
        $this->resetAfterTest();

        $expected = '<p>Intro with unclosed tag</div>';
        $this->assertTrue(
            local\html_scanner::is_malformed_html($expected),
            'Sample malformed HTML used in task tests must be detected as malformed.'
        );
    }

    /**
     * Test get_cmid_for_instance returns correct cmid for a real module instance.
     */
    public function test_get_cmid_for_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $cmid = local\html_scanner::get_cmid_for_instance('page', (int) $page->id);
        $this->assertNotNull($cmid);
        $cm = $DB->get_record('course_modules', ['id' => $cmid]);
        $this->assertSame((int) $page->id, (int) $cm->instance);
        $this->assertSame($page->cmid, $cmid);
    }

    /**
     * Test get_cmid_for_instance returns null for invalid module name.
     */
    public function test_get_cmid_for_instance_invalid_module(): void {
        $this->resetAfterTest();
        $this->assertNull(local\html_scanner::get_cmid_for_instance('nonexistent_mod', 1));
    }

    /**
     * Test get_html_content_sources returns mod_page entries when page is installed.
     */
    public function test_get_html_content_sources_includes_page(): void {
        $this->resetAfterTest();

        $sources = local\html_scanner::get_html_content_sources();
        $pageintro = null;
        $pagecontent = null;
        foreach ($sources as $s) {
            if ($s['component'] === 'mod_page') {
                if ($s['field'] === 'intro') {
                    $pageintro = $s;
                }
                if ($s['field'] === 'content') {
                    $pagecontent = $s;
                }
            }
        }
        $this->assertNotNull($pageintro, 'mod_page intro source should exist');
        $this->assertNotNull($pagecontent, 'mod_page content source should exist');
        $this->assertSame('page', $pageintro['table']);
        $this->assertSame('introformat', $pageintro['formatfield']);
        $this->assertSame('contentformat', $pagecontent['formatfield']);
    }
}
