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
            private array &$log;

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
            private array &$log;

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
