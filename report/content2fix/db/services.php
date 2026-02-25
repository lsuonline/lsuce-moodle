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
// along with Moodle.  If not, see <http://moodle.org/licenses/>.

/**
 * Web service definitions for report_content2fix.
 *
 * @package    report_content2fix
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_content2fix_get_filter_values' => [
        'classname'    => 'report_content2fix\external',
        'methodname'   => 'get_filter_values',
        'classpath'    => '',
        'description'  => 'Get current user filter values for the content2fix report (for bulk Format with TinyMCE).',
        'type'         => 'read',
        'capabilities' => 'report/content2fix:fix',
        'ajax'         => true,
    ],
    'report_content2fix_get_filtered_entry_count' => [
        'classname'    => 'report_content2fix\external',
        'methodname'   => 'get_filtered_entry_count',
        'classpath'    => '',
        'description'  => 'Get the number of entries matching the given filter values (same payload as filter form).',
        'type'         => 'read',
        'capabilities' => 'report/content2fix:fix',
        'ajax'         => true,
    ],
    'report_content2fix_get_next_filtered_entry' => [
        'classname'    => 'report_content2fix\external',
        'methodname'   => 'get_next_filtered_entry',
        'classpath'    => '',
        'description'  => 'Get the next report_content2fix entry to process for bulk TinyMCE formatting.',
        'type'         => 'read',
        'capabilities' => 'report/content2fix:fix',
        'ajax'         => true,
    ],
    'report_content2fix_queue_format_all_task' => [
        'classname'    => 'report_content2fix\external',
        'methodname'   => 'queue_format_all_task',
        'classpath'    => '',
        'description'  => 'Queue ad-hoc task to format HTML in entries matching the given filters (same as confirmation modal).',
        'type'         => 'write',
        'capabilities' => 'report/content2fix:fix',
        'ajax'         => true,
    ],
];
