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
 * Web service function definitions for Backadel.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_backadel_get_year_backups' => [
        'classname' => 'block_backadel\\external\\get_year_backups',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Get catalogue backup entries for a given year',
        'type' => 'read',
        'capabilities' => 'block/backadel:viewresults',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'block_backadel_get_help_content' => [
        'classname' => 'block_backadel\\external\\get_help_content',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Fetch rendered help content for a given Backadel topic',
        'type' => 'read',
        'capabilities' => 'block/backadel:viewresults',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'block_backadel_get_migrate_status' => [
        'classname' => 'block_backadel\\external\\get_migrate_status',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Get status of the Backadel filesystem migration adhoc task',
        'type' => 'read',
        'capabilities' => 'block/backadel:managemigration',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'block_backadel_set_coursetype_override' => [
        'classname' => 'block_backadel\\external\\set_coursetype_override',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Manually override the course type for a catalogue row',
        'type' => 'write',
        'capabilities' => 'block/backadel:managebackups',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
