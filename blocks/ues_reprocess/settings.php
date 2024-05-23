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
 * The main block file.
 *
 * @package    block_ues_reprocess
 * @copyright  Louisiana State University
 * @copyright  David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

// Create the settings block.
$settings = new admin_settingpage($section, get_string('settings', 'block_ues_reprocess'));


$semesters = 'Fall Semester
Winter Session
Spring Semester
Summer Session
All';


// Make sure only admins see this one.
if ($ADMIN->fulltree) {
    // --------------------------------
    // Dashboard Link.
    $settings->add(
        new admin_setting_heading(
            'ues_reprocess_link_back_title',
            get_string('ues_repall_link_back_title', 'block_ues_reprocess'),
            ''
        )
    );

    // --------------------------------
    // UES Reprocess Settings Title.
    $settings->add(
        new admin_setting_heading(
            'ues_reprocess_semester_title',
            get_string('ues_semester_title', 'block_ues_reprocess'),
            ''
        )
    );

    // --------------------------------
    // Semester Settings.
    $settings->add(
        new admin_setting_configtextarea(
            'ues_reprocess_semesters',
            get_string('ues_reprocess_semesters', 'block_ues_reprocess'),
            'List of Semesters',
            $semesters,
            PARAM_TEXT
        )
    );

    // --------------------------------
    // Scheduled Task Title.
    $settings->add(
        new admin_setting_heading(
            'ues_reprocess_task_title',
            get_string('ues_task_title', 'block_ues_reprocess'),
            ''
        )
    );

    // --------------------------------
    // Scheduled Task Settings.
    $settings->add(
        new admin_setting_configtext(
            'ues_reprocess_task_year',
            get_string('ues_task_year_title', 'block_ues_reprocess'),
            get_string('ues_task_year', 'block_ues_reprocess'),
            date("Y"),
            PARAM_TEXT
        )
    );

    // --------------------------------
    // Scheduled Task Settings.
    $settings->add(
        new admin_setting_configtext(
            'ues_reprocess_task_semester',
            get_string('ues_task_semester_title', 'block_ues_reprocess'),
            get_string('ues_task_semester', 'block_ues_reprocess'),
            '',
            PARAM_TEXT
        )
    );

    // --------------------------------
    // Scheduled Task Settings.
    $settings->add(
        new admin_setting_configtext(
            'ues_reprocess_task_department',
            get_string('ues_task_department_title', 'block_ues_reprocess'),
            get_string('ues_task_department', 'block_ues_reprocess'),
            '',
            PARAM_TEXT
        )
    );

    // --------------------------------
    // Test Settings.
    $settings->add(
        new admin_setting_heading(
            'ues_reprocess_testing',
            get_string('ues_reprocess_testing_title', 'block_ues_reprocess'),
            ''
        )
    );
    // --------------------------------
    // This is for testing purposes ONLY.
    $settings->add(
        new admin_setting_configcheckbox(
            'ues_reprocess_turn_on_testing',
            get_string('ues_testing_title', 'block_ues_reprocess'),
            get_string('ues_testing', 'block_ues_reprocess'),
            0
        )
    );
}
