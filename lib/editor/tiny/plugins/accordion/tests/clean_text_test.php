<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Tests that accordion markup keeps class and style through clean_text (HTMLPurifier).
 *
 * @package    tiny_accordion
 * @copyright  2026 LSU Online & Continuing Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_accordion;

/**
 * Clean text tests for details/summary.
 *
 * @package    tiny_accordion
 * @category   test
 * @coversNothing
 */
final class clean_text_test extends \advanced_testcase {
    /**
     * Ensure class and inline style on TinyMCE accordion elements survive cleaning.
     */
    public function test_details_summary_class_and_style_preserved(): void {
        $this->resetAfterTest();

        $html = '<details class="mce-accordion course-acc" style="border:1px solid #ccc" open>'
            . '<summary class="acc-hdr" style="font-weight:bold">Q</summary>'
            . '<p>Answer</p></details>';

        $clean = clean_text($html, FORMAT_HTML);

        $this->assertStringContainsString('mce-accordion', $clean);
        $this->assertStringContainsString('course-acc', $clean);
        $this->assertStringContainsString('acc-hdr', $clean);
        $this->assertStringContainsString('border', $clean);
        $this->assertStringContainsString('font-weight', $clean);
    }
}
