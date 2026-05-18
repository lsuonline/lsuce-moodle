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

    if ($oldversion < 2026051400) {
        // bug-044: add unique constraints on warm-path tables so concurrent migrate_file()
        //          chains can't insert duplicate (filepath / coursesid+username) rows.
        // bug-045: add composite indexes on the catalogue + courses tables to fix the
        //          year/semester/status/backup_ts filter queries (Q1, Q7, Q9-Q12) and
        //          the correlated subquery in catalogue_table::setup_sql() (Q8).

        // ---------------------------------------------------------------
        // bug-044: block_backadel_courses — add filepath_hash + UNIQUE key
        // ---------------------------------------------------------------
        $coursestable = new xmldb_table('block_backadel_courses');
        if ($dbman->table_exists($coursestable)) {

            // Step 1a: add filepath_hash column. We use NOTNULL with DEFAULT '' so the column
            // matches install.xml exactly (a single-shape schema reduces XMLDB validator drift).
            // The empty default lets us add the NOTNULL column to existing rows in one shot,
            // then UPDATE backfills sha1(filepath) below.
            $hashfield = new xmldb_field(
                'filepath_hash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '', 'filepath'
            );
            if (!$dbman->field_exists($coursestable, $hashfield)) {
                $dbman->add_field($coursestable, $hashfield);
            }

            // Step 1b: backfill sha1(filepath) for rows that still have the empty default.
            // Moodle DBAL has no SHA1 helper — use raw SQL. SHA1() is supported by both MySQL
            // and MariaDB (the only DB engines this plugin runs against).
            $DB->execute(
                "UPDATE {block_backadel_courses}
                    SET filepath_hash = SHA1(filepath)
                  WHERE filepath_hash = '' OR filepath_hash IS NULL"
            );

            // Step 1c: delete duplicate rows, keeping the lowest id per filepath_hash.
            // Add a temporary non-unique index on filepath_hash before the self-join DELETE
            // so MariaDB can use an index lookup instead of a full O(N²) table scan.
            // Without this, 142 K rows produce ~10 billion comparisons and the query
            // hangs for hours (bug-046b). Drop the temp index after; the UNIQUE key in
            // step 1d is a separate, narrower index object.
            $tmpcoursesidx = new xmldb_index('tmp_filepath_hash_dedup', XMLDB_INDEX_NOTUNIQUE, ['filepath_hash']);
            $uniquecoursesidx = new xmldb_index('filepath_hash_uk', XMLDB_INDEX_UNIQUE, ['filepath_hash']);
            $addedtmpcoursesidx = false;
            if (!$dbman->index_exists($coursestable, $uniquecoursesidx) &&
                    !$dbman->index_exists($coursestable, $tmpcoursesidx)) {
                $dbman->add_index($coursestable, $tmpcoursesidx);
                $addedtmpcoursesidx = true;
            }
            try {
                $DB->execute(
                    "DELETE c1 FROM {block_backadel_courses} c1
                       INNER JOIN {block_backadel_courses} c2
                               ON c1.filepath_hash = c2.filepath_hash
                              AND c1.id > c2.id"
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026051400: courses dedup DELETE failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }
            if ($addedtmpcoursesidx && $dbman->index_exists($coursestable, $tmpcoursesidx)) {
                $dbman->remove_index($coursestable, $tmpcoursesidx);
            }

            // Step 1d: add the UNIQUE index via xmldb_index (Moodle treats unique indexes
            // and unique keys interchangeably at the DBAL level).
            $hashuk = new xmldb_index('filepath_hash_uk', XMLDB_INDEX_UNIQUE, ['filepath_hash']);
            if (!$dbman->index_exists($coursestable, $hashuk)) {
                $dbman->add_index($coursestable, $hashuk);
            }
        }

        // ---------------------------------------------------------------
        // bug-044: block_backadel_teachers — dedup + UNIQUE (coursesid, username)
        // ---------------------------------------------------------------
        $teacherstable = new xmldb_table('block_backadel_teachers');
        if ($dbman->table_exists($teacherstable)) {

            // Step 2a: delete duplicate rows, keeping the lowest id per (coursesid, username).
            // Same temp-index pattern as courses step 1c — avoids O(N²) self-join on large tables.
            $tmpteachersidx = new xmldb_index('tmp_csid_uname_dedup', XMLDB_INDEX_NOTUNIQUE, ['coursesid', 'username']);
            $uniqueteachersidx = new xmldb_index('coursesid_username_uk', XMLDB_INDEX_UNIQUE, ['coursesid', 'username']);
            $addedtmpteachersidx = false;
            if (!$dbman->index_exists($teacherstable, $uniqueteachersidx) &&
                    !$dbman->index_exists($teacherstable, $tmpteachersidx)) {
                $dbman->add_index($teacherstable, $tmpteachersidx);
                $addedtmpteachersidx = true;
            }
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
                    'block_backadel upgrade 2026051400: teachers dedup DELETE failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }
            if ($addedtmpteachersidx && $dbman->index_exists($teacherstable, $tmpteachersidx)) {
                $dbman->remove_index($teacherstable, $tmpteachersidx);
            }

            // Step 2b: add the UNIQUE composite index.
            $teacheruk = new xmldb_index(
                'coursesid_username_uk', XMLDB_INDEX_UNIQUE, ['coursesid', 'username']
            );
            if (!$dbman->index_exists($teacherstable, $teacheruk)) {
                $dbman->add_index($teacherstable, $teacheruk);
            }
        }

        // ---------------------------------------------------------------
        // bug-045: block_backadel_catalogue — composite filter indexes
        // ---------------------------------------------------------------
        // Keep existing single-column shortname_ix / status_ix to minimise risk; the
        // optimizer will simply prefer the new composites where they match. This avoids
        // the "drop+replace" failure mode flagged in the bug report.
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

        // ---------------------------------------------------------------
        // bug-045: block_backadel_courses — covering index for catalogue subquery
        // ---------------------------------------------------------------
        if ($dbman->table_exists($coursestable)) {
            $filenamecourseidix = new xmldb_index(
                'filename_courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['filename', 'courseid']
            );
            if (!$dbman->index_exists($coursestable, $filenamecourseidix)) {
                $dbman->add_index($coursestable, $filenamecourseidix);
            }
        }

        upgrade_plugin_savepoint(true, 2026051400, 'block', 'backadel');
    }

    if ($oldversion < 2026051600) {
        // bug-058: remove teacher rows where username is clearly a backup-filename
        // slug. These rows have userid IS NULL (unresolved) and username either
        // exceeds 32 chars or contains '-for-' — the hallmark of a course-name slug.
        // MariaDB LIKE is case-insensitive on utf8mb4_unicode_ci, no BINARY needed.
        if ($dbman->table_exists(new xmldb_table('block_backadel_teachers'))) {
            $DB->execute(
                "DELETE FROM {block_backadel_teachers}
                  WHERE userid IS NULL
                    AND (CHAR_LENGTH(username) > 32
                      OR username LIKE '%-for-%')"
            );
        }
        upgrade_block_savepoint(true, 2026051600, 'backadel');
    }

    return true;
}
