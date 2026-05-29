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
 * CLI: find and fix block_backadel_catalogue rows whose instructors JSON
 * contains full email tokens instead of bare local-parts.
 *
 * Background (bug-070): the bug-059 parser fix preserved full email tokens
 * (e.g. ["wjian15@lsu.edu"]) in the catalogue's instructors column.
 * simple_restore's LIKE predicate searches for '"%wjian15%"' (local-part
 * surrounded by double quotes), which does not match when @domain is present.
 *
 * Usage:
 *   php blocks/backadel/cli/fix_catalogue_instructor_emails.php [--fix] [--dry-run] [--verbose]
 *
 *   --fix       Actually update the rows (default: report only / dry-run).
 *   --dry-run   Explicit alias for the default "report only" mode.
 *   --verbose   Print each affected row's id, old JSON, and new JSON.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use block_backadel\local\instructor_resolver;

[$options, $unrecognised] = cli_get_params(
    ['fix' => false, 'dry-run' => false, 'verbose' => false, 'help' => false],
    ['v' => 'verbose', 'h' => 'help']
);

if ($options['help']) {
    echo <<<'EOT'
Find and optionally fix block_backadel_catalogue rows where the instructors
JSON column contains full email tokens instead of bare local-parts.

Usage: php blocks/backadel/cli/fix_catalogue_instructor_emails.php [options]

Options:
  --fix       Write the corrected local-parts back to the database.
  --dry-run   Report affected rows without writing (default).
  --verbose   Print id, old JSON, and new JSON for each affected row.
  --help      Show this help.

EOT;
    exit(0);
}

$dofix   = !empty($options['fix']);
$verbose = !empty($options['verbose']);

if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
    cli_writeln('block_backadel_catalogue table does not exist — nothing to do.');
    exit(0);
}

cli_writeln('Scanning block_backadel_catalogue for email-token instructor entries...');

// Fetch all rows that contain '@' anywhere in the instructors column.
// This is the cheapest filter: rows without '@' cannot have email tokens.
$sql = "SELECT id, instructors FROM {block_backadel_catalogue} WHERE " .
    $DB->sql_like('instructors', ':pat', false, true, false);
$rows = $DB->get_records_sql($sql, ['pat' => '%@%']);

$affected = 0;
$fixed    = 0;

foreach ($rows as $row) {
    $tokens = json_decode((string) $row->instructors, true);
    if (!is_array($tokens)) {
        continue;
    }

    // Normalise to local-parts.
    $normalised = array_values(array_unique(array_map(
        [instructor_resolver::class, 'extract_local_part'],
        $tokens
    )));

    // Skip rows that are already clean (no change needed).
    if ($normalised === $tokens) {
        continue;
    }

    $affected++;
    $newjson = json_encode($normalised);

    if ($verbose) {
        cli_writeln(sprintf(
            '  id=%-8d  old=%s  new=%s',
            $row->id,
            $row->instructors,
            $newjson
        ));
    }

    if ($dofix) {
        $DB->set_field('block_backadel_catalogue', 'instructors', $newjson, ['id' => $row->id]);
        $fixed++;
    }
}

cli_writeln(sprintf('Rows scanned (instructors contains @): %d', count($rows)));
cli_writeln(sprintf('Rows with email tokens needing fix: %d', $affected));

if ($dofix) {
    cli_writeln(sprintf('Rows updated: %d', $fixed));
    cli_writeln('Done.');
} else {
    if ($affected > 0) {
        cli_writeln('Run with --fix to apply corrections.');
    } else {
        cli_writeln('No rows affected — catalogue instructors column is clean.');
    }
}
exit(0);
