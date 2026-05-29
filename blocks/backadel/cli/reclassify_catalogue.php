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
 * CLI: re-classify existing block_backadel_catalogue rows without filesystem scan.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use block_backadel\local\catalogue_allowlists;
use block_backadel\local\reclassifier;

list($options, $unrecognized) = cli_get_params([
    'pattern' => '',
    'null-semester' => false,
    'dry-run' => false,
    'source' => '',
    'chunk' => 500,
    'help' => false,
], [
    'n' => 'dry-run',
    'h' => 'help',
]);

if ($unrecognized !== []) {
    cli_error('Unrecognized option(s): ' . implode(', ', $unrecognized) . "\nRun with --help.");
}

if (!empty($options['help'])) {
    $help = <<<EOF
Re-parse filenames for existing catalogue rows (course type, instructors, missing-file status).

Options:
  --pattern=X       Comma-separated pattern names; repeat to merge lists. Omit for all
                    patterns in catalogue_allowlists::VALID_PATTERNS.
  --null-semester   Include catalogue rows where semester IS NULL.
  --dry-run, -n     COUNT(*) only; no database writes from reclassification.
  --source=X        Restrict to catalogue rows where source = X (e.g. backadel_current).
  --chunk=N         Batch size per query (default 500).
  --help, -h        This help.

Examples:
  php blocks/backadel/cli/reclassify_catalogue.php --dry-run --pattern=unknown
  php blocks/backadel/cli/reclassify_catalogue.php --pattern=semester_legacy,unknown --null-semester

EOF;
    echo $help;
    exit(0);
}

$datasource = trim((string) ($options['source'] ?? ''));
$datasource = $datasource !== '' ? $datasource : null;

$chunk = (int) ($options['chunk'] ?? 500);
if ($chunk < 1) {
    cli_error('--chunk must be at least 1.');
}

$patterns = [];
$patternchunks = [];
if (!empty($_SERVER['argv'])) {
    foreach (array_slice($_SERVER['argv'], 1) as $arg) {
        if (is_string($arg) && str_starts_with($arg, '--pattern=')) {
            $patternchunks[] = substr($arg, strlen('--pattern='));
        }
    }
}
if ($patternchunks === []) {
    $pv = $options['pattern'] ?? '';
    if (is_string($pv) && $pv !== '') {
        $patternchunks[] = $pv;
    }
}
foreach ($patternchunks as $po) {
    foreach (explode(',', (string) $po) as $p) {
        $p = strtolower(trim($p));
        if ($p !== '') {
            $patterns[] = $p;
        }
    }
}
$patterns = array_values(array_unique($patterns));

$nullsemester = !empty($options['null-semester']);
$dodry = !empty($options['dry-run']);

$valid = catalogue_allowlists::VALID_PATTERNS;
if ($patterns !== []) {
    $bad = array_diff($patterns, $valid);
    if ($bad !== []) {
        cli_error('Invalid pattern name(s): ' . implode(', ', $bad) .
            "\nValid: " . implode(', ', $valid));
    }
} else {
    $patterns = $valid;
}

$classifier = new reclassifier();

if ($dodry) {
    cli_writeln(sprintf('Dry run — counting matches (chunk size %d).', $chunk));
    $count = $classifier->count_matching($patterns, $nullsemester, $datasource);
    cli_writeln('Matching catalogue rows: ' . $count);
    exit(0);
}

cli_writeln(sprintf(
    'Re-classifying patterns=%s null_semester=%s source=%s chunk=%d',
    implode(',', $patterns),
    $nullsemester ? 'yes' : 'no',
    $datasource ?? '(any)',
    $chunk
));

$afterid = 0;
$grandprocessed = 0;
$grandrec = 0;
$grandmiss = 0;
$grandunp = 0;

while (true) {
    $batch = $classifier->reclassify_batch($patterns, $nullsemester, $chunk, $afterid, $datasource);
    $processed = (int) $batch['processed'];
    if ($processed === 0) {
        break;
    }
    $grandprocessed += $processed;
    $grandrec += (int) $batch['reclassified'];
    $grandmiss += (int) $batch['missing'];
    $grandunp += (int) $batch['unparseable'];
    $afterid = (int) $batch['last_id'];
    cli_writeln(sprintf(
        'batch: processed=%d reclassified=%d missing=%d unparseable=%d last_id=%d (totals processed=%d)',
        $processed,
        (int) $batch['reclassified'],
        (int) $batch['missing'],
        (int) $batch['unparseable'],
        $afterid,
        $grandprocessed
    ));
}

cli_writeln(sprintf(
    'Done. processed=%d reclassified=%d missing=%d unparseable=%d',
    $grandprocessed,
    $grandrec,
    $grandmiss,
    $grandunp
));

exit(0);
