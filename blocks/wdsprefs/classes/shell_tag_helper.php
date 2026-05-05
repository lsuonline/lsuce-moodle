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
 * Shell tag validation and normalization for cross-split / team-teaching shells.
 *
 * @package    block_wdsprefs
 * @copyright  2026 onwards Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_wdsprefs;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for shell tag validation and normalization.
 */
class shell_tag_helper {

    /**
     * Validates shell tag format (letters, numbers, dashes, underscores, spaces).
     *
     * @param string $tag The shell tag to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_format($tag) {
        if ($tag === '') {
            return true;
        }
        return (bool) preg_match('/^[a-zA-Z0-9_ -]+$/', $tag);
    }

    /**
     * Normalizes shell tag by trimming and truncating to max length.
     *
     * @param string $tag The shell tag to normalize
     * @param int $maxlength Maximum length (default 64)
     * @return string Normalized shell tag
     */
    public static function normalize($tag, $maxlength = 64) {
        $normalized = trim($tag);
        return \core_text::substr($normalized, 0, $maxlength);
    }

    /**
     * Validates and normalizes a shell tag in one step.
     *
     * @param string $tag The shell tag to validate and normalize
     * @param int $maxlength Maximum length (default 64)
     * @return string Normalized shell tag
     */
    public static function validate_and_normalize($tag, $maxlength = 64) {
        return self::normalize($tag, $maxlength);
    }

    /**
     * Returns duplicate tag values (normalized) from a list.
     *
     * @param array $tags Tag values (keys may be field names)
     * @return array Duplicate normalized tag values
     */
    public static function find_duplicates($tags) {
        $duplicates = [];
        $tagcounts = [];

        foreach ($tags as $tag) {
            $normalized = self::normalize($tag);
            if ($normalized !== '') {
                $tagcounts[$normalized] = ($tagcounts[$normalized] ?? 0) + 1;
            }
        }

        foreach ($tagcounts as $tag => $count) {
            if ($count > 1) {
                $duplicates[] = $tag;
            }
        }

        return $duplicates;
    }

    /**
     * Whether the normalized tag is in the unavailable list.
     *
     * @param string $tag The tag to check
     * @param array $unavailabletags Unavailable normalized tag values
     * @return bool True if unavailable
     */
    public static function is_unavailable($tag, $unavailabletags) {
        $normalized = self::normalize($tag);
        return in_array($normalized, $unavailabletags, true);
    }
}
