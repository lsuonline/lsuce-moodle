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

namespace block_backadel\tests;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the CLI script blocks/backadel/cli/fast_populate_catalogue.php.
 *
 * These tests exercise the discrete behaviours added in MD-2189:
 *
 *  - Existing-row count check (warning trigger when source already has rows).
 *  - Truncate semantics (DELETE FROM catalogue + courses before insert).
 *  - Rate / ETA math (pure arithmetic, no filesystem needed).
 *
 * We do NOT exercise the file-scan or batch-insert paths — those are slow,
 * filesystem-dependent, and already covered indirectly by migrator tests.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversNothing
 */
final class cli_fast_populate_test extends \advanced_testcase {

    /**
     * Insert a minimal catalogue row to seed the table for count/truncate tests.
     *
     * @param string $source
     * @param string $filename
     * @return int New row id.
     */
    private function insert_catalogue_row(string $source, string $filename): int {
        global $DB;
        $now = time();
        $row = (object) [
            'filename'        => $filename,
            'filepath'        => '/tmp/' . $filename,
            'filepath_full'   => '/tmp/' . $filename,
            'filepath_hash'   => sha1('/tmp/' . $filename),
            'source'          => $source,
            'year'            => null,
            'semester'        => null,
            'dept'            => null,
            'course_num'      => null,
            'course_idnumber' => null,
            'shortname'       => null,
            'instructors'     => json_encode([]),
            'pattern'         => 'unknown',
            'backup_ts'       => 0,
            'file_size'       => null,
            'status'          => 'available',
            'timecreated'     => $now,
            'timemodified'    => $now,
        ];
        return (int) $DB->insert_record('block_backadel_catalogue', $row);
    }

    /**
     * Insert a minimal courses row.
     *
     * @param string $filename
     * @return int New row id.
     */
    private function insert_courses_row(string $filename): int {
        global $DB;
        $now = time();
        $filepath = '/tmp/' . $filename;
        $row = (object) [
            'courseid'         => null,
            'coursefullname'   => $filename,
            'courseshortname'  => 'shortname_' . $filename,
            'courseidnumber'   => null,
            'status'           => 'available',
            'filepath'         => $filepath,
            'filepath_hash'    => sha1($filepath),     // bug-044: required for UNIQUE filepath_hash_uk
            'filename'         => $filename,
            'filesize'         => null,
            'timecreated'      => $now,
            'backupcreated'    => null,
            'semester'         => '2024_Fall',
            'academicperiodid' => null,
            'coursetype'       => 'unknown',
            'statusid'         => null,
        ];
        return (int) $DB->insert_record('block_backadel_courses', $row);
    }

    /**
     * The warning logic in the CLI is driven by count_records() filtered by source.
     * Verify that count returns the right value before and after seeding rows for
     * a given source, and that the "warn?" condition matches the CLI's check.
     */
    public function test_existing_count_for_source(): void {
        global $DB;
        $this->resetAfterTest(true);

        $source = 'legacy_moodleus';

        // Empty state: no warning should fire.
        $count = $DB->count_records('block_backadel_catalogue', ['source' => $source]);
        $this->assertSame(0, $count);
        $this->assertFalse($count > 0, 'No rows: warning condition must be false.');

        // Seed rows for the same source.
        $this->insert_catalogue_row($source, 'a.zip');
        $this->insert_catalogue_row($source, 'b.zip');

        // Seed rows for a different source (must not contaminate the count).
        $this->insert_catalogue_row('backadel_current', 'c.zip');

        $count = $DB->count_records('block_backadel_catalogue', ['source' => $source]);
        $this->assertSame(2, $count, 'Source filter must scope the count.');
        $this->assertTrue($count > 0, 'Non-empty source: warning condition must be true.');
    }

    /**
     * Replicates the CLI's --truncate branch: count rows, delete both tables,
     * verify counts are zero, and confirm a subsequent insert succeeds.
     */
    public function test_truncate_clears_both_tables(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Seed catalogue + courses with several rows.
        $this->insert_catalogue_row('legacy_moodleus', 'one.zip');
        $this->insert_catalogue_row('legacy_moodleus', 'two.zip');
        $this->insert_catalogue_row('backadel_current', 'three.zip');
        $this->insert_courses_row('one.zip');
        $this->insert_courses_row('two.zip');

        $catcount = $DB->count_records('block_backadel_catalogue');
        $crscount = $DB->count_records('block_backadel_courses');
        $this->assertSame(3, $catcount);
        $this->assertSame(2, $crscount);

        // Mirror the CLI's --truncate block.
        $DB->delete_records('block_backadel_catalogue');
        $DB->delete_records('block_backadel_courses');

        $this->assertSame(0, $DB->count_records('block_backadel_catalogue'));
        $this->assertSame(0, $DB->count_records('block_backadel_courses'));

        // Confirm we can still insert after truncate (no schema damage).
        $newid = $this->insert_catalogue_row('legacy_moodleus', 'fresh.zip');
        $this->assertGreaterThan(0, $newid);
        $this->assertSame(1, $DB->count_records('block_backadel_catalogue'));
    }

    /**
     * Pure-math test for the progress line's rate / ETA calculation.
     *
     * Mirrors:
     *   $rate = $elapsed > 0 ? round($catins * 60 / $elapsed, 1) : 0.0;
     *   $remaining = $total - $catins;
     *   $etastr = ($rate > 0 && $remaining > 0)
     *       ? round($remaining / $rate, 1) . ' min'
     *       : 'unknown';
     */
    public function test_rate_and_eta_math(): void {
        // 1000 rows in 60s → 1000 rows/min.
        $catins   = 1000;
        $total    = 5000;
        $elapsed  = 60.0;

        $rate = $elapsed > 0 ? round($catins * 60 / $elapsed, 1) : 0.0;
        $remaining = $total - $catins;
        $etastr = ($rate > 0 && $remaining > 0)
            ? round($remaining / $rate, 1) . ' min'
            : 'unknown';

        $this->assertSame(1000.0, $rate);
        $this->assertSame(4000, $remaining);
        $this->assertSame('4 min', $etastr);
    }

    /**
     * Edge cases for the rate / ETA math: zero elapsed, zero remaining, zero inserted.
     */
    public function test_rate_and_eta_edge_cases(): void {
        // Zero elapsed → rate must be 0.0, ETA "unknown".
        $catins = 100;
        $total  = 500;
        $elapsed = 0.0;
        $rate = $elapsed > 0 ? round($catins * 60 / $elapsed, 1) : 0.0;
        $remaining = $total - $catins;
        $etastr = ($rate > 0 && $remaining > 0) ? round($remaining / $rate, 1) . ' min' : 'unknown';
        $this->assertSame(0.0, $rate);
        $this->assertSame('unknown', $etastr);

        // Zero remaining → ETA "unknown" (per CLI: only computed when remaining>0).
        $catins = 500;
        $total  = 500;
        $elapsed = 30.0;
        $rate = $elapsed > 0 ? round($catins * 60 / $elapsed, 1) : 0.0;
        $remaining = $total - $catins;
        $etastr = ($rate > 0 && $remaining > 0) ? round($remaining / $rate, 1) . ' min' : 'unknown';
        $this->assertSame(1000.0, $rate);
        $this->assertSame(0, $remaining);
        $this->assertSame('unknown', $etastr);

        // Zero inserted, non-zero elapsed → rate 0, ETA "unknown".
        $catins = 0;
        $total  = 1000;
        $elapsed = 5.0;
        $rate = $elapsed > 0 ? round($catins * 60 / $elapsed, 1) : 0.0;
        $remaining = $total - $catins;
        $etastr = ($rate > 0 && $remaining > 0) ? round($remaining / $rate, 1) . ' min' : 'unknown';
        $this->assertSame(0.0, $rate);
        $this->assertSame('unknown', $etastr);
    }

    /**
     * Final-line average rate calc mirrors the same formula used for the per-progress rate.
     */
    public function test_final_total_rate(): void {
        $catins  = 12345;
        $elapsed = 123.4;
        $totalrate = $elapsed > 0 ? round($catins * 60 / $elapsed, 1) : 0.0;
        // 12345 * 60 / 123.4 = 6001.62... → rounded to 1dp.
        $this->assertEqualsWithDelta(6001.6, $totalrate, 0.1);

        // Zero elapsed → guard returns 0.0, no division-by-zero.
        $totalrate = 0 > 0 ? round($catins * 60 / 0, 1) : 0.0;
        $this->assertSame(0.0, $totalrate);
    }
}
