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
     * Builds an inventory of files in a directory.
     *
     * Iterates through the given directory and its subdirectories to gather file details,
     * including size, path, and a calculated SHA-256 hash.
     *
     * @param string $directory Absolute path to the directory to scan.
     * @return array Array of file details keyed by filename. Each entry contains 'size', 'sha256', and 'path'.
     */
    public function build_file_inventory(string $directory): array {
        // Ensure the provided path is a valid directory.
        if (!is_dir($directory)) {
            $this->output_progress("Directory not found: {$directory}");
            return [];
        }

        $files = [];

        // Set up the iterator to traverse all directories and files, skipping '.' and '..'.
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;

        // Process each item found by the iterator.
        foreach ($iterator as $fileinfo) {
            // We only care about actual files, skip directories or symlinks.
            if (!$fileinfo->isFile()) {
                continue;
            }

            $filename = $fileinfo->getFilename();
            $fullpath = $fileinfo->getPathname();

            // Calculate the SHA-256 checksum for the file. Use @ to suppress warnings if unreadable.
            $hash = @hash_file('sha256', $fullpath);

            if ($hash === false) {
                $this->output_progress("Failed to hash: {$fullpath}");
                continue;
            }

            // Store the file information.
            $files[$filename] = [
                'size' => $fileinfo->getSize(),
                'sha256' => strtolower($hash),
                'path' => $fullpath,
            ];

            $count++;

            // Periodically output progress to avoid timeouts or silent long-running processes.
            if (($count % 1000) === 0) {
                $this->output_progress("Processed {$count} files...");
            }
        }

        // Sort the resulting array alphabetically by filename for consistent output.
        ksort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return $files;
    }

    /**
     * Generates a manifest CSV for a directory.
     *
     * Scans the directory to build a file inventory and writes the results to a CSV file.
     * The generated file is then stored in Moodle's file storage.
     *
     * @param string $directory Path to the directory to process.
     * @param string $filename Name of the output file (without the .csv extension).
     * @return bool True if the manifest was successfully generated and stored, false otherwise.
     */
    public function generate_manifest(string $directory, string $filename): bool {
        // Retrieve the inventory of files.
        $localfiles = $this->build_file_inventory($directory);

        if (empty($localfiles)) {
            $this->output_progress("No files found or directory is invalid.");
            return false;
        }

        // Create a temporary file path to store the CSV before moving it to Moodle storage.
        $tempfile = make_request_directory() . '/' . $filename . '.csv';
        $out = fopen($tempfile, 'wb');

        if ($out === false) {
            $this->output_progress("Cannot write temporary manifest.");
            return false;
        }

        // Write the CSV header.
        fputcsv($out, [
            'filename',
            'size',
            'sha256'
        ]);

        // Write each file's details as a new row in the CSV.
        foreach ($localfiles as $name => $file) {
            fputcsv($out, [
                $name,
                $file['size'],
                $file['sha256']
            ]);
        }

        fclose($out);

        // Save the generated file into the 'generated' file area within Moodle.
        $this->store_file_in_moodle($tempfile, $filename . '.csv', 'generated');

        $this->output_progress("Manifest generated successfully.");
        $this->output_progress("Files: " . count($localfiles));

        return true;
    }

    /**
     * Compares a directory against a source manifest CSV.
     *
     * This method builds an inventory of the target directory and compares it against an expected
     * state defined in a source manifest. A report is generated detailing matches, mismatches,
     * missing files, and extra files found in the directory.
     *
     * @param string $directory Path to the directory to compare.
     * @param string $source_manifest_path File path to the uploaded source manifest CSV.
     * @param string $report_filename Name of the output report file (without the .csv extension).
     * @return bool True if the report was successfully generated, false otherwise.
     */
    public function compare_manifest(string $directory, string $source_manifest_path, string $report_filename): bool {
        // Ensure the provided manifest file exists and can be read.
        if (!is_readable($source_manifest_path)) {
            $this->output_progress("Cannot read source manifest: {$source_manifest_path}");
            return false;
        }

        // Build the actual inventory of files in the destination directory.
        $localfiles = $this->build_file_inventory($directory);

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
            ];
        }
        fclose($in);

        // Prepare the temporary file for the comparison report.
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

        // Iterate through expected files to find matches, mismatches, and missing files.
        foreach ($expected as $filename => $expectedfile) {
            // Check if expected file is entirely missing from the destination directory.
            if (!isset($localfiles[$filename])) {
                fputcsv($out, [
                    $filename,
                    'missing_on_destination',
                    $expectedfile['size'],
                    '',
                    $expectedfile['sha256'],
                    '',
                    '',
                ]);
                $missing++;
                continue;
            }

            $actual = $localfiles[$filename];

            // Verify both size and sha256 checksum perfectly match.
            if ($expectedfile['size'] === $actual['size'] && $expectedfile['sha256'] === $actual['sha256']) {
                $matched++;
                continue;
            }

            // Record a mismatch if either size or hash is different.
            fputcsv($out, [
                $filename,
                'mismatch',
                $expectedfile['size'],
                $actual['size'],
                $expectedfile['sha256'],
                $actual['sha256'],
                $actual['path'],
            ]);
            $mismatched++;
        }

        // Identify any files present in the destination that were not in the expected manifest.
        foreach ($localfiles as $filename => $actual) {
            if (!isset($expected[$filename])) {
                fputcsv($out, [
                    $filename,
                    'extra_on_destination',
                    '',
                    $actual['size'],
                    '',
                    $actual['sha256'],
                    $actual['path'],
                ]);
                $extra++;
            }
        }

        fclose($out);

        // Save the comparison report into the Moodle File API.
        $this->store_file_in_moodle($tempfile, $report_filename . '.csv', 'generated');

        // Output summary statistics.
        $this->output_progress("Comparison complete.");
        $this->output_progress("Matched: {$matched}");
        $this->output_progress("Missing: {$missing}");
        $this->output_progress("Mismatched: {$mismatched}");
        $this->output_progress("Extra: {$extra}");

        return true;
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
