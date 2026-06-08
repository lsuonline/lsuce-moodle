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
 * Service class for checksummer logic.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_checksummer;

defined('MOODLE_INTERNAL') || die();

/**
 * Checksummer service class.
 * Provides methods for generating directory checksum manifests and comparing them.
 */
class checksummer {

    /** @var callable|null Callback for progress output. */
    private $progresscallback = null;

    /**
     * Sets a callback function for progress updates.
     * The callback should accept a string message.
     *
     * @param callable $callback
     */
    public function set_progress_callback(callable $callback): void {
        $this->progresscallback = $callback;
    }

    /**
     * Outputs a progress message if a callback is set.
     *
     * @param string $message
     */
    private function output_progress(string $message): void {
        if ($this->progresscallback !== null) {
            call_user_func($this->progresscallback, $message);
        }
    }

    /**
     * Generates a manifest CSV for a directory by processing files and logging them to the database.
     *
     * Scans the directory, calculates checksums, logs to local_checksummer_data,
     * and then generates a CSV file from the sorted database records.
     *
     * @param string $directory Path to the directory to process.
     * @param string $filename Name of the output file (without the .csv extension).
     * @param bool $is_continuation True if continuing from a previous run, false for a fresh start.
     * @param int $timeout_seconds The maximum time (in seconds) the task should run before yielding.
     * @return array Status array with key 'status' as either 'timeout', 'complete', or 'error'.
     */
    public function generate_manifest(string $directory, string $filename, bool $is_continuation = false, int $timeout_seconds = 3600): array {
        global $DB;

        $starttime = time();

        if (!is_dir($directory)) {
            $this->output_progress("Directory not found: {$directory}");
            return ['status' => 'error'];
        }

        if (!$is_continuation) {
            // Truncate the table for a fresh start.
            $DB->delete_records('local_checksummer_data');
            $this->output_progress("Cleared old data from local_checksummer_data table.");

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $count = 0;
            $now = time();

            // First, populate the database with all files, leaving sha256 as null.
            $this->output_progress("Populating file list into the database...");
            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isFile()) {
                    continue;
                }

                // Set the time to when we look at this file.
                $filetime = time();

                // Get filename and path info.
                $name = $fileinfo->getFilename();
                $fullpath = $fileinfo->getPathname();

                $record = new \stdClass();
                $record->filename = $name;
                $record->path = $fullpath;

                // Get the real size.
                $record->size = $fileinfo->getSize();

                // Set this to null for not.
                $record->sha256 = null;

                // Look for an underscore followed by exactly 10 digits, followed by any file extensions.
                if (preg_match('/_(\d{10})(?:\.[^.]+)*$/', $name, $matches)) {
                    $record->filemtime = (int)$matches[1];
                } else {
                    $record->filemtime = $fileinfo->getMTime();
                }

                // Set the real scan time.
                $record->lastscanned = $filetime;

                $DB->insert_record('local_checksummer_data', $record);

                $count++;
                if (($count % 1000) === 0) {
                    $this->output_progress("Found {$count} files...");
                }
            }
            $this->output_progress("Finished file list population. Found {$count} files.");
        }

        // Now, hash the files that need hashing.
        $this->output_progress("Starting/continuing hashing process...");
        $rs = $DB->get_recordset_select('local_checksummer_data', 'sha256 IS NULL', null, 'id ASC');
        $hashed_count = 0;

        foreach ($rs as $record) {
            $hash = @hash_file('sha256', $record->path);
            
            if ($hash === false) {
                $this->output_progress("Failed to hash: {$record->path}");
                // To prevent infinite loops on unreadable files, we set a dummy/error hash or 'error'.
                $hash = 'error';
            }

            $updaterecord = new \stdClass();
            $updaterecord->id = $record->id;
            $updaterecord->sha256 = strtolower($hash);
            $DB->update_record('local_checksummer_data', $updaterecord);
            
            $hashed_count++;

            if (($hashed_count % 100) === 0) {
                $this->output_progress("Hashed {$hashed_count} files in this run...");
                // Check timeout
                if ((time() - $starttime) >= $timeout_seconds) {
                    $rs->close();
                    $this->output_progress("Time limit reached. Saving progress and scheduling continuation.");
                    return ['status' => 'timeout'];
                }
            }
        }
        $rs->close();
        $this->output_progress("All files hashed.");

        $count = $DB->count_records('local_checksummer_data');
        if ($count === 0) {
            $this->output_progress("No files found or directory is invalid.");
            return ['status' => 'error'];
        }

        // Now read from the DB sorted by filename to generate the CSV.
        $tempfile = make_request_directory() . '/' . $filename . '.csv';
        $out = fopen($tempfile, 'wb');

        if ($out === false) {
            $this->output_progress("Cannot write temporary manifest.");
            return ['status' => 'error'];
        }

        // Write the CSV header.
        fputcsv($out, [
            'filename',
            'size',
            'sha256'
        ]);

        // Get records ordered by filename. We use recordset to handle many records efficiently.
        $rs = $DB->get_recordset('local_checksummer_data', null, 'filename ASC');
        foreach ($rs as $record) {
            fputcsv($out, [
                $record->filename,
                $record->size,
                $record->sha256 === 'error' ? '' : $record->sha256
            ]);
        }
        $rs->close();

        fclose($out);

        // Save the generated file into the 'generated' file area within Moodle.
        $this->store_file_in_moodle($tempfile, $filename . '.csv', 'generated');

        $this->output_progress("Manifest generated successfully.");
        $this->output_progress("Total Files Processed: " . $count);

        return ['status' => 'complete'];
    }

    /**
     * Compares a directory against a source manifest CSV.
     *
     * This method builds an inventory of the target directory, compares it against an expected
     * state defined in a source manifest, and logs results directly to the local_checksummer_comp table.
     * A report is then generated detailing matches, mismatches, missing files, and extra files.
     *
     * @param string $directory Path to the directory to compare.
     * @param string $source_manifest_path File path to the uploaded source manifest CSV.
     * @param string $report_filename Name of the output report file (without the .csv extension).
     * @param bool $is_continuation True if continuing from a previous run, false for a fresh start.
     * @param int $timeout_seconds The maximum time (in seconds) the task should run before yielding.
     * @return array Status array with key 'status' as either 'timeout', 'complete', or 'error'.
     */
    public function compare_manifest(string $directory, string $source_manifest_path, string $report_filename, bool $is_continuation = false, int $timeout_seconds = 3600): array {
        global $DB;

        $starttime = time();

        // Ensure the provided manifest file exists and can be read.
        if (!is_readable($source_manifest_path)) {
            $this->output_progress("Cannot read source manifest: {$source_manifest_path}");
            return ['status' => 'error'];
        }

        if (!is_dir($directory)) {
            $this->output_progress("Directory not found: {$directory}");
            return ['status' => 'error'];
        }

        if (!$is_continuation) {
            // Truncate the table for a fresh start.
            $DB->delete_records('local_checksummer_comp');
            $this->output_progress("Cleared old data from local_checksummer_comp table.");

            $expected = [];
            $in = fopen($source_manifest_path, 'rb');

            // Skip the CSV header row.
            fgetcsv($in);

            // Parse the expected files from the source manifest.
            while (($row = fgetcsv($in)) !== false) {
                if (count($row) < 3) {
                    continue; // Skip invalid rows.
                }
                [$filename, $size, $sha256] = $row;
                $expected[$filename] = [
                    'size' => (int)$size,
                    'sha256' => strtolower($sha256),
                    'found' => false // Track if we found it in the directory
                ];
            }
            fclose($in);

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $now = time();
            $count = 0;

            $this->output_progress("Populating database with actual and expected files...");

            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isFile()) {
                    continue;
                }

                $name = $fileinfo->getFilename();
                $fullpath = $fileinfo->getPathname();
                $actual_size = $fileinfo->getSize();

                $record = new \stdClass();
                $record->filename = $name;
                $record->path = $fullpath;
                $record->actual_size = $actual_size;
                $record->actual_hash = null; // Will hash later
                $record->scandate = $now;

                if (isset($expected[$name])) {
                    $expected[$name]['found'] = true;
                    $record->expected_size = $expected[$name]['size'];
                    $record->expected_hash = $expected[$name]['sha256'];
                    // Temporarily set status to pending, we'll finalize during hashing.
                    $record->status = 'pending';
                } else {
                    $record->expected_size = null;
                    $record->expected_hash = null;
                    // For extra files, we still want to compute actual hash if needed, or we can just set status now.
                    // But to be consistent, we'll hash them too.
                    $record->status = 'pending_extra';
                }

                $DB->insert_record('local_checksummer_comp', $record);

                $count++;
                if (($count % 1000) === 0) {
                    $this->output_progress("Found {$count} files...");
                }
            }

            // Now find all expected files that were NOT found in the destination.
            $missing = 0;
            foreach ($expected as $name => $data) {
                if (!$data['found']) {
                    $record = new \stdClass();
                    $record->filename = $name;
                    $record->path = null;
                    $record->expected_size = $data['size'];
                    $record->actual_size = null;
                    $record->expected_hash = $data['sha256'];
                    $record->actual_hash = null;
                    $record->status = 'missing_on_destination';
                    $record->scandate = $now;

                    $DB->insert_record('local_checksummer_comp', $record);
                    $missing++;
                }
            }
            $this->output_progress("Finished populating database. Added {$count} actual files, {$missing} missing files.");
        }

        $this->output_progress("Starting/continuing hashing and comparison process...");

        // Find files that need hashing and comparison (those with actual_size IS NOT NULL but actual_hash IS NULL)
        $rs = $DB->get_recordset_select('local_checksummer_comp', 'actual_size IS NOT NULL AND actual_hash IS NULL', null, 'id ASC');
        $hashed_count = 0;

        foreach ($rs as $record) {
            $hash = @hash_file('sha256', $record->path);
            if ($hash === false) {
                $this->output_progress("Failed to hash: {$record->path}");
                $hash = 'error';
            }
            $actual_hash = strtolower($hash);

            $updaterecord = new \stdClass();
            $updaterecord->id = $record->id;
            $updaterecord->actual_hash = $actual_hash;

            // Determine final status
            if ($record->status === 'pending') {
                if ((int)$record->expected_size === (int)$record->actual_size && $record->expected_hash === $actual_hash) {
                    $updaterecord->status = 'matched';
                } else {
                    $updaterecord->status = 'mismatch';
                }
            } elseif ($record->status === 'pending_extra') {
                $updaterecord->status = 'extra_on_destination';
            }

            $DB->update_record('local_checksummer_comp', $updaterecord);
            $hashed_count++;

            if (($hashed_count % 100) === 0) {
                $this->output_progress("Processed {$hashed_count} files in this run...");
                // Check timeout
                if ((time() - $starttime) >= $timeout_seconds) {
                    $rs->close();
                    $this->output_progress("Time limit reached. Saving progress and scheduling continuation.");
                    return ['status' => 'timeout'];
                }
            }
        }
        $rs->close();
        $this->output_progress("All files hashed and compared.");

        // Generate the report CSV from the database.
        $tempfile = make_request_directory() . '/' . $report_filename . '.csv';
        $out = fopen($tempfile, 'wb');

        // Write the report header.
        fputcsv($out, [
            'filename',
            'status',
            'expected_size',
            'actual_size',
            'expected_sha256',
            'actual_sha256',
            'actual_path',
        ]);

        $matched = 0;
        $mismatched = 0;
        $missing = 0;
        $extra = 0;

        $rs = $DB->get_recordset('local_checksummer_comp', null, 'filename ASC');
        foreach ($rs as $record) {
            // Count statistics
            switch ($record->status) {
                case 'matched':
                    $matched++;
                    break;
                case 'mismatch':
                    $mismatched++;
                    break;
                case 'missing_on_destination':
                    $missing++;
                    break;
                case 'extra_on_destination':
                    $extra++;
                    break;
            }

            // Handle null values to match previous CSV output (empty string)
            $expected_size = $record->expected_size !== null ? $record->expected_size : '';
            $actual_size = $record->actual_size !== null ? $record->actual_size : '';
            $expected_hash = $record->expected_hash !== null ? $record->expected_hash : '';
            $actual_hash = $record->actual_hash !== null ? ($record->actual_hash === 'error' ? '' : $record->actual_hash) : '';
            $actual_path = $record->path !== null ? $record->path : '';

            // make the new compare csv match the old one.
            if ($record->status === 'matched') {
                continue;
            }

            fputcsv($out, [
                $record->filename,
                $record->status,
                $expected_size,
                $actual_size,
                $expected_hash,
                $actual_hash,
                $actual_path,
            ]);
        }
        $rs->close();

        fclose($out);

        // Save the comparison report into the Moodle File API.
        $this->store_file_in_moodle($tempfile, $report_filename . '.csv', 'generated');

        // Output summary statistics.
        $this->output_progress("Comparison complete.");
        $this->output_progress("Matched: {$matched}");
        $this->output_progress("Missing: {$missing}");
        $this->output_progress("Mismatched: {$mismatched}");
        $this->output_progress("Extra: {$extra}");

        return ['status' => 'complete'];
    }

    /**
     * Stores a file securely in the Moodle File API.
     *
     * Takes a physical file path and registers it in the standard Moodle file storage system
     * so it can be managed and served securely to authenticated administrators.
     *
     * @param string $filepath Path to the temporary physical file.
     * @param string $filename The name under which to store the file in Moodle.
     * @param string $filearea The destination file area (e.g., 'generated' or 'source').
     */
    private function store_file_in_moodle(string $filepath, string $filename, string $filearea): void {
        $fs = get_file_storage();
        $context = \context_system::instance();

        // Check if a file with the same name already exists in this area and delete it to overwrite safely.
        if ($existing = $fs->get_file($context->id, 'local_checksummer', $filearea, 0, '/', $filename)) {
            $existing->delete();
        }

        // Define the metadata for the file.
        $record = new \stdClass();
        $record->contextid = $context->id;
        $record->component = 'local_checksummer';
        $record->filearea = $filearea;
        $record->itemid = 0;
        $record->filepath = '/';
        $record->filename = $filename;

        // Move the physical file into the protected Moodle file system.
        $fs->create_file_from_pathname($record, $filepath);
    }
}
