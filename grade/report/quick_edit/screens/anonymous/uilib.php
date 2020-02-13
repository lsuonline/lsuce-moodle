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

class anonymous_ui_factory extends quick_edit_grade_ui_factory {
    public function create($type) {
        $attempt = 'anonymous_quick_edit_' . $type;

        if (class_exists($attempt)) {
            return $this->wrap($attempt);
        } else {
            return parent::create($type);
        }
    }
}

class anonymous_quick_edit_finalgrade extends quick_edit_finalgrade_ui {
    public function determine_format() {
        if ($this->grade->load_item()->is_completed()) {
            return new quick_edit_empty_element($this->get_value());
        } else {
            return parent::determine_format();
        }
    }

    public function set($value) {
        // Swap grade_items.
        $mainuserfields = user_picture::fields();
        $moodlegradeitem = $this->grade->load_grade_item();

        $this->grade->grade_item = $this->grade->load_item();

        $msg = parent::set($value);

        $this->grade->grade_item = $moodlegradeitem;

        // Mask student.
        if (!empty($msg) and !$this->grade->load_item()->is_completed()) {
            global $DB;

            $params = array('id' => $this->grade->userid);
            $user = $DB->get_record('user', $params, $mainuserfields);

            $number = $this->grade->anonymous_number();
            if (!empty($user->alternatename)) {
                $displayname = $user->alternatename . ' \(' . $user->firstname . '\) ' . $user->lastname;
            } else {
                $displayname = fullname($user);
            }
            $msg = preg_replace('/' . $displayname . '/', $number, $msg);
        }

        return $msg;
    }
}

class anonymous_quick_edit_adjust_value extends quick_edit_finalgrade_ui {
    public $name = 'adjust_value';

    public function is_disabled() {
        $boundary = $this->grade->load_item()->adjust_boundary();
        return empty($boundary) ? true : parent::is_disabled();
    }

    public function adjust_type_name() {
        return "adjust_type_{$this->grade->itemid}_{$this->grade->userid}";
    }

    public function get_value() {
        return format_float(
            $this->grade->adjust_value,
            $this->grade->grade_item->get_decimals()
        );
    }

    public function determine_format() {
        return new quick_edit_text_attribute(
            $this->get_name(),
            $this->get_value(),
            $this->is_disabled(),
            $this->get_tabindex()
        );
    }

    public function set($value) {
        global $DB;
        $code = '';
        if (filter_var($value, FILTER_VALIDATE_FLOAT) || $value == '0') {
            $bounded = $this->grade->bound_adjust_value($value);
            if ($bounded < $value) {
                $code = 'anonymousmorethanmax';
            } else if ($bounded > $value) {
                $code = 'anonymouslessthanmin';
            }
        } else {
            $value = '0.0';
            $code = 'notagrade';
            $bounded = $this->grade->bound_adjust_value($value);
        }

        // Diff checker will fail on screen.
        if ($code) {
            $params = array('id' => $this->grade->userid);
            $user = $DB->get_record('user', $params, 'id, firstname, alternatename, lastname');

            $obj = new stdClass;
            if (!empty($user->alternatename)) {
                $obj->username = $user->alternatename . ' (' . $user->firstname . ') ' . $user->lastname;
            } else {
                $obj->username = $user->firstname . ' ' . $user->lastname;
            }
            $obj->itemname = $this->grade->load_item()->get_name();
            $obj->boundary = $this->grade->load_item()->adjust_boundary();
            $code = get_string($code, 'grades', $obj) . ' ';
        }

        $this->grade->load_item()->update_final_grade(
            $this->grade->userid, $bounded, 'quick_edit'
        );

        return $code;
    }
}