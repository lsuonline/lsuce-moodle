#!/usr/bin/env php
<?php
/**
 * File checksum utility (CLI).
 *
 * @package    local_checksummer
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

// Adjust the path to config.php according to your moodle installation.
// Assuming this is in moodle/local/checksummer/cli/
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/checksummer/classes/checksummer.php');

$usage =
"File checksum utility.

Modes:

Generate:
php checksummer.php <directory> <manifest.csv>

Compare:
php checksummer.php <directory> <source_manifest.csv> <report.csv>

CSV format:
filename,size,sha256
";

if ($argc < 3) {
    cli_error($usage);
}

$directory = rtrim($argv[1], DIRECTORY_SEPARATOR);
$manifest = $argv[2];
$report = $argv[3] ?? null;

$service = new \local_checksummer\checksummer();

// Register a callback to output progress messages directly to standard output.
$service->set_progress_callback(function($message) {
    echo $message . "\n";
});

// GENERATE MODE
// If the third argument (report) is not provided, we default to generating a new manifest.
if ($report === null) {
    // Determine the base filename without extension to pass to the core service.
    $filename = pathinfo($manifest, PATHINFO_FILENAME);
    
    $success = $service->generate_manifest($directory, $filename);
    
    if ($success) {
        // The service logic inherently stores the generated file in Moodle's internal file storage system.
        // However, for this CLI utility, we must also write the file to the physical path requested by the user
        // to preserve backward compatibility and allow standard CLI piping or file inspection.
        $context = \context_system::instance();
        $fs = get_file_storage();
        if ($file = $fs->get_file($context->id, 'local_checksummer', 'generated', 0, '/', $filename . '.csv')) {
            // Extract from Moodle's protected storage back to the file system at the requested location.
            $file->copy_content_to($manifest);
            echo "Manifest written to {$manifest}\n";
        }
        exit(0);
    } else {
        exit(1);
    }
}

// COMPARE MODE
// If a third argument is present, we are comparing a directory against an existing source manifest.
$report_filename = pathinfo($report, PATHINFO_FILENAME);
$success = $service->compare_manifest($directory, $manifest, $report_filename);

if ($success) {
    // Similar to generate mode, retrieve the newly created report from Moodle's internal file storage.
    // We then write it out to the physical file system path requested by the CLI user.
    $context = \context_system::instance();
    $fs = get_file_storage();
    if ($file = $fs->get_file($context->id, 'local_checksummer', 'generated', 0, '/', $report_filename . '.csv')) {
        $file->copy_content_to($report);
        echo "Report written to {$report}\n";
    }
    exit(0);
} else {
    exit(1);
}
