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
use core_reportbuilder\system_report;
use html_writer;
use moodle_url;
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
     * Add filters: course, component (activity type), time checked.
     */
    protected function add_filters(): void {
        $this->add_filters_from_entities([
            'course:courseselector',
            'malformed_content:component',
            'malformed_content:timechecked',
        ]);
    }
}
