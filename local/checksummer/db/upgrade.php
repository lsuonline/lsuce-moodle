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
 * Upgrade script for local_checksummer.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_checksummer upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_checksummer_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060201) {

        // Define table local_checksummer_data to be created.
        $table = new xmldb_table('local_checksummer_data');

        // Adding fields to table local_checksummer_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('path', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('size', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sha256', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filemtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastscanned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_checksummer_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_checksummer_data.
        $table->add_index('filename', XMLDB_INDEX_NOTUNIQUE, ['filename']);

        // Conditionally launch create table for local_checksummer_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_checksummer_comp to be created.
        $table = new xmldb_table('local_checksummer_comp');

        // Adding fields to table local_checksummer_comp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('path', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('expected_size', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('actual_size', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('expected_hash', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('actual_hash', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scandate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_checksummer_comp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_checksummer_comp.
        $table->add_index('filename', XMLDB_INDEX_NOTUNIQUE, ['filename']);

        // Conditionally launch create table for local_checksummer_comp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Checksummer savepoint reached.
        upgrade_plugin_savepoint(true, 2026060201, 'local', 'checksummer');
    }

    if ($oldversion < 2026060202) {

        // Define field sha256 to be changed in local_checksummer_data.
        $table = new xmldb_table('local_checksummer_data');
        $field = new xmldb_field('sha256', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'size');

        // Launch change of nullability for field sha256.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        // Checksummer savepoint reached.
        upgrade_plugin_savepoint(true, 2026060202, 'local', 'checksummer');

    }

    return true;
}
