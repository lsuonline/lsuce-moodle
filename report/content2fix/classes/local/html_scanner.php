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
 * Discovers HTML fields in activity modules (mod_*) and checks for malformed HTML.
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
     * Check if HTML content is malformed.
     * Detects: libxml errors when loading, and invalid HTML structure (e.g. orphan li).
     *
     * @param string $html
     * @return bool true if malformed
     */
    public static function is_malformed_html(string $html): bool {
        if (trim($html) === '') {
            return false;
        }
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = @$dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !empty($errors)) {
            return true;
        }

        return self::has_invalid_html_structure($dom);
    }

    /**
     * HTML5 content model: elements that require specific parents.
     * Key = child element (lowercase), value = allowed parent elements (lowercase).
     * See https://html.spec.whatwg.org/ for full content model.
     *
     * @var array<string, array<string>>
     */
    protected static $contentmodel_parents = [
        'li' => ['ol', 'ul', 'menu'],
        'td' => ['tr'],
        'th' => ['tr'],
        'tr' => ['table', 'thead', 'tbody', 'tfoot'],
        'thead' => ['table'],
        'tbody' => ['table'],
        'tfoot' => ['table'],
        'dt' => ['dl'],
        'dd' => ['dl'],
        'option' => ['select', 'datalist', 'optgroup'],
        'optgroup' => ['select'],
        'col' => ['colgroup'],
        'colgroup' => ['table'],
        'legend' => ['fieldset'],
        'figcaption' => ['figure'],
        'summary' => ['details'],
    ];

    /**
     * Check for invalid HTML structure using the content model map.
     *
     * @param \DOMDocument $dom
     * @return bool true if invalid structure found
     */
    protected static function has_invalid_html_structure(\DOMDocument $dom): bool {
        foreach (self::$contentmodel_parents as $childtag => $allowedparents) {
            $elements = $dom->getElementsByTagName($childtag);
            foreach ($elements as $el) {
                $parent = $el->parentNode;
                if (!$parent) {
                    return true;
                }
                $parentname = $parent->nodeName ?? '';
                if ($parentname === '#document') {
                    return true;
                }
                if ($parent->nodeType === \XML_ELEMENT_NODE) {
                    $parenttag = strtolower($parent->nodeName);
                    if (!in_array($parenttag, $allowedparents, true)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get course_modules.id for a module instance (table + row id).
     *
     * @param string $modname e.g. page
     * @param int $instanceid
     * @return int|null cmid or null
     */
    public static function get_cmid_for_instance(string $modname, int $instanceid): ?int {
        global $DB;
        $moduleid = $DB->get_field('modules', 'id', ['name' => $modname]);
        if ($moduleid === false) {
            return null;
        }
        $cm = $DB->get_record('course_modules', [
            'module' => $moduleid,
            'instance' => $instanceid,
        ], 'id', IGNORE_MISSING);
        return $cm ? (int) $cm->id : null;
    }
}
