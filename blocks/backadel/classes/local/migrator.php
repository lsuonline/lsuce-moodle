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

declare(strict_types=1);

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

use core_text;
use stdClass;

/**
 * Batch-migrates filesystem backup archives into catalogue and warm-path tables.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrator {

    /** @var int Progress notification cadence (files processed). */
    public const BATCH_SIZE = 200;

    /** @var instructor_resolver */
    private $instructorresolver;

    /** @var period_resolver */
    private $periodresolver;

    /**
     * Teachers inserted by the last {@see self::resolve_instructors()} invocation.
     *
     * @var int
     */
    private $resolveinstructorsinserted = 0;

    /**
     * @param instructor_resolver|null $instructors
     * @param period_resolver|null $periods
     */
    public function __construct(
        ?instructor_resolver $instructors = null,
        ?period_resolver $periods = null,
    ) {
        $this->instructorresolver = $instructors ?? new instructor_resolver();
        $this->periodresolver = $periods ?? new period_resolver();
    }

    /**
     * Return sorted .mbz/.zip basenames found directly inside $dirpath. Non-recursive.
     * Returns an empty array if the directory is missing or unreadable.
     *
     * @param string $dirpath Absolute path to the directory to scan.
     * @return string[] Sorted list of archive basenames (no path prefix).
     */
    public static function list_archives(string $dirpath): array {
        $dirpath = rtrim($dirpath, "/\\");
        if ($dirpath === '' || !is_dir($dirpath)) {
            return [];
        }
        $entries = @scandir($dirpath, SCANDIR_SORT_ASCENDING);
        if ($entries === false) {
            return [];
        }
        $out = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $filepathfull = rtrim($dirpath, '/') . '/' . $entry;
            if (!is_file($filepathfull)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, ['zip', 'mbz'], true)) {
                $out[] = $entry;
            }
        }
        return $out;
    }

    /**
     * Process a single backup file: parse name, upsert catalogue, optionally upsert
     * warm-path course + teacher rows. Stateless w.r.t. the directory iteration.
     *
     * Errors (parse failures, DB exceptions) are caught and logged via mtrace —
     * matches the existing per-file error behaviour in migrate_directory().
     *
     * @param string $filepathfull Absolute path to a .mbz/.zip file.
     * @param string $source       Catalogue source key (e.g. 'backadel_current').
     * @return int Number of rows inserted (catalogue + courses + teachers). 0 on
     *             unparseable / errored / non-archive files.
     */
    public function migrate_file(string $filepathfull, string $source): int {
        if (!is_file($filepathfull)) {
            return 0;
        }
        $ext = strtolower(pathinfo($filepathfull, PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'mbz'], true)) {
            return 0;
        }

        try {
            $parsed = filename_parser::parse($filepathfull);
            if ($parsed === null) {
                return 0;
            }
            $catalogueinserted = $this->upsert_catalogue($source, $filepathfull, $parsed);
            $inserted  = $catalogueinserted ? 1 : 0;
            $inserted += $this->maybe_sync_courses_and_teachers($filepathfull, $parsed);
            return $inserted;
        } catch (\Throwable $e) {
            mtrace('block_backadel migrator: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Scan a directory for archives and upsert catalogue (+ warm path) rows.
     *
     * Delegates per-file work to {@see self::migrate_file()} so the adhoc task
     * can drive the same logic with its own time budget and cursor.
     *
     * @param string $dirpath Absolute or relative directory root (non-recursive).
     * @param string $source Catalogue source key e.g. backadel_current.
     * @return int Count of rows inserted (catalogue + courses + teachers); updates excluded.
     */
    public function migrate_directory(string $dirpath, string $source): int {
        $basenames = self::list_archives($dirpath);

        $total = count($basenames);
        $done = 0;
        $insertedtotal = 0;

        foreach ($basenames as $basename) {
            $insertedtotal += $this->migrate_file(rtrim($dirpath, '/') . '/' . $basename, $source);
            $done++;
            $this->maybe_track_progress($done, $total);
        }

        return $insertedtotal;
    }

    /**
     * Hook for progress reporting; override in tests or subclasses.
     *
     * @param int $done
     * @param int $total
     */
    protected function track_progress(int $done, int $total): void {
    }

    /**
     * Insert or update catalogue metadata for one archive.
     *
     * @param string $source
     * @param string $filepathfull
     * @param array<string, mixed> $parsed
     * @return bool True when a new catalogue row was inserted.
     */
    private function upsert_catalogue(string $source, string $filepathfull, array $parsed): bool {
        global $DB;

        $now = time();
        $basename = basename($filepathfull);
        $filepathhash = sha1($filepathfull);
        $filepathshort = core_text::substr($filepathfull, 0, 255);

        $filesize = filesize($filepathfull);
        $filesizenull = ($filesize !== false) ? (int) $filesize : null;

        $instructors = $parsed['instructors'] ?? [];
        if (!is_array($instructors)) {
            $instructors = [];
        }

        $row = new stdClass();
        $row->filename = core_text::substr($basename, 0, 255);
        $row->filepath = $filepathshort;
        $row->filepath_full = $filepathfull;
        $row->filepath_hash = $filepathhash;
        $row->source = $source;
        $row->year = isset($parsed['year']) && $parsed['year'] !== null ? (int) $parsed['year'] : null;
        $row->semester = isset($parsed['semester']) && $parsed['semester'] !== null
            ? (string) $parsed['semester']
            : null;
        $row->dept = isset($parsed['dept']) && $parsed['dept'] !== null ? (string) $parsed['dept'] : null;
        $row->course_num = isset($parsed['course_num']) && $parsed['course_num'] !== null
            ? (string) $parsed['course_num']
            : null;
        $row->course_idnumber = isset($parsed['course_idnumber']) && $parsed['course_idnumber'] !== null
            ? (string) $parsed['course_idnumber']
            : null;
        $row->shortname = isset($parsed['shortname_hint']) && $parsed['shortname_hint'] !== null
            ? core_text::substr((string) $parsed['shortname_hint'], 0, 255)
            : null;
        $row->instructors = json_encode(array_values($instructors));
        $row->pattern = (string) ($parsed['pattern'] ?? 'unknown');
        $row->backup_ts = (int) ($parsed['backup_ts'] ?? 0);
        $row->file_size = $filesizenull;
        $row->status = 'available';
        $row->timemodified = $now;

        $existing = $DB->get_record('block_backadel_catalogue', [
            'source' => $source,
            'filepath_hash' => $filepathhash,
        ]);

        if ($existing !== false) {
            $row->id = $existing->id;
            $row->timecreated = $existing->timecreated;
            $DB->update_record('block_backadel_catalogue', $row);
            return false;
        }

        $row->timecreated = $now;
        $DB->insert_record('block_backadel_catalogue', $row);
        return true;
    }

    /**
     * Upsert warm-path row plus instructors when the parse pattern warrants it.
     *
     * @param string $filepathfull
     * @param array<string, mixed> $parsed
     * @return int Number of new rows inserted into courses/teachers.
     */
    private function maybe_sync_courses_and_teachers(string $filepathfull, array $parsed): int {
        $pattern = (string) ($parsed['pattern'] ?? '');
        if (!in_array($pattern, [
            'semester_legacy',
            'semester_legacy_lc',
            'backadel_modern',
            'backadel_instructor',
            'storage_course',      // Storage_Course_* / storage_course_* (~211 files; all blueprint)
            'storage_legacy',      // Blank_Course_*, Master_Course_*, Flagship_*, etc. (~9,735 files)
            'storagecourse_dept',  // storagecourse_DEPT_NUM_* (~5 files; may classify as teaching)
        ], true)) {
            return 0;
        }

        global $DB;

        $now = time();
        $basename = basename($filepathfull);
        $filesizeraw = filesize($filepathfull);
        $filesize = ($filesizeraw !== false) ? (int) $filesizeraw : null;
        $mtime = filemtime($filepathfull);
        $backupcreated = ($mtime !== false) ? (int) $mtime : null;

        $year = isset($parsed['year']) ? (int) $parsed['year'] : 0;
        $semesterphrase = isset($parsed['semester']) && $parsed['semester'] !== null
            ? trim((string) $parsed['semester'])
            : '';
        $semesterfolder = ($year > 0 && $semesterphrase !== '')
            ? $this->periodresolver->resolve($year, $semesterphrase)
            : null;

        $hint = isset($parsed['shortname_hint']) && $parsed['shortname_hint'] !== null
            ? trim((string) $parsed['shortname_hint'])
            : '';
        $label = $hint !== '' ? $hint : $basename;
        $label = core_text::substr($label, 0, 255);

        $row = new stdClass();
        $row->courseid = null;
        $row->coursefullname = $label;
        $row->courseshortname = $label;
        $row->courseidnumber = isset($parsed['course_idnumber']) && $parsed['course_idnumber'] !== null
            ? core_text::substr((string) $parsed['course_idnumber'], 0, 255)
            : null;
        $row->status = 'available';
        $row->filepath = $filepathfull;
        $row->filename = core_text::substr($basename, 0, 255);
        $row->filesize = $filesize;
        $row->backupcreated = $backupcreated;
        $row->semester = $semesterfolder;
        $row->academicperiodid = null;
        $row->coursetype = course_type_resolver::resolve($parsed);
        $row->statusid = null;

        $existing = $DB->get_record_select(
            'block_backadel_courses',
            $DB->sql_compare_text('filepath') . ' = ' . $DB->sql_compare_text(':fp'),
            ['fp' => $filepathfull]
        );
        $wasinsert = false;
        if ($existing !== false) {
            $row->id = $existing->id;
            $row->timecreated = $existing->timecreated;
            $DB->update_record('block_backadel_courses', $row);
            $coursesid = (int) $existing->id;
        } else {
            $row->timecreated = $now;
            $coursesid = (int) $DB->insert_record('block_backadel_courses', $row);
            $wasinsert = true;
        }

        $this->resolve_instructors($parsed, $coursesid);

        return ($wasinsert ? 1 : 0) + $this->resolveinstructorsinserted;
    }

    /**
     * Resolve instructor tokens into teacher rows for a warm-path backup row.
     *
     * @param array<string, mixed> $parsed
     * @param int $coursesid block_backadel_courses.id
     */
    private function resolve_instructors(array $parsed, int $coursesid): void {
        global $DB;

        $this->resolveinstructorsinserted = 0;

        $usernames = $parsed['instructors'] ?? [];
        if (!is_array($usernames)) {
            return;
        }

        foreach ($usernames as $username) {
            if (!is_string($username)) {
                continue;
            }
            $username = trim($username);
            if ($username === '') {
                continue;
            }

            $exists = $DB->get_record('block_backadel_teachers', [
                'coursesid' => $coursesid,
                'username' => $username,
            ]);
            if ($exists !== false) {
                continue;
            }

            $user = $this->instructorresolver->resolve($username);

            $teacher = new stdClass();
            $teacher->coursesid = $coursesid;
            $teacher->username = $username;
            if ($user !== null) {
                $teacher->userid = (int) $user->id;
                $teacher->email = $user->email ?? null;
                $teacher->resolved = 1;
                $teacher->resolvedvia = 'username';
            } else {
                $teacher->userid = null;
                $teacher->email = null;
                $teacher->resolved = 0;
                $teacher->resolvedvia = 'none';
            }
            $teacher->timecreated = time();

            $DB->insert_record('block_backadel_teachers', $teacher);
            $this->resolveinstructorsinserted++;
        }
    }

    /**
     * Fire {@see self::track_progress()} every {@see self::BATCH_SIZE} files and on completion.
     *
     * @param int $done
     * @param int $total
     */
    private function maybe_track_progress(int $done, int $total): void {
        if ($done % self::BATCH_SIZE === 0 || $done === $total) {
            $this->track_progress($done, $total);
        }
    }
}
