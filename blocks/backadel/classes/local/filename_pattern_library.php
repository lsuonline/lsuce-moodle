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

// NOTE: No defined('MOODLE_INTERNAL') || die() — this file is intentionally loadable
// outside the Moodle bootstrap (e.g. from standalone CLI tools). The class is final and
// stateless; it has zero Moodle dependencies.

/**
 * Pure regex/parsing logic for backup archive filename classification.
 *
 * This class is extracted from {@see filename_parser} so that it can be loaded
 * without bootstrapping Moodle. All pattern constants and parsing helpers live
 * here; {@see filename_parser} is a thin Moodle-aware shim that delegates to
 * this class.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filename_pattern_library {

    /** @var string Primary legacy semester backup pattern (uppercase semester + dept). */
    public const PATTERN_SEMESTER_LEGACY =
        '/^(?P<year>\d{4})(?P<semester>Spring|Summer|Fall|Winter|SecondFall|SecondSummer)(?P<dept>[A-Z]{2,8})(?P<digit_run>\d{4,})(?P<tt_suffix>tt\d+)?' .
        '(?:_(?P<instructors>[a-z0-9][a-z0-9_-]*?))?_(?P<backup_ts>\d{9,12})\.(zip|mbz)$/';

    /** @var string Lowercase-semester variant of {@see self::PATTERN_SEMESTER_LEGACY}. */
    public const PATTERN_SEMESTER_LEGACY_LC =
        '/^(?P<year>\d{4})(?P<semester>spring|summer|fall|winter|secondfall|secondsummer)(?P<dept>[a-zA-Z]{2,8})(?P<digit_run>\d{4,})(?P<tt_suffix>tt\d+)?' .
        '(?:_(?P<instructors>[a-z0-9][a-z0-9_-]*?))?_(?P<backup_ts>\d{9,12})\.(zip|mbz)$/';

    /** @var string Optional instructor tokens without departmental course numbering. */
    public const PATTERN_STORAGE_COURSE = '/^storage_course_(?P<body>.+?)_(?P<backup_ts>\d{9,12})\.zip$/i';

    /** @var string Hybrid storage path with dept and course number. */
    public const PATTERN_STORAGECOURSE_DEPT =
        '/^storagecourse_(?P<dept>[A-Z]{2,8})_(?P<course_num>\d{3,5})_(?P<body>.+?)_(?P<backup_ts>\d{9,12})\.zip$/';

    /**
     * @var string Named-instructor Backadel archive produced by the backup task.
     * Format: backadel-{year}-{Season}-{DEPT}-{num}-for-{First}-{Last}_{user}@{domain}[_{user2}@{domain}].zip
     * Captures year, semester, dept, course_num, and email-style instructor tokens.
     */
    public const PATTERN_BACKADEL_INSTRUCTOR =
        '/^backadel-(?P<year>\d{4})-(?P<semester>Spring|Summer|Fall|Winter|SecondFall|SecondSummer)' .
        '-(?P<dept>[A-Z]{2,8})-(?P<course_num>\d{3,5})' .
        '(?:-Course-\d+)?' .
        '-for-[A-Za-z]+(?:-[A-Za-z]+)+' .
        '(?P<instructors>(?:_[a-z][a-z0-9._-]*@[a-z0-9._-]+)+)' .
        '\.(?P<ext>zip|mbz)$/i';

    /** @var string Current or historical Backadel-generated archives. */
    public const PATTERN_BACKADEL = '/^backadel-[-*]*(?P<slug>.+?)\.(?P<ext>zip|mbz)$/i';

    /** @var string Native Moodle 2 course backup naming. */
    public const PATTERN_MOODLE_NATIVE =
        '/^backup-moodle2-course-(?P<courseid>\d+)-(?P<shortname>.+?)-(?P<datestamp>\d{8})-(?P<hhmm>\d{4})(?:-nu)?\.mbz$/';

    /** @var string Trailing timestamp on unknown legacy names. */
    public const PATTERN_FALLBACK_TS = '/_(?P<backup_ts>\d{9,12})\.(zip|mbz)$/i';

    /** @var string Pre-semester-era archives: letter prefix, no year, trailing 9-12 digit timestamp. Allows hyphen and dot in slug. */
    public const PATTERN_STORAGE_LEGACY = '/^(?P<slug>[A-Za-z][A-Za-z0-9_.\-]*)_(?P<backup_ts>\d{9,12})\.(zip|mbz)$/i';

    /** @var string Clone/copy of a semester course (slug ending in cl\d+, no strict dept code). */
    public const PATTERN_SEMESTER_LEGACY_CLONE =
        '/^(?P<year>\d{4})(?P<semester>Spring|Summer|Fall|Winter|SecondFall|SecondSummer|spring|summer|fall|winter|secondfall|secondsummer)' .
        '(?P<slug>[A-Za-z0-9]+cl\d+)(?P<instructor_tail>(?:_[A-Za-z0-9]+)*)_(?P<backup_ts>\d{9,12})\.(zip|mbz)$/i';

    /** @var string Moodle username token at end of Backadel slug segments. */
    private const USERNAME_SEGMENT = '/^[a-z][a-z0-9._-]{2,}$/i';

    /**
     * Parse a backup filename (basename preferred; directories are tolerated) into catalogue-oriented fields.
     *
     * @param string $filename Archive name or path; {@see basename()} used for regex matching only.
     * @return array<string, mixed>|null Null when the name should not be catalogue-imported (.filepart/.tmp/path-only).
     */
    public static function parse(string $filename): ?array {
        $trimmed = trim($filename);
        if ($trimmed === '') {
            return null;
        }
        if (str_ends_with($trimmed, '/')) {
            return null;
        }
        if (preg_match('/\.(?:filepart|tmp|partial)$/i', $trimmed)) {
            return null;
        }

        $basename = basename($trimmed);
        return self::parse_basename_internal($basename, $filename);
    }

    /**
     * Core matcher against basename only while preserving raw input.
     *
     * @param string $basename Stripped basename for pattern tests.
     * @param string $raw Original filename argument.
     * @return array<string, mixed>
     */
    private static function parse_basename_internal(string $basename, string $raw): array {
        if (preg_match(self::PATTERN_SEMESTER_LEGACY, $basename, $m)) {
            return self::parsed_semester('semester_legacy', $raw, $m);
        }
        if (preg_match(self::PATTERN_SEMESTER_LEGACY_LC, $basename, $m)) {
            return self::parsed_semester('semester_legacy_lc', $raw, $m);
        }
        if (preg_match(self::PATTERN_SEMESTER_LEGACY_CLONE, $basename, $m)) {
            return self::parsed_semester_clone($raw, $m);
        }
        if (preg_match(self::PATTERN_STORAGE_COURSE, $basename, $m)) {
            return self::parsed_storage_course($raw, $m);
        }
        if (preg_match(self::PATTERN_STORAGECOURSE_DEPT, $basename, $m)) {
            return self::parsed_storagecourse_dept($raw, $m);
        }
        if (preg_match(self::PATTERN_BACKADEL_INSTRUCTOR, $basename, $m)) {
            return self::parsed_backadel_instructor($raw, $m);
        }
        if (preg_match(self::PATTERN_BACKADEL, $basename, $m)) {
            return self::parsed_backadel($raw, $m);
        }
        if (preg_match(self::PATTERN_MOODLE_NATIVE, $basename, $m)) {
            return self::parsed_moodle_native($raw, $m);
        }
        if (preg_match(self::PATTERN_STORAGE_LEGACY, $basename, $m)) {
            return self::parsed_storage_legacy($raw, $m);
        }

        $backupts = 0;
        if (preg_match(self::PATTERN_FALLBACK_TS, $basename, $fm)) {
            $backupts = (int) $fm['backup_ts'];
        }

        return self::result_template('unknown', $raw, $backupts, null, null, null, null, null, [], null);
    }

    /**
     * Build the standard result array with all keys present.
     *
     * @param string $pattern Catalogue pattern key.
     * @param string $raw Original filename.
     * @param int $backupts Unix time from filename (0 if absent).
     * @param int|null $year
     * @param string|null $semester Normalized title case.
     * @param string|null $dept
     * @param string|null $course_num
     * @param string|null $course_idnumber
     * @param string[] $instructors
     * @param string|null $shortname_hint
     * @return array<string, mixed>
     */
    private static function result_template(
        string $pattern,
        string $raw,
        int $backupts,
        ?int $year,
        ?string $semester,
        ?string $dept,
        ?string $coursenum,
        ?string $courseidnumber,
        array $instructors,
        ?string $shortnamehint
    ): array {
        $instructors = array_values(array_unique(array_filter($instructors, static fn($t) => $t !== '')));

        return [
            'pattern' => $pattern,
            'year' => $year,
            'semester' => $semester,
            'dept' => $dept,
            'course_num' => $coursenum,
            'course_idnumber' => $courseidnumber,
            'instructors' => $instructors,
            'backup_ts' => $backupts,
            'shortname_hint' => $shortnamehint,
            'raw_filename' => $raw,
        ];
    }

    /**
     * @param array<string, string> $m Regex named captures.
     * @return array<string, mixed>
     */
    private static function parsed_semester(string $patternkey, string $raw, array $m): array {
        $year = (int) $m['year'];
        $semester = self::normalize_semester($m['semester']);
        $dept = strtoupper($m['dept']);
        $split = self::split_digit_run($dept, $m['digit_run']);
        $coursenum = $split['course_num'] !== '' ? $split['course_num'] : null;
        $courseidnumber = $split['course_idnumber'] !== '' ? $split['course_idnumber'] : null;

        $instructors = self::instructors_from_capture($m['instructors'] ?? '');
        $backupts = (int) $m['backup_ts'];

        $shortnamehint = null;
        if ($coursenum !== null && $dept !== '') {
            $shortnamehint = $dept . ' ' . $coursenum;
        }

        $result = self::result_template(
            $patternkey,
            $raw,
            $backupts,
            $year,
            $semester,
            $dept,
            $coursenum,
            $courseidnumber,
            $instructors,
            $shortnamehint
        );

        if (!empty($m['tt_suffix'])) {
            $result['team_teach_suffix'] = (string) $m['tt_suffix'];
        }

        return $result;
    }

    /**
     * @param array<string, string> $m Regex named captures for PATTERN_SEMESTER_LEGACY_CLONE.
     * @return array<string, mixed>
     */
    private static function parsed_semester_clone(string $raw, array $m): array {
        $year = (int) $m['year'];
        $semester = self::normalize_semester($m['semester']);
        $slug = $m['slug'];
        $backupts = (int) $m['backup_ts'];
        $instructors = [];
        $tail = trim($m['instructor_tail'] ?? '', '_');
        if ($tail !== '') {
            $instructors = array_values(array_filter(explode('_', $tail)));
        }

        return self::result_template(
            'semester_legacy_clone',
            $raw,
            $backupts,
            $year,
            $semester,
            null,
            null,
            null,
            $instructors,
            $slug
        );
    }

    /**
     * @param array<string, string> $m Regex named captures for PATTERN_STORAGE_LEGACY.
     * @return array<string, mixed>
     */
    private static function parsed_storage_legacy(string $raw, array $m): array {
        $backupts = (int) $m['backup_ts'];
        $slug = $m['slug'] ?? '';

        return self::result_template(
            'storage_legacy',
            $raw,
            $backupts,
            null,
            null,
            null,
            null,
            null,
            [],
            $slug !== '' ? $slug : null
        );
    }

    /**
     * @param array<string, string> $m
     * @return array<string, mixed>
     */
    private static function parsed_storage_course(string $raw, array $m): array {
        $body = $m['body'] ?? '';
        $instructors = self::body_instructors($body);
        $backupts = (int) $m['backup_ts'];

        return self::result_template('storage_course', $raw, $backupts, null, null, null, null, null, $instructors, null);
    }

    /**
     * @param array<string, string> $m
     * @return array<string, mixed>
     */
    private static function parsed_storagecourse_dept(string $raw, array $m): array {
        $dept = $m['dept'];
        $coursenum = $m['course_num'];
        $instructors = self::body_instructors($m['body'] ?? '');
        $backupts = (int) $m['backup_ts'];

        $shortnamehint = $dept !== '' && $coursenum !== ''
            ? $dept . ' ' . $coursenum
            : null;

        return self::result_template(
            'storagecourse_dept',
            $raw,
            $backupts,
            null,
            null,
            $dept,
            $coursenum,
            null,
            $instructors,
            $shortnamehint
        );
    }

    /**
     * @param array<string, string> $m Regex named captures for PATTERN_BACKADEL_INSTRUCTOR.
     * @return array<string, mixed>
     */
    private static function parsed_backadel_instructor(string $raw, array $m): array {
        $dept = strtoupper($m['dept']);
        $coursenum = $m['course_num'];
        $shortnamehint = $dept !== '' && $coursenum !== '' ? $dept . '-' . $coursenum : null;

        // Extract email-style instructor tokens: "_user@domain" repeated group.
        $instructors = [];
        $tail = ltrim($m['instructors'] ?? '', '_');
        foreach (explode('_', $tail) as $token) {
            $token = trim($token);
            if ($token !== '' && strpos($token, '@') !== false) {
                // Store just the local part (username) for consistency with other patterns.
                $instructors[] = explode('@', $token)[0];
            }
        }

        $semester = ucfirst(strtolower($m['semester']));
        $year = (int) $m['year'];

        return self::result_template(
            'backadel_instructor',
            $raw,
            0,
            $year,
            $semester,
            $dept,
            $coursenum,
            null,
            $instructors,
            $shortnamehint
        );
    }

    private static function parsed_backadel(string $raw, array $m): array {
        $slug = $m['slug'];
        $parts = explode('_', $slug);
        $instructors = [];
        while ($parts !== [] && preg_match(self::USERNAME_SEGMENT, (string) end($parts))) {
            array_unshift($instructors, array_pop($parts));
        }
        $core = implode('_', $parts);
        $shortnamehint = preg_replace('/-{1,3}(works|broken|fixed|copy)$/i', '', $core);
        $shortnamehint = $shortnamehint === '' ? null : $shortnamehint;

        return self::result_template('backadel_modern', $raw, 0, null, null, null, null, null, $instructors, $shortnamehint);
    }

    /**
     * @param array<string, string> $m
     * @return array<string, mixed>
     */
    private static function parsed_moodle_native(string $raw, array $m): array {
        $datestamp = $m['datestamp'];
        $hhmm = $m['hhmm'];
        $y = (int) substr($datestamp, 0, 4);
        $month = (int) substr($datestamp, 4, 2);
        $day = (int) substr($datestamp, 6, 2);
        $hour = (int) substr($hhmm, 0, 2);
        $minute = (int) substr($hhmm, 2, 2);
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $hour > 23 || $minute > 59) {
            return self::result_template('moodle_native', $raw, 0, null, null, null, null, null, [], $m['shortname']);
        }
        $ts = mktime($hour, $minute, 0, $month, $day, $y);
        $backupts = ($ts !== false) ? (int) $ts : 0;

        return self::result_template(
            'moodle_native',
            $raw,
            $backupts,
            null,
            null,
            null,
            null,
            null,
            [],
            $m['shortname']
        );
    }

    /**
     * Normalise semester word to canonical catalogue form.
     * SecondFall/SecondSummer require special handling (ucfirst produces Secondfall/Secondsummer).
     */
    private static function normalize_semester(string $semester): string {
        $lower = strtolower($semester);
        $map = [
            'secondfall'   => 'SecondFall',
            'secondsummer' => 'SecondSummer',
        ];
        return $map[$lower] ?? ucfirst($lower);
    }

    /**
     * Instructor list from underscore-separated capture segment.
     *
     * @param string $capture Instructors substring or empty.
     * @return string[]
     */
    private static function instructors_from_capture(string $capture): array {
        if ($capture === '') {
            return [];
        }
        return array_values(array_filter(explode('_', $capture), static fn(string $t) => $t !== ''));
    }

    /**
     * @param string $body Lazy-matched body segment from storage patterns.
     * @return string[]
     */
    private static function body_instructors(string $body): array {
        if ($body === '') {
            return [];
        }
        return array_values(array_filter(explode('_', $body), static fn(string $t) => $t !== ''));
    }

    /**
     * Split a legacy digit run into course number and idnumber suffix (section+localid).
     *
     * LSU course numbers are always 4 digits. Any trailing digits are the section/local-id
     * suffix stored as course_idnumber. A plain 4-digit run returns empty idnumber.
     *
     * @param string $dept Department code (reserved for future scoring / logging).
     * @param string $digit_run Contiguous digits after the department in Pattern A.
     * @return array{course_num: string, course_idnumber: string}
     */
    private static function split_digit_run(string $dept, string $digitrun): array {
        $len = strlen($digitrun);
        if ($len === 0) {
            return ['course_num' => '', 'course_idnumber' => ''];
        }
        if ($len <= 4) {
            return ['course_num' => $digitrun, 'course_idnumber' => ''];
        }
        return [
            'course_num' => substr($digitrun, 0, 4),
            'course_idnumber' => substr($digitrun, 4),
        ];
    }
}
