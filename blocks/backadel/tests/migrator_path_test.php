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

namespace block_backadel\tests;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../../simple_restore/lib.php');

use block_backadel\local\migrator;

/**
 * Tests for path-normalisation in migrator and backadel_resolve_path() — bug-039.
 *
 * Verifies that trailing slashes on directory paths do not produce double-slash
 * separators in filepath / filepath_full values, and that the defensive
 * str_replace('//', '/', ...) in backadel_resolve_path() cleans stale DB data.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \block_backadel\local\migrator
 */
final class migrator_path_test extends \advanced_testcase {

    // -------------------------------------------------------------------------
    // list_archives() — path normalisation
    // -------------------------------------------------------------------------

    /**
     * list_archives() must return basenames only (no path component), so the
     * caller is responsible for joining. But the input dirpath with a trailing
     * slash must NOT break scandir or introduce // when callers concatenate.
     *
     * @covers \block_backadel\local\migrator::list_archives
     */
    public function test_list_archives_trailing_slash_returns_basenames(): void {
        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));
        $this->assertIsString($tmpdir);

        // Place two archives and one non-archive.
        file_put_contents($tmpdir . '/course_2024_fall.mbz', 'data');
        file_put_contents($tmpdir . '/backup_spring.zip', 'data');
        file_put_contents($tmpdir . '/readme.txt', 'data');

        // Call with trailing slash — must still work.
        $basenames = migrator::list_archives($tmpdir . '/');

        sort($basenames);
        $this->assertSame(['backup_spring.zip', 'course_2024_fall.mbz'], $basenames);

        // Sanity: each returned value must be a bare basename (no slash).
        foreach ($basenames as $b) {
            $this->assertStringNotContainsString('/', $b, 'list_archives must return basenames, not full paths');
        }

        // Callers join with rtrim — result must have no //.
        foreach ($basenames as $b) {
            $joined = rtrim($tmpdir . '/', '/') . '/' . $b;
            $this->assertStringNotContainsString('//', $joined,
                'rtrim + basename join must not produce double slashes');
        }
    }

    /**
     * list_archives() with a path that already has no trailing slash must
     * also work correctly (regression guard).
     *
     * @covers \block_backadel\local\migrator::list_archives
     */
    public function test_list_archives_no_trailing_slash(): void {
        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));

        file_put_contents($tmpdir . '/FA2023_CSC1234_jdoe.mbz', 'data');

        $basenames = migrator::list_archives($tmpdir);   // No trailing slash.
        $this->assertSame(['FA2023_CSC1234_jdoe.mbz'], $basenames);
    }

    /**
     * list_archives() with a missing directory returns an empty array.
     *
     * @covers \block_backadel\local\migrator::list_archives
     */
    public function test_list_archives_missing_dir_returns_empty(): void {
        $result = migrator::list_archives('/this/path/does/not/exist/');
        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // list_all_archives() — recursive walker (bug-057)
    // -------------------------------------------------------------------------

    /**
     * list_all_archives() must descend into subdirectories and return relative
     * paths (not basenames) for every .mbz/.zip file found.
     *
     * @covers \block_backadel\local\migrator::list_all_archives
     */
    public function test_list_all_archives_finds_files_in_subdirectories(): void {
        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));
        mkdir($tmpdir . '/depth1', 0777, true);
        mkdir($tmpdir . '/depth1/depth2', 0777, true);

        file_put_contents($tmpdir . '/root.mbz', 'data');
        file_put_contents($tmpdir . '/depth1/one.zip', 'data');
        file_put_contents($tmpdir . '/depth1/depth2/two.mbz', 'data');

        $result = migrator::list_all_archives($tmpdir);

        $this->assertContains('root.mbz', $result);
        $this->assertContains('depth1/one.zip', $result);
        $this->assertContains('depth1/depth2/two.mbz', $result);
        $this->assertCount(3, $result);
    }

    /**
     * list_all_archives() with a missing directory returns an empty array.
     *
     * @covers \block_backadel\local\migrator::list_all_archives
     */
    public function test_list_all_archives_missing_dir_returns_empty(): void {
        $this->assertSame([], migrator::list_all_archives('/this/path/does/not/exist/'));
        $this->assertSame([], migrator::list_all_archives(''));
    }

    /**
     * list_all_archives() must exclude non-archive files such as .txt and .php.
     *
     * @covers \block_backadel\local\migrator::list_all_archives
     */
    public function test_list_all_archives_ignores_non_archive_files(): void {
        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));
        mkdir($tmpdir . '/sub', 0777, true);

        file_put_contents($tmpdir . '/good.zip', 'data');
        file_put_contents($tmpdir . '/readme.txt', 'data');
        file_put_contents($tmpdir . '/script.php', '<?php');
        file_put_contents($tmpdir . '/sub/notes.md', 'data');
        file_put_contents($tmpdir . '/sub/keep.mbz', 'data');

        $result = migrator::list_all_archives($tmpdir);

        sort($result);
        $this->assertSame(['good.zip', 'sub/keep.mbz'], $result);
        foreach ($result as $r) {
            $this->assertDoesNotMatchRegularExpression('/\.(txt|php|md)$/', $r);
        }
    }

    /**
     * list_all_archives() must return relative paths that include the subdir
     * prefix, not the basename only. Regression guard against accidentally
     * regressing to scandir() / basename() semantics.
     *
     * @covers \block_backadel\local\migrator::list_all_archives
     */
    public function test_list_all_archives_returns_relative_paths_not_basenames(): void {
        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));
        mkdir($tmpdir . '/2024/spring', 0777, true);

        file_put_contents($tmpdir . '/2024/spring/course.zip', 'data');

        $result = migrator::list_all_archives($tmpdir);

        $this->assertCount(1, $result);
        $this->assertSame('2024/spring/course.zip', $result[0]);
        // Reject basename-only result.
        $this->assertNotSame('course.zip', $result[0]);
        $this->assertStringContainsString('2024/spring/', $result[0]);
    }

    /**
     * migrate_directory() must recurse into subdirectories so files in nested
     * folders are catalogued. Uses the same spy pattern as the trailing-slash
     * test — we only verify the recorded file paths.
     *
     * @covers \block_backadel\local\migrator::migrate_directory
     */
    public function test_migrate_directory_recurses_into_subdirectories(): void {
        $this->resetAfterTest(true);

        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));
        mkdir($tmpdir . '/nested', 0777, true);

        $root = 'FA2023_CSC1010_jdoe.mbz';
        $nested = 'SP2024_ENGL1001_smith.zip';
        file_put_contents($tmpdir . '/' . $root, 'fake-mbz');
        file_put_contents($tmpdir . '/nested/' . $nested, 'fake-zip');

        $recorded = [];
        $spy = new class($recorded) extends migrator {
            private $log;

            public function __construct(array &$log) {
                parent::__construct();
                $this->log = &$log;
            }

            public function migrate_file(string $filepathfull, string $source): int {
                $this->log[] = $filepathfull;
                return 0;
            }
        };

        $spy->migrate_directory($tmpdir, 'backadel_current');

        $this->assertCount(2, $recorded,
            'migrate_directory must recurse so both root and nested archive get processed');
        $joined = implode("\n", $recorded);
        $this->assertStringContainsString('/' . $root, $joined);
        $this->assertStringContainsString('/nested/' . $nested, $joined);
        foreach ($recorded as $r) {
            $this->assertStringNotContainsString('//', $r);
        }
    }

    // -------------------------------------------------------------------------
    // migrate_directory() — no // in stored filepath
    // -------------------------------------------------------------------------

    /**
     * migrate_directory() with a trailing-slash dirpath must not store any
     * filepath / filepath_full value containing //.
     *
     * We use an observable subclass that exposes the filepathfull value passed
     * to upsert_catalogue via the public migrate_file() entry point, and a
     * real temp directory with a parseable archive filename so the migrator
     * actually processes the file.
     *
     * @covers \block_backadel\local\migrator::migrate_directory
     */
    public function test_migrate_directory_trailing_slash_no_double_slash(): void {
        $this->resetAfterTest(true);

        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));

        // Use a filename that the filename_parser can parse (semester_legacy pattern).
        // Pattern: FA2023_CSC1010_jdoe.mbz — should match backadel_instructor or similar.
        // We just need any .mbz so migrate_file() calls upsert_catalogue().
        $basename = 'FA2023_CSC1010_jdoe.mbz';
        file_put_contents($tmpdir . '/' . $basename, 'fake-mbz');

        // Spy subclass: intercepts migrate_file calls and records filepathfull.
        $recorded = [];
        $spy = new class($recorded) extends migrator {
            private $log; // typed reference properties are not valid PHP; type omitted

            public function __construct(array &$log) {
                parent::__construct();
                $this->log = &$log;
            }

            public function migrate_file(string $filepathfull, string $source): int {
                $this->log[] = $filepathfull;
                // Do NOT call parent — we don't want real DB writes.
                return 0;
            }
        };

        // Call migrate_directory with trailing slash.
        $spy->migrate_directory($tmpdir . '/', 'backadel_current');

        $this->assertCount(1, $recorded,
            'Expected exactly one file to be processed');
        $this->assertStringNotContainsString('//', $recorded[0],
            'migrate_directory with trailing-slash dirpath must not produce // in filepathfull');
        $this->assertStringEndsWith('/' . $basename, $recorded[0],
            'Recorded path must end with the archive basename');
    }

    /**
     * migrate_directory() with NO trailing slash must also pass (regression).
     *
     * @covers \block_backadel\local\migrator::migrate_directory
     */
    public function test_migrate_directory_no_trailing_slash_no_double_slash(): void {
        $this->resetAfterTest(true);

        $tmpdir = make_temp_directory('block_backadel_test_' . uniqid('', true));
        $basename = 'SP2024_ENGL1001_smith.zip';
        file_put_contents($tmpdir . '/' . $basename, 'fake-zip');

        $recorded = [];
        $spy = new class($recorded) extends migrator {
            private $log; // typed reference properties are not valid PHP; type omitted

            public function __construct(array &$log) {
                parent::__construct();
                $this->log = &$log;
            }

            public function migrate_file(string $filepathfull, string $source): int {
                $this->log[] = $filepathfull;
                return 0;
            }
        };

        $spy->migrate_directory($tmpdir, 'backadel_current');   // No trailing slash.

        $this->assertCount(1, $recorded);
        $this->assertStringNotContainsString('//', $recorded[0]);
    }

    // -------------------------------------------------------------------------
    // backadel_resolve_path() — defensive // collapse
    // -------------------------------------------------------------------------

    /**
     * backadel_resolve_path() must normalise // in filepath_full that came from
     * stale catalogue rows written before bug-039 was fixed.
     *
     * @covers backadel_resolve_path
     */
    public function test_resolve_path_collapses_double_slashes_in_db(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Insert a catalogue row with // in filepath_full (simulating pre-fix data).
        $badpath = '/moodledata//backadel//FA2023_CSC1010_jdoe.mbz';
        $goodpath = '/moodledata/backadel/FA2023_CSC1010_jdoe.mbz';

        $row = new \stdClass();
        $row->filename        = 'FA2023_CSC1010_jdoe.mbz';
        $row->filepath        = substr($badpath, 0, 255);
        $row->filepath_full   = $badpath;
        $row->filepath_hash   = sha1($badpath);
        $row->source          = 'backadel_current';
        $row->year            = 2023;
        $row->semester        = 'Fall';
        $row->dept            = 'CSC';
        $row->course_num      = '1010';
        $row->course_idnumber = null;
        $row->shortname       = null;
        $row->instructors     = '["jdoe"]';
        $row->pattern         = 'semester_legacy';
        $row->backup_ts       = 0;
        $row->file_size       = null;
        $row->status          = 'available';
        $row->timecreated     = time();
        $row->timemodified    = time();

        $id = $DB->insert_record('block_backadel_catalogue', $row);

        $resolved = backadel_resolve_path((int) $id);

        $this->assertStringNotContainsString('//', $resolved,
            'backadel_resolve_path() must collapse // from stale DB data');
        $this->assertSame($goodpath, $resolved,
            'backadel_resolve_path() must return the normalised absolute path');
    }

    /**
     * backadel_resolve_path() must return the empty string for a missing catalogue ID.
     *
     * @covers backadel_resolve_path
     */
    public function test_resolve_path_missing_id_returns_empty(): void {
        $this->resetAfterTest(true);

        $result = backadel_resolve_path(PHP_INT_MAX);
        $this->assertSame('', $result);
    }

    /**
     * backadel_resolve_path() must return a clean path when filepath_full has no //.
     *
     * @covers backadel_resolve_path
     */
    public function test_resolve_path_clean_path_unchanged(): void {
        global $DB;
        $this->resetAfterTest(true);

        $cleanpath = '/moodledata/backadel/FA2023_CSC1010_jdoe.mbz';

        $row = new \stdClass();
        $row->filename        = 'FA2023_CSC1010_jdoe.mbz';
        $row->filepath        = substr($cleanpath, 0, 255);
        $row->filepath_full   = $cleanpath;
        $row->filepath_hash   = sha1($cleanpath . '_clean');
        $row->source          = 'backadel_current';
        $row->year            = 2023;
        $row->semester        = 'Fall';
        $row->dept            = null;
        $row->course_num      = null;
        $row->course_idnumber = null;
        $row->shortname       = null;
        $row->instructors     = '[]';
        $row->pattern         = 'unknown';
        $row->backup_ts       = 0;
        $row->file_size       = null;
        $row->status          = 'available';
        $row->timecreated     = time();
        $row->timemodified    = time();

        $id = $DB->insert_record('block_backadel_catalogue', $row);

        $resolved = backadel_resolve_path((int) $id);
        $this->assertSame($cleanpath, $resolved,
            'A path with no // must pass through unchanged');
    }
}
