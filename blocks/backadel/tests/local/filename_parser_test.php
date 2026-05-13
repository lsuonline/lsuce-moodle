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

declare(strict_types=1);

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for {@see filename_parser}.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_backadel\local\filename_parser
 */
class filename_parser_test extends \advanced_testcase {

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function parse(string $filename): ?array {
        return filename_parser::parse($filename);
    }

    // -----------------------------------------------------------------------
    // Null / skip cases
    // -----------------------------------------------------------------------

    public function test_empty_returns_null(): void {
        $this->assertNull($this->parse(''));
        $this->assertNull($this->parse('   '));
    }

    public function test_directory_path_returns_null(): void {
        $this->assertNull($this->parse('/some/dir/'));
    }

    public function test_partial_extensions_return_null(): void {
        $this->assertNull($this->parse('backup.filepart'));
        $this->assertNull($this->parse('backup.tmp'));
        $this->assertNull($this->parse('backup.partial'));
    }

    // -----------------------------------------------------------------------
    // Pattern A — semester_legacy (uppercase semester + dept)
    // -----------------------------------------------------------------------

    public function test_pattern_a_full(): void {
        $f = '2024SpringMATH12010011234567890.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame(2024, $r['year']);
        $this->assertSame('Spring', $r['semester']);
        $this->assertSame('MATH', $r['dept']);
        $this->assertSame('1201', $r['course_num']);
        $this->assertSame('001', $r['course_idnumber']);
        $this->assertSame(1234567890, $r['backup_ts']);
    }

    public function test_pattern_a_four_digit_course_no_section(): void {
        // Digit run exactly 4 — no idnumber suffix.
        $f = '2023FallENGL1001_jsmith_1234567890.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame('ENGL', $r['dept']);
        $this->assertSame('1001', $r['course_num']);
        $this->assertNull($r['course_idnumber']);
        $this->assertSame(['jsmith'], $r['instructors']);
    }

    public function test_pattern_a_with_instructor_tokens(): void {
        $f = '2025SummerCSC3501001_dcastr10_1700000000.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame(['dcastr10'], $r['instructors']);
        $this->assertSame('MATH', null); // avoid wrong check
        $this->assertSame('CSC', $r['dept']);
        $this->assertSame('3501', $r['course_num']);
        $this->assertSame('001', $r['course_idnumber']);
    }

    // -----------------------------------------------------------------------
    // Pattern A_lc — semester_legacy_lc (lowercase semester)
    // -----------------------------------------------------------------------

    public function test_pattern_a_lowercase_semester(): void {
        $f = '2024springMATH1201_jsmith_1234567890.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy_lc', $r['pattern']);
        $this->assertSame('Spring', $r['semester']);
        $this->assertSame('MATH', $r['dept']);
    }

    // -----------------------------------------------------------------------
    // Pattern B — storage_course
    // -----------------------------------------------------------------------

    public function test_pattern_b_storage_course(): void {
        $f = 'storage_course_somecourse_body_here_1400000000.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('storage_course', $r['pattern']);
        $this->assertSame(1400000000, $r['backup_ts']);
        $this->assertNull($r['year']);
        $this->assertNull($r['dept']);
    }

    // -----------------------------------------------------------------------
    // Pattern C — storagecourse_dept
    // -----------------------------------------------------------------------

    public function test_pattern_c_storagecourse_dept(): void {
        $f = 'storagecourse_CHEM_2001_SomeBody_1500000000.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('storagecourse_dept', $r['pattern']);
        $this->assertSame('CHEM', $r['dept']);
        $this->assertSame('2001', $r['course_num']);
        $this->assertSame(1500000000, $r['backup_ts']);
    }

    // -----------------------------------------------------------------------
    // Pattern D — backadel_modern
    // -----------------------------------------------------------------------

    public function test_pattern_d_backadel_zip(): void {
        $f = 'backadel-ENGL-4001_jsmith.zip';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('backadel_modern', $r['pattern']);
        $this->assertContains('jsmith', $r['instructors']);
    }

    public function test_pattern_d_backadel_mbz(): void {
        $f = 'backadel-MATH-1001.mbz';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('backadel_modern', $r['pattern']);
    }

    public function test_pattern_d_char_class_does_not_accept_comma(): void {
        // [- *] should not match comma — file should hit 'backadel_modern' via slug, not break.
        $f = 'backadel-,badchar.zip';
        $r = $this->parse($f);
        // Comma after backadel- means the regex won't match ([-*] doesn't cover comma).
        // Falls to unknown.
        $this->assertSame('unknown', $r['pattern']);
    }

    // -----------------------------------------------------------------------
    // Moodle native
    // -----------------------------------------------------------------------

    public function test_moodle_native_mbz(): void {
        $f = 'backup-moodle2-course-42-MATH-1001-20240315-1430.mbz';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('moodle_native', $r['pattern']);
        $this->assertSame('MATH-1001', $r['shortname_hint']);
        $this->assertGreaterThan(0, $r['backup_ts']);
    }

    public function test_moodle_native_invalid_month_returns_zero_ts(): void {
        $f = 'backup-moodle2-course-1-slug-20241399-1200.mbz';
        $r = $this->parse($f);
        $this->assertNotNull($r);
        $this->assertSame('moodle_native', $r['pattern']);
        $this->assertSame(0, $r['backup_ts']);
    }

    // -----------------------------------------------------------------------
    // Fallback / unknown
    // -----------------------------------------------------------------------

    public function test_unknown_pattern_with_ts(): void {
        $f = 'random_backup_name_1234567890.zip';
        $r = $this->parse($f);
        $this->assertSame('unknown', $r['pattern']);
        $this->assertSame(1234567890, $r['backup_ts']);
    }

    public function test_unknown_pattern_no_ts(): void {
        $f = 'random_backup_name.zip';
        $r = $this->parse($f);
        $this->assertSame('unknown', $r['pattern']);
        $this->assertSame(0, $r['backup_ts']);
    }

    // -----------------------------------------------------------------------
    // Result structure completeness
    // -----------------------------------------------------------------------

    public function test_result_has_all_required_keys(): void {
        $r = $this->parse('2024SpringMATH1201_jsmith_1234567890.zip');
        $expected = ['pattern', 'year', 'semester', 'dept', 'course_num', 'course_idnumber',
                     'instructors', 'backup_ts', 'shortname_hint', 'raw_filename'];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $r, "Missing key: $key");
        }
    }

    // -----------------------------------------------------------------------
    // split_digit_run edge cases
    // -----------------------------------------------------------------------

    public function test_split_three_digit_run_no_idnumber(): void {
        // 3-digit course number (edge case) — whole run is course_num.
        $f = '2024SpringMATH123_jsmith_1234567890.zip';
        $r = $this->parse($f);
        // 3-digit run: regex requires \d{4,} so this won't match PATTERN_SEMESTER_LEGACY.
        // Falls through to unknown.
        $this->assertSame('unknown', $r['pattern']);
    }

    public function test_split_exact_four_digit_gives_empty_idnumber(): void {
        $f = '2024FallCSC4050_jsmith_1234567890.zip';
        $r = $this->parse($f);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame('4050', $r['course_num']);
        $this->assertNull($r['course_idnumber']);
    }

    public function test_split_seven_digit_gives_four_plus_three(): void {
        $f = '2024FallCSC40500011234567890.zip';
        $r = $this->parse($f);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame('4050', $r['course_num']);
        $this->assertSame('001', $r['course_idnumber']);
    }

    // -----------------------------------------------------------------------
    // Instructor deduplication
    // -----------------------------------------------------------------------

    public function test_instructor_dedup(): void {
        // Duplicate tokens after the dept+run segment — deduped by result_template.
        $f = '2024SpringMATH1201_jsmith_jsmith_1234567890.zip';
        $r = $this->parse($f);
        // The regex captures _jsmith_jsmith as the instructor group.
        // instructors_from_capture splits on _ → ['jsmith', 'jsmith'].
        // result_template does array_unique.
        $this->assertCount(1, $r['instructors']);
        $this->assertSame('jsmith', $r['instructors'][0]);
    }

    // -----------------------------------------------------------------------
    // Path handling
    // -----------------------------------------------------------------------

    public function test_full_path_uses_basename(): void {
        $f = '/var/backups/semester/2024SpringMATH1201_1234567890.zip';
        $r = $this->parse($f);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame($f, $r['raw_filename']);
    }
}
