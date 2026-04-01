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

/**
 * Tests for the MD-2149 HTMLPurifier accordion element whitelist.
 *
 * Verifies that purify_html() correctly preserves <details> and <summary>
 * elements (and the 'open' attribute on <details>) while still stripping
 * disallowed markup embedded within them.
 *
 * @package    core
 * @category   test
 * @group      core_weblib
 * @covers     \purify_html
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class purify_html_accordion_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * A basic <details>/<summary> accordion is preserved unchanged.
     */
    public function test_details_summary_preserved(): void {
        $input = '<details><summary>Title</summary><p>Body text.</p></details>';
        $result = purify_html($input);
        $this->assertStringContainsString('<details>', $result);
        $this->assertStringContainsString('<summary>', $result);
        $this->assertStringContainsString('Title', $result);
        $this->assertStringContainsString('Body text.', $result);
    }

    /**
     * The boolean 'open' attribute on <details> is preserved.
     */
    public function test_details_open_attribute_preserved(): void {
        $input = '<details open="open"><summary>Open by default</summary><p>Visible body.</p></details>';
        $result = purify_html($input);
        $this->assertStringContainsString('<details', $result);
        $this->assertMatchesRegularExpression('/open=["\']open["\']/', $result);
    }

    /**
     * A bare boolean 'open' attribute (no value) is normalised but preserved.
     */
    public function test_details_open_bare_attribute_preserved(): void {
        // HTMLPurifier normalises bare booleans to open="open" in XHTML mode.
        $input = '<details open><summary>Open</summary><p>Content</p></details>';
        $result = purify_html($input);
        $this->assertStringContainsString('<details', $result);
        $this->assertStringContainsString('open', $result);
    }

    /**
     * Disallowed attributes on <details> are stripped while the element itself remains.
     */
    public function test_details_unknown_attribute_stripped(): void {
        $input = '<details data-foo="bar"><summary>Title</summary><p>Body</p></details>';
        $result = purify_html($input);
        $this->assertStringContainsString('<details>', $result);
        $this->assertStringNotContainsString('data-foo', $result);
    }

    /**
     * Malicious script inside <details> is stripped; the accordion wrapper survives.
     */
    public function test_script_inside_details_stripped(): void {
        $exploit = '<details><summary>Click me</summary>'
            . '<script>alert("xss")</script>'
            . '<p>Safe content</p></details>';
        $result = purify_html($exploit);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringContainsString('<details', $result);
        $this->assertStringContainsString('Safe content', $result);
    }

    /**
     * Multiple nested accordions are preserved correctly.
     */
    public function test_nested_accordions_preserved(): void {
        $input = '<details><summary>Outer</summary>'
            . '<details><summary>Inner</summary><p>Nested body.</p></details>'
            . '</details>';
        $result = purify_html($input);
        $this->assertStringContainsString('<details>', $result);
        $this->assertStringContainsString('<summary>', $result);
        $this->assertStringContainsString('Outer', $result);
        $this->assertStringContainsString('Inner', $result);
    }

    /**
     * A <details> without a <summary> still passes through (summary is optional
     * per the spec, though browsers may synthesise one).
     */
    public function test_details_without_summary(): void {
        $input = '<details><p>Just body.</p></details>';
        $result = purify_html($input);
        $this->assertStringContainsString('<details>', $result);
        $this->assertStringContainsString('Just body.', $result);
    }

    /**
     * A <summary> outside <details> is allowed as a standalone block element.
     */
    public function test_standalone_summary_preserved(): void {
        $input = '<summary>Standalone summary</summary>';
        $result = purify_html($input);
        $this->assertStringContainsString('<summary>', $result);
        $this->assertStringContainsString('Standalone summary', $result);
    }
}
