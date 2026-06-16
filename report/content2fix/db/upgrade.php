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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for report_content2fix.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_report_content2fix_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025021906) {
        $table = new xmldb_table('report_content2fix');

        $htmlerrors = new xmldb_field('htmlerrors', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'summary');
        if (!$dbman->field_exists($table, $htmlerrors)) {
            $dbman->add_field($table, $htmlerrors);
        }

        $malformedhtml = new xmldb_field('malformedhtml', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'htmlerrors');
        if (!$dbman->field_exists($table, $malformedhtml)) {
            $dbman->add_field($table, $malformedhtml);
        }

        upgrade_plugin_savepoint(true, 2025021906, 'report', 'content2fix');
    }

    return true;
}
