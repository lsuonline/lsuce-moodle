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

use block_simple_restore\local\table\restore_files_table;
use ReflectionProperty;

/**
 * Bug-050 tests for the {@see restore_files_table} {@code 'flat'} variant.
 *
 * The flat variant drops year/semester/dept (which are meaningless for blueprint
 * and other-typed backups) and adds a Course code surrogate column populated
 * from {@code course_num}.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_simple_restore\local\table\restore_files_table
 */
final class restore_files_table_variant_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_flat_variant_columns_dont_include_year_or_semester_or_dept(): void {
        $table = new restore_files_table('unit_flat_cols', 1, 'C1', 0, false, 'flat');
        $this->assertArrayHasKey('coursecode', $table->columns);
        $this->assertArrayHasKey('filename', $table->columns);
        $this->assertArrayHasKey('filesize', $table->columns);
        $this->assertArrayHasKey('modified', $table->columns);
        $this->assertArrayHasKey('action', $table->columns);
        $this->assertArrayNotHasKey('year', $table->columns);
        $this->assertArrayNotHasKey('semester', $table->columns);
        $this->assertArrayNotHasKey('dept', $table->columns);
    }

    public function test_flat_variant_col_coursecode_shows_course_num(): void {
        $table = new restore_files_table('unit_flat_cc_ok', 1, 'C1', 0, false, 'flat');
        $table->populate([
            (object) [
                'filename'   => 'bp.zip',
                'modified'   => 1700000000,
                'course_num' => 'LA-1203',
            ],
        ], 'catalogue');

        $prop = new ReflectionProperty(restore_files_table::class, 'filerows');
        $prop->setAccessible(true);
        $rows = $prop->getValue($table);

        $this->assertCount(1, $rows);
        $this->assertSame('LA-1203', $rows[0]->course_num);
        $this->assertSame('LA-1203', $table->col_coursecode($rows[0]));
    }

    public function test_flat_variant_col_coursecode_dash_when_empty(): void {
        $table = new restore_files_table('unit_flat_cc_dash', 1, 'C1', 0, false, 'flat');
        $row = (object) ['course_num' => ''];

        $rendered = $table->col_coursecode($row);

        $this->assertStringContainsString('—', $rendered);
        $this->assertStringContainsString('text-muted', $rendered);
    }

    public function test_flat_variant_col_coursecode_dash_when_missing(): void {
        $table = new restore_files_table('unit_flat_cc_missing', 1, 'C1', 0, false, 'flat');
        $row = (object) ['filename' => 'no-cc.zip'];

        $rendered = $table->col_coursecode($row);

        $this->assertStringContainsString('—', $rendered);
        $this->assertStringContainsString('text-muted', $rendered);
    }

    public function test_year_variant_col_year_unchanged(): void {
        // Regression guard: the 'year' variant default behavior must keep working
        // exactly as before the bug-050 refactor introduced the variant parameter.
        $table = new restore_files_table('unit_year_regression', 1, 'C1', 0, false, 'year');
        $this->assertArrayHasKey('year', $table->columns);
        $this->assertArrayHasKey('semester', $table->columns);
        $this->assertArrayHasKey('dept', $table->columns);

        $row = (object) ['year' => '2024'];
        $this->assertSame('2024', $table->col_year($row));

        $emptyrow = (object) ['year' => ''];
        $rendered = $table->col_year($emptyrow);
        $this->assertStringContainsString('—', $rendered);
    }

    public function test_default_variant_is_year(): void {
        // Existing call sites that don't pass the 6th argument must keep the year layout.
        $table = new restore_files_table('unit_default_variant', 1, 'C1', 0);
        $this->assertArrayHasKey('year', $table->columns);
        $this->assertArrayHasKey('semester', $table->columns);
        $this->assertArrayHasKey('dept', $table->columns);
        $this->assertArrayNotHasKey('coursecode', $table->columns);
    }

    public function test_flat_variant_admin_columns_appear_when_admin(): void {
        // Admin viewer should still see coursetype + status columns alongside the flat layout.
        $table = new restore_files_table('unit_flat_admin', 1, 'C1', 0, true, 'flat');
        $this->assertArrayHasKey('coursecode', $table->columns);
        $this->assertArrayHasKey('coursetype', $table->columns);
        $this->assertArrayHasKey('status', $table->columns);
    }
}
