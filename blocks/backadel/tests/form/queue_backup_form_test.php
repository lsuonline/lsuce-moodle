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

namespace block_backadel\tests\form;

defined('MOODLE_INTERNAL') || die();

use block_backadel\form\queue_backup_form;
use ReflectionClass;

/**
 * Tests for {@see queue_backup_form}.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_backadel\form\queue_backup_form
 * @coversDefaultClass \block_backadel\form\queue_backup_form
 * @runInSeparateProcess
 */
final class queue_backup_form_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * @coversNothing
     */
    public function test_form_class_exists(): void {
        $this->assertTrue(class_exists(queue_backup_form::class));
    }

    /**
     * @covers ::check_access_for_dynamic_submission
     */
    public function test_check_access_requires_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $form = new queue_backup_form(null, null, 'post', '', [], true, null, false);
        $method = (new ReflectionClass($form))->getMethod('check_access_for_dynamic_submission');
        $method->setAccessible(true);

        $this->expectException(\required_capability_exception::class);
        $method->invoke($form);
    }
}
