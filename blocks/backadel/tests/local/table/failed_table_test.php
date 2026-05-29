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

use block_backadel\local\table\failed_table;
use moodle_url;

/**
 * Tests for {@see failed_table}.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_backadel\local\table\failed_table
 * @coversDefaultClass \block_backadel\local\table\failed_table
 */
final class failed_table_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * @covers ::define_base_columns
     */
    public function test_columns_include_actions(): void {
        global $PAGE;

        $PAGE->set_url(new moodle_url('/blocks/backadel/failed.php'));
        $table = new failed_table('unittest_failed_cols');

        $this->assertArrayHasKey('actions', $table->columns);
    }

    /**
     * Status rendering is implemented on the parent but exercised through this table.
     *
     * @covers \block_backadel\local\table\base_backadel_table::col_status
     */
    public function test_col_status_renders_fail_badge(): void {
        global $PAGE;

        $PAGE->set_url(new moodle_url('/blocks/backadel/failed.php'));
        $table = new failed_table('unittest_failed_status');
        $row = (object) ['status' => 'FAIL'];

        $html = $table->col_status($row);

        $this->assertStringContainsString('badge-danger', $html);
        $this->assertStringContainsString(get_string('results_status_failed', 'block_backadel'), $html);
    }

    /**
     * @covers ::col_actions
     */
    public function test_col_actions_returns_requeue_button(): void {
        global $PAGE;

        $PAGE->set_url(new moodle_url('/blocks/backadel/failed.php'));
        $table = new failed_table('unittest_failed_actions');
        $row = (object) ['id' => 42];

        $html = $table->col_actions($row);

        $this->assertStringContainsString('btn-warning', $html);
        $this->assertStringContainsString('data-action="requeue"', $html);
        $this->assertStringContainsString(get_string('results_action_requeue', 'block_backadel'), $html);
        $this->assertStringContainsString('data-courseid="42"', $html);
    }
}
