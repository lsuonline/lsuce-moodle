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

defined('MOODLE_INTERNAL') || die();

class quick_edit_incomplete_ui extends quick_edit_override_ui {
    public $name = 'incomplete';

    private static $courseitem;

    private static $checked = false;

    private static $anonitems = array();

    public function is_checked() {
        return (
            $this->grade->is_overridden() and
            $this->grade->finalgrade == null
        );
    }

    public function set($value) {
        // Setting to incomplete... Anonymous items need actual grades.
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

        // No grade yet, so set one.
        if (empty($this->grade->id) and $value) {
            $grade = $this->grade->grade_item->get_grade($this->grade->userid);
            $this->grade = $grade;
        }

        return parent::set($value);
    }

    public function __construct($grade, $tab = null) {
        $courseitem = $this->get_current_course_item($grade);

        $coursegrade = $courseitem->get_grade($grade->userid, false);
        if (empty($coursegrade->id)) {
            $coursegrade->finalgrade = null;
        }
        $coursegrade->grade_item = $courseitem;

        parent::__construct($coursegrade, $tab);
    }

    public function get_current_course_item($grade) {
        if (empty(self::$courseitem) or
            self::$courseitem->courseid != $grade->grade_item->courseid) {

            $courseid = $grade->grade_item->courseid;

            self::$courseitem = grade_item::fetch_course_item($courseid);
        }

        return self::$courseitem;
    }

    public function get_anonymous_items($grade) {
        if (empty(self::$checked)) {
            self::$checked = true;

            $allitems = grade_item::fetch_all(array(
                'courseid' => $grade->grade_item->courseid,
                'itemtype' => 'manual'
            ));

            foreach ($allitems as $item) {
                if (class_exists('grade_anonymous')) {
                    $anon = grade_anonymous::fetch(array('itemid' => $item->id));
                }

                if (empty($anon)) {
                    continue;
                }

                self::$anonitems[] = $anon;
            }
        }

        return self::$anonitems;
    }
}