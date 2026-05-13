<?php
/**
 * Fast bulk-load: scan a directory and batch-INSERT into block_backadel_catalogue
 * AND block_backadel_courses (for patterns that warrant a courses row).
 *
 * Designed for an empty/clean table — skips per-row existence checks.
 * Uses Moodle's insert_records() for batch efficiency.
 *
 * Usage:
 *   php blocks/backadel/cli/fast_populate_catalogue.php [--dir=PATH] [--source=NAME] [--batch=N] [--dry-run]
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'dir'     => null,
    'source'  => 'legacy_moodleus',
    'batch'   => 500,
    'dry-run' => false,
    'help'    => false,
], ['h' => 'help', 'n' => 'dry-run']);

if ($options['help'] || $unrecognised) {
    echo "Fast bulk-load for corpus testing. Options:\n";
    echo "  --dir=PATH      Directory to scan (default: dataroot/backadel-corpus)\n";
    echo "  --source=NAME   Source label (default: legacy_moodleus)\n";
    echo "  --batch=N       Rows per INSERT batch (default: 500)\n";
    echo "  --dry-run       Parse only; count but do not write\n";
    exit($unrecognised ? 1 : 0);
}

$dirpath = $options['dir'] ?? ($CFG->dataroot . '/backadel-corpus');
$source  = (string) $options['source'];
$batch   = max(1, (int) $options['batch']);
$dryrun  = (bool) $options['dry-run'];

if (!is_dir($dirpath)) {
    cli_error("Directory not found: $dirpath");
}

require_once($CFG->dirroot . '/blocks/backadel/classes/local/filename_pattern_library.php');
require_once($CFG->dirroot . '/blocks/backadel/classes/local/course_type_resolver.php');

// Patterns that get a block_backadel_courses row (mirrors migrator.php allowlist).
const COURSES_PATTERNS = [
    'semester_legacy', 'semester_legacy_lc', 'semester_legacy_clone',
    'backadel_modern', 'backadel_instructor',
    'storage_course', 'storage_legacy', 'storagecourse_dept',
];

mtrace("Scanning: $dirpath  (source: $source)");

$start = microtime(true);
$files = array_values(array_filter(
    scandir($dirpath),
    fn($f) => str_ends_with(strtolower($f), '.zip') || str_ends_with(strtolower($f), '.mbz')
));
sort($files);
$total = count($files);
mtrace("  Found $total archive files");

if ($total === 0) {
    mtrace("  Nothing to import.");
    exit(0);
}

$now = time();
$catrows   = [];
$crsrows   = [];
$catins    = 0;
$crsins    = 0;

$lib = new \block_backadel\local\filename_pattern_library();

function flush_batch_to(array &$rows, string $table, bool $dryrun, int &$counter): void {
    if (empty($rows)) {
        return;
    }
    if (!$dryrun) {
        global $DB;
        $DB->insert_records($table, $rows);
    }
    $counter += count($rows);
    $rows = [];
}

$progress_every = 10000;
foreach ($files as $i => $basename) {
    $filepathfull = $dirpath . '/' . $basename;
    $parsed = $lib->parse($basename);

    $pattern     = (string) ($parsed['pattern'] ?? 'unknown');
    $instructors = $parsed['instructors'] ?? [];
    $coursetype  = \block_backadel\local\course_type_resolver::resolve($parsed);

    // --- Catalogue row ---
    $catrow = new stdClass();
    $catrow->filename      = core_text::substr($basename, 0, 255);
    $catrow->filepath      = core_text::substr($filepathfull, 0, 255);
    $catrow->filepath_full = $filepathfull;
    $catrow->filepath_hash = sha1($filepathfull);
    $catrow->source        = $source;
    $catrow->year          = isset($parsed['year']) ? (int) $parsed['year'] : null;
    $catrow->semester      = isset($parsed['semester']) ? (string) $parsed['semester'] : null;
    $catrow->dept          = isset($parsed['dept']) ? (string) $parsed['dept'] : null;
    $catrow->course_num    = isset($parsed['course_num']) ? (string) $parsed['course_num'] : null;
    $catrow->course_idnumber = null;
    $catrow->shortname     = isset($parsed['shortname_hint'])
        ? core_text::substr((string) $parsed['shortname_hint'], 0, 255)
        : null;
    $catrow->instructors   = json_encode(array_values((array) $instructors));
    $catrow->pattern       = $pattern;
    $catrow->backup_ts     = (int) ($parsed['backup_ts'] ?? 0);
    $catrow->file_size     = null; // skip stat() for speed
    $catrow->status        = 'available';
    $catrow->timecreated   = $now;
    $catrow->timemodified  = $now;
    $catrows[] = $catrow;

    // --- Courses row (only for patterns in the allowlist) ---
    if (in_array($pattern, COURSES_PATTERNS, true)) {
        $year     = isset($parsed['year']) ? (int) $parsed['year'] : 0;
        $sem      = isset($parsed['semester']) ? trim((string) $parsed['semester']) : '';
        $semfolder = ($year > 0 && $sem !== '') ? "{$year}_{$sem}" : ($year > 0 ? (string) $year : 'Other');
        $dept     = isset($parsed['dept']) ? (string) $parsed['dept'] : '';
        $cnum     = isset($parsed['course_num']) ? (string) $parsed['course_num'] : '';
        $shortname = $dept && $cnum ? "{$dept}_{$cnum}" : $basename;

        $crsrow = new stdClass();
        $crsrow->courseid        = null;
        $crsrow->coursefullname  = core_text::substr($basename, 0, 255);
        $crsrow->courseshortname = core_text::substr($shortname, 0, 255);
        $crsrow->courseidnumber  = null;
        $crsrow->status          = 'available';
        $crsrow->filepath        = $filepathfull;
        $crsrow->filename        = core_text::substr($basename, 0, 255);
        $crsrow->filesize        = null;
        $crsrow->timecreated     = $now;
        $crsrow->backupcreated   = null;
        $crsrow->semester        = core_text::substr($semfolder, 0, 64);
        $crsrow->academicperiodid = null;
        $crsrow->coursetype      = $coursetype;
        $crsrow->statusid        = null;
        $crsrows[] = $crsrow;
    }

    // Flush when batches are full.
    if (count($catrows) >= $batch) {
        flush_batch_to($catrows, 'block_backadel_catalogue', $dryrun, $catins);
        flush_batch_to($crsrows, 'block_backadel_courses', $dryrun, $crsins);
        if ($catins % $progress_every < $batch) {
            $elapsed = round(microtime(true) - $start, 1);
            mtrace("  Progress: $catins / $total catalogue rows  ({$elapsed}s)");
        }
    }
}

flush_batch_to($catrows, 'block_backadel_catalogue', $dryrun, $catins);
flush_batch_to($crsrows, 'block_backadel_courses', $dryrun, $crsins);

$elapsed = round(microtime(true) - $start, 1);
$verb = $dryrun ? 'parsed (dry-run)' : 'inserted';

mtrace(sprintf(
    "  Done: %d catalogue rows %s, %d courses rows %s in %.1fs",
    $catins, $verb, $crsins, $verb, $elapsed
));

if (!$dryrun) {
    // Coursetype summary from DB.
    mtrace("\nCoursetype summary (block_backadel_courses):");
    $sql = "SELECT coursetype, COUNT(*) AS cnt
              FROM {block_backadel_courses}
          GROUP BY coursetype
          ORDER BY cnt DESC";
    foreach ($DB->get_records_sql($sql) as $r) {
        mtrace(sprintf("  %-12s %d", $r->coursetype, $r->cnt));
    }

    mtrace("\nPattern summary (block_backadel_catalogue):");
    $sql = "SELECT pattern, COUNT(*) AS cnt
              FROM {block_backadel_catalogue}
             WHERE source = :source
          GROUP BY pattern
          ORDER BY cnt DESC";
    foreach ($DB->get_records_sql($sql, ['source' => $source]) as $r) {
        mtrace(sprintf("  %-30s %d", $r->pattern, $r->cnt));
    }
}
