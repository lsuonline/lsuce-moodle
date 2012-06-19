<?php

abstract class meta_data_ui_element {
    protected $name;
    protected $key;
    protected $value;

    function __construct($field, $name) {
        $this->key($field);

        $this->name($name);

        $this->value = optional_param($this->key, null, PARAM_TEXT);
    }

    public function key($key = null) {
        if ($key) {
            $this->key = $key;
        }
        return $this->key;
    }

    public function name($name = null) {
        if ($name) {
            $this->name = $name;
        }

        return $this->name;
    }

    public function value() {
        return $this->value;
    }

    public function format($user) {
        if (!isset($user->{$this->key()})) {
            return get_string('not_available', 'block_ues_meta_viewer');
        }

        return $user->{$this->key()};
    }

    public function translate_value($dsl) {
        $value = trim($this->value());
        $strip = function ($what) use ($value) {
            return preg_replace('/%/', '', $value);
        };

        if (strpos($value, ',')) {
            return $dsl->in(explode(',', $value));
        } else if (strpos($value, '%') === 0 and strpos($value, '%', 1) > 0) {
            return $dsl->like($strip('%'));
        } else if (strpos($value, '%') === 0) {
            return $dsl->ends_with($strip('%'));
        } else if (strpos($value, '%') > 0) {
            return $dsl->starts_with($strip('%'));
        } else if (strpos($value, '<') === 0) {
            return $dsl->less($strip('<'));
        } else if (strpos($value, '>') === 0) {
            return $dsl->greater($strip('>'));
        } else if (strtolower($value) == 'null') {
            return $dsl->is(NULL)->equal('');
        } else if (strtolower($value) == 'not null') {
            return $dsl->not_equal('');
        } else {
            return $dsl->equal($value);
        }
    }

    public abstract function html();
    public abstract function sql($dsl);
}

class meta_data_text_box extends meta_data_ui_element {
    public function html() {
        $params = array(
            'type' => 'text',
            'placeholder' => $this->name(),
            'name' => $this->key()
        );

        if (trim($this->value()) !== '') {
            $params['value'] = $this->value();
        }

        return html_writer::empty_tag('input', $params);
    }

    public function sql($dsl) {
        $key = $this->key();
        $value = $this->value();

        if (trim($value) === '') {
            return $dsl;
        }

        return $this->translate_value($dsl->{$key});
    }
}

