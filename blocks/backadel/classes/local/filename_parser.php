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

require_once(__DIR__ . '/filename_pattern_library.php');

/**
 * Moodle-aware shim over {@see filename_pattern_library}.
 *
 * All parsing logic lives in {@see filename_pattern_library}, which has no
 * Moodle dependencies and can be loaded from standalone CLI tools. This class
 * preserves the public API used by the rest of the plugin (migrator, resolver,
 * import CLI, and all PHPUnit tests) and provides backward-compatible
 * PATTERN_* constant aliases.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filename_parser {

    // -------------------------------------------------------------------------
    // Backward-compatible PATTERN_* constant aliases.
    // Any downstream code or test referencing filename_parser::PATTERN_* still works.
    // -------------------------------------------------------------------------

    /** @var string {@see filename_pattern_library::PATTERN_SEMESTER_LEGACY} */
    public const PATTERN_SEMESTER_LEGACY = filename_pattern_library::PATTERN_SEMESTER_LEGACY;

    /** @var string {@see filename_pattern_library::PATTERN_SEMESTER_LEGACY_LC} */
    public const PATTERN_SEMESTER_LEGACY_LC = filename_pattern_library::PATTERN_SEMESTER_LEGACY_LC;

    /** @var string {@see filename_pattern_library::PATTERN_STORAGE_COURSE} */
    public const PATTERN_STORAGE_COURSE = filename_pattern_library::PATTERN_STORAGE_COURSE;

    /** @var string {@see filename_pattern_library::PATTERN_STORAGECOURSE_DEPT} */
    public const PATTERN_STORAGECOURSE_DEPT = filename_pattern_library::PATTERN_STORAGECOURSE_DEPT;

    /** @var string {@see filename_pattern_library::PATTERN_BACKADEL_INSTRUCTOR} */
    public const PATTERN_BACKADEL_INSTRUCTOR = filename_pattern_library::PATTERN_BACKADEL_INSTRUCTOR;

    /** @var string {@see filename_pattern_library::PATTERN_BACKADEL} */
    public const PATTERN_BACKADEL = filename_pattern_library::PATTERN_BACKADEL;

    /** @var string {@see filename_pattern_library::PATTERN_MOODLE_NATIVE} */
    public const PATTERN_MOODLE_NATIVE = filename_pattern_library::PATTERN_MOODLE_NATIVE;

    /** @var string {@see filename_pattern_library::PATTERN_FALLBACK_TS} */
    public const PATTERN_FALLBACK_TS = filename_pattern_library::PATTERN_FALLBACK_TS;

    /** @var string {@see filename_pattern_library::PATTERN_STORAGE_LEGACY} */
    public const PATTERN_STORAGE_LEGACY = filename_pattern_library::PATTERN_STORAGE_LEGACY;

    /** @var string {@see filename_pattern_library::PATTERN_SEMESTER_LEGACY_CLONE} */
    public const PATTERN_SEMESTER_LEGACY_CLONE = filename_pattern_library::PATTERN_SEMESTER_LEGACY_CLONE;

    // -------------------------------------------------------------------------
    // Public API (delegates entirely to the library).
    // -------------------------------------------------------------------------

    /**
     * Parse a backup filename into catalogue-oriented fields.
     *
     * Delegates to {@see filename_pattern_library::parse()}.
     *
     * @param string $filename Archive name or path.
     * @return array<string, mixed>|null Null when the name should not be catalogue-imported.
     */
    public static function parse(string $filename): ?array {
        return filename_pattern_library::parse($filename);
    }
}
