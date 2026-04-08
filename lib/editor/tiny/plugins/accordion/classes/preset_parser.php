<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tiny_accordion;

/**
 * Service class responsible solely for parsing, validating, and sanitising
 * accordion style preset JSON.
 *
 * Single Responsibility: this class owns the canonical definition of what a
 * valid preset array looks like. Both setting_style_presets::write_setting()
 * and plugininfo::get_plugin_configuration_for_context() delegate to it so
 * the logic lives in exactly one place.
 *
 * @package     tiny_accordion
 * @copyright   2026 LSU Online & Continuing Education
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class preset_parser {

    /** @var string[] Required and optional keys for a preset row. */
    private const PRESET_KEYS = ['label', 'detailsclass', 'detailsstyle', 'summaryclass', 'summarystyle'];

    /**
     * Parse a raw JSON string into a validated, sanitised preset array.
     *
     * Returns an indexed array of clean preset objects. Invalid or empty JSON
     * returns an empty array. Each entry is sanitised via clean_param(PARAM_TEXT).
     * Rows whose label sanitises to an empty string are silently skipped.
     *
     * @param string $json Raw config value (JSON array string or empty).
     * @return array<int, array{label: string, detailsclass: string, detailsstyle: string,
     *                          summaryclass: string, summarystyle: string}>
     */
    public function parse(string $json): array {
        $decoded = $this->decode($json);
        if ($decoded === null) {
            return [];
        }
        return $this->sanitise($decoded);
    }

    /**
     * Validate that raw data is a non-null JSON array.
     *
     * Returns null when the input is blank, '[]', or not valid JSON array syntax.
     *
     * @param string $json Raw JSON string.
     * @return array|null Decoded array, or null when input is invalid.
     */
    public function decode(string $json): ?array {
        $trimmed = trim($json);
        if ($trimmed === '' || $trimmed === '[]') {
            return null;
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return null;
        }
        return $decoded;
    }

    /**
     * Sanitise a decoded preset array with clean_param(PARAM_TEXT).
     *
     * Each row must be an array. Missing optional keys default to empty string.
     * Rows with an empty label after cleaning are skipped.
     *
     * @param array $rows Decoded (but unsanitised) preset rows.
     * @return array<int, array{label: string, detailsclass: string, detailsstyle: string,
     *                          summaryclass: string, summarystyle: string}>
     */
    public function sanitise(array $rows): array {
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = clean_param($row['label'] ?? '', PARAM_TEXT);
            if ($label === '') {
                continue;
            }
            $clean[] = [
                'label'        => $label,
                'detailsclass' => clean_param($row['detailsclass'] ?? '', PARAM_TEXT),
                'detailsstyle' => clean_param($row['detailsstyle'] ?? '', PARAM_TEXT),
                'summaryclass' => clean_param($row['summaryclass'] ?? '', PARAM_TEXT),
                'summarystyle' => clean_param($row['summarystyle'] ?? '', PARAM_TEXT),
            ];
        }
        return $clean;
    }

    /**
     * Serialise a clean preset array back to JSON for storage.
     *
     * @param array $presets Array of clean preset rows.
     * @return string JSON-encoded string.
     */
    public function serialise(array $presets): string {
        return json_encode(array_values($presets), JSON_UNESCAPED_UNICODE);
    }
}
