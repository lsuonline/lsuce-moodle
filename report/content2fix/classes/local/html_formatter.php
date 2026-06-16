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
// terms and conditions for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_content2fix\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Formats HTML content by repairing invalid structure via DOMDocument.
 * Preserves iframes, script tags, and other elements that clean_text would remove.
 * Fixes structural issues (e.g. orphan li, mismatched list tags) without stripping content.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_formatter {

    /**
     * Format HTML by repairing invalid structure using DOMDocument.
     * Preserves iframes and script tags; fixes orphan list items and mismatched tags.
     *
     * @param string $html Raw HTML content
     * @return string Repaired HTML
     */
    public static function format_html(string $html): string {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $dom = new \DOMDocument();
        $dom->encoding = 'UTF-8';
        $previous = libxml_use_internal_errors(true);
        $wrapper = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="content2fix">' .
            $html . '</div></body></html>';
        $loaded = @$dom->loadHTML($wrapper);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $container = $dom->getElementById('content2fix');
        if (!$container) {
            return $html;
        }

        self::repair_misplaced_list_after_li($dom, $container);
        self::repair_orphan_list_items($dom, $container);
        self::repair_orphan_table_elements($dom, $container);

        $result = '';
        foreach ($container->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
        return $result;
    }

    /**
     * When a ul/ol appears as a sibling right after an ol/ul, move it into the last li
     * of the preceding list. Also move any following orphan li elements into that list.
     */
    protected static function repair_misplaced_list_after_li(\DOMDocument $dom, \DOMElement $container): void {
        $changed = true;
        while ($changed) {
            $changed = false;
            $children = [];
            foreach ($container->childNodes as $child) {
                if ($child->nodeType === \XML_ELEMENT_NODE) {
                    $children[] = $child;
                }
            }
            for ($i = 0; $i < count($children) - 1; $i++) {
                $list1 = $children[$i];
                $list2 = $children[$i + 1];
                $tag1 = strtolower($list1->nodeName);
                $tag2 = strtolower($list2->nodeName);
                if (($tag1 !== 'ol' && $tag1 !== 'ul') || ($tag2 !== 'ol' && $tag2 !== 'ul')) {
                    continue;
                }
                $lastli = null;
                foreach ($list1->childNodes as $c) {
                    if ($c->nodeType === \XML_ELEMENT_NODE && strtolower($c->nodeName) === 'li') {
                        $lastli = $c;
                    }
                }
                if ($lastli === null) {
                    continue;
                }
                $lastli->appendChild($list2);
                $changed = true;
                break;
            }
            if (!$changed) {
                $children = [];
                foreach ($container->childNodes as $child) {
                    if ($child->nodeType === \XML_ELEMENT_NODE) {
                        $children[] = $child;
                    }
                }
                for ($i = 0; $i < count($children); $i++) {
                    $list = $children[$i];
                    $tag = strtolower($list->nodeName);
                    if ($tag !== 'ol' && $tag !== 'ul') {
                        continue;
                    }
                    for ($j = $i + 1; $j < count($children); $j++) {
                        $sib = $children[$j];
                        if ($sib->nodeType === \XML_ELEMENT_NODE && strtolower($sib->nodeName) === 'li') {
                            $list->appendChild($sib);
                            $changed = true;
                            break 2;
                        }
                        if (strtolower($sib->nodeName) === 'ol' || strtolower($sib->nodeName) === 'ul') {
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Wrap orphan li elements (not inside ol/ul) in a ul to produce valid HTML.
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $container
     */
    protected static function repair_orphan_list_items(\DOMDocument $dom, \DOMElement $container): void {
        $tofix = [];
        foreach ($container->childNodes as $child) {
            if ($child->nodeType !== \XML_ELEMENT_NODE || strtolower($child->nodeName) !== 'li') {
                continue;
            }
            $parentTag = strtolower($child->parentNode->nodeName ?? '');
            if ($parentTag !== 'ol' && $parentTag !== 'ul') {
                $tofix[] = $child;
            }
        }
        foreach ($tofix as $li) {
            $ul = $dom->createElement('ul');
            $li->parentNode->insertBefore($ul, $li);
            $ul->appendChild($li);
        }
    }

    /**
     * Wrap orphan table elements (td, th, tr, thead, tbody, tfoot) in parent tags.
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $container
     */
    protected static function repair_orphan_table_elements(\DOMDocument $dom, \DOMElement $container): void {
        self::wrap_orphan_elements_in_parent($dom, $container, 'td', ['tr']);
        self::wrap_orphan_elements_in_parent($dom, $container, 'th', ['tr']);
        self::wrap_orphan_elements_in_parent($dom, $container, 'tr', ['thead', 'tbody', 'tfoot']);
        self::wrap_orphan_elements_in_parent($dom, $container, 'thead', ['table']);
        self::wrap_orphan_elements_in_parent($dom, $container, 'tbody', ['table']);
        self::wrap_orphan_elements_in_parent($dom, $container, 'tfoot', ['table']);
    }

    /**
     * Wrap elements with tag $childtag that are not inside an allowed parent.
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $container
     * @param string $childtag
     * @param string[] $allowedparents
     */
    protected static function wrap_orphan_elements_in_parent(
        \DOMDocument $dom,
        \DOMElement $container,
        string $childtag,
        array $allowedparents
    ): void {
        $tofix = [];
        $elements = $dom->getElementsByTagName($childtag);
        foreach ($elements as $el) {
            $parent = $el->parentNode;
            if (!$parent || $parent->nodeType !== \XML_ELEMENT_NODE) {
                $tofix[] = $el;
                continue;
            }
            $parenttag = strtolower($parent->nodeName ?? '');
            if (!in_array($parenttag, $allowedparents, true)) {
                $tofix[] = $el;
            }
        }
        $parenttag = $allowedparents[0];
        foreach ($tofix as $el) {
            $wrapper = $dom->createElement($parenttag);
            $el->parentNode->insertBefore($wrapper, $el);
            $wrapper->appendChild($el);
        }
    }

    /**
     * Load HTML from a database row (component, comptable, compfield, rowid), format it,
     * and persist the cleaned version back to the source table.
     *
     * @param object $entry Report entry with component, comptable, compfield, rowid
     * @return bool True if content was changed and persisted
     */
    public static function format_and_persist_entry(object $entry): bool {
        global $DB;

        $table = $entry->comptable;
        $field = $entry->compfield;
        $rowid = (int) $entry->rowid;

        if (!$DB->get_manager()->table_exists($table)) {
            return false;
        }
        $columns = $DB->get_columns($table);
        if (!isset($columns[$field])) {
            return false;
        }
        $formatfield = $field . 'format';
        if (!isset($columns[$formatfield])) {
            return false;
        }

        $row = $DB->get_record($table, ['id' => $rowid], $field . ',' . $formatfield);
        if (!$row || $row->{$formatfield} != FORMAT_HTML) {
            return false;
        }

        $original = $row->$field ?? '';
        if (!is_string($original)) {
            return false;
        }

        $cleaned = self::format_html($original);
        if ($cleaned === $original) {
            return false;
        }

        $DB->update_record($table, (object) [
            'id' => $rowid,
            $field => $cleaned,
        ]);

        if (isset($entry->id) && $entry->id) {
            $DB->delete_records('report_content2fix', ['id' => (int) $entry->id]);
        }

        $courseid = (int) ($entry->courseid ?? 0);
        if ($courseid > 0) {
            rebuild_course_cache($courseid, true);
        }

        return true;
    }
}
