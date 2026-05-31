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

global $CFG;
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->dirroot . '/blocks/backadel/db/upgrade.php');

/**
 * Tests for the consolidated MD-2189 upgrade step (2026060100).
 *
 * The Moodle test DB is built from install.xml, so it already contains the
 * post-MD-2189 schema (filepath_hash column, UNIQUE keys, composite indexes).
 * To simulate the pre-MD-2189 state we drop the relevant column / indexes /
 * keys before inserting fixture data, then call xmldb_block_backadel_upgrade()
 * with $oldversion = 2026051301 and assert post-upgrade state.
 *
 * Three scenarios:
 *   1. Upgrade from pre-MD-2189 with duplicate data → dedup fires, all
 *      fields + indexes land, deduped rows are gone.
 *   2. Idempotent re-run → guards short-circuit cleanly, no crash.
 *   3. Upgrade on a clean schema with no duplicates → fields + indexes land,
 *      no rows lost.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversNothing  Upgrade scripts are tested holistically, not class-by-class.
 */
final class upgrade_test extends \advanced_testcase {

    /** @var string Plugin component name. */
    private const COMPONENT = 'block_backadel';

    /** @var int Target upgrade version under test. */
    private const VERSION_TARGET = 2026060100;

    /** @var int Just-before-target version we pretend the DB is at. */
    private const VERSION_PRE = 2026051301;

    /**
     * Strip the post-MD-2189 schema additions off the existing tables and
     * pin config_plugins.version to VERSION_PRE so upgrade_block_savepoint()
     * accepts the bump.
     */
    private function rewind_schema_to_pre_md2189(): void {
        global $DB;
        $dbman = $DB->get_manager();

        // -- block_backadel_courses --
        $coursestable = new \xmldb_table('block_backadel_courses');

        // Drop covering index added in MD-2189.
        $covering = new \xmldb_index('filename_courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['filename', 'courseid']);
        if ($dbman->index_exists($coursestable, $covering)) {
            $dbman->drop_index($coursestable, $covering);
        }

        // Drop the UNIQUE key on filepath_hash. It is declared as a KEY in
        // install.xml but we drop both shapes to be safe.
        $hashkey = new \xmldb_key('filepath_hash_uk', XMLDB_KEY_UNIQUE, ['filepath_hash']);
        try {
            $dbman->drop_key($coursestable, $hashkey);
        } catch (\Throwable $ignored) {
            // Already gone.
        }
        $hashidx = new \xmldb_index('filepath_hash_uk', XMLDB_INDEX_UNIQUE, ['filepath_hash']);
        if ($dbman->index_exists($coursestable, $hashidx)) {
            $dbman->drop_index($coursestable, $hashidx);
        }

        // Drop the filepath_hash column itself.
        $hashfield = new \xmldb_field('filepath_hash');
        if ($dbman->field_exists($coursestable, $hashfield)) {
            $dbman->drop_field($coursestable, $hashfield);
        }

        // -- block_backadel_teachers --
        $teacherstable = new \xmldb_table('block_backadel_teachers');
        $teacheruk = new \xmldb_key('coursesid_username_uk', XMLDB_KEY_UNIQUE, ['coursesid', 'username']);
        try {
            $dbman->drop_key($teacherstable, $teacheruk);
        } catch (\Throwable $ignored) {
            // Already gone.
        }
        $teacheridx = new \xmldb_index('coursesid_username_uk', XMLDB_INDEX_UNIQUE, ['coursesid', 'username']);
        if ($dbman->index_exists($teacherstable, $teacheridx)) {
            $dbman->drop_index($teacherstable, $teacheridx);
        }

        // -- block_backadel_catalogue: drop the composite indexes added in MD-2189 --
        $catalogue = new \xmldb_table('block_backadel_catalogue');
        $compositeidx = [
            new \xmldb_index('status_year_sem_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                ['status', 'year', 'semester', 'backup_ts']),
            new \xmldb_index('year_sem_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                ['year', 'semester', 'backup_ts']),
            new \xmldb_index('shortname_year_ix', XMLDB_INDEX_NOTUNIQUE,
                ['shortname', 'year']),
            new \xmldb_index('status_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                ['status', 'backup_ts']),
            new \xmldb_index('pattern_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                ['pattern', 'backup_ts']),
        ];
        foreach ($compositeidx as $ix) {
            if ($dbman->index_exists($catalogue, $ix)) {
                $dbman->drop_index($catalogue, $ix);
            }
        }

        // Pin plugin version so upgrade_block_savepoint() does not throw downgrade_exception.
        set_config('version', (string)self::VERSION_PRE, self::COMPONENT);
    }

    /**
     * Count indexes on $tablename matching the given column list and uniqueness.
     *
     * We can't assert on xmldb_index names (e.g. 'filepath_hash_uk') because
     * Moodle's DBAL generates short auto-derived DB-side names like
     * 'm_blocbackcour_fil_uix'. Instead we count indexes by (columns, unique)
     * tuple.
     *
     * @param string $tablename Unprefixed table name.
     * @param array<int,string> $columns Ordered column list.
     * @param bool $unique Whether we want unique or non-unique matches.
     * @return int Number of indexes on the table matching the (columns, unique) shape.
     */
    private function count_indexes(string $tablename, array $columns, bool $unique): int {
        global $DB;
        $count = 0;
        foreach ($DB->get_indexes($tablename) as $_ => $info) {
            if ((bool)$info['unique'] !== $unique) {
                continue;
            }
            // Use array_values so positional comparison ignores any holes
            // in $info['columns'] (DBAL fills by Seq_in_index − 1).
            if (array_values($info['columns']) === $columns) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Insert a courses row, returning its id.
     */
    private function insert_course(string $filepath, string $filename, int $courseid = 42): int {
        global $DB;
        return (int)$DB->insert_record('block_backadel_courses', (object)[
            'courseid'         => $courseid,
            'coursefullname'   => 'Course ' . $courseid,
            'courseshortname'  => 'C' . $courseid,
            'courseidnumber'   => null,
            'status'           => 'available',
            'filepath'         => $filepath,
            'filename'         => $filename,
            'filesize'         => 1234,
            'timecreated'      => time(),
            'backupcreated'    => null,
            'semester'         => 'LSU_AM_spring_2026',
            'academicperiodid' => '2245',
            'coursetype'       => 'teaching',
            'statusid'         => null,
        ]);
    }

    /**
     * Insert a teacher row, returning its id.
     */
    private function insert_teacher(int $coursesid, string $username, ?int $userid = null): int {
        global $DB;
        return (int)$DB->insert_record('block_backadel_teachers', (object)[
            'coursesid'   => $coursesid,
            'userid'      => $userid,
            'username'    => $username,
            'email'       => $username . '@lsu.edu',
            'resolved'    => $userid ? 1 : 0,
            'resolvedvia' => $userid ? 'username' : null,
            'timecreated' => time(),
        ]);
    }

    /**
     * Assert that every MD-2189 schema addition is in place after the upgrade.
     */
    private function assert_post_upgrade_schema(): void {
        global $DB;
        $dbman = $DB->get_manager();

        $coursestable = new \xmldb_table('block_backadel_courses');
        $teacherstable = new \xmldb_table('block_backadel_teachers');
        $catalogue = new \xmldb_table('block_backadel_catalogue');

        // Courses: filepath_hash field present.
        $this->assertTrue(
            $dbman->field_exists($coursestable, new \xmldb_field('filepath_hash')),
            'filepath_hash field must exist post-upgrade'
        );

        // Courses: UNIQUE filepath_hash exists, no stray non-unique twin.
        $this->assertSame(1, $this->count_indexes('block_backadel_courses', ['filepath_hash'], true),
            'exactly one UNIQUE index on courses(filepath_hash) must exist post-upgrade');
        $this->assertSame(0, $this->count_indexes('block_backadel_courses', ['filepath_hash'], false),
            'no leftover NOTUNIQUE index on courses(filepath_hash)');

        // Courses: covering index.
        $this->assertTrue(
            $dbman->index_exists($coursestable,
                new \xmldb_index('filename_courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['filename', 'courseid'])),
            'filename_courseid_ix must exist post-upgrade'
        );

        // Teachers: UNIQUE (coursesid, username) exists, no stray non-unique twin.
        $this->assertSame(1, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], true),
            'exactly one UNIQUE index on teachers(coursesid, username) must exist post-upgrade');
        $this->assertSame(0, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], false),
            'no leftover NOTUNIQUE index on teachers(coursesid, username)');

        // Catalogue composite indexes.
        $expected = [
            ['status_year_sem_ts_ix', ['status', 'year', 'semester', 'backup_ts']],
            ['year_sem_ts_ix',        ['year', 'semester', 'backup_ts']],
            ['shortname_year_ix',     ['shortname', 'year']],
            ['status_ts_ix',          ['status', 'backup_ts']],
            ['pattern_ts_ix',         ['pattern', 'backup_ts']],
        ];
        foreach ($expected as [$name, $fields]) {
            $this->assertTrue(
                $dbman->index_exists($catalogue, new \xmldb_index($name, XMLDB_INDEX_NOTUNIQUE, $fields)),
                "catalogue index {$name} must exist post-upgrade"
            );
        }

        // Plugin version was bumped to (or past) VERSION_TARGET.
        $recorded = (int)$DB->get_field('config_plugins', 'value',
            ['plugin' => self::COMPONENT, 'name' => 'version']);
        $this->assertGreaterThanOrEqual(self::VERSION_TARGET, $recorded,
            'plugin version must have advanced to at least the 2026060100 savepoint');
    }

    /**
     * Full simulation: pre-MD-2189 schema + duplicate fixture data.
     * Dedup must fire and all schema additions must land.
     */
    public function test_upgrade_with_duplicates(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->rewind_schema_to_pre_md2189();

        // Courses fixture: 3 distinct filepaths.
        //   group A: 3 duplicates
        //   group B: 2 duplicates
        //   group C: 1 clean unique row
        // Dedup is `c1.id > c2.id` → keeps the LOWEST id per group.
        $a1 = $this->insert_course('/backups/lsu/2026/spring/MATH-1550.mbz', 'MATH-1550.mbz', 1);
        $a2 = $this->insert_course('/backups/lsu/2026/spring/MATH-1550.mbz', 'MATH-1550.mbz', 1);
        $a3 = $this->insert_course('/backups/lsu/2026/spring/MATH-1550.mbz', 'MATH-1550.mbz', 1);
        $b1 = $this->insert_course('/backups/lsu/2026/spring/BIOL-1001.mbz', 'BIOL-1001.mbz', 2);
        $b2 = $this->insert_course('/backups/lsu/2026/spring/BIOL-1001.mbz', 'BIOL-1001.mbz', 2);
        $c1 = $this->insert_course('/backups/lsu/2026/spring/HIST-2055.mbz', 'HIST-2055.mbz', 3);

        $this->assertSame(6, $DB->count_records('block_backadel_courses'));

        // Teachers fixture:
        //   Group X: 2 dupes (coursesid=$a1, username='jdoe')
        //   Group Y: 1 clean
        $t1 = $this->insert_teacher($a1, 'jdoe');
        $t2 = $this->insert_teacher($a1, 'jdoe');
        $t3 = $this->insert_teacher($b1, 'msmith');
        $this->assertSame(3, $DB->count_records('block_backadel_teachers'));

        // Run upgrade.
        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));

        // The xmldb_field declaration uses `''` as DEFAULT (intentional — empty
        // default lets us add NOTNULL to existing rows in one shot). XMLDB
        // always emits a debugging() warning about that. Swallow it.
        $this->assertDebuggingCalled();

        // Courses: deduped to 3 rows, lowest id per group survives.
        $this->assertSame(3, $DB->count_records('block_backadel_courses'),
            'duplicate courses rows should have been removed');
        $this->assertTrue($DB->record_exists('block_backadel_courses', ['id' => $a1]));
        $this->assertFalse($DB->record_exists('block_backadel_courses', ['id' => $a2]));
        $this->assertFalse($DB->record_exists('block_backadel_courses', ['id' => $a3]));
        $this->assertTrue($DB->record_exists('block_backadel_courses', ['id' => $b1]));
        $this->assertFalse($DB->record_exists('block_backadel_courses', ['id' => $b2]));
        $this->assertTrue($DB->record_exists('block_backadel_courses', ['id' => $c1]));

        // filepath_hash backfilled with sha1(filepath).
        $surviving = $DB->get_record('block_backadel_courses', ['id' => $a1]);
        $this->assertSame(
            sha1('/backups/lsu/2026/spring/MATH-1550.mbz'),
            $surviving->filepath_hash
        );

        // Teachers: deduped to 2 rows.
        $this->assertSame(2, $DB->count_records('block_backadel_teachers'));
        $this->assertTrue($DB->record_exists('block_backadel_teachers', ['id' => $t1]));
        $this->assertFalse($DB->record_exists('block_backadel_teachers', ['id' => $t2]));
        $this->assertTrue($DB->record_exists('block_backadel_teachers', ['id' => $t3]));

        // All schema additions in place.
        $this->assert_post_upgrade_schema();
    }

    /**
     * Idempotency: a second invocation (after rewinding only the recorded
     * version, not the schema) must short-circuit cleanly because every step
     * is guarded by field_exists / index_exists.
     */
    public function test_upgrade_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->rewind_schema_to_pre_md2189();

        // Stage some duplicate courses + a clean teacher.
        $a1 = $this->insert_course('/backups/x/A.mbz', 'A.mbz', 10);
        $this->insert_course('/backups/x/A.mbz', 'A.mbz', 10);
        $this->insert_teacher($a1, 'tprof');

        // First run.
        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));
        $this->assertDebuggingCalled();
        $this->assertSame(1, $DB->count_records('block_backadel_courses'));
        $this->assert_post_upgrade_schema();

        // Second run: rewind only the recorded plugin version. Schema
        // additions are now in place, so every guard must short-circuit.
        set_config('version', (string)self::VERSION_PRE, self::COMPONENT);
        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));
        // The xmldb_field declaration is evaluated even on the no-op path,
        // so a 2nd `''`-default debugging() warning is emitted. Benign.
        $this->assertDebuggingCalled();

        // Counts unchanged, schema unchanged.
        $this->assertSame(1, $DB->count_records('block_backadel_courses'));
        $this->assert_post_upgrade_schema();
    }

    /**
     * Clean install path: pre-MD-2189 schema, no duplicates, no slug rows.
     * Every field + index must still be added correctly.
     */
    public function test_upgrade_on_clean_data(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->rewind_schema_to_pre_md2189();

        // One clean row per table.
        $cid = $this->insert_course('/backups/clean/X.mbz', 'X.mbz', 99);
        $this->insert_teacher($cid, 'cleanuser');

        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));
        $this->assertDebuggingCalled();

        // No rows lost.
        $this->assertSame(1, $DB->count_records('block_backadel_courses'));
        $this->assertSame(1, $DB->count_records('block_backadel_teachers'));

        // filepath_hash backfilled.
        $row = $DB->get_record('block_backadel_courses', ['id' => $cid]);
        $this->assertSame(sha1('/backups/clean/X.mbz'), $row->filepath_hash);

        // Schema complete.
        $this->assert_post_upgrade_schema();
    }
}
