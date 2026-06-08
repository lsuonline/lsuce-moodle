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
 * Settings for local_checksummer.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (is_siteadmin()) {

    $settings = new admin_settingpage('local_checksummer', get_string('pluginname', 'local_checksummer'));
    $ADMIN->add('localplugins', $settings);

    // Directory to checksum.
    $settings->add(new admin_setting_configtext(
        'local_checksummer/directory',
        get_string('directory', 'local_checksummer'),
        get_string('directory_desc', 'local_checksummer'),
        '',
        PARAM_RAW
    ));

    // Timeout Limit.
    $settings->add(new admin_setting_configtext(
        'local_checksummer/timeout',
        get_string('timeout', 'local_checksummer'),
        get_string('timeout_desc', 'local_checksummer'),
        '60',
        PARAM_INT
    ));

    // Mode (Generate or Compare).
    $modes = [
        'generate' => get_string('mode_generate', 'local_checksummer'),
        'compare' => get_string('mode_compare', 'local_checksummer'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_checksummer/mode',
        get_string('mode', 'local_checksummer'),
        get_string('mode_desc', 'local_checksummer'),
        'generate',
        $modes
    ));

    // Output filename.
    $settings->add(new admin_setting_configtext(
        'local_checksummer/file_name',
        get_string('file_name_req', 'local_checksummer'),
        get_string('file_name_req_desc', 'local_checksummer'),
        'manifest',
        PARAM_FILE
    ));

    // For Compare Mode: Select Source Manifest.
// We dynamically populate the dropdown options based on the actual CSV files admins have uploaded to the source filearea.
    $fs = get_file_storage();
    $context = context_system::instance();

// Fetch all available source manifests from the database, ignoring directories (itemid=0 indicates standard filearea usage here).
    $sourcefiles = $fs->get_area_files($context->id, 'local_checksummer', 'source', 0, 'filename', false);
    $sourcemanifests = ['' => 'None'];

    foreach ($sourcefiles as $file) {
    // Populate the select options with the filenames.
        $sourcemanifests[$file->get_filename()] = $file->get_filename();
    }

    $settings->add(new admin_setting_configselect(
        'local_checksummer/source_manifest',
        get_string('source_manifest', 'local_checksummer'),
        get_string('source_manifest_desc', 'local_checksummer'),
        '',
        $sourcemanifests
    ));


// Include the custom admin settings block which renders the file management UI and task scheduling buttons.
    require_once($CFG->dirroot . '/local/checksummer/classes/admin_setting_actions.php');

    $settings->add(new \local_checksummer\admin_setting_actions(
        'local_checksummer/actions',
    get_string('actions', 'local_checksummer'),
        '',
        ''
    ));
}
