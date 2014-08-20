<?php

class quick_edit_incomplete_ui extends quick_edit_override_ui {
    var $name = 'incomplete';

    private static $course_item;

    private static $checked = false;

    private static $anon_items = array();

    public function is_checked() {
        return (
            $this->grade->is_overridden() and
            $this->grade->finalgrade == null
        );
    }

    public function set($value) {
        // Setting to incomplete... Anonymous items need actual grades
        foreach ($this->get_anonymous_items($this->grade) as $anon) {
            if ($anon->is_completed()) {
                continue;
            }

            $grade = $anon->load_grade($this->grade->userid, false);
            if (empty($grade) and !empty($value)) {
                $anon->update_final_grade(
                    $this->grade->userid, 0.00000, 'quick_edit'
                );
            }
        }

        // No grade yet, so set one
        if (empty($this->grade->id) and $value) {
            $grade = $this->grade->grade_item->get_grade($this->grade->userid);
            $this->grade = $grade;
        }

        return parent::set($value);
    }

    public function __construct($grade, $tab = null) {
        $course_item = $this->get_current_course_item($grade);

        $course_grade = $course_item->get_grade($grade->userid, false);
        if (empty($course_grade->id)) {
            $course_grade->finalgrade = null;
        }
        $course_grade->grade_item = $course_item;

        parent::__construct($course_grade, $tab);
    }

    public function get_current_course_item($grade) {
        if (empty(self::$course_item) or
            self::$course_item->courseid != $grade->grade_item->courseid) {

            $courseid = $grade->grade_item->courseid;

            self::$course_item = grade_item::fetch_course_item($courseid);
        }

        return self::$course_item;
    }

    public function get_anonymous_items($grade) {
        if (empty(self::$checked)) {
            self::$checked = true;

            $all_items = grade_item::fetch_all(array(
                'courseid' => $grade->grade_item->courseid,
                'itemtype' => 'manual'
            ));

            foreach ($all_items as $item) {
                if (class_exists('grade_anonymous')) {
                    $anon = grade_anonymous::fetch(array('itemid' => $item->id));
                }

                if (empty($anon)) {
                    continue;
                }

                self::$anon_items[] = $anon;
            }
        }

        return self::$anon_items;
    }
}
