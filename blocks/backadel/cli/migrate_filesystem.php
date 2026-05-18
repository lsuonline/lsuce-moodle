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
 * CLI: migrate filesystem backup archives into block_backadel_catalogue.
 *
 * Scans one or more directories for .mbz/.zip archives and upserts rows into
 * block_backadel_catalogue (and block_backadel_courses / block_backadel_teachers
 * for recognised filename patterns), reusing the same logic as the adhoc task.
 *
 * Usage:
 *   php blocks/backadel/cli/migrate_filesystem.php [--dry-run] [--verbose] [--dir=<path>]
 *
 * When --dir is not supplied, the script reads the block_backadel/path admin setting
 * (relative to $CFG->dataroot) and block_backadel/migration_extra_paths, exactly as
 * the adhoc task does.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use block_backadel\local\migrator;
use block_backadel\local\filename_parser;

// ---------------------------------------------------------------------------
// Parameter parsing
// ---------------------------------------------------------------------------

list($options, $unrecognized) = cli_get_params([
    'dry-run' => false,
    'verbose'  => false,
    'dir'      => '',
    'help'     => false,
], [
    'n' => 'dry-run',
    'v' => 'verbose',
    'h' => 'help',
]);

if ($unrecognized) {
    $bad = implode(', ', $unrecognized);
    cli_error('Unrecognized option(s): ' . $bad . "\nRun with --help for usage.");
}

if ($options['help']) {
    $help = <<<EOF
Migrate filesystem backup archives into block_backadel_catalogue.

Scans directories for .mbz / .zip files and upserts catalogue rows using the
same migrator logic as the adhoc task (migrate_filesystem_adhoc).

When --dir is omitted, the block_backadel/path admin setting (relative to
\$CFG->dataroot) and block_backadel/migration_extra_paths are used, exactly
as the adhoc task does.

Options:
  --dry-run, -n   Count files and parse filenames; do NOT write to the database.
  --verbose, -v   Print a progress line every 1 000 files processed.
  --dir=<path>    Override the scan directory (absolute path). Repeat for multiple
                  directories: --dir=/foo --dir=/bar (only the first --dir= value
                  is accepted via cli_get_params; pass additional directories as
                  comma-separated values or use the admin setting instead).
  --help, -h      Show this help.

Examples:
  php blocks/backadel/cli/migrate_filesystem.php --dry-run --verbose
  php blocks/backadel/cli/migrate_filesystem.php --dir=/mnt/backups/backadel
  php blocks/backadel/cli/migrate_filesystem.php --verbose

EOF;
    echo $help;
    exit(0);
}

$dryrun  = !empty($options['dry-run']);
$verbose = !empty($options['verbose']);

// ---------------------------------------------------------------------------
// Build list of (dir => source) runs
// ---------------------------------------------------------------------------

$runs = [];

if (!empty($options['dir'])) {
    // Caller supplied an explicit directory override.
    $dirsraw = (string) $options['dir'];
    foreach (explode(',', $dirsraw) as $d) {
        $d = trim($d);
        if ($d !== '') {
            $runs[] = ['dir' => $d, 'source' => 'backadel_current'];
        }
    }
} else {
    // Mirror exactly what migrate_filesystem_adhoc task does.
    $relpath = get_config('block_backadel', 'path');
    if ($relpath === false || trim((string) $relpath) === '') {
        cli_error(
            'block_backadel/path admin setting is not configured.' . "\n" .
            'Either configure it in Site administration > Plugins > Blocks > Backadel,' . "\n" .
            'or supply an explicit directory with --dir=<path>.'
        );
    }

    $rootdir = rtrim($CFG->dataroot, '/') . '/' . ltrim(trim((string) $relpath), '/');
    $runs[]  = ['dir' => $rootdir, 'source' => 'backadel_current'];

    $legacyraw   = get_config('block_backadel', 'migration_extra_paths');
    $legacylines = [];
    if (is_string($legacyraw) && $legacyraw !== '') {
        $split = preg_split('/\R/', $legacyraw);
        $legacylines = is_array($split) ? $split : [];
    }
    foreach ($legacylines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $runs[] = ['dir' => $line, 'source' => 'legacy_moodleus'];
        }
    }
}

if ($runs === []) {
    cli_error('No directories to process. Configure block_backadel/path or use --dir=<path>.');
}

// ---------------------------------------------------------------------------
// Validate directories
// ---------------------------------------------------------------------------

foreach ($runs as $run) {
    $dir = $run['dir'];
    if (!is_dir($dir)) {
        cli_error('Directory does not exist or is not accessible: ' . $dir);
    }
}

// ---------------------------------------------------------------------------
// Dry-run path: count only, no DB writes
// ---------------------------------------------------------------------------

if ($dryrun) {
    cli_writeln('Dry run — no database writes will occur.');
    cli_writeln('');

    $grandtotal = 0;
    foreach ($runs as $run) {
        $dir    = $run['dir'];
        $source = $run['source'];

        $relpaths = migrator::list_all_archives($dir);
        if ($relpaths === [] && !is_dir($dir)) {
            cli_writeln('  WARNING: cannot scan directory: ' . $dir);
            continue;
        }

        $count        = 0;
        $parseable    = 0;
        $unparseable  = 0;
        $patterncounts = [];

        foreach ($relpaths as $relpath) {
            $filepath = rtrim($dir, '/') . '/' . $relpath;
            if (!is_file($filepath)) {
                continue;
            }

            $count++;
            $grandtotal++;

            if ($verbose && $count % 1000 === 0) {
                cli_writeln('  [dry-run] Scanned ' . $count . ' files in ' . $dir . ' ...');
            }

            $parsed = filename_parser::parse($filepath);
            if ($parsed === null) {
                $unparseable++;
            } else {
                $parseable++;
                $pattern = (string) ($parsed['pattern'] ?? 'unknown');
                $patterncounts[$pattern] = ($patterncounts[$pattern] ?? 0) + 1;
            }
        }

        cli_writeln('Directory : ' . $dir . '  (source: ' . $source . ')');
        cli_writeln('  Files found : ' . $count);
        cli_writeln('  Parseable   : ' . $parseable);
        cli_writeln('  Unparseable : ' . $unparseable);
        if ($patterncounts !== []) {
            ksort($patterncounts, SORT_STRING);
            cli_writeln('  Pattern breakdown:');
            foreach ($patterncounts as $pattern => $cnt) {
                cli_writeln('    ' . $pattern . ': ' . $cnt);
            }
        }
        cli_writeln('');
    }

    cli_writeln('Done (dry run): ' . $grandtotal . ' files found across ' . count($runs) . ' director' .
        (count($runs) === 1 ? 'y' : 'ies') . '. No rows written.');
    exit(0);
}

// ---------------------------------------------------------------------------
// Live migration path — subclass migrator to get per-file progress callbacks
// ---------------------------------------------------------------------------

/**
 * CLI-aware migrator subclass that emits verbose progress and tracks counters.
 *
 * @package    block_backadel
 */
class block_backadel_cli_migrator extends migrator {

    /** @var bool */
    private bool $verbose;

    /** @var int Files processed so far in this directory run. */
    public int $processedcount = 0;

    /** @var int Total files in this directory run. */
    public int $totalcount = 0;

    /** @var string Directory label for progress messages. */
    public string $dirlabel = '';

    public function __construct(bool $verbose) {
        parent::__construct();
        $this->verbose = $verbose;
    }

    protected function track_progress(int $done, int $total): void {
        $this->processedcount = $done;
        $this->totalcount     = $total;

        if ($this->verbose) {
            cli_writeln('  Processing file ' . $done . ' of ' . $total .
                ($this->dirlabel !== '' ? ' in ' . $this->dirlabel : '') . ' ...');
        }
    }
}

// ---------------------------------------------------------------------------
// Run migrations
// ---------------------------------------------------------------------------

$grandprocessed = 0;
$grandinserted  = 0;
$granderrors    = 0;
$dirsprocessed  = 0;

$mig = new block_backadel_cli_migrator($verbose);

foreach ($runs as $run) {
    $dir    = $run['dir'];
    $source = $run['source'];

    cli_writeln('Scanning: ' . $dir . '  (source: ' . $source . ')');

    $mig->dirlabel      = $dir;
    $mig->processedcount = 0;
    $mig->totalcount     = 0;

    try {
        $inserted = $mig->migrate_directory($dir, $source);
        $processed = $mig->processedcount;

        $grandprocessed += $processed;
        $grandinserted  += $inserted;
        $dirsprocessed++;

        cli_writeln('  Done: ' . $processed . ' files processed, ' . $inserted . ' rows inserted/updated.');
    } catch (\Throwable $e) {
        $granderrors++;
        cli_writeln('  ERROR processing ' . $dir . ': ' . $e->getMessage());
        if ($verbose) {
            cli_writeln($e->getTraceAsString());
        }
    }

    cli_writeln('');
}

// ---------------------------------------------------------------------------
// Final summary
// ---------------------------------------------------------------------------

cli_writeln(sprintf(
    'Done: %d files processed, %d inserted, %d updated, %d errors.',
    $grandprocessed,
    // migrator::migrate_directory returns total *new* rows (inserts); updates are implicit.
    $grandinserted,
    // We do not have a separate update counter from the migrator; report as N/A.
    0,
    $granderrors,
));

exit($granderrors > 0 ? 1 : 0);
