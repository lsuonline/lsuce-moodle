<?php

require_once $CFG->libdir . '/formslib.php';
require_once 'lib.php';

class posting_period_form extends moodleform {
    function definition() {
        $semesters = $this->_customdata['semesters'];

        $m =& $this->_form;

        $_s = ues::gen_str('block_post_grades');

        $m->addElement('header', 'header', $_s('posting_period'));

        $options = array();
        foreach ($semesters as $semester) {
            $options[$semester->id] = "$semester";
        }

        $m->addElement('select', 'post_type', $_s('post_type'), post_grades::valid_types());

        $m->addElement('select', 'semesterid', $_s('semester'), $options);
        $m->setType('semesterid', PARAM_INT);

        $m->addElement('date_time_selector', 'start_time', $_s('start_time'));

        $m->addElement('date_time_selector', 'end_time', $_s('end_time'));

        $m->addElement('checkbox', 'export_number', $_s('export_number'));

        $m->addElement('hidden', 'id', '');
        $m->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}
