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

        $customdata = (array) $this->get_custom_data();
        $is_continuation = !empty($customdata['is_continuation']);

        // Fetch configurations configured by the admin in settings or use saved continuation custom data.
        if ($is_continuation) {
            $directory = $customdata['directory'] ?? get_config('local_checksummer', 'directory');
            $mode = $customdata['mode'] ?? get_config('local_checksummer', 'mode');
            $filename = $customdata['file_name'] ?? get_config('local_checksummer', 'file_name');
            $source_manifest_name = $customdata['source_manifest'] ?? get_config('local_checksummer', 'source_manifest');
        } else {
            $directory = get_config('local_checksummer', 'directory');
            $mode = get_config('local_checksummer', 'mode');
            $filename = get_config('local_checksummer', 'file_name');
            $source_manifest_name = get_config('local_checksummer', 'source_manifest');
        }

        $timeout_minutes = get_config('local_checksummer', 'timeout');
        
        if (empty($timeout_minutes) || !is_numeric($timeout_minutes)) {
            $timeout_minutes = 60;
        }
        $timeout_seconds = (int)$timeout_minutes * 60;

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

        $result = ['status' => 'error'];

        if ($mode === 'generate') {
            mtrace("Mode: Generate" . ($is_continuation ? " (Continuation)" : " (New Start)"));
            // Call the service to perform generation based on settings.
            $result = $service->generate_manifest($directory, $filename, $is_continuation, $timeout_seconds);
        } elseif ($mode === 'compare') {
            mtrace("Mode: Compare" . ($is_continuation ? " (Continuation)" : " (New Start)"));
            
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
            $result = $service->compare_manifest($directory, $temp_source_path, $filename, $is_continuation, $timeout_seconds);
        } else {
            mtrace("Error: Invalid mode selected.");
            return;
        }

        if ($result['status'] === 'timeout') {
            mtrace("Task timed out after {$timeout_minutes} minutes. Scheduling a continuation task...");
            $task = new \local_checksummer\task\run_checksummer_task();
            $task->set_custom_data([
                'is_continuation' => true,
                'directory' => $directory,
                'mode' => $mode,
                'file_name' => $filename,
                'source_manifest' => $source_manifest_name ?? null
            ]);
            \core\task\manager::queue_adhoc_task($task);
        } elseif ($result['status'] === 'complete') {
            mtrace('Checksummer task completed successfully.');
        } else {
            mtrace('Checksummer task finished with an error.');
        }
    }
}
