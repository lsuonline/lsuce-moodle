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
 * Tests for block_simple_restore\local\catalogue_staging_service.
 *
 * @package    block_simple_restore
 * @category   test
 * @group      block_simple_restore
 * @covers     \block_simple_restore\local\catalogue_staging_service
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_simple_restore\tests\local;

use block_simple_restore\local\catalogue_staging_service;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_simple_restore\local\catalogue_staging_service
 */
final class catalogue_staging_service_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * resolve_source_path with catalogueid=0 and empty filename returns empty string.
     *
     * @covers ::resolve_source_path
     */
    public function test_resolve_source_path_empty_filename_returns_empty(): void {
        $result = catalogue_staging_service::resolve_source_path(0, '');
        $this->assertSame('', $result);
    }

    /**
     * resolve_source_path with catalogueid=0 and no backadel path configured returns empty.
     *
     * @covers ::resolve_source_path
     */
    public function test_resolve_source_path_no_backadel_path_returns_empty(): void {
        // Ensure no backadel path is set.
        set_config('path', '', 'block_backadel');

        $result = catalogue_staging_service::resolve_source_path(0, 'backup-moodle2-course-42.mbz');
        $this->assertSame('', $result);
    }

    /**
     * resolve_source_path with catalogueid=0, a valid filename, and a configured backadel path
     * returns the expected absolute path string.
     *
     * @covers ::resolve_source_path
     */
    public function test_resolve_source_path_legacy_builds_correct_path(): void {
        global $CFG;

        set_config('path', '/backadel/', 'block_backadel');

        $filename = 'backup-moodle2-course-42.mbz';
        $result = catalogue_staging_service::resolve_source_path(0, $filename);

        $expected = rtrim($CFG->dataroot, '/') . '/backadel/' . $filename;
        $this->assertSame($expected, $result);
    }

    /**
     * resolve_source_path strips directory traversal from filenames.
     *
     * @covers ::resolve_source_path
     */
    public function test_resolve_source_path_strips_directory_traversal(): void {
        global $CFG;

        set_config('path', '/backadel/', 'block_backadel');

        // A path with traversal should be reduced to just the basename.
        $result = catalogue_staging_service::resolve_source_path(0, '../../etc/passwd');

        // clean_param(PARAM_FILE) strips path separators so traversal is impossible.
        // The result must stay inside the configured base dir (no /etc/ segment)
        // and must not contain a raw slash-dot-dot sequence.
        if ($result !== '') {
            $this->assertStringNotContainsString('/../', $result);
            $this->assertStringNotContainsString('/etc/', $result);
            // The result must be under the base dir, not above it.
            $this->assertStringStartsWith($CFG->dataroot, $result);
        } else {
            $this->assertSame('', $result);
        }
    }

    /**
     * validate_source throws moodle_exception for a non-existent file.
     *
     * @covers ::validate_source
     */
    public function test_validate_source_throws_for_missing_file(): void {
        $this->expectException(\moodle_exception::class);
        catalogue_staging_service::validate_source('/tmp/this_file_does_not_exist_phpunit_test_xyz.mbz');
    }

    /**
     * validate_source throws moodle_exception for an empty path.
     *
     * @covers ::validate_source
     */
    public function test_validate_source_throws_for_empty_path(): void {
        $this->expectException(\moodle_exception::class);
        catalogue_staging_service::validate_source('');
    }

    /**
     * validate_source returns true for a valid, readable, non-empty file.
     *
     * @covers ::validate_source
     */
    public function test_validate_source_returns_true_for_valid_file(): void {
        // Create a real temp file with content.
        $tmpfile = tempnam(sys_get_temp_dir(), 'phpunit_backadel_');
        file_put_contents($tmpfile, 'dummy backup content');

        try {
            $result = catalogue_staging_service::validate_source($tmpfile);
            $this->assertTrue($result);
        } finally {
            @unlink($tmpfile);
        }
    }
}
