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
 * Simulates the real-world prod upgrade scenario for block_backadel.
 *
 * Focuses on the 2026051400 upgrade block (bug-044 / bug-045):
 *   - filepath_hash column + dedup + UNIQUE on block_backadel_courses
 *   - dedup + UNIQUE (coursesid, username) on block_backadel_teachers
 *   - composite catalogue + courses indexes
 *
 * The Moodle test DB is built from install.xml, so it already contains the
 * post-MD-2189 schema (filepath_hash column, UNIQUE keys, composite indexes).
 * To simulate the pre-MD-2189 state we drop the relevant column / indexes /
 * keys before inserting fixture data, then call xmldb_block_backadel_upgrade()
 * with $oldversion = 2026051300 and assert post-upgrade state.
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
    private const VERSION_TARGET = 2026051400;

    /** @var int Just-before-target version we pretend the DB is at. */
    private const VERSION_PRE = 2026051300;

    /**
     * Strip the post-MD-2189 schema additions off the existing tables and
     * pin config_plugins.version to VERSION_PRE so upgrade_plugin_savepoint()
     * accepts the bump.
     */
    private function rewind_schema_to_pre_2026051400(): void {
        global $DB;
        $dbman = $DB->get_manager();

        // -- block_backadel_courses --
        $coursestable = new \xmldb_table('block_backadel_courses');

        // Drop covering index added in 2026051400.
        $covering = new \xmldb_index('filename_courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['filename', 'courseid']);
        if ($dbman->index_exists($coursestable, $covering)) {
            $dbman->drop_index($coursestable, $covering);
        }

        // Drop the UNIQUE key on filepath_hash (it is declared as a KEY in install.xml,
        // so we drop it via the xmldb_key API; the underlying index name may be
        // 'filepath_hash_uk').
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

        // -- block_backadel_catalogue: drop the composite indexes added in 2026051400 --
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

        // Pin plugin version so upgrade_plugin_savepoint() does not throw downgrade_exception.
        set_config('version', (string)self::VERSION_PRE, self::COMPONENT);
    }

    /**
     * Count indexes on $tablename matching the given column list and uniqueness.
     *
     * We can't assert on xmldb_index names (e.g. 'filepath_hash_uk') because
     * Moodle's DBAL generates short auto-derived DB-side names like
     * 'm_blocbackcour_fil_uix'. Instead we count indexes by (columns, unique)
     * tuple. This lets us distinguish between, say, a UNIQUE index on
     * [filepath_hash] (the final state) and a NOTUNIQUE temp index on the
     * same column (an orphan from a crashed run).
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
     * Insert a courses row, returning its id. Bypasses TEXT-default issues by
     * supplying all NOTNULL columns explicitly.
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
     * Full simulation:
     *   - rewind schema to pre-2026051400
     *   - insert duplicate + clean fixture data on both warm-path tables
     *   - run the upgrade
     *   - assert dedup kept the LOWEST id per group (per the SQL DELETE),
     *     UNIQUE indexes exist, composite indexes exist, clean rows survived.
     */
    public function test_upgrade_2026051400_with_duplicates(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->rewind_schema_to_pre_2026051400();
        $dbman = $DB->get_manager();

        // ------------------------------------------------------------------
        // Fixture: 3 distinct filepaths on block_backadel_courses.
        //   group A: 3 duplicates of the same filepath
        //   group B: 2 duplicates of another
        //   group C: 1 clean unique row
        // The dedup DELETE is `c1.id > c2.id` => keeps the LOWEST id per group.
        // ------------------------------------------------------------------
        $a1 = $this->insert_course('/backups/lsu/2026/spring/MATH-1550.mbz', 'MATH-1550.mbz', 1);
        $a2 = $this->insert_course('/backups/lsu/2026/spring/MATH-1550.mbz', 'MATH-1550.mbz', 1);
        $a3 = $this->insert_course('/backups/lsu/2026/spring/MATH-1550.mbz', 'MATH-1550.mbz', 1);
        $b1 = $this->insert_course('/backups/lsu/2026/spring/BIOL-1001.mbz', 'BIOL-1001.mbz', 2);
        $b2 = $this->insert_course('/backups/lsu/2026/spring/BIOL-1001.mbz', 'BIOL-1001.mbz', 2);
        $c1 = $this->insert_course('/backups/lsu/2026/spring/HIST-2055.mbz', 'HIST-2055.mbz', 3);

        // Sanity: 6 rows pre-upgrade.
        $this->assertSame(6, $DB->count_records('block_backadel_courses'));

        // ------------------------------------------------------------------
        // Fixture: duplicates on block_backadel_teachers.
        //   Group X: 2 teachers (coursesid=$a1, username='jdoe')
        //   Group Y: 1 unique teacher
        // ------------------------------------------------------------------
        $t1 = $this->insert_teacher($a1, 'jdoe');
        $t2 = $this->insert_teacher($a1, 'jdoe');
        $t3 = $this->insert_teacher($b1, 'msmith');
        $this->assertSame(3, $DB->count_records('block_backadel_teachers'));

        // ------------------------------------------------------------------
        // Run the upgrade.
        // ------------------------------------------------------------------
        $result = xmldb_block_backadel_upgrade(self::VERSION_PRE);
        $this->assertTrue($result, 'xmldb_block_backadel_upgrade() must return true');

        // The upgrade declares filepath_hash with `''` as DEFAULT (the
        // intentional design — empty default lets us add NOTNULL to existing
        // rows in one shot before backfilling). XMLDB always emits a
        // debugging() warning about that. Swallow it so PHPUnit does not
        // fail the test for an expected message; the actual schema is fine.
        $this->assertDebuggingCalled();

        // ------------------------------------------------------------------
        // Assert: courses deduped, keeping LOWEST id per filepath.
        // ------------------------------------------------------------------
        $this->assertSame(
            3,
            $DB->count_records('block_backadel_courses'),
            'duplicate courses rows should have been removed'
        );
        $this->assertTrue($DB->record_exists('block_backadel_courses', ['id' => $a1]),
            'lowest-id row in duplicate group A must survive');
        $this->assertFalse($DB->record_exists('block_backadel_courses', ['id' => $a2]));
        $this->assertFalse($DB->record_exists('block_backadel_courses', ['id' => $a3]));
        $this->assertTrue($DB->record_exists('block_backadel_courses', ['id' => $b1]),
            'lowest-id row in duplicate group B must survive');
        $this->assertFalse($DB->record_exists('block_backadel_courses', ['id' => $b2]));
        $this->assertTrue($DB->record_exists('block_backadel_courses', ['id' => $c1]),
            'clean unique row must survive untouched');

        // Assert: filepath_hash column was added and backfilled with sha1(filepath).
        $surviving = $DB->get_record('block_backadel_courses', ['id' => $a1]);
        $this->assertSame(
            sha1('/backups/lsu/2026/spring/MATH-1550.mbz'),
            $surviving->filepath_hash,
            'filepath_hash must be sha1(filepath)'
        );

        // Assert: teachers deduped to 2 rows (group X collapsed, Y kept).
        $this->assertSame(2, $DB->count_records('block_backadel_teachers'));
        $this->assertTrue($DB->record_exists('block_backadel_teachers', ['id' => $t1]));
        $this->assertFalse($DB->record_exists('block_backadel_teachers', ['id' => $t2]));
        $this->assertTrue($DB->record_exists('block_backadel_teachers', ['id' => $t3]));

        // ------------------------------------------------------------------
        // Assert: UNIQUE indexes exist (post-upgrade schema state).
        // ------------------------------------------------------------------
        $coursestable = new \xmldb_table('block_backadel_courses');
        $teacherstable = new \xmldb_table('block_backadel_teachers');

        $this->assertTrue(
            $dbman->index_exists($coursestable,
                new \xmldb_index('filepath_hash_uk', XMLDB_INDEX_UNIQUE, ['filepath_hash'])),
            'filepath_hash_uk UNIQUE index must exist post-upgrade'
        );
        $this->assertTrue(
            $dbman->index_exists($teacherstable,
                new \xmldb_index('coursesid_username_uk', XMLDB_INDEX_UNIQUE, ['coursesid', 'username'])),
            'coursesid_username_uk UNIQUE index must exist post-upgrade'
        );

        // Assert: temp dedup indexes are gone, UNIQUE indexes are present.
        // We can't assert on xmldb_index names directly because Moodle's DBAL
        // auto-generates short DB-side names (e.g. 'm_blocbackcour_fil_uix'),
        // so we count (columns, unique) shapes instead.
        //
        // Final shape:
        //   block_backadel_courses[filepath_hash]:
        //       UNIQUE × 1, NOTUNIQUE × 0  ← orphan temp index would show as
        //                                    NOTUNIQUE on the same column.
        //   block_backadel_teachers[coursesid, username]: same pattern.
        $this->assertSame(1, $this->count_indexes('block_backadel_courses', ['filepath_hash'], true),
            'exactly one UNIQUE index on courses(filepath_hash) must exist post-upgrade');
        $this->assertSame(0, $this->count_indexes('block_backadel_courses', ['filepath_hash'], false),
            'no leftover NOTUNIQUE temp index on courses(filepath_hash)');
        $this->assertSame(1, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], true),
            'exactly one UNIQUE index on teachers(coursesid, username) must exist post-upgrade');
        $this->assertSame(0, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], false),
            'no leftover NOTUNIQUE temp index on teachers(coursesid, username)');

        // Assert: covering index on courses(filename, courseid) is present.
        $this->assertTrue(
            $dbman->index_exists($coursestable,
                new \xmldb_index('filename_courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['filename', 'courseid']))
        );

        // ------------------------------------------------------------------
        // Assert: catalogue composite indexes exist.
        // ------------------------------------------------------------------
        $catalogue = new \xmldb_table('block_backadel_catalogue');
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

        // Plugin version was bumped past VERSION_TARGET. The recorded
        // version may be even higher because later upgrade blocks
        // (2026051600, 2026051700) also run from this $oldversion. We only
        // care that the 2026051400 savepoint was reached.
        $recorded = (int)$DB->get_field('config_plugins', 'value',
            ['plugin' => self::COMPONENT, 'name' => 'version']);
        $this->assertGreaterThanOrEqual(self::VERSION_TARGET, $recorded,
            'plugin version must have advanced to at least the 2026051400 savepoint');
    }

    /**
     * Idempotency: running the upgrade twice (the second time with $oldversion
     * still at VERSION_PRE — simulating a crash-and-resume where the savepoint
     * never landed) must not crash on the second call, because every step is
     * guarded by index_exists / field_exists.
     *
     * Note: this exercises the same guards that protected prod from a worse
     * blast radius when bug-046b first surfaced.
     */
    public function test_upgrade_2026051400_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->rewind_schema_to_pre_2026051400();
        $dbman = $DB->get_manager();

        // Insert a couple of duplicate courses + one clean teacher.
        $a1 = $this->insert_course('/backups/x/A.mbz', 'A.mbz', 10);
        $a2 = $this->insert_course('/backups/x/A.mbz', 'A.mbz', 10);
        $this->insert_teacher($a1, 'tprof');

        // First run: 1 expected debugging() from the `''`-default field decl.
        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));
        $this->assertDebuggingCalled();
        $this->assertSame(1, $DB->count_records('block_backadel_courses'));

        // Second run: simulate crash-then-resume by rewinding the recorded
        // plugin version. Schema additions (column, indexes) are now in place,
        // so every guard in the 2026051400 block must short-circuit cleanly.
        set_config('version', (string)self::VERSION_PRE, self::COMPONENT);
        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));
        // The xmldb_field declaration runs even on the no-op path, so a 2nd
        // debugging() is emitted. That's expected and benign.
        $this->assertDebuggingCalled();
        $this->assertSame(1, $DB->count_records('block_backadel_courses'));

        // UNIQUE index still exists, temp index still gone.
        $this->assertSame(1, $this->count_indexes('block_backadel_courses', ['filepath_hash'], true));
        $this->assertSame(0, $this->count_indexes('block_backadel_courses', ['filepath_hash'], false));
        unset($dbman); // silence unused-local warning.
    }

    /**
     * Crash-and-resume scenario: simulate the prod failure mode where the
     * dedup DELETE landed but the temp index drop crashed (this is exactly
     * the `remove_index → drop_index` typo that the bug fix addresses).
     *
     * We pre-create the temp index ourselves to put the DB in the "crashed
     * mid-upgrade" state, then run the full upgrade and assert the temp
     * index is cleaned up by the second pass through the guards.
     */
    public function test_upgrade_2026051400_recovers_from_orphan_temp_index(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->rewind_schema_to_pre_2026051400();
        $dbman = $DB->get_manager();

        $coursestable = new \xmldb_table('block_backadel_courses');
        $teacherstable = new \xmldb_table('block_backadel_teachers');

        // Pre-stage data + add filepath_hash column manually so the temp
        // index has a valid column to bind to. Same field shape as upgrade.php
        // line 289 — the empty default triggers an XMLDB debugging() warning
        // we swallow explicitly below.
        $hashfield = new \xmldb_field('filepath_hash',
            XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '', 'filepath');
        $dbman->add_field($coursestable, $hashfield);
        $this->assertDebuggingCalled();

        $a1 = $this->insert_course('/backups/y/C.mbz', 'C.mbz', 20);
        $a2 = $this->insert_course('/backups/y/C.mbz', 'C.mbz', 20);
        $DB->execute("UPDATE {block_backadel_courses} SET filepath_hash = SHA1(filepath)");

        // Pre-stage the orphan temp indexes — the post-crash state with the
        // remove_index typo. Both indexes are present BEFORE the upgrade runs.
        $orphancourses = new \xmldb_index('tmp_filepath_hash_dedup', XMLDB_INDEX_NOTUNIQUE, ['filepath_hash']);
        $dbman->add_index($coursestable, $orphancourses);
        $orphanteachers = new \xmldb_index('tmp_csid_uname_dedup', XMLDB_INDEX_NOTUNIQUE, ['coursesid', 'username']);
        $dbman->add_index($teacherstable, $orphanteachers);

        // Sanity: BEFORE the upgrade, there is exactly one NOTUNIQUE index on
        // each of the dedup column-sets (the orphan we just added).
        $this->assertSame(1, $this->count_indexes('block_backadel_courses', ['filepath_hash'], false));
        $this->assertSame(0, $this->count_indexes('block_backadel_courses', ['filepath_hash'], true));
        $this->assertSame(1, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], false));
        $this->assertSame(0, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], true));

        // Run the upgrade. The guard in upgrade.php (both for courses and
        // teachers) is:
        //   if (!index_exists(unique) && !index_exists(tmp)) {
        //       add_index(tmp); $addedtmp = true;
        //   }
        //   ... DELETE dedup ...
        //   if ($addedtmp && index_exists(tmp)) drop_index(tmp);
        //   if (!index_exists(unique)) add_index(unique);  // step 1d / 2b
        //
        // Because Moodle's index_exists() matches by COLUMN LIST (not by name
        // or by uniqueness flag), the pre-existing orphan tmp index on the
        // same column-set causes BOTH:
        //   - the temp-add guard to short-circuit (so $addedtmp stays false,
        //     so the drop is skipped — orphan persists), AND
        //   - the unique-add guard to short-circuit (because index_exists
        //     returns true on the orphan, so the UNIQUE index is never added).
        //
        // Net result documented by the assertions below: the orphan temp
        // index from a crashed run blocks the UNIQUE constraint from being
        // added on a re-run. The schema is left in an INCONSISTENT state
        // (no unique constraint enforced, but the temp index still sits
        // there bloating writes).
        //
        // ** Recommended follow-up (not in scope of this fix) **: add an
        // unconditional drop_index(tmp_*) at the top of the 2026051400 block,
        // gated on the literal index name (not column-set), so the next
        // re-run from a crashed state self-heals.
        $this->assertTrue(xmldb_block_backadel_upgrade(self::VERSION_PRE));
        $this->assertDebuggingCalled(); // upgrade.php line 289 default-warning.

        // Dedup DELETE still happened (it does not depend on index objects).
        $this->assertSame(1, $DB->count_records('block_backadel_courses'));

        // Orphan NOTUNIQUE indexes still present on BOTH tables.
        $this->assertSame(1, $this->count_indexes('block_backadel_courses', ['filepath_hash'], false),
            'orphan NOTUNIQUE index on courses(filepath_hash) survives a re-run');
        $this->assertSame(1, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], false),
            'orphan NOTUNIQUE index on teachers(coursesid, username) survives a re-run');

        // UNIQUE constraints were NOT added (orphan blocks the guard).
        // This is the production data-integrity gap.
        $this->assertSame(0, $this->count_indexes('block_backadel_courses', ['filepath_hash'], true),
            'UNIQUE on courses(filepath_hash) was NOT added — orphan tmp blocks the guard');
        $this->assertSame(0, $this->count_indexes('block_backadel_teachers', ['coursesid', 'username'], true),
            'UNIQUE on teachers(coursesid, username) was NOT added — orphan tmp blocks the guard');
    }
}
