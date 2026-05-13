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

    return true;
}
