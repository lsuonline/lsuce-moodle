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
 * Adhoc task for local_checksummer.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_checksummer\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/checksummer/classes/checksummer.php');

/**
 * Adhoc task to run the checksummer logic.
 */
class run_checksummer_task extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_run_checksummer', 'local_checksummer');
    }

    /**
     * Run task.
     *
     * This method executes the adhoc task for either generating or comparing checksum manifests
     * depending on the current global configuration settings for the plugin.
     */
    public function execute() {
        global $CFG;
        
        mtrace('Starting checksummer task...');

        // Fetch configurations configured by the admin in settings.
        $directory = get_config('local_checksummer', 'directory');
        $mode = get_config('local_checksummer', 'mode');
        $filename = get_config('local_checksummer', 'file_name');

        // Check if the directory to be scanned is provided.
        if (empty($directory)) {
            mtrace('Error: Directory setting is empty.');
            return;
        }

        // Fallback to a default name if the file name configuration is somehow empty.
        if (empty($filename)) {
            $filename = 'manifest'; // Fallback
        }

        $service = new \local_checksummer\checksummer();
        
        // Output progress directly to mtrace so it's captured in the task logs.
        $service->set_progress_callback(function($message) {
            mtrace($message);
        });

        if ($mode === 'generate') {
            mtrace("Mode: Generate");
            // Call the service to perform generation based on settings.
            $service->generate_manifest($directory, $filename);
        } elseif ($mode === 'compare') {
            mtrace("Mode: Compare");
            $source_manifest_name = get_config('local_checksummer', 'source_manifest');
            
            // Validate that we actually have a source manifest selected for comparison.
            if (empty($source_manifest_name)) {
                mtrace("Error: Source manifest setting is empty. Please upload and select a source manifest in the settings.");
                return;
            }

            // Extract the source manifest to a temporary location to read it.
            $fs = get_file_storage();
            $context = \context_system::instance();
            $file = $fs->get_file($context->id, 'local_checksummer', 'source', 0, '/', $source_manifest_name);
            
            if (!$file) {
                mtrace("Error: Source manifest file not found in Moodle storage: {$source_manifest_name}");
                return;
            }

            // Temporarily copy the stored file so the CSV parsing logic can read it from the local filesystem.
            $tempdir = make_request_directory();
            $temp_source_path = $tempdir . '/' . $source_manifest_name;
            $file->copy_content_to($temp_source_path);

            // Call the service to perform comparison.
            $service->compare_manifest($directory, $temp_source_path, $filename);
        } else {
            mtrace("Error: Invalid mode selected.");
        }

        mtrace('Checksummer task completed.');
    }
}
