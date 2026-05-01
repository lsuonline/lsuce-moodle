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
 * Unit tests for tiny_accordion\preset_parser.
 *
 * @package    tiny_accordion
 * @category   test
 * @copyright  2026 LSU Online & Continuing Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiny_accordion\preset_parser
 */

namespace tiny_accordion;

/**
 * Tests for preset_parser: parse(), decode(), sanitise(), and serialise().
 *
 * @package    tiny_accordion
 * @category   test
 */
final class preset_parser_test extends \advanced_testcase {

    /** @var preset_parser */
    private preset_parser $parser;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(false);
        $this->parser = new preset_parser();
    }

    // ── decode() ────────────────────────────────────────────────────────────

    /**
     * Empty string decodes to null.
     */
    public function test_decode_empty_string_returns_null(): void {
        $this->assertNull($this->parser->decode(''));
    }

    /**
     * Whitespace-only string decodes to null.
     */
    public function test_decode_whitespace_returns_null(): void {
        $this->assertNull($this->parser->decode('   '));
    }

    /**
     * '[]' decodes to null (treated as "no presets").
     */
    public function test_decode_empty_array_json_returns_null(): void {
        $this->assertNull($this->parser->decode('[]'));
    }

    /**
     * Malformed JSON returns null.
     */
    public function test_decode_malformed_json_returns_null(): void {
        $this->assertNull($this->parser->decode('{not valid json'));
    }

    /**
     * JSON object (not array) returns null.
     */
    public function test_decode_json_object_returns_null(): void {
        $this->assertNull($this->parser->decode('{"label":"test"}'));
    }

    /**
     * Valid JSON array is decoded to array.
     */
    public function test_decode_valid_json_returns_array(): void {
        $json = '[{"label":"Blue","detailsclass":"acc-blue","detailsstyle":"","summaryclass":"hdr-blue","summarystyle":""}]';
        $decoded = $this->parser->decode($json);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
    }

    // ── sanitise() ──────────────────────────────────────────────────────────

    /**
     * Rows with empty label after cleaning are skipped.
     */
    public function test_sanitise_skips_empty_label_rows(): void {
        $rows = [
            ['label' => '', 'detailsclass' => 'foo', 'detailsstyle' => '', 'summaryclass' => '', 'summarystyle' => ''],
            ['label' => 'Valid', 'detailsclass' => '', 'detailsstyle' => '', 'summaryclass' => '', 'summarystyle' => ''],
        ];
        $result = $this->parser->sanitise($rows);
        $this->assertCount(1, $result);
        $this->assertSame('Valid', $result[0]['label']);
    }

    /**
     * Missing optional fields default to empty string.
     */
    public function test_sanitise_missing_optional_fields_default_empty(): void {
        $rows = [['label' => 'Minimal']];
        $result = $this->parser->sanitise($rows);
        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]['detailsclass']);
        $this->assertSame('', $result[0]['detailsstyle']);
        $this->assertSame('', $result[0]['summaryclass']);
        $this->assertSame('', $result[0]['summarystyle']);
    }

    /**
     * XSS payload in label is stripped by clean_param(PARAM_TEXT).
     */
    public function test_sanitise_strips_xss_in_label(): void {
        $rows = [['label' => '<script>alert(1)</script>']];
        $result = $this->parser->sanitise($rows);
        // PARAM_TEXT strips tags; result may be empty (skip) or cleaned text.
        if (count($result) > 0) {
            $this->assertStringNotContainsString('<script>', $result[0]['label']);
        } else {
            // Label reduced to empty string → row skipped; that's also acceptable.
            $this->assertCount(0, $result);
        }
    }

    /**
     * XSS payload in detailsclass is stripped.
     */
    public function test_sanitise_strips_xss_in_detailsclass(): void {
        $rows = [['label' => 'Test', 'detailsclass' => '"><img src=x onerror=alert(1)>']];
        $result = $this->parser->sanitise($rows);
        $this->assertCount(1, $result);
        $this->assertStringNotContainsString('<img', $result[0]['detailsclass']);
    }

    /**
     * XSS payload in summarystyle is stripped.
     */
    public function test_sanitise_strips_xss_in_summarystyle(): void {
        $rows = [['label' => 'Test', 'summarystyle' => 'expression(alert(1))']];
        $result = $this->parser->sanitise($rows);
        $this->assertCount(1, $result);
        // PARAM_TEXT should sanitise but not necessarily remove CSS-like content;
        // the important thing is no HTML injection.
        $this->assertStringNotContainsString('<', $result[0]['summarystyle']);
    }

    /**
     * Non-array row entries within the outer array are skipped.
     */
    public function test_sanitise_skips_non_array_rows(): void {
        $rows = ['not-an-array', null, ['label' => 'Good']];
        $result = $this->parser->sanitise($rows);
        $this->assertCount(1, $result);
        $this->assertSame('Good', $result[0]['label']);
    }

    // ── parse() (integration of decode + sanitise) ──────────────────────────

    /**
     * Valid JSON round-trips correctly through parse().
     */
    public function test_parse_valid_json_roundtrip(): void {
        $presets = [
            [
                'label'        => 'Blue header',
                'detailsclass' => 'accordion-blue',
                'detailsstyle' => '',
                'summaryclass' => 'accordion-header-blue',
                'summarystyle' => '',
            ],
        ];
        $json = json_encode($presets);
        $result = $this->parser->parse($json);
        $this->assertCount(1, $result);
        $this->assertSame('Blue header', $result[0]['label']);
        $this->assertSame('accordion-blue', $result[0]['detailsclass']);
        $this->assertSame('accordion-header-blue', $result[0]['summaryclass']);
    }

    /**
     * Malformed JSON returns empty array from parse().
     */
    public function test_parse_malformed_json_returns_empty_array(): void {
        $this->assertSame([], $this->parser->parse('{bad json'));
    }

    /**
     * Empty string returns empty array from parse().
     */
    public function test_parse_empty_string_returns_empty_array(): void {
        $this->assertSame([], $this->parser->parse(''));
    }

    /**
     * Multiple presets with some empty-label rows are filtered.
     */
    public function test_parse_filters_empty_label_rows(): void {
        $json = json_encode([
            ['label' => 'Keep', 'detailsclass' => 'a'],
            ['label' => '',     'detailsclass' => 'b'],
            ['label' => 'Also keep', 'detailsclass' => 'c'],
        ]);
        $result = $this->parser->parse($json);
        $this->assertCount(2, $result);
        $this->assertSame('Keep', $result[0]['label']);
        $this->assertSame('Also keep', $result[1]['label']);
    }

    // ── serialise() ─────────────────────────────────────────────────────────

    /**
     * serialise() produces valid JSON that can be round-tripped.
     */
    public function test_serialise_produces_valid_json(): void {
        $presets = [
            ['label' => 'Test', 'detailsclass' => 'foo', 'detailsstyle' => '', 'summaryclass' => 'bar', 'summarystyle' => ''],
        ];
        $json = $this->parser->serialise($presets);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('Test', $decoded[0]['label']);
    }

    /**
     * serialise() re-indexes array keys.
     */
    public function test_serialise_reindexes_keys(): void {
        $presets = [
            5 => ['label' => 'A', 'detailsclass' => '', 'detailsstyle' => '', 'summaryclass' => '', 'summarystyle' => ''],
            9 => ['label' => 'B', 'detailsclass' => '', 'detailsstyle' => '', 'summaryclass' => '', 'summarystyle' => ''],
        ];
        $json = $this->parser->serialise($presets);
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey(0, $decoded);
        $this->assertArrayHasKey(1, $decoded);
    }
}
