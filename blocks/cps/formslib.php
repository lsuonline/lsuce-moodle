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
 * @package    block_cps
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once $CFG->libdir . '/formslib.php';

interface generic_states {
    const SELECT = 'select';
    const SHELLS = 'shells';
    const DECIDE = 'decide';
    const CONFIRM = 'confirm';
    const LOADING = 'loading';
    const FINISHED = 'finish';
    const UPDATE = 'update';
}

interface finalized_form {
    function process($data, $courses);

    function display();
}

interface updating_form {
    const UNDO = 1;
    const RESHELL = 2;
    const REARRANGE = 3;
}

abstract class cps_form extends moodleform implements generic_states {
    var $current;
    var $next;
    var $prev;

    public static function _s($key, $a = null) {
        return get_string($key, 'block_cps', $a);
    }

    public static function first() {
        return optional_param('current', self::SELECT, PARAM_ALPHA);
    }

    public static function next_from($prefix, $next, $data, $courses) {
        $form = self::create($prefix, $courses, $next, $data);

        self::navs($form->current);

        $directions = new stdClass;
        $directions->current = $form->current;
        $directions->prev = $form->prev;
        $directions->next = $form->next;

        $form->set_data($directions);

        return $form;
    }

    public static function create($prefix, $courses, $state = null, $extra= null) {
        $state = $state ? $state : self::first();

        // Interject loading screen
        if ($state == self::LOADING) {
            return new cps_loading_form($extra);
        }

        $class = $prefix . '_form_' . $state;

        $data = $class::build($courses);

        if ($extra) {
            $data += get_object_vars($extra);
        }

        $form = new $class(null, $data);
        $form->set_data($data);

        return $form;
    }

    public static function prep_reshell() {
        $reshell = optional_param('reshelled', 0, PARAM_INT);

        $shells = optional_param('shells', null, PARAM_INT);

        $extra = $shells ? array('shells' => $shells) : array();

        return $extra;
    }

    public static function conform_reshell() {
        $shells = required_param('shells', PARAM_INT);

        $reshell = optional_param('reshelled', 0, PARAM_INT);

        // Don't need to dup this add
        $current = required_param('current', PARAM_TEXT);

        $to_add = ($reshell and $current == self::UPDATE);

        $extra = array(
            'shells' => $to_add ? $reshell : $shells,
            'reshelled' => $reshell
        );

        return $extra;
    }

    public static function navs($state) {
        global $PAGE;
        $PAGE->navbar->add(self::_s($state));
    }

    public function to_display($sem) {
        $func = array($this, 'display_course');
        return function ($course) use ($sem, $func) {
            return call_user_func_array($func, array($course, $sem));
        };
    }

    public function display_course($course, $sem) {
        $semester_name = $this->display_semester($sem);
        return "$semester_name $course->department $course->cou_number";
    }

    public function display_semester($sem) {
        $session = $sem->get_session_key();

        return "$sem->year $sem->name$session";
    }

    protected function generate_states() {
        $m =& $this->_form;

        $m->addElement('hidden', 'current', $this->current);
        $m->setType('current', PARAM_ALPHA);

        if (!empty($this->next)) {
            $m->addElement('hidden', 'next', $this->next);
            $m->setType('next', PARAM_ALPHA);
        }

        if (!empty($this->prev)) {
            $m->addElement('hidden', 'prev', $this->prev);
            $m->setType('prev', PARAM_ALPHA);
        }
    }

    protected function generate_buttons() {
        $m =& $this->_form;

        $buttons = array();

        if (!empty($this->prev)) {
            $buttons[] = $m->createElement('submit', 'back', self::_s('back'));
        }
        

        $buttons[] = $m->createElement('cancel');

        if (!empty($this->next)) {
            $buttons[] = $m->createElement('submit', 'save', self::_s('next'));
        }

        $m->addGroup($buttons, 'buttons', '&nbsp;', array(' '), false);
        $m->closeHeaderBefore('buttons');
    }

    protected function generate_states_and_buttons() {
        $this->generate_states();

        $this->generate_buttons();
    }

    protected function split_movers() {
        global $OUTPUT;

        $move_left = html_writer::empty_tag('input', array(
            'type' => 'button',
            'value' => $OUTPUT->larrow(),
            'name' => 'move_left'
        ));

        $move_right= html_writer::empty_tag('input', array(
            'type' => 'button',
            'value' => $OUTPUT->rarrow(),
            'name' => 'move_right'
        ));

        return html_writer::tag('div',
            $move_left . '<br/>' . $move_right,
            array('class' => 'split_movers')
        );
    }

    protected function mover_form($previous_label, $previous, $shells) {
        $this->_form->addElement('html', '<div id="split_error"></div>');

        $previous_html = html_writer::tag('div',
            $previous_label->toHtml() . '<br/>' . $previous->toHtml(),
            array('class' => 'split_available_sections')
        );

        $button_html = $this->split_movers();

        $split_html = html_writer::tag('div',
            implode('<br/>', $shells),
            array('class' => 'split_bucket_sections')
        );

        return html_writer::tag('div',
            implode(' ', array($previous_html, $button_html, $split_html)),
            array('class' => 'split_mover_form')
        );
    }
}

class cps_loading_form implements generic_states {
    var $next = self::FINISHED;
    var $current = self::LOADING;
    var $prev = self::CONFIRM;

    function __construct($data) {
        unset($data->next);
        unset($data->current);

        $this->data = $data;
    }

    function get_data() {
        $data = data_submitted();

        return (object) $data;
    }

    // Stub?
    function set_data($data) {
    }

    function is_cancelled() {
        return false;
    }

    function display() {
        global $PAGE, $OUTPUT;

        $PAGE->requires->js('/blocks/cps/js/loading.js');

        $_s = ues::gen_str('block_cps');

        echo $OUTPUT->box_start('generalbox cps_loading');
        echo $OUTPUT->notification($_s('please_wait'));

        $this->data->next = self::FINISHED;
        $this->data->current = self::LOADING;

        $attrs = array('type' => 'hidden', 'class' => 'passed');

        foreach (get_object_vars($this->data) as $name => $value) {
            $unqiue = array('name' => $name, 'value' => $value);

            echo html_writer::empty_tag('input', $attrs + $unqiue);
        }

        echo html_writer::tag('center',
            $OUTPUT->pix_icon('i/loading', 'Loading')
        );

        echo $OUTPUT->box_end();
        echo html_writer::tag('div',
            $OUTPUT->notification($_s('network_failure')) .
            $OUTPUT->continue_button(new moodle_url('/my')),
            array('class' => 'network_failure', 'style' => 'display: none;')
        );
    }
}
