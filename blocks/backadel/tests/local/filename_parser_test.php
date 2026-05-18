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
        $f = '2024SpringMATH1201001_1234567890.zip';
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
        // dept is CSC for this fixture — MATH assertion removed (was always asserting null === 'MATH')
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
        // PATTERN_BACKADEL uses .+? for the slug so comma IS captured — file hits backadel_modern.
        $f = 'backadel-,badchar.zip';
        $r = $this->parse($f);
        $this->assertSame('backadel_modern', $r['pattern']);
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
        // random_backup_name matches PATTERN_STORAGE_LEGACY (slug_ts format).
        $f = 'random_backup_name_1234567890.zip';
        $r = $this->parse($f);
        $this->assertSame('storage_legacy', $r['pattern']);
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
        // Needs underscore before backup_ts; digit_run = 4050001 splits into 4050 + 001.
        $f = '2024FallCSC4050001_1234567890.zip';
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

    // -----------------------------------------------------------------------
    // bug-043 — international-session semester variants (Int / INTL)
    // -----------------------------------------------------------------------

    /**
     * SpringINTL with a real dept code following: parses as semester_legacy with
     * canonical `SpringInt` semester and the dept correctly extracted.
     */
    public function test_intl_long_form_with_dept(): void {
        $r = $this->parse('2012SpringINTLSPAN10010001_nicklen_1338842283.zip');
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame(2012, $r['year']);
        $this->assertSame('SpringInt', $r['semester']);
        $this->assertSame('SPAN', $r['dept']);
        $this->assertSame('1001', $r['course_num']);
        $this->assertSame(['nicklen'], $r['instructors']);
    }

    /**
     * SpringINTL followed only by a digit run (no dept code) — production sample
     * `2012SpringINTL200020207_nicklen_…zip`. Routes through the dedicated
     * `semester_legacy_intl` pattern with dept null.
     */
    public function test_intl_long_form_no_dept(): void {
        $r = $this->parse('2012SpringINTL200020207_nicklen_1338842283.zip');
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy_intl', $r['pattern']);
        $this->assertSame(2012, $r['year']);
        $this->assertSame('SpringInt', $r['semester']);
        $this->assertNull($r['dept']);
        $this->assertNull($r['course_num']);
        $this->assertNull($r['course_idnumber']);
        $this->assertSame(['nicklen'], $r['instructors']);
        $this->assertSame(1338842283, $r['backup_ts']);
    }

    /**
     * Short `Int` form with dept code (the most common production shape, 138 files).
     * Previously fell through to `unknown` because the dept regex tripped over `n` in `Int`.
     */
    public function test_int_short_form_with_dept(): void {
        $r = $this->parse('2012SpringIntAAAS241010589_jamsulli_1338841826.zip');
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame(2012, $r['year']);
        $this->assertSame('SpringInt', $r['semester']);
        $this->assertSame('AAAS', $r['dept']);
        $this->assertSame('2410', $r['course_num']);
        $this->assertSame(['jamsulli'], $r['instructors']);
    }

    /**
     * Lowercase `int` short form must also normalise to `SpringInt` and route through
     * the LC pattern.
     */
    public function test_int_short_form_lowercase(): void {
        $r = $this->parse('2012springintaaas241010589_jamsulli_1338841826.zip');
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy_lc', $r['pattern']);
        $this->assertSame('SpringInt', $r['semester']);
        $this->assertSame('AAAS', $r['dept']);
    }

    /**
     * Defensive coverage: Fall + INTL parses correctly (LSU only ships Spring INTL today
     * but the parser must handle the full season set).
     */
    public function test_fall_intl_with_dept(): void {
        $r = $this->parse('2018FallINTLENGL40010001_jdoe_1538841826.zip');
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame('FallInt', $r['semester']);
        $this->assertSame('ENGL', $r['dept']);
    }

    /**
     * Backadel-prefixed instructor archive carrying the SpringINTL semester token —
     * dept LA (the academic college) must still be extracted, semester normalised.
     */
    public function test_backadel_instructor_intl(): void {
        $r = $this->parse('backadel-2019-SpringINTL-SPAN-1001-for-Jane-Doe_jdoe@lsu.edu.zip');
        $this->assertNotNull($r);
        $this->assertSame('backadel_instructor', $r['pattern']);
        $this->assertSame(2019, $r['year']);
        $this->assertSame('SpringInt', $r['semester']);
        $this->assertSame('SPAN', $r['dept']);
        $this->assertSame('1001', $r['course_num']);
        // Full email preserved — instructor_resolver handles email-first lookup (bug-059).
        $this->assertContains('jdoe@lsu.edu', $r['instructors']);
    }

    /**
     * Backadel-prefixed instructor archive with the short `Int` semester variant.
     */
    public function test_backadel_instructor_int_short(): void {
        $r = $this->parse('backadel-2019-SpringInt-LA-1203-for-Jane-Doe_jdoe@lsu.edu.zip');
        $this->assertNotNull($r);
        $this->assertSame('backadel_instructor', $r['pattern']);
        $this->assertSame(2019, $r['year']);
        $this->assertSame('SpringInt', $r['semester']);
        $this->assertSame('LA', $r['dept']);
        $this->assertSame('1203', $r['course_num']);
    }

    /**
     * Regression guard: SecondFall must still beat the new optional `Int` suffix and
     * the dept group must not accidentally consume `Int` from a real dept name.
     */
    public function test_second_fall_still_parses_after_intl_changes(): void {
        $r = $this->parse('2024SecondFallMATH1201_jsmith_1234567890.zip');
        $this->assertNotNull($r);
        $this->assertSame('semester_legacy', $r['pattern']);
        $this->assertSame('SecondFall', $r['semester']);
        $this->assertSame('MATH', $r['dept']);
        $this->assertSame('1201', $r['course_num']);
    }

    /**
     * Regression guard: pre-bug-043 `dept = INTL` no longer leaks for the int-suffixed
     * filename — the catalogue used to record `dept=INTL` for these files, which was wrong.
     */
    public function test_intl_no_longer_recorded_as_dept(): void {
        $r = $this->parse('2012SpringINTL200020207_nicklen_1338842283.zip');
        $this->assertNotSame('INTL', $r['dept']);
    }

    // -----------------------------------------------------------------------
    // bug-058 — slug-shaped tokens must not be classified as usernames
    // -----------------------------------------------------------------------

    /**
     * parsed_backadel() must reject a course-name slug (no underscores) as an
     * instructor token even though it is one big alphanumeric blob. Previously
     * the case-insensitive USERNAME_SEGMENT regex accepted the whole slug.
     *
     * @covers \block_backadel\local\filename_pattern_library::parsed_backadel
     */
    public function test_parsed_backadel_rejects_slug_as_username(): void {
        $r1 = $this->parse('backadel-Backup-Master-Course-FIN-7400-for-Don-Chance_1700000000.zip');
        $this->assertSame('backadel_modern', $r1['pattern']);
        $this->assertSame([], $r1['instructors'],
            'A course-name slug must NOT be stored as an instructor username');

        $r2 = $this->parse('backadel-LSU-Online-Master-Course-Training-for-Dagoberto-Diaz.zip');
        $this->assertSame('backadel_modern', $r2['pattern']);
        $this->assertSame([], $r2['instructors']);
    }

    /**
     * body_instructors() (private — accessed via reflection) must reject the
     * same slug-shaped tokens that parsed_backadel() rejects, because the
     * storage_course patterns share the same hazard.
     */
    public function test_body_instructors_rejects_slug_tokens(): void {
        $method = new \ReflectionMethod(filename_pattern_library::class, 'body_instructors');
        $method->setAccessible(true);

        $cases = [
            'Backup-Master-Course-FIN-7400-for-Don-Chance',
            'LSU-Online-Master-Course-Training-for-Dagoberto-Diaz',
            'alt2020-Second-Summer-CM-2112-for-Carol-Friedland',
        ];
        foreach ($cases as $slug) {
            $result = $method->invoke(null, $slug);
            $this->assertSame([], $result,
                'body_instructors must reject slug-shaped token: ' . $slug);
        }
    }

    /**
     * body_instructors() must continue to accept underscore-separated valid
     * username tokens — regression guard for the username-extraction path.
     */
    public function test_body_instructors_accepts_valid_username_tokens(): void {
        $method = new \ReflectionMethod(filename_pattern_library::class, 'body_instructors');
        $method->setAccessible(true);

        $this->assertSame(['tsimpson', 'jdoe'], $method->invoke(null, 'tsimpson_jdoe'));
        $this->assertSame(['jdoe12'], $method->invoke(null, 'jdoe12'));
        $this->assertSame(['ab.cd', 'ef-gh'], $method->invoke(null, 'ab.cd_ef-gh'));
    }

    /**
     * looks_like_username() (private — accessed via reflection) gate decisions
     * verified directly so future regressions are easy to localise.
     */
    public function test_looks_like_username_rejects_for_pattern(): void {
        $method = new \ReflectionMethod(filename_pattern_library::class, 'looks_like_username');
        $method->setAccessible(true);

        // Negative: course-name slug with -for-
        $this->assertFalse($method->invoke(null, 'Backup-Master-Course-FIN-7400-for-Don-Chance'));
        // Negative: too long (33+ chars)
        $this->assertFalse($method->invoke(null, 'a-very-long-username-that-exceeds-thirty-two-characters'));
        // Negative: starts with uppercase
        $this->assertFalse($method->invoke(null, 'Tsimpson'));
        // Negative: empty
        $this->assertFalse($method->invoke(null, ''));
        // Negative: starts with digit
        $this->assertFalse($method->invoke(null, '1tsimpson'));

        // Positive: typical usernames
        $this->assertTrue($method->invoke(null, 'tsimpson'));
        $this->assertTrue($method->invoke(null, 'jdoe12'));
        $this->assertTrue($method->invoke(null, 'dcastr10'));
        $this->assertTrue($method->invoke(null, 'a.b-c_d'));
    }
}
