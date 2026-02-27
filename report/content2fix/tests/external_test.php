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
 * Unit tests for external API queue_format_all_task.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class external_test extends \advanced_testcase {

    /**
     * Require externallib (must be loaded in isolated process).
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
    }

    public function test_queue_format_all_task_queues_with_normalised_filters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $filtervalues = [
            ['name' => 'course:fullname_group[course:fullname_operator]', 'value' => '0'],
            ['name' => 'malformed_content:component_operator', 'value' => '1'],
        ];
        $result = external::queue_format_all_task($filtervalues);
        $this->assertTrue($result['queued']);
        $tasks = \core\task\manager::get_adhoc_tasks(task\format_all_html_task::class);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertNotFalse($task);
        $flat = (array) $task->get_custom_data()->filtervalues;
        $this->assertArrayHasKey('course:fullname_operator', $flat);
        $this->assertSame('0', $flat['course:fullname_operator']);
    }

    public function test_queue_format_all_task_throws_without_fix_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\moodle_exception::class);
        external::queue_format_all_task([['name' => 'x', 'value' => '0']]);
    }
}
