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

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Idempotency / dedup tests for {@see migrator} — bug-044.
 *
 * Verifies that:
 *   - block_backadel_courses.filepath_hash is populated on insert.
 *   - calling migrate_file() twice on the same archive produces exactly one
 *     courses row (no duplicate).
 *   - calling resolve_instructors() twice for the same (coursesid, username)
 *     produces exactly one teachers row (no duplicate).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group      backadel_dedup
 *
 * @covers \block_backadel\local\migrator
 */
final class migrator_dedup_test extends \advanced_testcase {

    /** @var string */
    private string $tmpdir;

    /** @var string */
    private string $basename;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Use an archive name the filename_parser will accept as a "warm-path" pattern
        // (semester_legacy → /\d{4}(Spring|...)[A-Z]+\d{4,7}\d{10}\.zip/).
        $this->tmpdir   = make_temp_directory('block_backadel_dedup_' . uniqid('', true));
        $this->basename = '2024SpringMATH12010011700000000.zip';
        file_put_contents($this->tmpdir . '/' . $this->basename, 'fake-zip-payload');
    }

    /**
     * Sanity check: the migrator inserts a courses row with filepath_hash equal
     * to sha1(filepathfull). Without this field set, the new UNIQUE key on the
     * courses table would refuse the insert (or, with DEFAULT '', would let the
     * first row through and fail on the second).
     *
     * @covers \block_backadel\local\migrator::migrate_file
     */
    public function test_migrate_file_populates_filepath_hash(): void {
        global $DB;

        $filepathfull = $this->tmpdir . '/' . $this->basename;
        $expectedhash = sha1($filepathfull);

        $migrator = new migrator();
        $migrator->migrate_file($filepathfull, 'backadel_current');

        $rows = $DB->get_records('block_backadel_courses');
        $this->assertCount(1, $rows, 'Expected exactly one courses row after first migrate_file().');

        $row = reset($rows);
        $this->assertSame(
            $expectedhash, $row->filepath_hash,
            'filepath_hash must equal sha1(filepathfull).'
        );
        $this->assertNotSame('', $row->filepath_hash, 'filepath_hash must not be the empty default.');
    }

    /**
     * Calling migrate_file() twice on the same path must NOT create a second
     * courses row — the UNIQUE key on filepath_hash plus the lookup-by-hash
     * upsert path keeps the table converged on a single row.
     *
     * @covers \block_backadel\local\migrator::migrate_file
     */
    public function test_migrate_file_twice_does_not_duplicate_courses(): void {
        global $DB;

        $filepathfull = $this->tmpdir . '/' . $this->basename;

        $migrator = new migrator();
        $migrator->migrate_file($filepathfull, 'backadel_current');
        $migrator->migrate_file($filepathfull, 'backadel_current');

        $count = $DB->count_records('block_backadel_courses', ['filepath_hash' => sha1($filepathfull)]);
        $this->assertSame(1, $count, 'Second migrate_file() must not insert a duplicate courses row.');
    }

    /**
     * The catalogue table is also covered by source_hash_uk and shouldn't
     * gain a duplicate from a re-migration.
     *
     * @covers \block_backadel\local\migrator::migrate_file
     */
    public function test_migrate_file_twice_does_not_duplicate_catalogue(): void {
        global $DB;

        $filepathfull = $this->tmpdir . '/' . $this->basename;

        $migrator = new migrator();
        $migrator->migrate_file($filepathfull, 'backadel_current');
        $migrator->migrate_file($filepathfull, 'backadel_current');

        $count = $DB->count_records('block_backadel_catalogue', [
            'source'        => 'backadel_current',
            'filepath_hash' => sha1($filepathfull),
        ]);
        $this->assertSame(1, $count, 'Catalogue must not gain a duplicate row.');
    }

    /**
     * Direct DB-level proof of the UNIQUE constraint on
     * block_backadel_courses.filepath_hash. Inserting two rows with the same
     * hash must fail at the database layer.
     */
    public function test_unique_constraint_blocks_duplicate_courses_row(): void {
        global $DB;

        $hash = sha1('/tmp/duplicate-path.zip');
        $base = $this->build_courses_row('/tmp/duplicate-path.zip', 'duplicate-path.zip', $hash);

        $DB->insert_record('block_backadel_courses', $base);

        $this->expectException(\dml_exception::class);
        $DB->insert_record('block_backadel_courses', $base);
    }

    /**
     * Direct DB-level proof of the UNIQUE constraint on
     * (coursesid, username) for block_backadel_teachers.
     */
    public function test_unique_constraint_blocks_duplicate_teachers_row(): void {
        global $DB;

        // First insert a parent courses row (needed for FK-shaped relationship,
        // even though no real FK exists).
        $coursesid = $DB->insert_record(
            'block_backadel_courses',
            $this->build_courses_row('/tmp/teacher-parent.zip', 'teacher-parent.zip',
                sha1('/tmp/teacher-parent.zip'))
        );

        $teacher = (object) [
            'coursesid'   => $coursesid,
            'userid'      => null,
            'username'    => 'jdoe',
            'email'       => null,
            'resolved'    => 0,
            'resolvedvia' => 'none',
            'timecreated' => time(),
        ];
        $DB->insert_record('block_backadel_teachers', $teacher);

        $this->expectException(\dml_exception::class);
        $DB->insert_record('block_backadel_teachers', $teacher);
    }

    /**
     * resolve_instructors() must be idempotent: calling it twice with the same
     * parsed metadata produces only one teachers row per username.
     *
     * Exercises the get_record() pre-check + dml_write_exception catch path
     * added in bug-044.
     *
     * @covers \block_backadel\local\migrator::migrate_file
     */
    public function test_resolve_instructors_twice_does_not_duplicate(): void {
        global $DB;

        // Use an instructor-pattern filename so the parser flags it as one of
        // the warm-path patterns AND extracts an instructor token.
        $basename = 'FA2023_CSC1010_jdoe.mbz';
        file_put_contents($this->tmpdir . '/' . $basename, 'fake-mbz');

        $filepathfull = $this->tmpdir . '/' . $basename;

        $migrator = new migrator();
        $migrator->migrate_file($filepathfull, 'backadel_current');
        $migrator->migrate_file($filepathfull, 'backadel_current');

        // The teacher row count for jdoe must be exactly 1 across both runs.
        $rows = $DB->get_records('block_backadel_teachers', ['username' => 'jdoe']);
        $this->assertCount(
            1, $rows,
            'resolve_instructors() must not produce a duplicate teacher row across re-runs.'
        );
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    /**
     * Build a minimal valid courses row stdClass.
     */
    private function build_courses_row(string $filepath, string $filename, string $filepathhash): \stdClass {
        return (object) [
            'courseid'         => null,
            'coursefullname'   => $filename,
            'courseshortname'  => $filename,
            'courseidnumber'   => null,
            'status'           => 'available',
            'filepath'         => $filepath,
            'filepath_hash'    => $filepathhash,
            'filename'         => $filename,
            'filesize'         => null,
            'timecreated'      => time(),
            'backupcreated'    => null,
            'semester'         => null,
            'academicperiodid' => null,
            'coursetype'       => 'other',
            'statusid'         => null,
        ];
    }
}
