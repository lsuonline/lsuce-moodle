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

namespace block_backadel\local\table;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds validated WHERE fragments for course catalogue filters.
 *
 * Expects standard Backadel FROM clause aliases in SQL column expressions
 * (`co` course, `cat` category): shortname/fullname/idnumber on `co`,
 * category on `cat.name`.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class query_support {

    /** @var string[] Logical field names allowed in constraint filters. */
    public const ALLOWED_CRITERIA = ['shortname', 'fullname', 'idnumber', 'category'];

    /** @var string[] Operators supported for {@see self::where_from_constraints()}. */
    public const ALLOWED_OPERATORS = ['is', 'contains', 'startswith', 'endswith', 'isnot'];

    /**
     * Map logical fields to SQL column expressions (standard course + category join).
     *
     * @var array<string, string>
     */
    private const FIELD_SQL = [
        'shortname' => 'co.shortname',
        'fullname' => 'co.fullname',
        'idnumber' => 'co.idnumber',
        'category' => 'cat.name',
    ];

    /**
     * Build a WHERE fragment and bound parameters from structured search constraints.
     *
     * Each constraint is `['field' => string, 'operator' => string, 'value' => string]`.
     * Fragments are combined with AND. Uses {@see \moodle_database::sql_like()} with
     * {@see \moodle_database::sql_like_escape()} for all LIKE patterns.
     *
     * @param array<int, array<string, mixed>> $constraints
     * @return array{0: string, 1: array<string, mixed>} Tuple: SQL WHERE body (without WHERE keyword) and bound parameters.
     */
    public static function where_from_constraints(array $constraints): array {
        global $DB;

        if ($constraints === []) {
            return ['1=1', []];
        }

        $parts = [];
        $params = [];
        $index = 0;

        foreach ($constraints as $constraint) {
            if (!is_array($constraint)) {
                throw new \coding_exception('Invalid constraint: expected array');
            }
            $field = $constraint['field'] ?? null;
            $operator = $constraint['operator'] ?? null;
            $value = $constraint['value'] ?? null;

            if (!is_string($field) || !in_array($field, self::ALLOWED_CRITERIA, true)) {
                throw new \coding_exception('Invalid constraint field');
            }
            if (!is_string($operator) || !in_array($operator, self::ALLOWED_OPERATORS, true)) {
                throw new \coding_exception('Invalid constraint operator');
            }

            $fieldsql = self::FIELD_SQL[$field];
            $valuestr = is_string($value) ? $value : (string) $value;
            $paramname = 'sc' . $index;
            $placeholder = ':' . $paramname;

            if ($operator === 'isnot') {
                $parts[] = '(' . $fieldsql . ' <> ' . $placeholder . ')';
                $params[$paramname] = $valuestr;
            } else {
                $escaped = $DB->sql_like_escape($valuestr);
                $pattern = match ($operator) {
                    'is' => $escaped,
                    'contains' => '%' . $escaped . '%',
                    'startswith' => $escaped . '%',
                    'endswith' => '%' . $escaped,
                    default => throw new \coding_exception('Invalid constraint operator'),
                };
                $parts[] = $DB->sql_like($fieldsql, $placeholder, false, false, false);
                $params[$paramname] = $pattern;
            }

            $index++;
        }

        return [implode(' AND ', $parts), $params];
    }
}
