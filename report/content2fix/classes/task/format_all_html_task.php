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
// GNU General Public License for the terms and conditions.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_content2fix\task;

use report_content2fix\local\html_formatter;
use report_content2fix\reportbuilder\local\systemreports\malformed_content_report;
use core_reportbuilder\system_report_factory;
use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc task: format HTML in all entries stored in report_content2fix
 * using clean_text with FORMAT_HTML, then persist back to source tables.
 *
 * When customdata contains 'filtervalues', only entries matching those filters are processed.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_all_html_task extends \core\task\adhoc_task {

    /**
     * Execute: process report_content2fix entries and format their HTML.
     */
    public function execute(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            return;
        }

        $customdata = $this->get_custom_data();
        $filtervalues = ($customdata && isset($customdata->filtervalues))
            ? (array) $customdata->filtervalues
            : [];
        $entries = $this->get_entries($filtervalues);

        foreach ($entries as $entry) {
            html_formatter::format_and_persist_entry($entry);
        }
    }

    /**
     * Get report_content2fix entries, optionally filtered using the report's filter logic.
     *
     * @param array $filtervalues Report filter values (from user_filter_manager::get)
     * @return \stdClass[]
     */
    protected function get_entries(array $filtervalues): array {
        $report = system_report_factory::create(
            malformed_content_report::class,
            context_system::instance(),
            '',
            '',
            0,
            []
        );

        return $report->get_filtered_entries($filtervalues);
    }
}
