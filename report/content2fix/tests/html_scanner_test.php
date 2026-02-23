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
 * Unit tests for html_scanner (clean_text diff detection and helpers).
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_scanner_test extends \advanced_testcase {

    /**
     * Test that HTML unchanged by clean_text is not reported as differing.
     */
    public function test_html_unchanged_by_clean_text(): void {
        $this->resetAfterTest();

        $unchanged = [
            '<p>Simple paragraph</p>',
            '<div><p>Nested</p></div>',
            '<p>With <strong>formatting</strong></p>',
            '<ul><li>One</li><li>Two</li></ul>',
            '<ol><li>Item</li></ol>',
            '<table><tr><td>Cell</td></tr></table>',
            '<a href="http://example.com">Link</a>',
            '',
            '   ',
        ];
        foreach ($unchanged as $html) {
            $differs = local\html_scanner::html_differs_after_clean($html);
            $this->assertFalse(
                $differs,
                'Expected no diff for: ' . \core_text::substr($html, 0, 50)
            );
        }
    }

    /**
     * Test that HTML modified by clean_text is reported as differing.
     */
    public function test_html_differs_when_clean_text_modifies(): void {
        $this->resetAfterTest();

        $malformed = '<p>Intro with unclosed tag</div>';
        $this->assertTrue(
            local\html_scanner::html_differs_after_clean($malformed),
            'Mismatched closing tag should be modified by clean_text.'
        );
    }

    /**
     * Test analyse_html returns differs and cleaned result.
     */
    public function test_analyse_html_returns_differs_and_cleaned(): void {
        $this->resetAfterTest();

        $html = '<p>Intro with unclosed tag</div>';
        $analysis = local\html_scanner::analyse_html($html);

        $this->assertTrue($analysis['differs']);
        $this->assertNotEmpty($analysis['errors']);
        $this->assertNotSame($html, $analysis['cleaned']);
    }

    /**
     * Test analyse_html returns no diff for empty content.
     */
    public function test_analyse_html_empty_returns_no_diff(): void {
        $this->resetAfterTest();

        $analysis = local\html_scanner::analyse_html('');
        $this->assertFalse($analysis['differs']);
        $this->assertEmpty($analysis['errors']);
        $this->assertSame('', $analysis['cleaned']);
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
