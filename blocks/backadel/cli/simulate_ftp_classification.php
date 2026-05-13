#!/usr/bin/env php
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
 * CLI: Simulate FTP file classification without bootstrapping Moodle.
 *
 * Reads one or more plain-text FTP filename lists, classifies each unique
 * basename using filename_pattern_library, and emits a CSV tally broken down
 * by (pattern, coursetype). No database connection required.
 *
 * Usage:
 *   php blocks/backadel/cli/simulate_ftp_classification.php \
 *       --list=ftp_file_list.moodleus.txt \
 *       --list=ftp_file_list.openlms.txt \
 *       --format=table \
 *       --verbose
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

// The only require: the Moodle-independent pattern library.
require_once __DIR__ . '/../classes/local/filename_pattern_library.php';

use block_backadel\local\filename_pattern_library;

// =============================================================================
// Inlined blueprint keyword list.
// KEEP IN SYNC with course_type_resolver::get_default_blueprint_keywords().
// Both lists must be identical; if you update one, update the other.
// =============================================================================
const BACKADEL_SIM_BLUEPRINT_KEYWORDS = [
    'blueprint',
    'template',
    'master',
    'master-course',
    'master course',
    'materials course',
    'storage course',
    'materials',
    'blank course',
    'blank',
    'flagship',
];

// =============================================================================
// Argument parsing
// =============================================================================

/**
 * Parse $argv into a structured options array.
 *
 * @param array<int, string> $argv Raw $argv from PHP.
 * @return array{lists: string[], output: string, format: string,
 *               year_output: string|null, unknown_output: string|null,
 *               verbose: bool}
 */
function block_backadel_sim_parse_args(array $argv): array {
    $opts = [
        'lists'          => [],
        'output'         => '-',
        'format'         => 'csv',
        'year_output'    => null,
        'unknown_output' => null,
        'verbose'        => false,
    ];

    // Skip $argv[0] (script name).
    for ($i = 1, $n = count($argv); $i < $n; $i++) {
        $arg = $argv[$i];

        if ($arg === '--help' || $arg === '-h') {
            block_backadel_sim_print_help();
            exit(0);
        }

        if ($arg === '--verbose' || $arg === '-v') {
            $opts['verbose'] = true;
            continue;
        }

        if (str_starts_with($arg, '--list=')) {
            $value = substr($arg, strlen('--list='));
            foreach (explode(',', $value) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $opts['lists'][] = $p;
                }
            }
            continue;
        }

        if (str_starts_with($arg, '--output=')) {
            $opts['output'] = substr($arg, strlen('--output='));
            continue;
        }

        if (str_starts_with($arg, '--format=')) {
            $fmt = substr($arg, strlen('--format='));
            if (!in_array($fmt, ['csv', 'table'], true)) {
                fwrite(STDERR, "Invalid --format value: $fmt (must be csv or table)\n");
                exit(2);
            }
            $opts['format'] = $fmt;
            continue;
        }

        if (str_starts_with($arg, '--year-output=')) {
            $opts['year_output'] = substr($arg, strlen('--year-output='));
            continue;
        }

        if (str_starts_with($arg, '--unknown-output=')) {
            $opts['unknown_output'] = substr($arg, strlen('--unknown-output='));
            continue;
        }

        fwrite(STDERR, "Unknown option: $arg\n");
        exit(2);
    }

    return $opts;
}

function block_backadel_sim_print_help(): void {
    $script = basename($_SERVER['argv'][0] ?? __FILE__);
    fwrite(STDOUT, <<<HELP
Simulate FTP file classification without Moodle bootstrap.

Usage:
  php $script [options]

Options:
  --list=FILE           Path to plain-text FTP file list (repeat ≥1 times or comma-separate).
                        One filepath per line; blank lines and lines starting with '#' are skipped.
  --output=FILE         Primary CSV destination. Use '-' for stdout (default: stdout).
  --format=csv|table    Output format: csv (default) or fixed-width table.
  --year-output=FILE    Write year tally CSV (year,count) to FILE. Use '-' for stdout.
  --unknown-output=FILE Write one basename per line to FILE for every 'unknown' pattern.
  --verbose, -v         Print progress to stderr every 10,000 filenames.
  --help, -h            Show this help and exit.

Example:
  php $script \\
      --list=tickets/MD-2189/file-lists/ftp_file_list.moodleus.txt \\
      --list=tickets/MD-2189/file-lists/ftp_file_list.openlms.txt \\
      --format=table --verbose

HELP
    );
}

// =============================================================================
// Classification helper (mirrors course_type_resolver::resolve())
// =============================================================================

/**
 * Classify a parsed filename array into teaching / blueprint / other.
 *
 * This is a verbatim port of course_type_resolver::resolve(), with the
 * Moodle-dependent get_blueprint_keywords() call replaced by the hard-coded
 * BACKADEL_SIM_BLUEPRINT_KEYWORDS constant defined above.
 *
 * @param array<string, mixed> $parsed Output of filename_pattern_library::parse().
 * @return string One of: teaching, blueprint, other.
 */
function block_backadel_sim_resolve(array $parsed): string {
    $pattern  = (string) ($parsed['pattern'] ?? '');
    $year     = isset($parsed['year']) ? (int) $parsed['year'] : null;
    $semester = isset($parsed['semester']) && $parsed['semester'] !== null
        ? (string) $parsed['semester']
        : '';
    $dept = isset($parsed['dept']) && $parsed['dept'] !== null
        ? (string) $parsed['dept']
        : '';

    // Teaching (highest precedence).
    if (
        in_array($pattern, ['semester_legacy', 'semester_legacy_lc'], true)
        && $year !== null
        && $year > 0
        && $semester !== ''
        && $dept !== ''
    ) {
        return 'teaching';
    }

    if ($pattern === 'semester_legacy_clone' && $year !== null && $year > 0 && $semester !== '') {
        return 'teaching';
    }

    if (($pattern === 'storagecourse_dept' || $pattern === 'storage_course') && $dept !== '') {
        return 'teaching';
    }

    if ($pattern === 'backadel_modern') {
        $instructors = $parsed['instructors'] ?? [];
        if (is_array($instructors) && count($instructors) > 0) {
            return 'teaching';
        }
    }

    // Blueprint keyword detection.
    $hint    = strtolower((string) ($parsed['shortname_hint'] ?? ''));
    $rawname = strtolower(basename((string) ($parsed['raw_filename'] ?? '')));

    foreach (BACKADEL_SIM_BLUEPRINT_KEYWORDS as $keyword) {
        $kw = strtolower($keyword);
        if (
            ($hint !== '' && strpos($hint, $kw) !== false)
            || ($rawname !== '' && strpos($rawname, $kw) !== false)
            || block_backadel_sim_slug_match($hint, $kw)
            || block_backadel_sim_slug_match($rawname, $kw)
        ) {
            return 'blueprint';
        }
    }

    return 'other';
}

function block_backadel_sim_slugify(string $s): string {
    return str_replace([' ', '_', '-'], '', strtolower($s));
}

function block_backadel_sim_slug_match(string $haystack, string $needle): bool {
    if ($haystack === '' || $needle === '') {
        return false;
    }
    return strpos(block_backadel_sim_slugify($haystack), block_backadel_sim_slugify($needle)) !== false;
}

// =============================================================================
// Output helpers
// =============================================================================

/**
 * Open a file handle for writing. '-' means stdout.
 *
 * @return resource
 */
function block_backadel_sim_open_write(string $path) {
    if ($path === '-') {
        return STDOUT;
    }
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        fwrite(STDERR, "Cannot open for writing: $path\n");
        exit(1);
    }
    return $fh;
}

/**
 * Close a handle unless it is STDOUT.
 *
 * @param resource $fh
 */
function block_backadel_sim_close($fh): void {
    if ($fh !== STDOUT && $fh !== STDERR) {
        fclose($fh);
    }
}

/**
 * Emit primary tally in CSV format.
 *
 * @param array<string, array<string, int>> $tally   $tally[$pattern][$coursetype] = count
 * @param int                               $total   Total unique basenames processed.
 * @param resource                          $fh
 */
function block_backadel_sim_emit_csv(array $tally, int $total, $fh): void {
    fwrite($fh, "pattern,coursetype,count,pct\n");

    $rows = [];
    foreach ($tally as $pattern => $coursetypes) {
        foreach ($coursetypes as $coursetype => $count) {
            $pct = $total > 0 ? ($count / $total) * 100.0 : 0.0;
            $rows[] = [$pattern, $coursetype, $count, $pct];
        }
    }

    // Sort: count DESC, pattern ASC, coursetype ASC.
    usort($rows, static function (array $a, array $b): int {
        if ($b[2] !== $a[2]) {
            return $b[2] <=> $a[2];
        }
        if ($a[0] !== $b[0]) {
            return strcmp($a[0], $b[0]);
        }
        return strcmp($a[1], $b[1]);
    });

    foreach ($rows as [$pattern, $coursetype, $count, $pct]) {
        $pctfmt = number_format($pct, 2, '.', '');
        fwrite($fh, "$pattern,$coursetype,$count,$pctfmt\n");
    }
}

/**
 * Emit primary tally in fixed-width table format.
 *
 * @param array<string, array<string, int>> $tally
 * @param int                               $total
 * @param resource                          $fh
 */
function block_backadel_sim_emit_table(array $tally, int $total, $fh): void {
    $rows = [];
    foreach ($tally as $pattern => $coursetypes) {
        foreach ($coursetypes as $coursetype => $count) {
            $pct = $total > 0 ? ($count / $total) * 100.0 : 0.0;
            $rows[] = [$pattern, $coursetype, $count, $pct];
        }
    }

    usort($rows, static function (array $a, array $b): int {
        if ($b[2] !== $a[2]) {
            return $b[2] <=> $a[2];
        }
        if ($a[0] !== $b[0]) {
            return strcmp($a[0], $b[0]);
        }
        return strcmp($a[1], $b[1]);
    });

    // Compute column widths.
    $w = [strlen('pattern'), strlen('coursetype'), strlen('count'), strlen('pct')];
    foreach ($rows as [$pat, $ct, $cnt, $pct]) {
        $w[0] = max($w[0], strlen($pat));
        $w[1] = max($w[1], strlen($ct));
        $w[2] = max($w[2], strlen(number_format($cnt, 0, '.', ',')));
        $w[3] = max($w[3], strlen(number_format($pct, 2, '.', '')));
    }

    $fmt = "%-{$w[0]}s  %-{$w[1]}s  %{$w[2]}s  %{$w[3]}s\n";
    $sep = [str_repeat('-', $w[0]), str_repeat('-', $w[1]), str_repeat('-', $w[2]), str_repeat('-', $w[3])];

    fwrite($fh, sprintf($fmt, 'pattern', 'coursetype', 'count', 'pct'));
    fwrite($fh, sprintf($fmt, ...$sep));

    foreach ($rows as [$pat, $ct, $cnt, $pct]) {
        fwrite($fh, sprintf($fmt,
            $pat,
            $ct,
            number_format($cnt, 0, '.', ','),
            number_format($pct, 2, '.', '')
        ));
    }
}

/**
 * Emit year tally CSV.
 *
 * @param array<string, int> $yeartally $yeartally[$year|'(empty)'] = count
 * @param resource           $fh
 */
function block_backadel_sim_emit_year_csv(array $yeartally, $fh): void {
    fwrite($fh, "year,count\n");

    ksort($yeartally, SORT_NATURAL);

    foreach ($yeartally as $year => $count) {
        fwrite($fh, "$year,$count\n");
    }
}

// =============================================================================
// Main
// =============================================================================

$opts = block_backadel_sim_parse_args($argv);

if (empty($opts['lists'])) {
    fwrite(STDERR, "Error: At least one --list=FILE argument is required.\n");
    fwrite(STDERR, "Run with --help for usage.\n");
    exit(1);
}

// Validate list files up front.
foreach ($opts['lists'] as $listpath) {
    if (!is_file($listpath) || !is_readable($listpath)) {
        fwrite(STDERR, "File not readable: $listpath\n");
        exit(1);
    }
}

$verbose       = $opts['verbose'];
$starttime     = microtime(true);

/** @var array<string, array<string, int>> $tally $tally[$pattern][$coursetype] = count */
$tally = [];

/** @var array<string, int> $yeartally */
$yeartally = [];

/** @var array<string, true> $seen Dedup hash: basename → true */
$seen = [];

$totalread    = 0;
$totalunique  = 0;
$totalmatched = 0;  // patterns that are not 'unknown'
$totalunknown = 0;

// Open unknown-output handle early (to write incrementally).
$unknownfh = null;
if ($opts['unknown_output'] !== null) {
    $unknownfh = block_backadel_sim_open_write($opts['unknown_output']);
}

foreach ($opts['lists'] as $listpath) {
    $fh = fopen($listpath, 'rb');
    if ($fh === false) {
        fwrite(STDERR, "Cannot open: $listpath\n");
        exit(1);
    }

    while (($line = fgets($fh)) !== false) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $totalread++;

        $basename = basename($line);

        // Dedup.
        if (isset($seen[$basename])) {
            continue;
        }
        $seen[$basename] = true;
        $totalunique++;

        // Parse.
        $parsed = filename_pattern_library::parse($basename);
        if ($parsed === null) {
            continue;
        }

        $pattern    = (string) ($parsed['pattern'] ?? 'unknown');
        $coursetype = block_backadel_sim_resolve($parsed);

        // Tally.
        if (!isset($tally[$pattern])) {
            $tally[$pattern] = [];
        }
        $tally[$pattern][$coursetype] = ($tally[$pattern][$coursetype] ?? 0) + 1;

        // Year tally.
        $year = $parsed['year'] ?? null;
        $yearkey = $year !== null ? (string) $year : '(empty)';
        $yeartally[$yearkey] = ($yeartally[$yearkey] ?? 0) + 1;

        if ($pattern === 'unknown') {
            $totalunknown++;
            if ($unknownfh !== null) {
                fwrite($unknownfh, $basename . "\n");
            }
        } else {
            $totalmatched++;
        }

        // Progress.
        if ($verbose && ($totalread % 10000) === 0) {
            fwrite(STDERR, sprintf(
                "[sim] read %-8d unique %-8d patterns matched %-8d unknown %d\n",
                $totalread,
                $totalunique,
                $totalmatched,
                $totalunknown
            ));
        }
    }
    fclose($fh);
}

if ($unknownfh !== null) {
    block_backadel_sim_close($unknownfh);
}

// Verbose summary.
if ($verbose) {
    $elapsed = microtime(true) - $starttime;
    $mem = memory_get_peak_usage(true);
    fwrite(STDERR, sprintf(
        "[sim] done   total %-8d unique %-8d patterns matched %-8d unknown %-8d in %.2fs  peak-mem %s\n",
        $totalread,
        $totalunique,
        $totalmatched,
        $totalunknown,
        $elapsed,
        number_format($mem / 1024 / 1024, 1) . ' MB'
    ));
}

// Emit primary output.
$outfh = block_backadel_sim_open_write($opts['output']);
if ($opts['format'] === 'table') {
    block_backadel_sim_emit_table($tally, $totalunique, $outfh);
} else {
    block_backadel_sim_emit_csv($tally, $totalunique, $outfh);
}
block_backadel_sim_close($outfh);

// Emit year output.
if ($opts['year_output'] !== null) {
    $yearfh = block_backadel_sim_open_write($opts['year_output']);
    block_backadel_sim_emit_year_csv($yeartally, $yearfh);
    block_backadel_sim_close($yearfh);
}

exit(0);
