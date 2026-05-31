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
 * Upgrade steps for block_backadel (MD-2189 wave 1 — schema).
 *
 * @package    block_backadel
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backadel block upgrade.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_block_backadel_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026050100) {
        $table = new xmldb_table('block_backadel_statuses');

        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $dbman->change_field_unsigned($table, $field);

        $field = new xmldb_field('coursesid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_unsigned($table, $field);

        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'BACKUP');
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $now = time();
        $DB->execute(
            'UPDATE {block_backadel_statuses} SET timecreated = :tc, timemodified = :tm',
            ['tc' => $now, 'tm' => $now]
        );

        upgrade_block_savepoint(true, 2026050100, 'backadel');
    }

    if ($oldversion < 2026050175) {
        $table = new xmldb_table('block_backadel_catalogue');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filepath', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filepath_full', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('filepath_hash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('year', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('semester', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('dept', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('course_num', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('instructors', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('pattern', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'unknown');
        $table->add_field('backup_ts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('file_size', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'available');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('source_hash_uk', XMLDB_KEY_UNIQUE, ['source', 'filepath_hash']);

        $table->add_index('shortname_ix', XMLDB_INDEX_NOTUNIQUE, ['shortname']);
        $table->add_index('backup_ts_ix', XMLDB_INDEX_NOTUNIQUE, ['backup_ts']);
        $table->add_index('dept_cn_ix', XMLDB_INDEX_NOTUNIQUE, ['dept', 'course_num']);
        $table->add_index('source_ix', XMLDB_INDEX_NOTUNIQUE, ['source']);
        $table->add_index('status_ix', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('filename_ix', XMLDB_INDEX_NOTUNIQUE, ['filename']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026050175, 'backadel');
    }

    if ($oldversion < 2026050200) {
        $table = new xmldb_table('block_backadel_courses');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('coursefullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseshortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseidnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'available');
        $table->add_field('filepath', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('backupcreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('semester', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('academicperiodid', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('coursetype', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'other');
        $table->add_field('statusid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('semester_ix', XMLDB_INDEX_NOTUNIQUE, ['semester']);
        $table->add_index('coursetype_ix', XMLDB_INDEX_NOTUNIQUE, ['coursetype']);
        $table->add_index('statusid_ix', XMLDB_INDEX_NOTUNIQUE, ['statusid']);
        $table->add_index('filename_ix', XMLDB_INDEX_NOTUNIQUE, ['filename']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026050200, 'backadel');
    }

    if ($oldversion < 2026050300) {
        $table = new xmldb_table('block_backadel_teachers');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursesid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('resolved', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('resolvedvia', XMLDB_TYPE_CHAR, '16', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('coursesid_ix', XMLDB_INDEX_NOTUNIQUE, ['coursesid']);
        $table->add_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('username_ix', XMLDB_INDEX_NOTUNIQUE, ['username']);
        $table->add_index('resolved_ix', XMLDB_INDEX_NOTUNIQUE, ['resolved']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026050300, 'backadel');
    }

    if ($oldversion < 2026050400) {
        $table = new xmldb_table('block_backadel_periods');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('periodid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('folder', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('label', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026050400, 'backadel');
    }

    if ($oldversion < 2026050500) {
        set_config('migration_state', 'pending', 'block_backadel');

        upgrade_block_savepoint(true, 2026050500, 'backadel');
    }

    if ($oldversion < 2026050800) {
        // Register db/services.php on upgrade (not covered by fresh install alone).
        upgrade_plugin_savepoint(true, 2026050800, 'block', 'backadel');
    }

    if ($oldversion < 2026050900) {
        // Catalogue admin UI (blocks/backadel/catalogue.php); no schema change.
        upgrade_plugin_savepoint(true, 2026050900, 'block', 'backadel');
    }

    if ($oldversion < 2026051200) {
        // Widen block_backadel_catalogue.semester from char(10) to char(20) to fit SecondSummer (12 chars).
        $table = new xmldb_table('block_backadel_catalogue');
        $field = new xmldb_field('semester', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'year');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026051200, 'block', 'backadel');
    }

    if ($oldversion < 2026051300) {
        $table = new xmldb_table('block_backadel_catalogue');

        $field = new xmldb_field(
            'coursetype_override',
            XMLDB_TYPE_CHAR, '16', null, null, null, null,
            'pattern'   // Insert after 'pattern'.
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'coursetype_override_note',
            XMLDB_TYPE_TEXT, null, null, null, null, null,
            'coursetype_override'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'coursetype_override_by',
            XMLDB_TYPE_INTEGER, '10', null, null, null, null,
            'coursetype_override_note'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'coursetype_override_ts',
            XMLDB_TYPE_INTEGER, '10', null, null, null, null,
            'coursetype_override_by'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026051300, 'block', 'backadel');
    }

    if ($oldversion < 2026051301) {
        // bug-039: Clean up any catalogue rows where path concatenation produced double slashes
        // before the rtrim() fix was applied. Safe to run mid-import: the adhoc task cursor
        // lives in task custom_data and is unaffected by changes to filepath column values.
        if ($dbman->table_exists(new xmldb_table('block_backadel_catalogue'))) {
            $DB->execute(
                "UPDATE {block_backadel_catalogue}
                    SET filepath_full = REPLACE(filepath_full, '//', '/'),
                        filepath      = REPLACE(filepath,      '//', '/')
                  WHERE filepath_full LIKE '%//%'
                     OR filepath      LIKE '%//%'"
            );
        }

        upgrade_plugin_savepoint(true, 2026051301, 'block', 'backadel');
    }

    if ($oldversion < 2026060100) {
        // ----------------------------------------------------------------------
        // MD-2189 consolidated schema step.
        //
        // Replaces the broken 2026051400 / 2026051600 / 2026051700 sequence that
        // tried to manage a temporary dedup index lifecycle and crashed in prod
        // (remove_index → drop_index typo, plus an orphan-index gate that
        // permanently blocked the UNIQUE constraint on re-run).
        //
        // Design: every operation is unconditionally guarded by field_exists /
        // index_exists, and the dedup DELETEs run directly with no temp index
        // scaffolding. They are idempotent — a run with no duplicates is a no-op.
        //
        // Covers:
        //   bug-044: UNIQUE on block_backadel_courses(filepath_hash)
        //   bug-044: UNIQUE on block_backadel_teachers(coursesid, username)
        //   bug-045: composite indexes on block_backadel_catalogue
        //   bug-045: covering index on block_backadel_courses(filename, courseid)
        //   bug-058: prune slug-username teacher rows
        //   bug-068: widen block_backadel_teachers.resolvedvia to char(32)
        // ----------------------------------------------------------------------

        // ----------------------------------------------------------------------
        // block_backadel_courses: filepath_hash column + dedup + UNIQUE.
        // ----------------------------------------------------------------------
        $coursestable = new xmldb_table('block_backadel_courses');
        if ($dbman->table_exists($coursestable)) {

            // Add filepath_hash column (NOTNULL, default '' so existing rows
            // satisfy the constraint until the UPDATE backfills sha1(filepath)).
            $hashfield = new xmldb_field(
                'filepath_hash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '', 'filepath'
            );
            if (!$dbman->field_exists($coursestable, $hashfield)) {
                $dbman->add_field($coursestable, $hashfield);
            }

            // Backfill sha1(filepath). Safe to re-run — only touches the
            // rows still on the '' default.
            $DB->execute(
                "UPDATE {block_backadel_courses}
                    SET filepath_hash = SHA1(filepath)
                  WHERE filepath_hash = '' OR filepath_hash IS NULL"
            );

            // Direct dedup on filepath_hash (the column getting the UNIQUE).
            // Wrapped in try/catch so a (very unlikely) DELETE failure on a
            // table with no dupes still lets the upgrade complete.
            try {
                $DB->execute(
                    "DELETE c1 FROM {block_backadel_courses} c1
                       INNER JOIN {block_backadel_courses} c2
                               ON c1.filepath_hash = c2.filepath_hash
                              AND c1.id > c2.id"
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026060100: courses dedup DELETE failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }

            // Add the UNIQUE index now that the column is populated and unique.
            $hashuk = new xmldb_index('filepath_hash_uk', XMLDB_INDEX_UNIQUE, ['filepath_hash']);
            if (!$dbman->index_exists($coursestable, $hashuk)) {
                $dbman->add_index($coursestable, $hashuk);
            }

            // bug-045: covering index for catalogue_table correlated subquery.
            $filenamecourseidix = new xmldb_index(
                'filename_courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['filename', 'courseid']
            );
            if (!$dbman->index_exists($coursestable, $filenamecourseidix)) {
                $dbman->add_index($coursestable, $filenamecourseidix);
            }
        }

        // ----------------------------------------------------------------------
        // block_backadel_teachers: bug-058 prune + dedup + UNIQUE + bug-068 widen.
        // ----------------------------------------------------------------------
        $teacherstable = new xmldb_table('block_backadel_teachers');
        if ($dbman->table_exists($teacherstable)) {

            // bug-058: remove teacher rows where username is clearly a
            // backup-filename slug (unresolved + > 32 chars or contains '-for-').
            // Runs BEFORE the dedup so we don't keep a slug row at the expense
            // of a real one. MariaDB LIKE is case-insensitive on the default
            // utf8mb4_unicode_ci collation, no BINARY needed.
            $DB->execute(
                "DELETE FROM {block_backadel_teachers}
                  WHERE userid IS NULL
                    AND (CHAR_LENGTH(username) > 32
                      OR username LIKE '%-for-%')"
            );

            // Direct dedup on (coursesid, username). Idempotent.
            try {
                $DB->execute(
                    "DELETE t1 FROM {block_backadel_teachers} t1
                       INNER JOIN {block_backadel_teachers} t2
                               ON t1.coursesid = t2.coursesid
                              AND t1.username  = t2.username
                              AND t1.id > t2.id"
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026060100: teachers dedup DELETE failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }

            // Add the UNIQUE composite index.
            $teacheruk = new xmldb_index(
                'coursesid_username_uk', XMLDB_INDEX_UNIQUE, ['coursesid', 'username']
            );
            if (!$dbman->index_exists($teacherstable, $teacheruk)) {
                $dbman->add_index($teacherstable, $teacheruk);
            }

            // bug-068: widen resolvedvia from char(16) to char(32) so long
            // domain first-labels (e.g. 'internationalcenter') fit without
            // a silent dml_write_exception. change_field_precision is a
            // no-op when the column is already at the target precision.
            $resolvedvia = new xmldb_field(
                'resolvedvia', XMLDB_TYPE_CHAR, '32', null, false, null, null, 'resolved'
            );
            if ($dbman->field_exists($teacherstable, $resolvedvia)) {
                $dbman->change_field_precision($teacherstable, $resolvedvia);
            }
        }

        // ----------------------------------------------------------------------
        // bug-045: block_backadel_catalogue composite filter indexes.
        // ----------------------------------------------------------------------
        // Keep existing single-column shortname_ix / status_ix in place; the
        // optimizer will pick the new composites where they match. Drop+replace
        // is risky here and offers no measurable win.
        $catalogue = new xmldb_table('block_backadel_catalogue');
        if ($dbman->table_exists($catalogue)) {
            $catindexes = [
                new xmldb_index('status_year_sem_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                    ['status', 'year', 'semester', 'backup_ts']),
                new xmldb_index('year_sem_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                    ['year', 'semester', 'backup_ts']),
                new xmldb_index('shortname_year_ix', XMLDB_INDEX_NOTUNIQUE,
                    ['shortname', 'year']),
                new xmldb_index('status_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                    ['status', 'backup_ts']),
                new xmldb_index('pattern_ts_ix', XMLDB_INDEX_NOTUNIQUE,
                    ['pattern', 'backup_ts']),
            ];
            foreach ($catindexes as $ix) {
                if (!$dbman->index_exists($catalogue, $ix)) {
                    $dbman->add_index($catalogue, $ix);
                }
            }
        }

        upgrade_block_savepoint(true, 2026060100, 'backadel');
    }

    return true;
}
