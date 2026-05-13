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
 * Determines high-level catalogue / restore course classification from parsed filenames.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_type_resolver {

    /**
     * Classify backup intent as teaching, blueprint, or other.
     *
     * Precedence: teaching → blueprint → other (per MEETING.md §3 / Robert 01:53:30).
     *
     * @param array<string, mixed> $parsed Output of {@see filename_parser::parse()} (never null callers).
     * @return string One of teaching, blueprint, other.
     */
    public static function resolve(array $parsed): string {
        $pattern = (string) ($parsed['pattern'] ?? '');
        $year = isset($parsed['year']) ? (int) $parsed['year'] : null;
        $semester = isset($parsed['semester']) && $parsed['semester'] !== null
            ? (string) $parsed['semester']
            : '';
        $dept = isset($parsed['dept']) && $parsed['dept'] !== null ? (string) $parsed['dept'] : '';

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

        // Blueprint keyword detection (meeting spec: master, master-course, materials course, etc.).
        $hint = strtolower((string) ($parsed['shortname_hint'] ?? ''));
        $rawname = strtolower(basename((string) ($parsed['raw_filename'] ?? '')));
        foreach (self::get_blueprint_keywords() as $keyword) {
            $kw = strtolower($keyword);
            if (
                ($hint !== '' && strpos($hint, $kw) !== false)
                || ($rawname !== '' && strpos($rawname, $kw) !== false)
                || self::slug_match($hint, $kw)
                || self::slug_match($rawname, $kw)
            ) {
                return 'blueprint';
            }
        }

        return 'other';
    }

    /**
     * Default blueprint detection keywords aligned with MEETING.md §3 (Robert 1:34:53).
     *
     * @return string[]
     */
    public static function get_default_blueprint_keywords(): array {
        return [
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
        ];
    }

    /**
     * Returns the default keyword list as a newline-delimited string (for admin settings default value).
     */
    public static function get_default_blueprint_keywords_text(): string {
        return implode("\n", static::get_default_blueprint_keywords());
    }

    /**
     * Returns configured keywords from plugin settings, falling back to defaults.
     *
     * @return string[]
     */
    protected static function get_blueprint_keywords(): array {
        $config = get_config('block_backadel', 'blueprint_keywords');
        if (!empty($config)) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $config))));
            if (!empty($lines)) {
                return $lines;
            }
        }
        return static::get_default_blueprint_keywords();
    }

    /**
     * Collapse spaces, hyphens, and underscores for slug-style comparison.
     */
    private static function slugify(string $s): string {
        return str_replace([' ', '_', '-'], '', strtolower($s));
    }

    /**
     * Returns true if slugified $haystack contains slugified $needle.
     */
    private static function slug_match(string $haystack, string $needle): bool {
        if ($haystack === '' || $needle === '') {
            return false;
        }
        return strpos(self::slugify($haystack), self::slugify($needle)) !== false;
    }
}
