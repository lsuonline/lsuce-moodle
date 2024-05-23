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
 *
 * @package    block_ues_reprocess
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = [
    'ues_reprocess_get_courses' => [
        'classname' => 'ues_reprocess\external',
        'methodname' => 'get_courses',
        'classpath' => 'blocks/ues_reprocess/classes/external.php',
        'description' => 'Get a list of the courses.',
        'type' => 'read',
        'ajax' => true,
    ],
    // 'ues_reprocess_get_year' => [
    //     'classname' => 'ues_reprocess\external',
    //     'methodname' => 'get_year',
    //     'classpath' => 'blocks/ues_reprocess/classes/external.php',
    //     'description' => 'Update if year changes.',
    //     'type' => 'read',
    //     'ajax' => true,
    // ],
    // 'ues_reprocess_get_departments' => [
    //     'classname' => 'ues_reprocess\external',
    //     'methodname' => 'get_departments',
    //     'classpath' => 'blocks/ues_reprocess/classes/external.php',
    //     'description' => 'Get a list of the departments.',
    //     'type' => 'read',
    //     'ajax' => true,
    // ],
    // 'ues_reprocess_get_sections' => [
    //     'classname' => 'ues_reprocess\external',
    //     'methodname' => 'get_sections',
    //     'classpath' => 'blocks/ues_reprocess/classes/external.php',
    //     'description' => 'Get a list of the sections in a course.',
    //     'type' => 'read',
    //     'ajax' => true,
    // ],
];
