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
use report_content2fix\local\format_helper;
use report_content2fix\local\report_filter_values;
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

    /** Maximum totalcount to return (avoids huge payloads and UI issues). */
    private const MAX_TOTALCOUNT = 9999;


    /**
     * Parameters for get_filter_values.
     *
     * @return external_function_parameters
     */
    public static function get_filter_values_parameters(): external_function_parameters {
        return new external_function_parameters([
            'reportid' => new external_value(PARAM_INT, 'Report builder report id (persistent id)'),
        ]);
    }

    /**
     * Get current user's filter values for the content2fix report.
     * Use this when starting bulk format so filters are up to date (e.g. after applying filters without page reload).
     *
     * @param int $reportid Report persistent id
     * @return array Array of {name, value} for use with get_next_filtered_entry
     */
    public static function get_filter_values(int $reportid): array {
        $params = self::validate_parameters(self::get_filter_values_parameters(), ['reportid' => $reportid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/content2fix:fix', $context);

        $filtervalues = \core_reportbuilder\local\helpers\user_filter_manager::get((int) $params['reportid']);
        $result = [];
        foreach ($filtervalues as $k => $v) {
            $result[] = [
                'name' => $k,
                'value' => is_array($v) ? json_encode($v) : (string) $v,
            ];
        }
        return $result;
    }

    /**
     * Return structure for get_filter_values.
     *
     * @return external_multiple_structure
     */
    public static function get_filter_values_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'name' => new external_value(PARAM_RAW, 'Filter name'),
                'value' => new external_value(PARAM_RAW, 'Filter value'),
            ])
        );
    }

    /**
     * Parameters for get_filtered_entry_count.
     * Accepts the same filter payload as the report filter form / get_next_filtered_entry.
     *
     * @return external_function_parameters
     */
    public static function get_filtered_entry_count_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtervalues' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_RAW, 'Filter name (e.g. course:fullname_operator)'),
                    'value' => new external_value(PARAM_RAW, 'Filter value'),
                ]),
                'Filter values (same format as report filter form / get_next_filtered_entry)'
            ),
        ]);
    }

    /**
     * Return the number of entries matching the given filter values.
     * Used to show an accurate count in the bulk-format confirmation when using form-derived filters.
     *
     * @param array $filtervalues Array of {name, value} pairs (same as get_next_filtered_entry)
     * @return array{entrycount: int, distinctcoursecount: int}
     */
    public static function get_filtered_entry_count(array $filtervalues): array {
        $params = self::validate_parameters(self::get_filtered_entry_count_parameters(), [
            'filtervalues' => $filtervalues,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/content2fix:fix', $context);

        $filtervalues = report_filter_values::from_request($params['filtervalues']);
        $filtermap = $filtervalues->to_flat_map();

        $report = system_report_factory::create(
            malformed_content_report::class,
            $context,
            '',
            '',
            0,
            ['canfix' => true]
        );

        $summary = $report->get_filtered_entry_summary($filtermap);
        $entrycount = min((int) $summary->entrycount, self::MAX_TOTALCOUNT);

        return [
            'entrycount' => $entrycount,
            'distinctcoursecount' => (int) $summary->distinctcoursecount,
        ];
    }

    /**
     * Return structure for get_filtered_entry_count.
     *
     * @return external_single_structure
     */
    public static function get_filtered_entry_count_returns(): external_single_structure {
        return new external_single_structure([
            'entrycount' => new external_value(PARAM_INT, 'Number of entries matching filters'),
            'distinctcoursecount' => new external_value(PARAM_INT, 'Number of distinct courses in result'),
        ]);
    }

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

        $filtervalues = report_filter_values::from_request($params['filtervalues']);
        $filtermap = $filtervalues->to_flat_map();

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
        $totalcount = (int) $summary->entrycount;
        $totalcount = min($totalcount, self::MAX_TOTALCOUNT);

        $result = [
            'totalcount' => $totalcount,
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

    /**
     * Parameters for queue_format_all_task.
     *
     * @return external_function_parameters
     */
    public static function queue_format_all_task_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtervalues' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_RAW, 'Filter name (e.g. course:fullname_operator)'),
                    'value' => new external_value(PARAM_RAW, 'Filter value'),
                ]),
                'Filter values (same format as confirmation modal / get_filtered_entry_count)'
            ),
        ]);
    }

    /**
     * Queue the ad-hoc task to format HTML for entries matching the given filters.
     * Uses the same filter normalisation as get_filtered_entry_count / get_next_filtered_entry.
     *
     * @param array $filtervalues Array of {name, value} pairs (same as confirmation modal)
     * @return array{queued: bool}
     */
    public static function queue_format_all_task(array $filtervalues): array {
        $params = self::validate_parameters(self::queue_format_all_task_parameters(), [
            'filtervalues' => $filtervalues,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/content2fix:fix', $context);

        $filtervaluesobj = report_filter_values::from_request($params['filtervalues']);
        $filtermap = $filtervaluesobj->to_flat_map();

        $report = system_report_factory::create(
            malformed_content_report::class,
            $context,
            '',
            '',
            0,
            ['canfix' => true]
        );
        $summary = $report->get_filtered_entry_summary($filtermap);

        $canqueue = format_helper::can_queue_filtered_format(
            is_siteadmin(),
            has_capability('report/content2fix:fixfiltered', $context),
            (int) $summary->distinctcoursecount
        );
        if (!$canqueue) {
            throw new \moodle_exception('fixformatall_unavailable', 'report_content2fix');
        }

        format_helper::queue_filtered_format_task($filtermap);

        return ['queued' => true];
    }

    /**
     * Return structure for queue_format_all_task.
     *
     * @return external_single_structure
     */
    public static function queue_format_all_task_returns(): external_single_structure {
        return new external_single_structure([
            'queued' => new external_value(PARAM_BOOL, 'Whether the task was queued'),
        ]);
    }
}
