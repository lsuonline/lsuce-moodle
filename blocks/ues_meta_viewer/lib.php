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
 * 
 * @package    block_ues_meta_viewer
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->dirroot . '/blocks/ues_meta_viewer/classes/lib.php';
require_once $CFG->dirroot . '/blocks/cps/events/ues_meta_viewer.php';

abstract class ues_meta_viewer {
    public static function sql($handlers) {
        $flatten = function($dsl, $handler) {
            return $handler->sql($dsl);
        };

        // What I'd give for an optional here
        try {
            $filters = array_reduce($handlers, $flatten, ues::where());

            // Catch empty
            $filters->get();
            return $filters;
        } catch (Exception $e) {
            return array();
        }
    }

    public static function result_table($users, $handlers) {
        $table = new html_table();
        $table->head = array();
        $table->data = array();

        foreach ($handlers as $handler) {
            $table->head[] = $handler->name();
        }

        foreach ($users as $id => $user) {
            $format = function($handler) use ($user) {
                return $handler->format($user);
            };

            $table->data[] = array_map($format, $handlers);
        }

        return $table;
    }

    public static function handler($type, $field) {
        $cur = current_language();
        $strs = get_string_manager()->load_component_strings('moodle', $cur);

        if (!isset($strs[$field])) {
            $name = $field;
        } else {
            $name = $strs[$field];
        }

        $handler = new stdClass;
        $handler->ui_element = new meta_data_text_box($field, $name);

        events_trigger_legacy($type . '_data_ui_element', $handler);
        return $handler->ui_element;
    }

    public static function generate_keys($type, $class, $user) {
        $types = self::supported_types();

        $fields = new stdClass;

        $fields->user = $user;
        $fields->keys = $types[$type]->defaults();

        // Auto fill based on system
        $additional_fields = $class::get_meta_names();
        foreach ($additional_fields as $field) {
            $fields->keys[] = $field;
        }

        // Should this user see appropriate fields?
        events_trigger_legacy($type . '_data_ui_keys', $fields);

        return $fields->keys;
    }

    public static function supported_types() {
        if (!class_exists('supported_meta')) {
            global $CFG;

            require_once $CFG->dirroot . '/blocks/ues_meta_viewer/classes/support.php';
        }

        $supported_types = new stdClass;

        $supported_types->types = array(
            'ues_user' => new ues_user_supported_meta(),
            'ues_section' => new ues_section_supported_meta(),
            'ues_course' => new ues_course_supported_meta(),
            'ues_semester' => new ues_semester_supported_meta(),
            'ues_teacher' => new ues_teacher_supported_meta(),
            'ues_student' => new ues_student_supported_meta()
        );

        events_trigger_legacy('ues_meta_supported_types', $supported_types);

        return $supported_types->types;
    }
}
