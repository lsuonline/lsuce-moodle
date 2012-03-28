<?php

abstract class ues_people {
    public static function primary_role() {
        return get_config('enrol_ues', 'editingteacher_role');
    }

    public static function nonprimary_role() {
        return get_config('enrol_ues', 'teacher_role');
    }

    public static function student_role() {
        return get_config('enrol_ues', 'student_role');
    }

    public static function ues_roles() {
        global $DB;

        $role_sql = ues::where()->id->in(
            self::primary_role(), self::nonprimary_role(), self::student_role()
        )->sql();

        return $DB->get_records_sql('SELECT * FROM {role} WHERE ' . $role_sql);
    }

    public static function defaults() {
        return explode(',', get_config('block_ues_people', 'outputs'));
    }

    public static function initial_bars($label, $name, $url) {
        $current = optional_param($name, 'all', PARAM_TEXT);

        $bar = html_writer::start_tag('div', array('class' => 'initialbar lastinitial'));
        $bar .= $label . ' : ';

        $letters = array('all' => get_string('all'));
        $alpha = explode(',', get_string('alphabet', 'langconfig'));

        $letters += array_combine($alpha, $alpha);

        foreach ($letters as $key => $letter) {
            if ($key == $current) {
                $bar .= html_writer::tag('strong', $letter);
            } else {
                $bar .= '<a href="'. $url . '&amp;' . $name . '=' . $key.'">'.$letter.'</a>';
            }
        }

        $bar .= html_writer::end_tag('div');

        return $bar;
    }

    public static function sortable($url, $label, $field) {
        $current = optional_param('meta', 'lastname', PARAM_TEXT);
        $dir = optional_param('dir', 'ASC', PARAM_TEXT);

        if ($current == $field) {
            global $OUTPUT;

            if ($dir == 'ASC') {
                $path = 'down';
                $new_dir = 'DESC';
            } else {
                $path = 'up';
                $new_dir = 'ASC';
            }
            $murl = new moodle_url($url, array('meta' => $field, 'dir' => $new_dir));

            $link = html_writer::link($murl, $label);
            $link .= ' ' . $OUTPUT->pix_icon('t/'. $path, $dir);
            return $link;
        } else {
            $murl = new moodle_url($url, array('meta' => $field, 'dir' => 'ASC'));

            return html_writer::link($murl, $label);
        }
    }

    public static function outputs() {
        $defaults = self::defaults();

        $internal = array('sec_number', 'credit_hours');
        $meta_names = array_merge($internal, ues_user::get_meta_names());

        $_s = ues::gen_str('block_ues_people');

        $outputs = array();

        foreach ($meta_names as $meta) {
            // Admin choice on limits
            if (!in_array($meta, $defaults)) {
                continue;
            }

            $element = in_array($meta, $internal) ?
                new ues_people_element_output($meta, $_s($meta)) :
                new ues_people_element_output($meta);

            $outputs[$meta] = $element;
        }

        $data = new stdClass;
        $data->outputs = $outputs;

        // Plugin interference
        events_trigger('ues_people_outputs', $data);

        return $data->outputs;
    }

    public static function control_elements($meta_names) {
        $defaults = array(
            'fullname' => get_string('fullname'),
            'username' => get_string('username'),
            'idnumber' => get_string('idnumber')
        );

        $controls = array();
        foreach ($defaults as $field => $name) {
            $controls[$field] = new ues_people_element_output($field, $name);
        }

        $controls += $meta_names;

        return $controls;
    }

    public static function get_filter($meta_name) {
        return (int)get_user_preferences('block_ues_people_filter_'.$meta_name, 1);
    }

    public static function set_filter($meta_name, $value) {
        return set_user_preference('block_ues_people_filter_'.$meta_name, (int)$value);
    }

    public static function is_filtered($meta_name) {
        $pref = self::get_filter($meta_name);
        return $pref === 0;
    }

    public static function controls(array $params, $meta_names) {
        global $OUTPUT;

        $controls = self::control_elements($meta_names);

        $table = new html_table();
        $head = array();
        $data = array();
        foreach ($controls as $meta => $control) {
            $head[] = $control->name;
            $attrs = array(
                'type' => 'checkbox',
                'value' => 1,
                'name' => $control->field
            );

            if (!self::is_filtered($meta)) {
                $attrs['checked'] = 'CHECKED';
            }

            $data[] = html_writer::empty_tag('input', $attrs);
        }

        $table->head = $head;
        $table->data[] = $data;

        $html_table = html_writer::table($table);

        $html = $OUTPUT->box_start();
        $html .= html_writer::start_tag('form', array('method' => 'POST'));
        $html .= $html_table;
        $html .= html_writer::start_tag('div', array('class' => 'export_button'));
        $html .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'export',
            'value' => get_string('export_entries', 'block_ues_people')
        ));
        $html .= ' ' . html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'save',
            'value' => get_string('savechanges')
        ));
        $html .= html_writer::end_tag('div');

        foreach ($params as $name => $value) {
            $html .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => $name,
                'value' => $value
            ));
        }

        $html .= html_writer::end_tag('form');
        $html .= $OUTPUT->box_end();

        return $html;
    }
}

class ues_people_element_output {
    var $name;
    var $field;

    function __construct($field, $name = '') {
        $this->field = $field;
        if (empty($name)) {
            $name = $field;
        }
        $this->name = $name;
    }

    function format($user) {
        if (isset($user->{$this->field})) {
            return $user->{$this->field};
        } else {
            return '';
        }
    }
}
