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
 * Shared allowlists for catalogue filters and WHERE builders.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Single source of truth for catalogue filter domain values (excluding empty = any).
 */
final class catalogue_allowlists {

    /** @var string[] */
    public const VALID_PATTERNS = [
        'semester_legacy',
        'semester_legacy_lc',
        'semester_legacy_clone',
        'storage_course',
        'storagecourse_dept',
        'storage_legacy',
        'backadel_modern',
        'backadel_instructor',
        'moodle_native',
        'unknown',
    ];

    /** @var string[] */
    public const VALID_SOURCES = [
        'backadel_current',
        'legacy_moodleus',
        'legacy_openlms',
    ];

    /** @var string[] */
    public const VALID_COURSETYPES = [
        'teaching',
        'blueprint',
        'other',
        'undetermined',
    ];

    /** @var string[] */
    public const VALID_STATUSES = [
        'none',
        'available',
        'missing',
        'archived',
    ];
}
