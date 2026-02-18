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

namespace report_content2fix\task;

use report_content2fix\local\html_scanner;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task: scan HTML fields in mod_* activities and store malformed entries in report_content2fix table.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scan_malformed_html_task extends \core\task\scheduled_task {

    /**
     * Name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_scan_malformed_html', 'report_content2fix');
    }

    /**
     * Execute the scan: clear previous results and repopulate report_content2fix from current content.
     */
    public function execute(): void {
        global $DB;

        $timerecorded = time();

        if (!$DB->get_manager()->table_exists('report_content2fix')) {
            return;
        }

        $DB->delete_records('report_content2fix');
        $sources = html_scanner::get_html_content_sources();

        foreach ($sources as $source) {
            $this->scan_source($source, $timerecorded);
        }
    }

    /**
     * Scan one (component, table, field) for malformed HTML and insert into report_content2fix.
     *
     * @param array $source keys: component, table, field, formatfield
     * @param int $timerecorded
     */
    protected function scan_source(array $source, int $timerecorded): void {
        global $DB;

        $table = $source['table'];
        $field = $source['field'];
        $formatfield = $source['formatfield'];
        $component = $source['component'];
        $modname = str_replace('mod_', '', $component);

        $coursefield = 'course';
        if ($table === 'resource') {
            $coursefield = 'course';
        }
        if (!$DB->get_manager()->field_exists($table, $coursefield)) {
            return;
        }

        $rs = $DB->get_recordset_select(
            $table,
            $formatfield . ' = ? AND ' . $DB->sql_isnotempty($table, $field, false, false),
            [FORMAT_HTML],
            $coursefield . ', id',
            'id, ' . $coursefield . ', ' . $field
        );

        foreach ($rs as $row) {
            $html = $row->$field;
            if (!is_string($html) || $html === '') {
                continue;
            }
            if (html_scanner::is_malformed_html($html)) {
                $cmid = html_scanner::get_cmid_for_instance($modname, (int) $row->id);
                $summary = $this->summarise_issue($html);
                $DB->insert_record('report_content2fix', (object) [
                    'component' => $component,
                    'comptable' => $table,
                    'compfield' => $field,
                    'rowid' => (int) $row->id,
                    'courseid' => (int) $row->$coursefield,
                    'cmid' => $cmid,
                    'summary' => $summary,
                    'timechecked' => $timerecorded,
                ]);
            }
        }
        $rs->close();
    }

    /**
     * Build a short summary string for the malformed content (e.g. first 200 chars, stripped).
     *
     * @param string $html
     * @return string
     */
    protected function summarise_issue(string $html): string {
        $stripped = strip_tags($html);
        $stripped = preg_replace('/\s+/', ' ', $stripped);
        $stripped = trim($stripped);
        if ($stripped === '') {
            return get_string('malformed_no_text', 'report_content2fix');
        }
        return \core_text::substr($stripped, 0, 250);
    }
}
