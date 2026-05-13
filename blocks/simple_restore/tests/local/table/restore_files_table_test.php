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

namespace block_simple_restore\tests\local\table;

defined('MOODLE_INTERNAL') || die();

use block_simple_restore\local\table\restore_files_table;
use ReflectionClass;
use ReflectionProperty;

/**
 * Tests for {@see restore_files_table}.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_simple_restore\local\table\restore_files_table
 * @coversDefaultClass \block_simple_restore\local\table\restore_files_table
 */
final class restore_files_table_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * @covers ::__construct
     */
    public function test_columns_defined(): void {
        $table = new restore_files_table('unittest_restore_files', 1, 'C1', 0);
        foreach (['filename', 'filesize', 'modified', 'action'] as $column) {
            $this->assertArrayHasKey($column, $table->columns);
        }
    }

    /**
     * Rows are stored in private {@see restore_files_table::$filerows} after populate().
     *
     * @covers ::populate
     */
    public function test_populate_normalizes_file_array(): void {
        $table = new restore_files_table('unittest_restore_populate', 1, 'C1', 0);
        $table->populate([
            ['filename' => 'backup.zip', 'size' => 10, 'modified' => 1700000000],
        ]);

        $prop = new ReflectionProperty(restore_files_table::class, 'filerows');
        $prop->setAccessible(true);
        /** @var \stdClass[] $rows */
        $rows = $prop->getValue($table);

        $this->assertCount(1, $rows);
        $this->assertSame('backup.zip', $rows[0]->filename);
    }

    /**
     * @covers ::col_filesize
     */
    public function test_col_filesize_formats_correctly(): void {
        $table = new restore_files_table('unittest_restore_size', 1, 'C1', 0);
        $row = (object) ['size' => 1048576];

        $formatted = $table->col_filesize($row);

        $this->assertSame(display_size(1048576), $formatted);
        $this->assertStringContainsString('MB', $formatted);
    }
}
