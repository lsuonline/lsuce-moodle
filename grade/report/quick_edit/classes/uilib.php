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

abstract class quick_edit_ui_factory {
    public abstract function create($type);

    protected function wrap($class) {
        return new quick_edit_factory_class_wrap($class);
    }
}

class quick_edit_grade_ui_factory extends quick_edit_ui_factory {
    public function create($type) {
        return $this->wrap("quick_edit_{$type}_ui");
    }
}

class quick_edit_factory_class_wrap {
    public function __construct($class) {
        $this->class = $class;
    }

    public function format() {
        $args = func_get_args();

        $reflect = new ReflectionClass($this->class);
        return $reflect->newInstanceArgs($args);
    }
}

abstract class quick_edit_ui_element {
    // Changed var to public.
    public $name;
    public $value;

    public function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
    }

    public function is_checkbox() {
        return false;
    }

    public function is_textbox() {
        return false;
    }

    public function is_dropdown() {
        return false;
    }

    // Added protected 10/4/2019.
    abstract protected function html();
}

class quick_edit_empty_element extends quick_edit_ui_element {
    public function __construct($msg = null) {
        if (is_null($msg)) {
            $this->text = get_string('notavailable', 'gradereport_quick_edit');
        } else {
            $this->text = $msg;
        }
    }

    public function html() {
        return $this->text;
    }
}

class quick_edit_text_attribute extends quick_edit_ui_element {
    // Changed var to public.
    public $is_disabled;
    public $tabindex;

    public function __construct($name, $value, $is_disabled = false, $tabindex = null) {
        $this->is_disabled = $is_disabled;
        $this->tabindex = $tabindex;
        parent::__construct($name, $value);
    }

    public function is_textbox() {
        return true;
    }

    public function html() {
        $attributes = array(
            'type' => 'text',
            'name' => $this->name,
            'value' => $this->value
        );

        if (!empty($this->tabindex)) {
            $attributes['tabindex'] = $this->tabindex;
        }
        if ($this->is_disabled) {
            $attributes['disabled'] = 'DISABLED';
        }

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $this->name,
            'value' => $this->value
        );

        return (
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
    }
}

class quick_edit_checkbox_attribute extends quick_edit_ui_element {
    public $is_checked;
    public $tabindex;

    // UCSB 2014-02-28 - add $locked to disable override checkbox when grade is locked.
    public function __construct($name, $is_checked = false, $tabindex = null, $locked=0) {
        $this->is_checked = $is_checked;
        $this->tabindex = $tabindex;
        $this->locked = $locked;
        parent::__construct($name, 1);
    }

    public function is_checkbox() {
        return true;
    }

    public function html() {

        $attributes = array(
            'type' => 'checkbox',
            'name' => $this->name,
            'value' => 1
        );

        // UCSB fixed user should not be able to override locked grade.
        if ( $this->locked) {
            $attributes['disabled'] = 'DISABLED';
        }

        $alt = array(
            'type' => 'hidden',
            'name' => $this->name,
            'value' => 0
        );

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $this->name
        );

        if (!empty($this->tabindex)) {
            $attributes['tabindex'] = $this->tabindex;
        }

        if ($this->is_checked) {
            $attributes['checked'] = 'CHECKED';
            $hidden['value'] = 1;
        }

        return (
            html_writer::empty_tag('input', $alt) .
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
    }
}

class quick_edit_dropdown_attribute extends quick_edit_ui_element {
    public $selected;
    public $options;
    public $is_disabled;

    public function __construct($name, $options, $selected = '', $is_disabled = false, $tabindex = null) {
        $this->selected = $selected;
        $this->options = $options;
        $this->tabindex = $tabindex;
        $this->is_disabled = $is_disabled;
        parent::__construct($name, $selected);
    }

    public function is_dropdown() {
        return true;
    }

    public function html() {
        $old = array(
            'type' => 'hidden',
            'name' => 'old' . $this->name,
            'value' => $this->selected
        );

        $attributes = array();
        if (!empty($this->tabindex)) {
            $attributes['tabindex'] = $this->tabindex;
        }

        if (!empty($this->is_disabled)) {
            $attributes['disabled'] = 'DISABLED';
        }

        $select = html_writer::select(
            $this->options, $this->name, $this->selected, false, $attributes
        );

        return ($select . html_writer::empty_tag('input', $old));
    }
}

abstract class quick_edit_grade_attribute_format extends quick_edit_attribute_format implements unique_name, tabbable {
    public $name;

    public function __construct() {
        $args = func_get_args();

        $this->get_arg_or_nothing($args, 0, 'grade');
        $this->get_arg_or_nothing($args, 1, 'tabindex');
    }

    public function get_name() {
        return "{$this->name}_{$this->grade->itemid}_{$this->grade->userid}";
    }

    public function get_tabindex() {
        return isset($this->tabindex) ? $this->tabindex : null;
    }

    private function get_arg_or_nothing($args, $index, $field) {
        if (isset($args[$index])) {
            $this->$field = $args[$index];
        }
    }

    public abstract function set($value);
}

interface unique_name {
    public function get_name();
}

interface unique_value {
    public function get_value();
}

interface be_disabled {
    public function is_disabled();
}

interface be_checked {
    public function is_checked();
}

interface tabbable {
    public function get_tabindex();
}

class quick_edit_bulk_insert_ui extends quick_edit_ui_element {
    public function __construct($item) {
        $this->name = 'bulk_' . $item->id;
        $this->applyname = $this->name_for('apply');
        $this->selectname = $this->name_for('type');
        $this->insertname = $this->name_for('value');
    }

    public function is_applied($data) {
        return isset($data->{$this->applyname});
    }

    public function get_type($data) {
        return $data->{$this->selectname};
    }

    public function get_insert_value($data) {
        return $data->{$this->insertname};
    }

    public function html() {
        $s = function($key) {
            return get_string($key, 'gradereport_quick_edit');
        };

        $apply = html_writer::checkbox($this->applyname, 1, false, ' ' . $s('bulk'));

        $insertoptions = array(
            'all' => $s('all_grades'),
            'blanks' => $s('blanks')
        );

        $select = html_writer::select(
            $insertoptions, $this->selectname, 'blanks', false
        );

        $label = html_writer::tag('label', $s('for'));
        $text = new quick_edit_text_attribute($this->insertname, "0");
        return implode(' ', array($apply, $text->html(), $label, $select));
    }

    private function name_for($extend) {
        return "{$this->name}_$extend";
    }
}

abstract class quick_edit_attribute_format {
    public abstract function determine_format();

    public function __toString() {
        return $this->determine_format()->html();
    }
}

class quick_edit_finalgrade_ui extends quick_edit_grade_attribute_format implements unique_value, be_disabled {

    public $name = 'finalgrade';

    public function get_value() {
        // Manual item raw grade support.
        $val = $this->grade->grade_item->is_manual_item() && (!is_null($this->grade->rawgrade)) ?
            $this->grade->rawgrade : $this->grade->finalgrade;

        if ($this->grade->grade_item->scaleid) {
            return $val ? (int)$val : -1;
        } else {
            return $val ? format_float($val, $this->grade->grade_item->get_decimals()) : '';
        }
    }

    public function is_disabled() {
        $locked = 0;
        $gradeitemlocked = 0;
        $overridden = 0;

        // UCSB - 2.24.2014
        // disable editing if grade item or grade score is locked
        // if any of these items are set, then we will disable editing
        // at some point, we might want to show the reason for the lock
        // this code could be simplified.
        if (!empty($this->grade->locked)) {
            $locked = 1;
        }
        if (!empty($this->grade->grade_item->locked)) {
            $gradeitemlocked = 1;
        }
        if ($this->grade->grade_item->is_overridable_item() and !$this->grade->is_overridden()) {
            $overridden = 1;
        }
        return ($locked || $gradeitemlocked || $overridden);
    }

    public function determine_format() {
        if ($this->grade->grade_item->load_scale()) {
            $scale = $this->grade->grade_item->load_scale();

            $options = array(-1 => get_string('nograde'));

            foreach ($scale->scale_items as $i => $name) {
                $options[$i + 1] = $name;
            }

            return new quick_edit_dropdown_attribute(
                $this->get_name(),
                $options,
                $this->get_value(),
                $this->is_disabled(),
                $this->get_tabindex()
            );
        } else {
            return new quick_edit_text_attribute(
                $this->get_name(),
                $this->get_value(),
                $this->is_disabled(),
                $this->get_tabindex()
            );
        }
    }

    public function set($value) {
        global $DB;

        $userid = $this->grade->userid;
        $gradeitem = $this->grade->grade_item;

        $feedback = false;
        $feedbackformat = false;
        if ($gradeitem->gradetype == GRADE_TYPE_SCALE) {
            if ($value == -1) {
                $finalgrade = null;
            } else {
                $finalgrade = $value;
            }
        } else {
            $finalgrade = unformat_float($value);
        }

        $errorstr = '';
        if (!is_null($finalgrade)) {
            if (filter_var($value, FILTER_VALIDATE_FLOAT) || $value == '0') {
                $bounded = $gradeitem->bounded_grade($finalgrade);
                if ($bounded > $finalgrade) {
                    $errorstr = 'lessthanmin';
                } else if ($bounded < $finalgrade) {
                    $errorstr = 'morethanmax';
                }
            } else {
                $finalgrade = '0.0';
                $errorstr = 'notagrade';
                $bounded = $gradeitem->bounded_grade($finalgrade);
            }
        }
        /*if (is_null($finalgrade)) {
            // Ok.
        } else {

            if (filter_var($value, FILTER_VALIDATE_FLOAT) || $value == '0') {
                $bounded = $grade_item->bounded_grade($finalgrade);
                if ($bounded > $finalgrade) {
                    $errorstr = 'lessthanmin';
                } else if ($bounded < $finalgrade) {
                    $errorstr = 'morethanmax';
                }
            } else {
                $finalgrade = '0.0';
                $errorstr = 'notagrade';
                $bounded = $grade_item->bounded_grade($finalgrade);
            }
        }*/

        if ($errorstr) {
            $user = $DB->get_record('user', array('id' => $userid), 'id, firstname, alternatename, lastname');
            $gradestr = new stdClass;
            if (!empty($user->alternatename)) {
                $gradestr->username = $user->alternatename . ' (' . $user->firstname . ') ' . $user->lastname;
            } else {
                $gradestr->username = $user->firstname . ' ' . $user->lastname;
            }
            $gradestr->itemname = $this->grade->grade_item->get_name();

            $errorstr = get_string($errorstr, 'grades', $gradestr);
        }

        $gradeitem->update_final_grade($userid, $finalgrade, 'quick_edit', $feedback, FORMAT_MOODLE);
        return $errorstr;
    }
}

class quick_edit_feedback_ui extends quick_edit_grade_attribute_format implements unique_value, be_disabled {

    public $name = 'feedback';

    public function get_value() {
        return $this->grade->feedback ? $this->grade->feedback : '';
    }

    public function is_disabled() {
        $locked = 0;
        $gradeitemlocked = 0;
        $overridden = 0;

        // UCSB - 2.24.2014
        // disable editing if grade item or grade score is locked
        // if any of these items are set,  then we will disable editing
        // at some point, we might want to show the reason for the lock
        // this code could be simplified.
        if (!empty($this->grade->locked)) {
            $locked = 1;
        }
        if (!empty($this->grade->grade_item->locked)) {
            $gradeitemlocked = 1;
        }
        if ($this->grade->grade_item->is_overridable_item() and !$this->grade->is_overridden()) {
            $overridden = 1;
        }
        return ($locked || $gradeitemlocked || $overridden);
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
        $finalgrade = false;
        $trimmed = trim($value);
        if (empty($trimmed)) {
            $feedback = null;
        } else {
            $feedback = $value;
        }

        $this->grade->grade_item->update_final_grade(
            $this->grade->userid, $finalgrade, 'quick_edit',
            $feedback, FORMAT_MOODLE
        );
        return false;
    }
}

// UCSB 2014-02-28 implement be_disabled to disable checkbox if grade/gradeitem is locked.
class quick_edit_override_ui extends quick_edit_grade_attribute_format implements be_checked, be_disabled {
    public $name = 'override';

    public function is_checked() {
        return $this->grade->is_overridden();
    }

    public function is_disabled() {
        $locked_grade = $locked_grade_item = 0;
        if ( ! empty($this->grade->locked) ) {
            $locked_grade = 1;
        }
        if ( ! empty($this->grade->grade_item->locked) ) {
            $locked_grade_item = 1;
        }
        return ($locked_grade || $locked_grade_item);
    }

    public function determine_format() {
        if (!$this->grade->grade_item->is_overridable_item()) {
            return new quick_edit_empty_element();
        }

        // UCSB 2014-02-28: add param is_disabled to disable override checkbox if grade is locked.
        return new quick_edit_checkbox_attribute(
            $this->get_name(),
            $this->is_checked(),
            null,
            $this->is_disabled()
        );
    }

    public function set($value) {
        if (empty($this->grade->id)) {
            return false;
        }

        $state = $value == 0 ? false : true;

        $this->grade->set_overridden($state);
        $this->grade->grade_item->get_parent_category()->force_regrading();
        return false;
    }
}

class quick_edit_exclude_ui extends quick_edit_grade_attribute_format implements be_checked {
    public $name = 'exclude';

    public function is_checked() {
        return $this->grade->is_excluded();
    }

    public function determine_format() {
        return new quick_edit_checkbox_attribute(
            $this->get_name(),
            $this->is_checked()
        );
    }

    public function set($value) {
        if (empty($this->grade->id)) {
            if (empty($value)) {
                return false;
            }

            $gradeitem = $this->grade->grade_item;

            // Fill in arbitrary grade to be excluded.
            $gradeitem->update_final_grade(
                $this->grade->userid, null, 'quick_edit', null, FORMAT_MOODLE
            );

            $gradeparams = array(
                'userid' => $this->grade->userid,
                'itemid' => $this->grade->itemid
            );

            $this->grade = grade_grade::fetch($gradeparams);
            $this->grade->grade_item = $gradeitem;
        }

        $state = $value == 0 ? false : true;

        $this->grade->set_excluded($state);

        $this->grade->grade_item->get_parent_category()->force_regrading();
        return false;
    }
}

class quick_edit_range_ui extends quick_edit_attribute_format {
    public function __construct($item) {
        $this->item = $item;
    }

    public function determine_format() {
        $decimals = $this->item->get_decimals();

        $min = format_float($this->item->grademin, $decimals);
        $max = format_float($this->item->grademax, $decimals);

        return new quick_edit_empty_element("$min - $max");
    }
}