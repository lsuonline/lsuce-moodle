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

namespace block_backadel\tests\local\table;

defined('MOODLE_INTERNAL') || die();

use block_backadel\local\table\results_table;
use moodle_url;

/**
 * Structural tests for {@see results_table} (no full DB integration).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_backadel\local\table\results_table
 * @coversDefaultClass \block_backadel\local\table\results_table
 */
final class results_table_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * @covers ::__construct
     */
    public function test_columns_defined(): void {
        global $PAGE;

        $PAGE->set_url(new moodle_url('/blocks/backadel/search.php'));
        $table = new results_table('unittest_results', ['q' => 'x', 'category' => 0, 'status' => '']);

        foreach (['shortname', 'coursefullname', 'category', 'status', 'actions'] as $column) {
            $this->assertArrayHasKey($column, $table->columns);
        }
    }

    /**
     * @covers ::setup_sql
     */
    public function test_sql_is_configured(): void {
        global $PAGE;

        $PAGE->set_url(new moodle_url('/blocks/backadel/search.php'));
        $table = new results_table('unittest_results_sql', ['q' => 't', 'category' => 0, 'status' => 'SUCCESS']);

        $this->assertNotNull($table->sql);
        $this->assertStringContainsString('co.id', $table->sql->fields);
        $this->assertStringContainsString('{block_backadel_statuses}', $table->sql->fields);
        $this->assertStringContainsString('bkst_sub.status', $table->sql->where);
    }
}
