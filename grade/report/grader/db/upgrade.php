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
 * Upgrade code for gradebook grader report.
 *
 * @package   gradereport_grader
 * @copyright 2013 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_gradereport_grader_upgrade($oldversion) {
    global $CFG, $DB;
    // Set rawgrade on manual grade items so that multiplicator and offset
    // works on them like any other grade item.
    $sql = "UPDATE  {grade_grades} gr, {grade_items} gi
            SET     gr.rawgrade = gr.finalgrade
            WHERE   gi.id = gr.itemid AND gi.itemtype = 'manual' AND gr.rawgrade IS NULL";
    $DB->execute($sql);
    upgrade_plugin_savepoint(true, 2016052301, 'gradereport', 'grader');
    return true;
}
