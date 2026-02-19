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

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc task: format HTML in all entries stored in report_content2fix
 * using clean_text with FORMAT_HTML, then persist back to source tables.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_all_html_task extends \core\task\adhoc_task {

    /**
     * Execute: process all report_content2fix entries and format their HTML.
     */
    public function execute(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            return;
        }

        $entries = $DB->get_records('report_content2fix');
        foreach ($entries as $entry) {
            html_formatter::format_and_persist_entry($entry);
        }
    }
}
