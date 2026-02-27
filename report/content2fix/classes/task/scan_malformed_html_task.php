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

        // Course table uses 'id' as course id; course_sections and mod tables use 'course'.
        $coursefield = ($table === 'course') ? 'id' : 'course';
        if (!$DB->get_manager()->field_exists($table, $coursefield)) {
            return;
        }

        $select = $formatfield . ' = ? AND ' . $DB->sql_isnotempty($table, $field, false, false);
        $order = $coursefield . ', id';
        $fields = 'id, ' . $coursefield . ', ' . $field;

        $rs = $DB->get_recordset_select($table, $select, [FORMAT_HTML], $order, $fields);

        foreach ($rs as $row) {
            $courseid = (int) $row->$coursefield;
            if (!$this->should_scan_course($courseid)) {
                continue;
            }
            $html = $row->$field;
            if (!is_string($html) || $html === '') {
                continue;
            }
            if (strpos($component, 'mod_') === 0 && !$this->should_scan_activity($component, (int) $row->id, $courseid)) {
                continue;
            }
            if ($component === 'core_section' && !$this->should_scan_section($courseid, (int) $row->id)) {
                continue;
            }
            $analysis = html_scanner::analyse_html($html);
            if ($analysis['differs']) {
                $cmid = (strpos($component, 'mod_') === 0)
                    ? html_scanner::get_cmid_for_instance($modname, (int) $row->id, $courseid)
                    : null;
                $summary = $this->summarise_issue($html);
                $DB->insert_record('report_content2fix', (object) [
                    'component' => $component,
                    'comptable' => $table,
                    'compfield' => $field,
                    'rowid' => (int) $row->id,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'summary' => $summary,
                    'htmlerrors' => implode("\n", $analysis['errors']),
                    'malformedhtml' => $html,
                    'timechecked' => $timerecorded,
                ]);
            }
        }
        $rs->close();
    }

    /**
     * Whether the given course should be scanned based on plugin settings.
     *
     * @param int $courseid
     * @return bool
     */
    protected function should_scan_course(int $courseid): bool {
        $courseids = get_config('report_content2fix', 'courseids');
        if (empty($courseids)) {
            return true;
        }
        $ids = array_map('intval', array_filter(explode(',', $courseids)));
        return in_array($courseid, $ids, true);
    }

    /**
     * Whether the given activity (mod) should be scanned based on section setting.
     *
     * @param string $component e.g. mod_page
     * @param int $instanceid
     * @param int $courseid
     * @return bool
     */
    protected function should_scan_activity(string $component, int $instanceid, int $courseid): bool {
        $mainpageonly = get_config('report_content2fix', 'mainpageonly');
        if (empty($mainpageonly)) {
            return true;
        }
        $modname = str_replace('mod_', '', $component);
        $cmid = html_scanner::get_cmid_for_instance($modname, $instanceid, $courseid);
        if ($cmid === null) {
            return true; // Cannot determine section, include it.
        }
        global $DB;
        $sectionid = $DB->get_field('course_modules', 'section', ['id' => $cmid]);
        if ($sectionid === false) {
            return true;
        }
        $sectionnum = $DB->get_field('course_sections', 'section', ['id' => $sectionid]);
        return $sectionnum === '0' || (int) $sectionnum === 0;
    }

    /**
     * Whether the given section should be scanned when main page only is set.
     *
     * @param int $courseid
     * @param int $sectionid course_sections.id
     * @return bool
     */
    protected function should_scan_section(int $courseid, int $sectionid): bool {
        $mainpageonly = get_config('report_content2fix', 'mainpageonly');
        if (empty($mainpageonly)) {
            return true;
        }
        global $DB;
        $sectionnum = $DB->get_field('course_sections', 'section', ['id' => $sectionid]);
        return $sectionnum === '0' || (int) $sectionnum === 0;
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
