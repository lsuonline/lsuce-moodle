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
 * @package   local_cas_help_links
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_cas_help_links_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017020200) {
        $table = new xmldb_table('local_cas_help_links');
        
        // Define field "dept" to be added to local_cas_help_links
        $field = new xmldb_field('dept', XMLDB_TYPE_CHAR, '10');

        // Conditionally launch add field "dept"
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field "number" to be added to local_cas_help_links
        $field = new xmldb_field('number', XMLDB_TYPE_CHAR, '10');

        // Conditionally launch add field "number"
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2017020202) {
        $table = new xmldb_table('local_cas_help_links');

        // Increase the length of the "type" field to 11 so as to accommodate the new "coursematch" type
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '11', null, XMLDB_NOTNULL);

        $dbman->change_field_precision($table, $field);
    }

    if ($oldversion < 2017020701) {
        $table = new xmldb_table('local_cas_help_links_log');

        if ( ! $dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('link_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            $table->add_field('course_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
            $table->add_field('time_clicked', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); // @TODO - index here?
            
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2017021400) {
        $table = new xmldb_table('local_cas_help_links_log');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
