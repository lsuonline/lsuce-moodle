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

/**
 * Tests for block_backadel\external\get_migration_status.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\tests;

use advanced_testcase;
use block_backadel\external\get_migration_status;
use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_backadel\external\get_migration_status
 */
final class external_get_migration_status_test extends advanced_testcase {

    /**
     * Helper: insert a status row directly.
     *
     * @param string $status The status code (SUCCESS, FAIL, BACKUP, ...).
     * @param int $coursesid Optional course id (default 1 — table requires NOT NULL).
     * @return int Inserted record id.
     */
    private function insert_status(string $status, int $coursesid = 1): int {
        global $DB;
        $now = time();
        return (int) $DB->insert_record('block_backadel_statuses', (object) [
            'coursesid'    => $coursesid,
            'status'       => $status,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Execute the external function as an admin and return the cleaned response.
     *
     * @return array
     */
    private function call_as_admin(): array {
        $this->setAdminUser();
        $result = get_migration_status::execute();
        return (array) external_api::clean_returnvalue(
            get_migration_status::execute_returns(),
            $result
        );
    }

    /**
     * With an empty DB and no running flag, all counts should be zero and idle.
     */
    public function test_empty_state(): void {
        $this->resetAfterTest(true);

        $result = $this->call_as_admin();

        $this->assertSame(0, $result['pending_count']);
        $this->assertSame(0, $result['failed_count']);
        $this->assertFalse($result['is_running']);
        $this->assertSame('', $result['elapsed_human']);
    }

    /**
     * SUCCESS rows feed pending_count.
     */
    public function test_pending_count(): void {
        $this->resetAfterTest(true);

        $this->insert_status('SUCCESS', 10);
        $this->insert_status('SUCCESS', 11);
        $this->insert_status('SUCCESS', 12);
        // A FAIL row should not bump pending.
        $this->insert_status('FAIL', 13);

        $result = $this->call_as_admin();

        $this->assertSame(3, $result['pending_count']);
        $this->assertSame(1, $result['failed_count']);
    }

    /**
     * FAIL rows feed failed_count and ignore other statuses.
     */
    public function test_failed_count(): void {
        $this->resetAfterTest(true);

        $this->insert_status('FAIL', 20);
        $this->insert_status('FAIL', 21);
        $this->insert_status('BACKUP', 22);

        $result = $this->call_as_admin();

        $this->assertSame(2, $result['failed_count']);
        $this->assertSame(0, $result['pending_count']);
    }

    /**
     * The running flag exposes is_running=true with a non-empty elapsed string.
     */
    public function test_running_flag(): void {
        $this->resetAfterTest(true);

        // Set the "running" flag to a moment 65 seconds ago.
        set_config('running', time() - 65, 'block_backadel');

        $result = $this->call_as_admin();

        $this->assertTrue($result['is_running']);
        $this->assertNotSame('', $result['elapsed_human']);
        // seconds2human always emits a "seconds" component.
        $this->assertStringContainsString('seconds', $result['elapsed_human']);
    }

    /**
     * Non-admin (no capability) must trigger required_capability_exception.
     */
    public function test_requires_capability(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        get_migration_status::execute();
    }
}
