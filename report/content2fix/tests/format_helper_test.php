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

namespace report_content2fix;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for format helper queue logic.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_helper_test extends \advanced_testcase {
    /**
     * Data provider for queue gating conditions.
     *
     * @return array[]
     */
    public static function can_queue_filtered_format_provider(): array {
        return [
            'allowed for admin with capability even multiple filtered courses' => [
                true,
                true,
                2,
                true,
            ],
            'blocked for non admin' => [
                false,
                true,
                1,
                false,
            ],
            'allowed for admin without capability when filtered course is single' => [
                true,
                false,
                1,
                true,
            ],
            'blocked for admin without capability when no filtered courses' => [
                true,
                false,
                0,
                false,
            ],
            'blocked for admin without capability when filtered spans multiple courses' => [
                true,
                false,
                2,
                false,
            ],
        ];
    }

    /**
     * Test queue gating condition helper.
     *
     * @dataProvider can_queue_filtered_format_provider
     * @param bool $isadmin
     * @param bool $canfixfiltered
     * @param int $filtereddistinctcoursecount
     * @param bool $expected
     */
    public function test_can_queue_filtered_format(
        bool $isadmin,
        bool $canfixfiltered,
        int $filtereddistinctcoursecount,
        bool $expected
    ): void {
        $actual = local\format_helper::can_queue_filtered_format(
            $isadmin,
            $canfixfiltered,
            $filtereddistinctcoursecount
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * Test helper queues adhoc task with filter values in custom data.
     */
    public function test_queue_filtered_format_task(): void {
        $this->resetAfterTest();

        $filtervalues = [
            'course:fullname_operator' => '2',
            'course:fullname_value' => 'History 101',
        ];

        local\format_helper::queue_filtered_format_task($filtervalues);

        $tasks = \core\task\manager::get_adhoc_tasks(task\format_all_html_task::class);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertNotFalse($task);
        $this->assertEquals((object) ['filtervalues' => (object) $filtervalues], $task->get_custom_data());
    }
}
