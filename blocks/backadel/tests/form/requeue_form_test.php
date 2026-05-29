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

namespace block_backadel\form;

defined('MOODLE_INTERNAL') || die();

use ReflectionClass;

/**
 * Stub tests for {@see \block_backadel\form\requeue_form}.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_backadel\form\requeue_form
 */
final class requeue_form_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_class_exists(): void {
        $this->assertTrue(class_exists('block_backadel\\form\\requeue_form'));
    }

    public function test_extends_dynamic_form(): void {
        $reflection = new ReflectionClass(requeue_form::class);
        $parent = $reflection->getParentClass();
        $this->assertNotFalse($parent);
        $this->assertSame(\core_form\dynamic_form::class, $parent->getName());
    }
}
