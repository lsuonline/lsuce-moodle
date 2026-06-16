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

namespace report_content2fix\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Discovers HTML fields in activity modules (mod_*) and checks whether content would be
 * modified by Moodle's clean_text. Content that differs after clean_text is added to the report.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_scanner {

    /**
     * Map of component => [ table => [ field names with HTML ] ].
     * Includes activity modules (mod_*) and course/section content (core_course, core_section).
     *
     * @var array
     */
    protected static $htmlfieldmap = [
        // Course intro/description (summary) - component used for "Course page" filter.
        'core_course'   => ['course' => ['summary']],
        // Section summaries on course page.
        'core_section'  => ['course_sections' => ['summary']],
        'mod_assign'   => ['assign' => ['intro']],
        'mod_book'     => ['book' => ['intro']],
        'mod_page'     => ['page' => ['intro', 'content']],
        'mod_forum'    => ['forum' => ['intro']],
        'mod_glossary' => ['glossary' => ['intro']],
        'mod_lesson'   => ['lesson' => ['intro']],
        'mod_label'    => ['label' => ['intro']],
        'mod_resource' => ['resource' => ['intro']],
        'mod_folder'   => ['folder' => ['intro']],
        'mod_url'      => ['url' => ['intro']],
        'mod_wiki'     => ['wiki' => ['intro']],
        'mod_feedback' => ['feedback' => ['intro']],
        'mod_choice'   => ['choice' => ['intro']],
        'mod_quiz'     => ['quiz' => ['intro']],
        'mod_scorm'    => ['scorm' => ['intro']],
        'mod_h5pactivity' => ['h5pactivity' => ['intro']],
        'mod_data'     => ['data' => ['intro']],
    ];

    /**
     * Get list of (component, table, field) for all mod_* HTML fields we scan.
     *
     * @return array of [ 'component' => string, 'table' => string, 'field' => string ]
     */
    public static function get_html_content_sources(): array {
        global $DB;
        $sources = [];
        $corecomponents = ['core_course', 'core_section'];
        foreach (self::$htmlfieldmap as $component => $tablefields) {
            $modname = str_replace('mod_', '', $component);
            if (!in_array($component, $corecomponents, true) &&
                    \core_component::get_component_directory($component) === null) {
                continue;
            }
            foreach ($tablefields as $table => $fields) {
                if (!$DB->get_manager()->table_exists($table)) {
                    continue;
                }
                $columns = $DB->get_columns($table);
                $formatfield = null;
                foreach ($fields as $field) {
                    if (!isset($columns[$field])) {
                        continue;
                    }
                    $formatfield = $field . 'format';
                    if (!isset($columns[$formatfield])) {
                        continue;
                    }
                    $sources[] = [
                        'component' => $component,
                        'table' => $table,
                        'field' => $field,
                        'formatfield' => $formatfield,
                    ];
                }
            }
        }
        return $sources;
    }

    /**
     * Check if HTML content would be modified by Moodle clean_text.
     * Uses clean_text and compares result to original; true if they differ.
     *
     * @param string $html
     * @return bool true if content differs after clean_text
     */
    public static function html_differs_after_clean(string $html): bool {
        return self::analyse_html($html)['differs'];
    }

    /**
     * Analyse HTML: run Moodle clean_text and compare with original.
     * If the diff is non-empty, content would be modified by clean_text.
     *
     * @param string $html
     * @return array{differs: bool, errors: string[], cleaned: string}
     */
    public static function analyse_html(string $html): array {
        if (trim($html) === '') {
            return [
                'differs' => false,
                'errors' => [],
                'cleaned' => '',
            ];
        }

        $cleaned = clean_text($html, FORMAT_HTML, []);
        $differs = $cleaned !== $html;

        $errors = [];
        if ($differs) {
            $errors[] = get_string('content_differs_after_clean', 'report_content2fix');
        }

        return [
            'differs' => $differs,
            'errors' => $errors,
            'cleaned' => $cleaned,
        ];
    }

    /**
     * Get course_modules.id for a module instance (table + row id).
     *
     * When multiple course_modules match (e.g. duplicate data), returns the one for $courseid
     * if provided, otherwise the first match.
     *
     * @param string $modname e.g. page
     * @param int $instanceid
     * @param int|null $courseid optional course id to disambiguate when multiple records exist
     * @return int|null cmid or null
     */
    public static function get_cmid_for_instance(string $modname, int $instanceid, ?int $courseid = null): ?int {
        global $DB;
        $moduleid = $DB->get_field('modules', 'id', ['name' => $modname]);
        if ($moduleid === false) {
            return null;
        }
        $conditions = [
            'module' => $moduleid,
            'instance' => $instanceid,
        ];
        if ($courseid !== null) {
            $conditions['course'] = $courseid;
        }
        $cms = $DB->get_records('course_modules', $conditions, 'id', 'id', 0, 2);
        if (count($cms) === 0) {
            return null;
        }
        $first = reset($cms);
        return (int) $first->id;
    }
}
