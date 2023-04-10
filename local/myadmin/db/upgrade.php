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
 * Local My Admin
 *
 * @package   local_myadmin
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Upgrade steps (such as database scheme changes and other things that must happen when the plugin is being upgraded) are defined here.
// The in-built XMLDB editor can be used to generate the code to change the database scheme.

defined('MOODLE_INTERNAL') || die();

function xmldb_local_myadmin_upgrade($oldversion){
    // Upgrade code goes here.
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2023040301) {

    // ======================================================
        // local_settings
        // ======================================================
        if (!$dbman->table_exists('local_myadmin_settings')) {

            $mya_settings = new xmldb_table('local_myadmin_settings');
            
            // $local_id = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $mya_settings->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            // $mya_settings->add_field($local_id);

            // if (! $dbman->field_exists('local_myadmin_settings', 't_name')) {
            // $local_mya_t_name = new xmldb_field('t_name', XMLDB_TYPE_CHAR, null, null, null, null, '');
            // $mya_settings->add_field($local_mya_t_name);
            $mya_settings->add_field('t_name', XMLDB_TYPE_CHAR, null, null, null, null, '');
            // }
            // if (! $dbman->field_exists('local_myadmin_settings', 't_value')) {
            // $local_mya_t_value = new xmldb_field('t_value', XMLDB_TYPE_CHAR, null, null, null, null, '');
            $mya_settings->add_field('t_value', XMLDB_TYPE_CHAR, null, null, null, null, '');
            // $mya_settings->add_field($local_mya_t_value);
            // }
            // 
            $mya_settings->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($mya_settings);
        }


    }

    return true;
}