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
 * Tests for block_simple_restore\task\stage_catalogue_restore_task.
 *
 * @package    block_simple_restore
 * @category   test
 * @group      block_simple_restore
 * @covers     \block_simple_restore\task\stage_catalogue_restore_task
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_simple_restore\tests\task;

use block_simple_restore\task\stage_catalogue_restore_task;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_simple_restore\task\stage_catalogue_restore_task
 */
final class stage_catalogue_restore_task_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Task throws moodle_exception when userid is missing from custom data.
     *
     * @covers ::execute
     */
    public function test_execute_throws_without_userid(): void {
        $task = new stage_catalogue_restore_task();
        $task->set_custom_data(['courseid' => 42]);

        $this->expectException(\moodle_exception::class);
        $task->execute();
    }

    /**
     * Task throws moodle_exception when courseid is missing (zero).
     *
     * @covers ::execute
     */
    public function test_execute_throws_without_courseid(): void {
        $user = $this->getDataGenerator()->create_user();

        $task = new stage_catalogue_restore_task();
        $task->set_custom_data(['userid' => $user->id]);

        $this->expectException(\moodle_exception::class);
        $task->execute();
    }

    /**
     * Task throws moodle_exception when restore_to=2 (archive mode not supported in queue).
     *
     * @covers ::execute
     */
    public function test_execute_throws_for_archive_restore_mode(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $task = new stage_catalogue_restore_task();
        $task->set_custom_data([
            'userid'     => $user->id,
            'courseid'   => $course->id,
            'restore_to' => 2,
            'catalogueid' => 0,
            'filename'   => 'test.mbz',
        ]);

        $this->expectException(\moodle_exception::class);
        $task->execute();
    }

    /**
     * Task throws moodle_exception when source path cannot be resolved (no backadel path, legacy row).
     *
     * @covers ::execute
     */
    public function test_execute_throws_when_source_file_missing(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // No backadel path configured → resolve_source_path returns '' → validate_source throws.
        set_config('path', '', 'block_backadel');

        $task = new stage_catalogue_restore_task();
        $task->set_custom_data([
            'userid'      => $user->id,
            'courseid'    => $course->id,
            'restore_to'  => 0,
            'catalogueid' => 0,
            'filename'    => 'nonexistent_backup.mbz',
        ]);

        // Task prints an error message to output before throwing — declare it expected.
        $this->expectOutputRegex('/.*/');
        $this->expectException(\moodle_exception::class);
        $task->execute();
    }

    /**
     * retry_until_success() returns false — failed restores should not auto-retry.
     *
     * @covers ::retry_until_success
     */
    public function test_retry_until_success_is_false(): void {
        $task = new stage_catalogue_restore_task();
        $this->assertFalse($task->retry_until_success());
    }
}
