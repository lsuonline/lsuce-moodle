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
