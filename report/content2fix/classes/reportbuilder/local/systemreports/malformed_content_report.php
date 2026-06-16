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

namespace report_content2fix\reportbuilder\local\systemreports;

use context_system;
use report_content2fix\reportbuilder\local\entities\malformed_content as malformed_content_entity;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use html_writer;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Malformed content system report.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class malformed_content_report extends system_report {

    /**
     * Initialise report: main table, entities, columns, filters.
     */
    protected function initialise(): void {
        $entitymain = new malformed_content_entity();
        $entitymainalias = $entitymain->get_table_alias('report_content2fix');

        $this->set_main_table('report_content2fix', $entitymainalias);
        $this->add_entity($entitymain);

        $this->add_base_fields("{$entitymainalias}.id");

        // Join course entity for course name column and course filter.
        $entitycourse = new course();
        $coursealias = $entitycourse->get_table_alias('course');
        $this->add_entity($entitycourse->add_join(
            "LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$entitymainalias}.courseid"
        ));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true, get_string('pluginname', 'report_content2fix'));
    }

    /**
     * Validates access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('report/content2fix:view', context_system::instance());
    }

    /**
     * Get the visible name of the report.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('pluginname', 'report_content2fix');
    }

    /**
     * Add columns to the report.
     */
    protected function add_columns(): void {
        $entitymainalias = $this->get_entity('malformed_content')->get_table_alias('report_content2fix');

        $this->add_columns_from_entities([
            'malformed_content:component',
            'malformed_content:courseid',
            'malformed_content:cmid',
            'course:fullname',
            'malformed_content:activity',
            'malformed_content:field',
            'malformed_content:summary',
            'malformed_content:timechecked',
            'malformed_content:link',
        ]);

        // Replace course fullname with link to course view where we have courseid.
        $column = $this->get_column('course:fullname');
        if ($column) {
            $column->add_field("{$entitymainalias}.courseid")
                ->add_callback(static function(string $fullname, stdClass $row): string {
                    if (empty($row->courseid)) {
                        return get_string('unknown', 'report_content2fix');
                    }
                    $url = new moodle_url('/course/view.php', ['id' => $row->courseid]);
                    return html_writer::link($url, s(format_string($fullname)));
                });
        }

        $this->set_initial_sort_column('malformed_content:timechecked', SORT_DESC);
    }

    /**
     * Add per-row actions: Format HTML with Backend, Format with TinyMCE.
     */
    protected function add_actions(): void {
        $canfix = $this->get_parameter('canfix', false, PARAM_BOOL);
        if (!$canfix) {
            return;
        }

        $backendurl = new moodle_url('/report/content2fix/index.php', [
            'action' => 'fixone',
            'id' => ':id',
            'sesskey' => sesskey(),
        ]);

        $this->add_action(new action(
            $backendurl,
            new pix_icon('t/edit', get_string('fixformatbackend', 'report_content2fix')),
            [],
            false,
            new lang_string('fixformatbackend', 'report_content2fix')
        ));

        $tinymceurl = new moodle_url('#');
        $this->add_action(new action(
            $tinymceurl,
            new pix_icon('i/tinymce', get_string('fixformattinymce', 'report_content2fix'), 'report_content2fix'),
            [
                'data-action' => 'format-tinymce',
                'data-entryid' => ':id',
            ],
            false,
            new lang_string('fixformattinymce', 'report_content2fix')
        ));
    }

    /**
     * Add filters: course fullname (text), component (activity type), time checked.
     */
    protected function add_filters(): void {
        $this->add_filters_from_entities([
            'course:fullname',
            'malformed_content:component',
            'malformed_content:timechecked',
        ]);
    }

    /**
     * Return report_content2fix entries matching the given filter values.
     * Uses the same filter logic as the report table.
     *
     * @param array $filtervalues Filter values (e.g. from user_filter_manager::get)
     * @return \stdClass[]
     */
    public function get_filtered_entries(array $filtervalues): array {
        global $DB;

        if (empty($filtervalues)) {
            return $DB->get_records('report_content2fix');
        }

        [$where, $params] = $this->build_filter_sql($filtervalues);

        $mainalias = $this->get_main_table_alias();
        $entitycourse = $this->get_entity('course');
        $coursealias = $entitycourse->get_table_alias('course');

        $sql = "SELECT {$mainalias}.*
                  FROM {" . $this->get_main_table() . "} {$mainalias}
             LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$mainalias}.courseid
                 WHERE {$where}
              ORDER BY {$mainalias}.id ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get the next report_content2fix entry after the given ID, matching filters.
     * Uses the same filter logic and ordering as get_filtered_entries.
     *
     * @param array $filtervalues Filter values (e.g. from user_filter_manager::get)
     * @param int $afterid ID of the last processed entry (0 for first)
     * @return stdClass|null The next entry, or null if none
     */
    public function get_next_filtered_entry(array $filtervalues, int $afterid): ?stdClass {
        global $DB;

        [$where, $params] = $this->build_filter_sql($filtervalues);
        $mainalias = $this->get_main_table_alias();
        $entitycourse = $this->get_entity('course');
        $coursealias = $entitycourse->get_table_alias('course');

        $params['content2fix_afterid'] = $afterid;
        $idcondition = $afterid > 0 ? "AND {$mainalias}.id > :content2fix_afterid" : "";

        $sql = "SELECT {$mainalias}.*
                  FROM {" . $this->get_main_table() . "} {$mainalias}
             LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$mainalias}.courseid
                 WHERE {$where} {$idcondition}
              ORDER BY {$mainalias}.id ASC";

        $record = $DB->get_record_sql($sql, $params);
        return $record ?: null;
    }

    /**
     * Get summary stats for entries matching report filters.
     *
     * @param array $filtervalues Filter values (e.g. from user_filter_manager::get)
     * @return stdClass Contains entrycount and distinctcoursecount.
     */
    public function get_filtered_entry_summary(array $filtervalues): stdClass {
        global $DB;

        [$where, $params] = $this->build_filter_sql($filtervalues);
        $mainalias = $this->get_main_table_alias();
        $entitycourse = $this->get_entity('course');
        $coursealias = $entitycourse->get_table_alias('course');

        $sql = "SELECT COUNT({$mainalias}.id) AS entrycount,
                       COUNT(DISTINCT {$mainalias}.courseid) AS distinctcoursecount
                  FROM {" . $this->get_main_table() . "} {$mainalias}
             LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$mainalias}.courseid
                 WHERE {$where}";

        return $DB->get_record_sql($sql, $params) ?: (object) [
            'entrycount' => 0,
            'distinctcoursecount' => 0,
        ];
    }

    /**
     * Count report_content2fix entries matching filters with id strictly greater than $afterid.
     * Used to determine whether more entries remain after the one just fetched.
     *
     * @param array $filtervalues Filter values (e.g. from user_filter_manager::get)
     * @param int $afterid Count only entries with id > this value
     * @return int Number of remaining entries
     */
    public function get_remaining_entry_count(array $filtervalues, int $afterid): int {
        global $DB;

        [$where, $params] = $this->build_filter_sql($filtervalues);
        $mainalias = $this->get_main_table_alias();
        $entitycourse = $this->get_entity('course');
        $coursealias = $entitycourse->get_table_alias('course');

        $params['content2fix_afterid'] = $afterid;

        $sql = "SELECT COUNT({$mainalias}.id)
                  FROM {" . $this->get_main_table() . "} {$mainalias}
             LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$mainalias}.courseid
                 WHERE {$where} AND {$mainalias}.id > :content2fix_afterid";

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Build SQL WHERE and params for the report's filters.
     *
     * @param array $filtervalues
     * @return array [string $where, array $params]
     */
    protected function build_filter_sql(array $filtervalues): array {
        $wheres = [];
        $params = [];
        $filterindex = 0;

        foreach ($this->get_active_filters() as $filter) {
            $filterclass = $filter->get_filter_class();
            $filterinstance = $filterclass::create($filter);
            [$filtersql, $filterparams] = $filterinstance->get_sql_filter($filtervalues);

            if ($filtersql !== '') {
                $paramprefix = 'f' . $filterindex++;
                if (!empty($filterparams)) {
                    [$filtersql, $filterparams] = \core_reportbuilder\local\helpers\database::sql_replace_parameters(
                        $filtersql,
                        $filterparams,
                        fn(string $param) => "{$paramprefix}_{$param}",
                    );
                }
                $wheres[] = "({$filtersql})";
                $params = array_merge($params, $filterparams);
            }
        }

        $where = empty($wheres) ? '1=1' : implode(' AND ', $wheres);
        return [$where, $params];
    }
}
