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

namespace report_content2fix\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Value object for report filter values used by bulk format (TinyMCE, ad-hoc task, web service).
 *
 * Normalises filter payloads from the confirmation modal / report builder form (including
 * group-style keys like "course:fullname_group[course:fullname_operator]") into a flat
 * key-value map suitable for malformed_content_report::build_filter_sql.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_filter_values {

    /** @var array Flat map: filter key => value (for reportbuilder filters) */
    private $flatmap;

    /**
     * Constructor.
     *
     * @param array $flatmap Normalised flat key => value map
     */
    private function __construct(array $flatmap) {
        $this->flatmap = $flatmap;
    }

    /**
     * Build from request payload (e.g. web service): array of {name, value}.
     *
     * @param array $filtervalues Each element has 'name' and 'value' (string; value may be JSON)
     * @return self
     */
    public static function from_request(array $filtervalues): self {
        $filtermap = [];
        foreach ($filtervalues as $fv) {
            $name = $fv['name'] ?? $fv['key'] ?? '';
            $value = $fv['value'] ?? '';
            if (is_string($value) && (str_starts_with(trim($value), '[') || str_starts_with(trim($value), '{'))) {
                $decoded = json_decode($value, true);
                $filtermap[$name] = $decoded !== null ? $decoded : $value;
            } else {
                $filtermap[$name] = $value;
            }
        }
        return new self(self::normalise_filter_map($filtermap));
    }

    /**
     * Build from a flat key => value map (e.g. user_filter_manager::get).
     *
     * @param array $flatmap Associative array of filter key => value
     * @return self
     */
    public static function from_flat_map(array $flatmap): self {
        return new self(self::normalise_filter_map($flatmap));
    }

    /**
     * Normalise filter map so keys match what reportbuilder filters expect.
     * Form group elements submit as "groupname[flatkey]"; we flatten to flatkey.
     *
     * @param array $filtermap Raw map (key => value)
     * @return array Normalised flat map
     */
    private static function normalise_filter_map(array $filtermap): array {
        $normalised = [];
        foreach ($filtermap as $key => $value) {
            if (preg_match('/^[^\[]+\[([^\]]+)\]$/', (string) $key, $m)) {
                $normalised[$m[1]] = $value;
            } else {
                $normalised[$key] = $value;
            }
        }
        return $normalised;
    }

    /**
     * Return the flat map for use with report's get_filtered_entries / build_filter_sql.
     *
     * @return array Flat key => value
     */
    public function to_flat_map(): array {
        return $this->flatmap;
    }
}
