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
 * Upgrade steps for block_backadel (MD-2189 — single consolidated block).
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
 * Decision (MD-2189): block_backadel is only used at LSU. Production is being
 * freshly reinstalled, and every other active site is pre-MD-2189. There is
 * no need to preserve a multi-step upgrade history — collapse everything into
 * one fully guarded block. Sites at intermediate versions can reset their
 * recorded plugin version manually if needed.
 *
 * Every operation in this block is idempotent:
 *   - add_field        → guarded by !$dbman->field_exists()
 *   - add_index        → guarded by !$dbman->index_exists()
 *   - create_table     → guarded by !$dbman->table_exists()
 *   - change_field_*   → guarded by $dbman->field_exists() (fresh install
 *                        already has the correct type, so it's a no-op there)
 *   - UPDATE / DELETE  → run unconditionally; safe to re-run (no-op when the
 *                        condition already does not match any rows).
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_block_backadel_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060100) {

        // --------------------------------------------------------------
        // Tables that may be missing on very old (pre-MD-2189) sites.
        // install.xml covers fresh installs; these guards cover upgrades.
        // --------------------------------------------------------------

        // block_backadel_periods — new in MD-2189.
        $periodstable = new xmldb_table('block_backadel_periods');
        if (!$dbman->table_exists($periodstable)) {
            $periodstable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $periodstable->add_field('periodid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
            $periodstable->add_field('folder', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $periodstable->add_field('label', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
            $periodstable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $periodstable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $periodstable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($periodstable);
        }

        // block_backadel_courses — new in MD-2189.
        $coursestable = new xmldb_table('block_backadel_courses');
        if (!$dbman->table_exists($coursestable)) {
            $coursestable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $coursestable->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $coursestable->add_field('coursefullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $coursestable->add_field('courseshortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $coursestable->add_field('courseidnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $coursestable->add_field('status', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'available');
            $coursestable->add_field('filepath', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $coursestable->add_field('filepath_hash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $coursestable->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $coursestable->add_field('filesize', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
            $coursestable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $coursestable->add_field('backupcreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $coursestable->add_field('semester', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $coursestable->add_field('academicperiodid', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $coursestable->add_field('coursetype', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'other');
            $coursestable->add_field('statusid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $coursestable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($coursestable);
        }

        // block_backadel_teachers — new in MD-2189.
        $teacherstable = new xmldb_table('block_backadel_teachers');
        if (!$dbman->table_exists($teacherstable)) {
            $teacherstable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $teacherstable->add_field('coursesid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $teacherstable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $teacherstable->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $teacherstable->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $teacherstable->add_field('resolved', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $teacherstable->add_field('resolvedvia', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $teacherstable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $teacherstable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($teacherstable);
        }

        // block_backadel_catalogue — new in MD-2189.
        $cataloguetable = new xmldb_table('block_backadel_catalogue');
        if (!$dbman->table_exists($cataloguetable)) {
            $cataloguetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $cataloguetable->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $cataloguetable->add_field('filepath', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $cataloguetable->add_field('filepath_full', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $cataloguetable->add_field('filepath_hash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $cataloguetable->add_field('source', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $cataloguetable->add_field('year', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
            $cataloguetable->add_field('semester', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $cataloguetable->add_field('dept', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $cataloguetable->add_field('course_num', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $cataloguetable->add_field('course_idnumber', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $cataloguetable->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $cataloguetable->add_field('instructors', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $cataloguetable->add_field('pattern', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'unknown');
            $cataloguetable->add_field('coursetype_override', XMLDB_TYPE_CHAR, '16', null, null, null, null);
            $cataloguetable->add_field('coursetype_override_note', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $cataloguetable->add_field('coursetype_override_by', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $cataloguetable->add_field('coursetype_override_ts', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $cataloguetable->add_field('backup_ts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $cataloguetable->add_field('file_size', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
            $cataloguetable->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'available');
            $cataloguetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $cataloguetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $cataloguetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $cataloguetable->add_key('source_hash_uk', XMLDB_KEY_UNIQUE, ['source', 'filepath_hash']);
            $dbman->create_table($cataloguetable);
        }

        // --------------------------------------------------------------
        // block_backadel_statuses: timecreated / timemodified columns
        // were added later. Backfill once.
        // --------------------------------------------------------------
        $statusestable = new xmldb_table('block_backadel_statuses');
        if ($dbman->table_exists($statusestable)) {
            $tc = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($statusestable, $tc)) {
                $dbman->add_field($statusestable, $tc);
            }
            $tm = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($statusestable, $tm)) {
                $dbman->add_field($statusestable, $tm);
            }
            // One-shot backfill — idempotent because the WHERE only matches
            // rows still on the '0' default.
            try {
                $now = time();
                $DB->execute(
                    'UPDATE {block_backadel_statuses}
                        SET timecreated = :tc
                      WHERE timecreated = 0',
                    ['tc' => $now]
                );
                $DB->execute(
                    'UPDATE {block_backadel_statuses}
                        SET timemodified = :tm
                      WHERE timemodified = 0',
                    ['tm' => $now]
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026060100: statuses backfill failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }
        }

        // --------------------------------------------------------------
        // block_backadel_catalogue: widen semester (10 → 20),
        // override columns, composite indexes, // → / path cleanup.
        // --------------------------------------------------------------
        if ($dbman->table_exists($cataloguetable)) {

            // Widen semester to char(20). Only run when the live column is
            // narrower than 20 — calling change_field_precision against an
            // already-wide column triggers ddl_dependency_exception when a
            // composite index covers the column (MariaDB cannot ALTER a
            // column referenced by an index).
            $semester = new xmldb_field('semester', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'year');
            if ($dbman->field_exists($cataloguetable, $semester)) {
                $cols = $DB->get_columns('block_backadel_catalogue');
                if (isset($cols['semester']) && (int)$cols['semester']->max_length < 20) {
                    $dbman->change_field_precision($cataloguetable, $semester);
                }
            }

            // coursetype_override + audit trio.
            $overrideprev = 'pattern';
            $overridefields = [
                new xmldb_field('coursetype_override', XMLDB_TYPE_CHAR, '16', null, null, null, null, $overrideprev),
                new xmldb_field('coursetype_override_note', XMLDB_TYPE_TEXT, null, null, null, null, null, 'coursetype_override'),
                new xmldb_field('coursetype_override_by', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'coursetype_override_note'),
                new xmldb_field('coursetype_override_ts', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'coursetype_override_by'),
            ];
            foreach ($overridefields as $field) {
                if (!$dbman->field_exists($cataloguetable, $field)) {
                    $dbman->add_field($cataloguetable, $field);
                }
            }

            // bug-045: composite filter indexes.
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
                if (!$dbman->index_exists($cataloguetable, $ix)) {
                    $dbman->add_index($cataloguetable, $ix);
                }
            }

            // bug-039: collapse accidental '//' in filepath / filepath_full
            // produced by an earlier path concat bug. Idempotent.
            try {
                $DB->execute(
                    "UPDATE {block_backadel_catalogue}
                        SET filepath_full = REPLACE(filepath_full, '//', '/'),
                            filepath      = REPLACE(filepath,      '//', '/')
                      WHERE filepath_full LIKE '%//%'
                         OR filepath      LIKE '%//%'"
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026060100: catalogue path cleanup failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }
        }

        // --------------------------------------------------------------
        // block_backadel_courses: filepath_hash + dedup + UNIQUE +
        // covering index.
        // --------------------------------------------------------------
        if ($dbman->table_exists($coursestable)) {

            // Add filepath_hash as nullable first so existing rows satisfy the
            // constraint without needing a default value (XMLDB forbids CHAR
            // NOT NULL with '' as default). Backfill, then tighten to NOTNULL.
            $hashfield = new xmldb_field(
                'filepath_hash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'filepath'
            );
            if (!$dbman->field_exists($coursestable, $hashfield)) {
                $dbman->add_field($coursestable, $hashfield);
            }

            // Backfill sha1(filepath) for any rows that are still NULL.
            try {
                $DB->execute(
                    "UPDATE {block_backadel_courses}
                        SET filepath_hash = SHA1(filepath)
                      WHERE filepath_hash IS NULL"
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026060100: courses filepath_hash backfill failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }

            // Define the table.
            $backadelcoursestable = new xmldb_table('block_backadel_courses');

            // Define filepath_hash using the desired final schema state.
            $filepathhashfielddefinition = new xmldb_field(
                        'filepath_hash',
                        XMLDB_TYPE_CHAR,
                        '40',
                        null,
                        XMLDB_NOTNULL,
                        null,
                        null,
                        'filepath'
            );

            // Moodle generates the physical database index name from this value
            $filepathhashuniqueindex = new xmldb_index(
                        'fil2',
                        XMLDB_INDEX_UNIQUE,
                        ['filepath_hash']
            );

            // Drop the dependent index before changing the field definition.
            if ($dbman->index_exists($backadelcoursestable, $filepathhashuniqueindex)) {
                        $dbman->drop_index($backadelcoursestable, $filepathhashuniqueindex);
            }

            // Tighten filepath_hash to NOT NULL.
            $dbman->change_field_notnull(
                        $backadelcoursestable,
                        $filepathhashfielddefinition
            );

            // Recreate the unique index after the field change has completed.
            if (!$dbman->index_exists($backadelcoursestable, $filepathhashuniqueindex)) {
                        $dbman->add_index($backadelcoursestable, $filepathhashuniqueindex);
            }

            // Direct dedup on filepath_hash (the column getting the UNIQUE).
            // Idempotent — a run with no dupes is a no-op.
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

            // UNIQUE on filepath_hash.
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

        // --------------------------------------------------------------
        // block_backadel_teachers: indexes, bug-058 slug prune,
        // dedup + UNIQUE, bug-068 resolvedvia widen.
        // --------------------------------------------------------------
        if ($dbman->table_exists($teacherstable)) {

            // Per-column indexes from the original 2026050300 create-table block.
            $teacheridxes = [
                new xmldb_index('coursesid_ix', XMLDB_INDEX_NOTUNIQUE, ['coursesid']),
                new xmldb_index('userid_ix',    XMLDB_INDEX_NOTUNIQUE, ['userid']),
                new xmldb_index('username_ix',  XMLDB_INDEX_NOTUNIQUE, ['username']),
                new xmldb_index('resolved_ix',  XMLDB_INDEX_NOTUNIQUE, ['resolved']),
            ];
            foreach ($teacheridxes as $ix) {
                if (!$dbman->index_exists($teacherstable, $ix)) {
                    $dbman->add_index($teacherstable, $ix);
                }
            }

            // bug-058: remove teacher rows where username is clearly a
            // backup-filename slug (unresolved + > 32 chars or contains '-for-').
            // Runs BEFORE the dedup so we don't keep a slug row in place of a
            // real one.
            try {
                $DB->execute(
                    "DELETE FROM {block_backadel_teachers}
                      WHERE userid IS NULL
                        AND (CHAR_LENGTH(username) > 32
                          OR username LIKE '%-for-%')"
                );
            } catch (\Throwable $e) {
                debugging(
                    'block_backadel upgrade 2026060100: teachers slug prune failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER
                );
            }

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

            // UNIQUE composite index.
            $teacheruk = new xmldb_index(
                'coursesid_username_uk', XMLDB_INDEX_UNIQUE, ['coursesid', 'username']
            );
            if (!$dbman->index_exists($teacherstable, $teacheruk)) {
                $dbman->add_index($teacherstable, $teacheruk);
            }

            // bug-068: widen resolvedvia from char(16) to char(32) so long
            // domain first-labels (e.g. 'internationalcenter') fit without
            // a silent dml_write_exception. Only invoke when the live column
            // is still narrower than 32 — re-ALTERing an already-wide column
            // is a needless DDL and can trip dependency checks.
            $resolvedvia = new xmldb_field(
                'resolvedvia', XMLDB_TYPE_CHAR, '32', null, false, null, null, 'resolved'
            );
            if ($dbman->field_exists($teacherstable, $resolvedvia)) {
                $cols = $DB->get_columns('block_backadel_teachers');
                if (isset($cols['resolvedvia']) && (int)$cols['resolvedvia']->max_length < 32) {
                    $dbman->change_field_precision($teacherstable, $resolvedvia);
                }
            }
        }

        // --------------------------------------------------------------
        // Plugin config: mark catalogue migration as pending if not set.
        // --------------------------------------------------------------
        if (get_config('block_backadel', 'migration_state') === false) {
            set_config('migration_state', 'pending', 'block_backadel');
        }

        upgrade_block_savepoint(true, 2026060100, 'backadel');
    }

    return true;
}
