<?php

require_once $CFG->dirroot . '/blocks/ues_meta_viewer/classes/lib.php';

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
        $name = get_string($field);

        if ($name == "[[$field]]") {
           $name = $field;
        }

        $handler = new stdClass;
        $handler->ui_element = new meta_data_text_box($field, $name);

        events_trigger($type . '_data_ui_element', $handler);
        return $handler->ui_element;
    }

    public static function generate_keys($type, $user) {
        $types = self::supported_types();

        $fields = new stdClass;

        $fields->user = $user;
        $fields->keys = $types[$type]->defaults();

        // Auto fill based on system
        $additional_fields = $type::get_meta_names();
        foreach ($additional_fields as $field) {
            $fields->keys[] = $field;
        }

        // Should this user see appropriate fields?
        events_trigger($type . '_data_ui_keys', $fields);

        return $fields->keys;
    }

    // Make this 
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
            'ues_semester' => new ues_semester_suppported_meta(),
            'ues_teacher' => new ues_teacher_supported_meta(),
            'ues_student' => new ues_student_supported_meta()
        );

        events_trigger('ues_meta_supported_types', $supported_types);

        return $supported_types->types;
    }
}
