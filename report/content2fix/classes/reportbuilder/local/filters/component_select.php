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

namespace report_content2fix\reportbuilder\local\filters;

use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\database;

defined('MOODLE_INTERNAL') || die();

/**
 * Component filter with "Course page" option that matches core_course and core_section.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class component_select extends select {

    /** Course page virtual value - matches core_course and core_section. */
    public const VALUE_COURSEPAGE = 'coursepage';

    /**
     * Return filter SQL, handling coursepage as component IN (core_course, core_section).
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        $operator = (int) ($values["{$this->name}_operator"] ?? self::ANY_VALUE);
        $value = $values["{$this->name}_value"] ?? '';

        if ($operator === self::ANY_VALUE || $value === '') {
            return ['', []];
        }

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        if ($value === self::VALUE_COURSEPAGE) {
            $param1 = database::generate_param_name();
            $param2 = database::generate_param_name();
            $params[$param1] = 'core_course';
            $params[$param2] = 'core_section';
            $sql = "{$fieldsql} IN (:$param1, :$param2)";
            return [$sql, $params];
        }

        $param = database::generate_param_name();
        $params[$param] = $value;
        if ($operator === self::EQUAL_TO) {
            return ["{$fieldsql} = :$param", $params];
        }
        return ["{$fieldsql} <> :$param", $params];
    }
}
