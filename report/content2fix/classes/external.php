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
// along with Moodle.  If not, see <http://moodle.org/licenses/>.

namespace report_content2fix;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_reportbuilder\system_report_factory;
use report_content2fix\reportbuilder\local\systemreports\malformed_content_report;

defined('MOODLE_INTERNAL') || die();

/**
 * External API for report_content2fix.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Parameters for get_next_filtered_entry.
     *
     * @return external_function_parameters
     */
    public static function get_next_filtered_entry_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtervalues' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_RAW, 'Filter name (e.g. course:fullname)'),
                    'value' => new external_value(PARAM_RAW, 'Filter value'),
                ]),
                'Filter values from the report'
            ),
            'afterid' => new external_value(PARAM_INT, 'ID of last processed entry (0 for first)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get the next report_content2fix entry after the given ID, matching the report filters.
     *
     * @param array $filtervalues Array of {name, value} pairs
     * @param int $afterid ID of last processed entry
     * @return array{entry: object|null, totalcount: int}
     */
    public static function get_next_filtered_entry(array $filtervalues, int $afterid): array {
        $params = self::validate_parameters(self::get_next_filtered_entry_parameters(), [
            'filtervalues' => $filtervalues,
            'afterid' => $afterid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/content2fix:fix', $context);

        $filtermap = [];
        foreach ($params['filtervalues'] as $fv) {
            $value = $fv['value'];
            if (is_string($value) && (str_starts_with(trim($value), '[') || str_starts_with(trim($value), '{'))) {
                $decoded = json_decode($value, true);
                $filtermap[$fv['name']] = $decoded !== null ? $decoded : $value;
            } else {
                $filtermap[$fv['name']] = $value;
            }
        }

        $report = system_report_factory::create(
            malformed_content_report::class,
            $context,
            '',
            '',
            0,
            ['canfix' => true]
        );

        $entry = $report->get_next_filtered_entry($filtermap, (int) $params['afterid']);
        $summary = $report->get_filtered_entry_summary($filtermap);

        $result = [
            'entry' => null,
            'totalcount' => (int) $summary->entrycount,
        ];

        if ($entry) {
            $result['entry'] = (object) [
                'id' => (int) $entry->id,
                'component' => $entry->component ?? '',
                'courseid' => (int) ($entry->courseid ?? 0),
                'cmid' => (int) ($entry->cmid ?? 0),
                'activity' => $entry->activity ?? '',
                'field' => $entry->field ?? '',
            ];
        }

        return $result;
    }

    /**
     * Return structure for get_next_filtered_entry.
     *
     * @return external_single_structure
     */
    public static function get_next_filtered_entry_returns(): external_single_structure {
        return new external_single_structure([
            'entry' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Entry ID'),
                'component' => new external_value(PARAM_RAW, 'Component'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'activity' => new external_value(PARAM_RAW, 'Activity name'),
                'field' => new external_value(PARAM_RAW, 'Field name'),
            ], 'Next entry to process', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'Total entries matching filters'),
        ]);
    }
}
