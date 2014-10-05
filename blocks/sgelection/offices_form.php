<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/formslib.php");
require_once($CFG->dirroot.'/blocks/sgelection/lib.php');

class office_form extends moodleform {
    function definition() {
        global $DB;
        $mform =& $this->_form;
        // Setup election return url
        $eid = !empty($this->_customdata['election_id']) ? $this->_customdata['election_id'] : false;
        $returneid = $eid ? array('election_id'=>$eid) : array();

        $id = isset($this->_customdata['id']) ? $this->_customdata['id'] : null;

        $mform->addElement('hidden', 'rtn', $this->_customdata['rtn']);
        $mform->setType('rtn', PARAM_ALPHAEXT);

        // add office header
        $mform->addElement('header', 'displayinfo', get_string('create_new_office', 'block_sgelection'));

        $attributes = array('size' => '50', 'maxlength' => '100');
        $mform->addElement('text', 'name', get_string('title_of_office', 'block_sgelection'), $attributes);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $attributes = array('size' => '50', 'maxlength' => '100');
        $mform->addElement('text', 'number', get_string('number_of_openings', 'block_sgelection'), $attributes);
        $mform->addRule('number', null, 'required', null, 'client');
        $mform->setType('number', PARAM_INT);

        $attributes = array('size' => '5', 'maxlength' => '4');
        $mform->addElement('text', 'weight', get_string('weight', 'block_sgelection'), $attributes);
        $mform->setType('weight', PARAM_INT);
        $mform->setDefault('weight', 3);


        // Limit to College
        $colleges = sge::get_college_selection_box($mform);

        $mform->addElement('static', 'edit_offices', html_writer::link(new moodle_url("officelist.php", $returneid), "edit offices"));

        $buttons = array(
            $mform->createElement('submit', 'save_office', get_string('savechanges')),
            $mform->createElement('cancel')
        );
        $mform->addGroup($buttons, 'buttons', 'actions', array(' '), false);

        if($id){
            $deleteparams = array_merge($returneid, array('id'=>$id, 'class'=>'office', 'rtn'=>'officelist'));
            $mform->addElement('static', 'delete', html_writer::link(new moodle_url("delete.php", $deleteparams), "Delete"));
        }

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
    }

    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        $errors += office::validate_unique_office($data);
        return $errors;
    }

}
