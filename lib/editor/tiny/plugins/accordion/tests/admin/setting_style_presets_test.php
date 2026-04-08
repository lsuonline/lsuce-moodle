<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for tiny_accordion\admin\setting_style_presets.
 *
 * @package    tiny_accordion
 * @category   test
 * @copyright  2026 LSU Online & Continuing Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiny_accordion\admin\setting_style_presets
 */

namespace tiny_accordion\admin;

use tiny_accordion\admin\setting_style_presets;

/**
 * Tests for setting_style_presets::write_setting() and get_setting().
 *
 * @package    tiny_accordion
 * @category   test
 */
final class setting_style_presets_test extends \advanced_testcase {

    /** @var setting_style_presets */
    private setting_style_presets $setting;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setting = new setting_style_presets(
            'tiny_accordion/stylepresets',
            'Style presets',
            'Configure presets.',
            ''
        );
    }

    // ── write_setting() ─────────────────────────────────────────────────────

    /**
     * Valid JSON is stored and returns empty string (success).
     */
    public function test_write_setting_valid_json_stores_and_returns_empty(): void {
        $json = json_encode([
            ['label' => 'Blue', 'detailsclass' => 'acc-blue', 'detailsstyle' => '',
             'summaryclass' => 'hdr-blue', 'summarystyle' => ''],
        ]);
        $result = $this->setting->write_setting($json);
        $this->assertSame('', $result);
        $stored = get_config('tiny_accordion', 'stylepresets');
        $this->assertNotFalse($stored);
        $decoded = json_decode($stored, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('Blue', $decoded[0]['label']);
    }

    /**
     * Empty string stores '[]' and returns success.
     */
    public function test_write_setting_empty_string_stores_empty_array(): void {
        $result = $this->setting->write_setting('');
        $this->assertSame('', $result);
        $this->assertSame('[]', get_config('tiny_accordion', 'stylepresets'));
    }

    /**
     * '[]' stores '[]' and returns success.
     */
    public function test_write_setting_empty_array_json_stores_empty_array(): void {
        $result = $this->setting->write_setting('[]');
        $this->assertSame('', $result);
        $this->assertSame('[]', get_config('tiny_accordion', 'stylepresets'));
    }

    /**
     * Malformed JSON returns an error lang string.
     */
    public function test_write_setting_malformed_json_returns_error(): void {
        $result = $this->setting->write_setting('{not: valid}');
        $this->assertNotSame('', $result);
        $this->assertStringContainsString('Invalid', $result);
    }

    /**
     * Rows with empty label are silently stripped from storage.
     */
    public function test_write_setting_strips_empty_label_rows(): void {
        $json = json_encode([
            ['label' => 'Keep', 'detailsclass' => 'a', 'detailsstyle' => '', 'summaryclass' => '', 'summarystyle' => ''],
            ['label' => '', 'detailsclass' => 'b', 'detailsstyle' => '', 'summaryclass' => '', 'summarystyle' => ''],
        ]);
        $result = $this->setting->write_setting($json);
        $this->assertSame('', $result);
        $stored = get_config('tiny_accordion', 'stylepresets');
        $decoded = json_decode($stored, true);
        $this->assertCount(1, $decoded);
        $this->assertSame('Keep', $decoded[0]['label']);
    }

    /**
     * XSS in label is sanitised via clean_param before storage.
     */
    public function test_write_setting_sanitises_xss_in_label(): void {
        $json = json_encode([
            ['label' => '<b>Bold</b>', 'detailsclass' => '', 'detailsstyle' => '',
             'summaryclass' => '', 'summarystyle' => ''],
        ]);
        $result = $this->setting->write_setting($json);
        $this->assertSame('', $result);
        $stored = get_config('tiny_accordion', 'stylepresets');
        $this->assertStringNotContainsString('<b>', $stored);
    }

    /**
     * Raw POST string is never stored — only re-encoded clean JSON.
     */
    public function test_write_setting_never_stores_raw_post(): void {
        $malicious = '[{"label":"Test","detailsclass":"<script>x</script>","detailsstyle":"","summaryclass":"","summarystyle":""}]';
        $this->setting->write_setting($malicious);
        $stored = get_config('tiny_accordion', 'stylepresets');
        $this->assertStringNotContainsString('<script>', $stored);
    }

    // ── get_setting() ───────────────────────────────────────────────────────

    /**
     * get_setting() returns previously stored value.
     */
    public function test_get_setting_returns_stored_value(): void {
        $json = json_encode([
            ['label' => 'Test', 'detailsclass' => 'foo', 'detailsstyle' => '',
             'summaryclass' => 'bar', 'summarystyle' => ''],
        ]);
        $this->setting->write_setting($json);
        $value = $this->setting->get_setting();
        $this->assertIsString($value);
        $decoded = json_decode($value, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Test', $decoded[0]['label']);
    }

    /**
     * get_setting() returns empty string when nothing has been stored yet.
     */
    public function test_get_setting_returns_empty_string_when_unset(): void {
        // Ensure the config key is unset.
        unset_config('stylepresets', 'tiny_accordion');
        $value = $this->setting->get_setting();
        $this->assertSame('', $value);
    }
}
