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
 * Event observers for report_content2fix.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => \core\event\course_module_updated::class,
        'callback'  => '\report_content2fix\event_observer::course_module_updated',
        'internal'  => true,
    ],
    [
        'eventname' => \core\event\course_module_deleted::class,
        'callback'  => '\report_content2fix\event_observer::course_module_deleted',
        'internal'  => true,
    ],
    [
        'eventname' => \core\event\course_updated::class,
        'callback'  => '\report_content2fix\event_observer::course_updated',
        'internal'  => true,
    ],
    [
        'eventname' => \core\event\course_section_updated::class,
        'callback'  => '\report_content2fix\event_observer::course_section_updated',
        'internal'  => true,
    ],
];
