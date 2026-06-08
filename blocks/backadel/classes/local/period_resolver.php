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

/**
 * Maps academic year plus semester phrases to LSU_AM_* folder identifiers.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class period_resolver {

    /**
     * Best-effort academic period slug for live Backadel backups (`LSU_AM_*`) from visible course identifiers.
     *
     * Parses year + semester fragments from {@see self::haystack_sources()} via filename-style tokens; falls back
     * to Moodle course {@see \stdClass::$startdate} when no explicit term is embedded.
     */
    public static function for_course(\stdClass $course): ?string {
        $resolver = new self();
        [$year, $semester] = self::infer_year_semester($course);

        return $resolver->resolve($year, $semester);
    }

    /**
     * Extract the bare four-digit academic year for a course (MD-2189 Bug-082).
     *
     * Returns 0 when year cannot be determined (no idnumber/shortname/fullname
     * token and no usable startdate).
     *
     * @param \stdClass $course Moodle course record (needs idnumber, shortname, fullname, startdate).
     * @return int Four-digit year, or 0 if unknown.
     */
    public static function year_for_course(\stdClass $course): int {
        [$year, ] = self::infer_year_semester($course);
        return $year;
    }

    /** @var array<string, string> Title-case semester keyword to folder token.
     *
     * Keys must match the post-`ucfirst(strtolower())` form of the semester string emitted
     * by {@see filename_pattern_library::normalize_semester()} (e.g. `Springint`, not
     * `SpringInt`) — see {@see self::abbr_from_semester()} for the casing pipeline. The
     * international-session variants (bug-043) round-trip into `LSU_AM_springint_<year>`. */
    private const SEM_ABBR = [
        'Spring' => 'spring',
        'Summer' => 'summer',
        'Fall' => 'fall',
        'Winter' => 'winter',
        'Secondfall' => 'secondfall',
        'Secondsummer' => 'secondsummer',
        'Springint' => 'springint',
        'Summerint' => 'summerint',
        'Fallint' => 'fallint',
        'Winterint' => 'winterint',
    ];

    /**
     * Resolve or synthesise LSU_AM_* folder slug for archiving.
     *
     * Looks up {@see block_backadel_periods} by canonical folder naming; falls back to the same
     * slug when administrators have not seeded the periods table yet.
     *
     * @param int $year Four-digit Gregorian year extracted from filenames.
     * @param string $semester Semester phrase (typically Spring Summer Fall Winter variants).
     * @return string|null Folder slug like LSU_AM_spring_2026, or null when inputs are unusable.
     */
    public function resolve(int $year, string $semester): ?string {
        global $DB;

        if ($year <= 0 || trim($semester) === '') {
            return null;
        }

        $abbr = self::abbr_from_semester($semester);
        if ($abbr === '') {
            return null;
        }

        $folder = sprintf('LSU_AM_%s_%d', $abbr, $year);
        $record = $DB->get_record('block_backadel_periods', ['folder' => $folder]);
        if ($record !== false && isset($record->folder)) {
            return (string) $record->folder;
        }

        return $folder;
    }

    /**
     * Human semester label derived from structured parse output for UI display.
     *
     * @param array<string, mixed> $parsed {@see filename_parser::parse()}
     * @return string Localised fallback when pedagogical cues are absent.
     */
    public function label_from_parsed(array $parsed): string {
        $year = isset($parsed['year']) ? (int) $parsed['year'] : 0;
        $semester = isset($parsed['semester']) && $parsed['semester'] !== null
            ? trim((string) $parsed['semester'])
            : '';

        if ($year > 0 && $semester !== '') {
            return $semester . ' ' . $year;
        }

        return get_string('unknown_period', 'block_backadel');
    }

    /**
     * @return array{0: int, 1: string} Year plus semester phrase for {@see self::resolve()}.
     */
    private static function infer_year_semester(\stdClass $course): array {
        $haystack = self::haystack_sources($course);
        // Longer literals (SecondFall/SecondSummer + the optional INTL/Int suffix) precede
        // shorter ones so PCRE alternation picks the most specific match (bug-043).
        if (
            preg_match(
                '/(?P<year>\d{4})' .
                '(?P<semester>SecondFall|SecondSummer|secondfall|secondsummer|' .
                '(?:Spring|Summer|Fall|Winter|spring|summer|fall|winter)(?:INTL|Int|intl|int)?)' .
                '/',
                $haystack,
                $m
            ) === 1
        ) {
            return [(int) $m['year'], (string) $m['semester']];
        }

        $start = isset($course->startdate) ? (int) $course->startdate : 0;
        if ($start <= 0) {
            return [0, ''];
        }

        $year = (int) gmdate('Y', $start);
        $month = (int) gmdate('n', $start);

        // Approximate LSU-style mapping from calendar month (courses without embedded term tokens).
        if ($month <= 5) {
            return [$year, 'Spring'];
        }
        if ($month <= 8) {
            return [$year, 'Summer'];
        }

        return [$year, 'Fall'];
    }

    private static function haystack_sources(\stdClass $course): string {
        $parts = [];

        foreach (['idnumber', 'shortname', 'fullname'] as $f) {
            if (isset($course->$f) && is_string($course->$f) && $course->$f !== '') {
                $parts[] = $course->$f;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Convert flexible semester casing to abbreviated folder slug fragment.
     *
     * Returns empty string when the input cannot be mapped to a known semester.
     */
    private static function abbr_from_semester(string $semester): string {
        // Tolerate the canonical normalised form (`SpringInt`) as well as raw filename
        // tokens like `SpringINTL` / `springint` by collapsing to the lowercase letter
        // run, mapping `*intl` to `*int` (the canonical folder slug suffix), then
        // checking the whitelist (bug-043).
        $title = ucfirst(strtolower(trim($semester)));
        if (isset(self::SEM_ABBR[$title])) {
            return self::SEM_ABBR[$title];
        }
        $clean = strtolower((string) preg_replace('/[^a-zA-Z]/i', '', $semester));
        if ($clean !== '' && str_ends_with($clean, 'intl')) {
            $clean = substr($clean, 0, -1);
        }
        $whitelist = [
            'spring', 'summer', 'fall', 'winter', 'secondfall', 'secondsummer',
            'springint', 'summerint', 'fallint', 'winterint',
        ];
        return in_array($clean, $whitelist, true) ? $clean : '';
    }
}
