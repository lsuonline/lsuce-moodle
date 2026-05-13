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
 * CLI: bulk seed {@see block_backadel_catalogue} from plain-text FTP filename lists.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/blocks/backadel/lib.php');

use block_backadel\local\filename_parser;

/** Catalogue source for rows created from FTP listing imports. */
const BLOCK_BACKADEL_FTP_IMPORT_SOURCE = 'legacy_moodleus';

/**
 * Collect all `--file=` paths (comma-separated values and repeated flags).
 *
 * @return string[]
 */
function block_backadel_cli_ftp_collect_file_args(): array {
    $paths = [];
    foreach ($_SERVER['argv'] ?? [] as $arg) {
        if (!str_starts_with($arg, '--file=')) {
            continue;
        }
        $value = substr($arg, strlen('--file='));
        foreach (explode(',', $value) as $p) {
            $p = trim($p);
            if ($p !== '') {
                $paths[] = $p;
            }
        }
    }
    return $paths;
}

list($options, $unrecognized) = cli_get_params([
    'dryrun' => false,
    'batch' => '500',
    'help' => false,
], [
    'h' => 'help',
]);

foreach ($unrecognized as $raw) {
    if (!str_starts_with($raw, '--file=')) {
        cli_error('Unrecognized option: ' . $raw);
    }
}

if ($options['help']) {
    $help = <<<EOF
Bulk import FTP filename lists into block_backadel_catalogue.

Each input file must be plain text: one filename per line. Empty lines and
lines beginning with # are ignored. Only the basename of each path is parsed.

Options:
--file=<path>     Path to a file list (repeat and/or comma-separate paths)
--dryrun          Parse and summarize by pattern; do not write to the database
--batch=<n>       Rows per DB transaction when not in dry-run mode (default: 500)
-h, --help        Show this help

Example:
  php blocks/backadel/cli/import_ftp_catalogue.php --file=/tmp/ftp-a.txt,/tmp/ftp-b.txt

EOF;
    echo $help;
    exit(0);
}

$filepaths = block_backadel_cli_ftp_collect_file_args();
if ($filepaths === []) {
    cli_error('At least one --file=<path> argument is required.');
}

$batchsize = max(1, (int) $options['batch']);
$dryrun = !empty($options['dryrun']);

foreach ($filepaths as $path) {
    if (!is_readable($path) || !is_file($path)) {
        cli_error('File not readable: ' . $path);
    }
}

$patterncounts = [];
$linesread = 0;
$inserted = 0;
$skipped = 0;
$parseerrors = 0;

/** @var \moodle_transaction|null $currenttxn */
$currenttxn = null;
$rowsinbatch = 0;
$progresshandled = 0;

global $DB;

try {
    foreach ($filepaths as $listpath) {
        $fh = fopen($listpath, 'rb');
        if ($fh === false) {
            cli_error('Could not open file: ' . $listpath);
        }
        try {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $linesread++;
                $progresshandled++;
                if ($progresshandled % 1000 === 0) {
                    cli_writeln('Progress: processed ' . $progresshandled . ' data lines.');
                }
                $basename = basename($line);
                $parsed = filename_parser::parse($basename);
                if ($parsed === null) {
                    $parseerrors++;
                    continue;
                }

                if ($dryrun) {
                    $pattern = (string) ($parsed['pattern'] ?? 'unknown');
                    $patterncounts[$pattern] = ($patterncounts[$pattern] ?? 0) + 1;
                } else {
                    if ($currenttxn === null) {
                        $currenttxn = $DB->start_delegated_transaction();
                    }

                    $hash = sha1($basename);
                    $existing = $DB->get_record('block_backadel_catalogue', ['filepath_hash' => $hash]);
                    if ($existing !== false) {
                        $skipped++;
                    } else {
                        $now = time();
                        $instructors = $parsed['instructors'] ?? [];
                        if (!is_array($instructors)) {
                            $instructors = [];
                        }
                        $row = new stdClass();
                        $row->filename = core_text::substr($basename, 0, 255);
                        $row->filepath = '';
                        $row->filepath_full = '';
                        $row->filepath_hash = $hash;
                        $row->source = BLOCK_BACKADEL_FTP_IMPORT_SOURCE;
                        $row->year = isset($parsed['year']) && $parsed['year'] !== null ? (int) $parsed['year'] : null;
                        $row->semester = isset($parsed['semester']) && $parsed['semester'] !== null
                            ? (string) $parsed['semester']
                            : null;
                        $row->dept = isset($parsed['dept']) && $parsed['dept'] !== null
                            ? (string) $parsed['dept']
                            : null;
                        $row->course_num = isset($parsed['course_num']) && $parsed['course_num'] !== null
                            ? (string) $parsed['course_num']
                            : null;
                        $row->course_idnumber = null;
                        $row->shortname = null;
                        $row->instructors = json_encode(array_values($instructors));
                        $row->pattern = (string) ($parsed['pattern'] ?? 'unknown');
                        $row->backup_ts = isset($parsed['backup_ts']) ? (int) $parsed['backup_ts'] : 0;
                        $row->file_size = null;
                        $row->status = 'available';
                        $row->timecreated = $now;
                        $row->timemodified = $now;

                        $DB->insert_record('block_backadel_catalogue', $row);
                        $inserted++;
                    }

                    $rowsinbatch++;
                    if ($rowsinbatch >= $batchsize) {
                        $currenttxn->allow_commit();
                        $currenttxn = null;
                        $rowsinbatch = 0;
                    }
                }
            }
        } finally {
            fclose($fh);
        }
    }

    if (!$dryrun && $currenttxn !== null) {
        $currenttxn->allow_commit();
        $currenttxn = null;
        $rowsinbatch = 0;
    }
} catch (\Throwable $e) {
    if ($currenttxn !== null && !$currenttxn->is_disposed()) {
        $currenttxn->rollback($e);
    }
    cli_writeln(get_class($e) . ': ' . $e->getMessage());
    cli_writeln($e->getTraceAsString());
    cli_error('Import aborted.');
}

cli_writeln('Total lines read: ' . $linesread);
if ($dryrun) {
    cli_writeln('Dry run pattern summary:');
    ksort($patterncounts, SORT_STRING);
    foreach ($patterncounts as $pattern => $count) {
        cli_writeln('  ' . $pattern . ': ' . $count);
    }
    cli_writeln('Rows inserted: 0 (dry run)');
    cli_writeln('Rows skipped: 0 (dry run)');
} else {
    cli_writeln('Rows inserted: ' . $inserted);
    cli_writeln('Rows skipped (already existed): ' . $skipped);
}
cli_writeln('Rows with parse errors (skipped): ' . $parseerrors);

exit(0);
