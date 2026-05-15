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

namespace block_simple_restore\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

use simple_restore_utils;
use stdClass;

/**
 * Bug-050 tests for {@see simple_restore_utils::partition_by_coursetype()}.
 *
 * The function buckets backup rows by their coursetype so the Restore Courses page
 * can render the three groups (Blueprints / year-bucketed teaching / Other) in
 * distinct sections rather than smearing blueprint rows across spurious year buckets.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \simple_restore_utils::partition_by_coursetype
 */
final class partition_coursetype_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Build a small fixture row with just a coursetype + filename for ordering checks.
     */
    private static function row(string $coursetype, string $filename = ''): stdClass {
        return (object) [
            'coursetype' => $coursetype,
            'filename'   => $filename,
        ];
    }

    public function test_partition_separates_blueprint_teaching_other(): void {
        $rows = [
            self::row('blueprint', 'bp1.zip'),
            self::row('teaching',  't1.zip'),
            self::row('other',     'o1.zip'),
            self::row('blueprint', 'bp2.zip'),
            self::row('teaching',  't2.zip'),
            self::row('other',     'o2.zip'),
        ];

        $result = simple_restore_utils::partition_by_coursetype($rows);

        $this->assertCount(2, $result['blueprint']);
        $this->assertCount(2, $result['teaching']);
        $this->assertCount(2, $result['other']);
        $this->assertSame('bp1.zip', $result['blueprint'][0]->filename);
        $this->assertSame('bp2.zip', $result['blueprint'][1]->filename);
        $this->assertSame('t1.zip',  $result['teaching'][0]->filename);
        $this->assertSame('o1.zip',  $result['other'][0]->filename);
    }

    public function test_partition_unknown_coursetype_falls_into_other(): void {
        $rows = [self::row('archived', 'a1.zip')];
        $result = simple_restore_utils::partition_by_coursetype($rows);

        $this->assertSame([], $result['blueprint']);
        $this->assertSame([], $result['teaching']);
        $this->assertCount(1, $result['other']);
        $this->assertSame('a1.zip', $result['other'][0]->filename);
    }

    public function test_partition_case_insensitive(): void {
        $rows = [
            self::row('Blueprint',  'bp1.zip'),
            self::row('TEACHING',   't1.zip'),
            self::row('blueprint',  'bp2.zip'),
        ];

        $result = simple_restore_utils::partition_by_coursetype($rows);

        $this->assertCount(2, $result['blueprint']);
        $this->assertCount(1, $result['teaching']);
        $this->assertCount(0, $result['other']);
    }

    public function test_partition_empty_input(): void {
        $this->assertSame(
            ['blueprint' => [], 'teaching' => [], 'other' => []],
            simple_restore_utils::partition_by_coursetype([])
        );
    }

    public function test_partition_preserves_ordering(): void {
        // Three blueprint rows in a specific order — partition must preserve relative order.
        $rows = [
            self::row('blueprint', 'a.zip'),
            self::row('teaching',  'x.zip'),
            self::row('blueprint', 'b.zip'),
            self::row('blueprint', 'c.zip'),
        ];

        $result = simple_restore_utils::partition_by_coursetype($rows);
        $bpfilenames = array_map(static fn($r): string => $r->filename, $result['blueprint']);

        $this->assertSame(['a.zip', 'b.zip', 'c.zip'], $bpfilenames);
        // Keys should be 0..n-1 (re-indexed via [] = push), confirming preserved order.
        $this->assertSame([0, 1, 2], array_keys($result['blueprint']));
    }

    public function test_partition_missing_coursetype_field_falls_into_other(): void {
        // Defensive: a row entirely missing the coursetype property should not error.
        $rows = [(object) ['filename' => 'nokey.zip']];
        $result = simple_restore_utils::partition_by_coursetype($rows);

        $this->assertCount(1, $result['other']);
        $this->assertSame('nokey.zip', $result['other'][0]->filename);
    }
}
