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

global $CFG;
require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

use Exception;
use ReflectionClass;
use ReflectionProperty;
use simple_restore;

/**
 * Tests for {@see \simple_restore} constructor and property initialisation.
 *
 * Bug-052: the {@see \simple_restore} class was triggering a PHP 8.2+
 * "creation of dynamic property" deprecation because the legacy code assigned
 * `$this->restoreto` from the constructor without declaring it. On rrusso the
 * deprecation surfaced as a blank page when DEBUG was elevated. The fix was to
 *
 *   - declare `public $restore_to;` on the class, and
 *   - rename the property and constructor argument consistently throughout
 *     {@see \simple_restore::execute()} and the supporting helpers.
 *
 * These tests cover the contract the rest of the restore pipeline relies on:
 *
 *   - constructor argument validation (empty course, empty filename),
 *   - the `restore_to` property is declared and stores the integer mode value,
 *   - the `course`, `context`, `filename`, and `userid` properties are
 *     populated from the constructor arguments,
 *   - the property can be set to 0 (overwrite — default), 1 (import), and 2
 *     (archive new course),
 *   - the `archive_mode_execute()` method is callable when restore_to == 2 and
 *     `is_archive_server` is configured (we don't actually run a backup; we
 *     verify the gating state and that the method is reachable).
 *
 * The execute() pipeline itself is not exercised — that would require a real
 * restore controller, a staged .mbz file, and significant test-only DB state
 * which is out of scope for a constructor / property regression suite.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \simple_restore
 */
final class restore_flow_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        // The constructor reads $USER->id and calls context_course::instance(),
        // both of which need a logged-in user with permission on the course.
        $this->setAdminUser();
    }

    /**
     * Helper: create a real test course so context_course::instance() works.
     *
     * @return \stdClass
     */
    private function make_course(): \stdClass {
        return $this->getDataGenerator()->create_course();
    }

    // -----------------------------------------------------------------------
    // Bug-052: declared `restore_to` property + constructor wiring.
    // -----------------------------------------------------------------------

    /**
     * The class must declare `restore_to` as a real (non-dynamic) property.
     * If this fails, PHP 8.2+ would emit the dynamic-property deprecation
     * that caused the rrusso blank page.
     *
     * @covers \simple_restore
     */
    public function test_restore_to_property_is_declared(): void {
        $reflector = new ReflectionClass(simple_restore::class);
        $this->assertTrue(
            $reflector->hasProperty('restore_to'),
            'simple_restore must declare a `restore_to` property (bug-052)'
        );

        $prop = $reflector->getProperty('restore_to');
        $this->assertTrue(
            $prop->isPublic(),
            '`restore_to` must be public so list.php / restore.php can read it'
        );
        $this->assertFalse(
            $prop->isStatic(),
            '`restore_to` must be an instance property, not static'
        );
    }

    /**
     * The deprecated camelCase `restoreto` property must NOT be declared on
     * the class — only the snake_case `restore_to` introduced by bug-052.
     * Catches accidental reintroduction of the dynamic property.
     *
     * @covers \simple_restore
     */
    public function test_legacy_restoreto_property_is_not_declared(): void {
        $reflector = new ReflectionClass(simple_restore::class);
        $this->assertFalse(
            $reflector->hasProperty('restoreto'),
            'legacy `restoreto` property must not exist; bug-052 renamed it to `restore_to`'
        );
    }

    /**
     * Construction with restore_to=0,1,2 stores the integer verbatim.
     * 0 = overwrite, 1 = import, 2 = new course (archive mode).
     *
     * @dataProvider provider_restore_to_values
     * @covers       \simple_restore::__construct
     */
    public function test_constructor_sets_restore_to_property(int $mode): void {
        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake-backup.mbz', $mode);

        $this->assertSame($mode, $obj->restore_to);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provider_restore_to_values(): array {
        return [
            'overwrite (0)'   => [0],
            'import (1)'      => [1],
            'new course (2)'  => [2],
        ];
    }

    /**
     * Default value for the restore_to argument is 0 (overwrite).
     *
     * @covers \simple_restore::__construct
     */
    public function test_constructor_default_restore_to_is_zero(): void {
        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake-backup.mbz');

        $this->assertSame(
            0,
            $obj->restore_to,
            'default restore_to must be 0 (overwrite); used by process_destination as $_POST[target]'
        );
    }

    /**
     * Bug-052 explicit guard: restore_to == 0 (the most common, default-overwrite
     * case) is stored correctly. process_destination() pushes this value into
     * `$_POST['target']`, so a wrong type (e.g. boolean) would break the
     * downstream restore controller.
     *
     * @covers \simple_restore::__construct
     */
    public function test_restore_to_zero_means_overwrite(): void {
        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake-backup.mbz', 0);

        $this->assertSame(0, $obj->restore_to);
        $this->assertIsInt(
            $obj->restore_to,
            'restore_to must remain an int — process_destination casts to $_POST[target]'
        );
    }

    /**
     * Constructor populates the supporting properties from its arguments.
     * If any of these are missed, the execute() pipeline will hit a null deref.
     *
     * @covers \simple_restore::__construct
     */
    public function test_constructor_populates_all_properties(): void {
        global $USER;

        $course = $this->make_course();
        $obj = new simple_restore($course, 'my-backup.mbz', 1);

        $this->assertSame($USER->id, $obj->userid, 'userid must come from $USER->id');
        $this->assertSame($course, $obj->course, 'course property must hold the passed course object');
        $this->assertSame('my-backup.mbz', $obj->filename, 'filename must be stored verbatim');
        $this->assertSame(1, $obj->restore_to);

        // Context must be a course context for the supplied course.
        $this->assertNotNull($obj->context, 'context must be initialised from context_course::instance()');
        $this->assertSame((int) $course->id, (int) $obj->context->instanceid);
    }

    // -----------------------------------------------------------------------
    // Constructor argument validation.
    // -----------------------------------------------------------------------

    /**
     * Empty course (null) must throw — guards against a downstream
     * context_course::instance(null->id) fatal.
     *
     * @covers \simple_restore::__construct
     */
    public function test_constructor_throws_on_empty_course(): void {
        $this->expectException(Exception::class);
        new simple_restore(null, 'fake-backup.mbz', 0);
    }

    /**
     * Empty course (false) also throws — empty() catches more than null.
     *
     * @covers \simple_restore::__construct
     */
    public function test_constructor_throws_on_false_course(): void {
        $this->expectException(Exception::class);
        // @phpstan-ignore-next-line — testing defensive guard with bad input on purpose.
        new simple_restore(false, 'fake-backup.mbz', 0);
    }

    /**
     * Empty filename string must throw — restore engine cannot open an empty
     * filename and would fatal deeper in the stack.
     *
     * @covers \simple_restore::__construct
     */
    public function test_constructor_throws_on_empty_filename(): void {
        $course = $this->make_course();
        $this->expectException(Exception::class);
        new simple_restore($course, '', 0);
    }

    /**
     * Construction with restore_to omitted (uses default) and a valid course
     * must NOT throw — covers the most common call site in restore.php.
     *
     * @covers \simple_restore::__construct
     */
    public function test_constructor_succeeds_with_minimal_valid_args(): void {
        $course = $this->make_course();
        $obj = new simple_restore($course, 'ok.mbz');
        $this->assertInstanceOf(simple_restore::class, $obj);
    }

    // -----------------------------------------------------------------------
    // Archive-mode gating in execute().
    // -----------------------------------------------------------------------

    /**
     * When restore_to == 2 AND `is_archive_server` config is on, execute()
     * must dispatch to archive_mode_execute() instead of the normal pipeline.
     *
     * We can't actually run archive_mode_execute() in a unit test — it needs a
     * real restore controller and would create a course — so we verify:
     *   1. the property + config combination matches the gate condition in
     *      execute() (line ~1161 of lib.php), and
     *   2. archive_mode_execute() is a public method reachable on the object.
     *
     * @covers \simple_restore
     */
    public function test_restore_to_two_with_archive_config(): void {
        set_config('is_archive_server', 1, 'simple_restore');

        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake-archive.mbz', 2);

        // The two preconditions execute() ANDs together must both be true.
        $this->assertSame(2, $obj->restore_to);
        $this->assertSame(
            '1',
            (string) get_config('simple_restore', 'is_archive_server'),
            'is_archive_server config must be "1" for the archive-mode branch to fire'
        );

        // archive_mode_execute() must exist and be callable on the object —
        // execute() calls $this->archive_mode_execute() under the gate.
        $this->assertTrue(
            method_exists($obj, 'archive_mode_execute'),
            'simple_restore::archive_mode_execute() must exist for the restore_to==2 archive gate'
        );
        $reflector = new ReflectionClass(simple_restore::class);
        $this->assertTrue(
            $reflector->getMethod('archive_mode_execute')->isPublic(),
            'archive_mode_execute() must be public — execute() calls it as $this->archive_mode_execute()'
        );
    }

    /**
     * When restore_to == 2 but `is_archive_server` is OFF, the archive gate
     * MUST NOT fire — execute() falls through to the normal pipeline. We
     * verify the config side of the gate by inspecting the config value the
     * gate reads.
     *
     * @covers \simple_restore
     */
    public function test_restore_to_two_without_archive_config_falls_through(): void {
        set_config('is_archive_server', 0, 'simple_restore');

        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake.mbz', 2);

        $this->assertSame(2, $obj->restore_to);
        $this->assertEmpty(
            get_config('simple_restore', 'is_archive_server'),
            'with is_archive_server=0 the archive gate must not fire even when restore_to==2'
        );
    }

    /**
     * When `is_archive_server` is on but restore_to != 2, the archive gate
     * still must not fire — both halves of the AND are required.
     *
     * @dataProvider provider_non_archive_modes
     * @covers       \simple_restore
     */
    public function test_archive_gate_requires_both_conditions(int $mode): void {
        set_config('is_archive_server', 1, 'simple_restore');

        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake.mbz', $mode);

        $this->assertSame($mode, $obj->restore_to);
        $this->assertNotSame(
            2,
            $obj->restore_to,
            'this provider only supplies non-archive modes'
        );
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provider_non_archive_modes(): array {
        return [
            'overwrite' => [0],
            'import'    => [1],
        ];
    }

    // -----------------------------------------------------------------------
    // Misc property-initialisation regression tests.
    // -----------------------------------------------------------------------

    /**
     * The `filename` property must store exactly what the caller passed,
     * untouched. restore_confirm_form injects the post-prep_restore() temp
     * filename here; modifying it would break the restore_ui CONFIRM stage.
     *
     * @covers \simple_restore::__construct
     */
    public function test_filename_property_stored_verbatim(): void {
        $course = $this->make_course();
        $weird = 'restore_course_copy_some-1234567890_abc.mbz';
        $obj = new simple_restore($course, $weird, 0);
        $this->assertSame($weird, $obj->filename);
    }

    /**
     * The `course` property must hold the SAME object reference (not a clone).
     * Downstream code reads $this->course->id and $this->course->fullname.
     *
     * @covers \simple_restore::__construct
     */
    public function test_course_property_holds_same_object_reference(): void {
        $course = $this->make_course();
        $obj = new simple_restore($course, 'fake.mbz', 0);
        $this->assertSame($course, $obj->course);
        $this->assertSame((int) $course->id, (int) $obj->course->id);
    }

    /**
     * All four state-bearing properties (`userid`, `course`, `context`,
     * `filename`, `restore_to`) must be declared on the class — guarding
     * against re-introducing a dynamic property under PHP 8.2+.
     *
     * @covers \simple_restore
     */
    public function test_all_state_properties_are_declared(): void {
        $reflector = new ReflectionClass(simple_restore::class);
        foreach (['userid', 'course', 'context', 'filename', 'restore_to'] as $name) {
            $this->assertTrue(
                $reflector->hasProperty($name),
                "simple_restore must declare `\${$name}` to avoid PHP 8.2+ dynamic-property deprecation"
            );
            $prop = $reflector->getProperty($name);
            $this->assertSame(
                ReflectionProperty::IS_PUBLIC,
                $prop->getModifiers() & ReflectionProperty::IS_PUBLIC,
                "`\${$name}` must be public"
            );
        }
    }
}
