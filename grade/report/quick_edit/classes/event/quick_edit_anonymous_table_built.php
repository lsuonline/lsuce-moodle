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
 
/**
 * The quick_edit_anonymous_table_built event.
 *
 * @package    grade_report_quick_edit
 * @copyright  2019 Louisiana State University
 * @author     Troy Kammerdiener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace grade_report_quick_edit\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The quick_edit_anonymous_table_built event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - html_table  table:    The table built for this event.
 *      - object      instance: quick_edit_screen (quick_edit tablelike or 
 *                              quick_edit_select) object being processed
 *                              for this event.
 * }
 *
 * @since     Moodle 3.7.1
 * @copyright 2019 Louisiana State University
 * @author    Troy Kammerdiener
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class quick_edit_anonymous_table_built extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        // TODO: When this plugin is refactored to actually use this event to
        // invoke handlers and trigger database changes, it needs to create
        // the objecttable property here, and the objectid property at initial
        // event construction.
    }
 
    public static function get_name() {
        return get_string('eventquick_edit_anonymous_table_built', 'grade_report_quick_edit');
    }
 
    public function get_description() {
        return "The user with id {$this->userid} built a table of " . 
            "anonymous grade items for a course with id {$this->courseid}.";
    }
 
    // TODO: Check whether get_url() is actually used by this plugin, and remove
    // if it is not.  It currently returns null because there is no valid location
    // where it can be observed later.
    public function get_url() {
        return null;
    }
 
    public static function get_legacy_quick_edit_anonymous_table_built() {
        return 'quick_edit_anonymous_table_built';
    }
 
    protected function get_legacy_eventdata() {
        return $this->data['other'];
    }
}