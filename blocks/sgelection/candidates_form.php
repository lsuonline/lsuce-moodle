<?php
global $CFG, $PAGE;
require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot.'/blocks/sgelection/lib.php');
require_once 'lib.php';
$PAGE->requires->js('/blocks/sgelection/js/autouserlookup.js');
class candidate_form extends moodleform {
    function definition() {
        global $DB, $PAGE;
        $mform =& $this->_form;
        $election = $this->_customdata['election'];
        $id = isset($this->_customdata['id']) ? $this->_customdata['id'] : null;

        // ADD CANDIDATES HEADER
        $offices = $DB->get_records('block_sgelection_office');
        $options = array();
        $configoffices = explode(',', sge::config('common_college_offices'));
        foreach($offices as $officeid => $office){
            $college = '';
            if(!empty($office->college)){
                if(!in_array($office->name, $configoffices)){
                    continue;
                }
                $college = sprintf(" [%s]", $office->college);
            }
            $options[$officeid] = $office->name.$college;
        }
        if(count($options) > 0){

            $mform->addElement('hidden', 'id', null);
            $mform->setType('id', PARAM_INT);

            $mform->addElement('hidden', 'election_id', $election->id);
            $mform->setType('election_id', PARAM_INT);

            $mform->addElement('header', 'displayinfo', get_string('create_new_candidate', 'block_sgelection'));

            $attributes = array('size' => '50', 'maxlength' => '100');
            $mform->addElement('text', 'username', get_string('paws_id_of_candidate', 'block_sgelection'), $attributes);
            $mform->setType('username', PARAM_ALPHANUM);
            $mform->addRule('username', null, 'required', null, 'client');

            //add office dropdown
            $mform->addElement('text', 'affiliation', get_string('affiliation', 'block_sgelection'));
            $mform->setType('affiliation', PARAM_TEXT);
            // add affiliation dropdown
            $mform->addElement('select', 'office', get_string('office_candidate_is_running_for', 'block_sgelection'),$options);

            $buttons = array(
                $mform->createElement('submit', 'save_candidate', get_string('savechanges')),
                $mform->createElement('cancel')
                );
            $mform->addGroup($buttons, 'buttons', 'actions', array(' '), false);
            if($id){
                $mform->addElement('static', 'delete', html_writer::link(new moodle_url("delete.php", array('id'=>$id, 'class'=>'candidate', 'election_id'=>$election->id, 'rtn'=>'ballot')), "Delete"));
            }
            $listofusers = sge::get_list_of_usernames();
            $PAGE->requires->js_init_call('autouserlookup', array($listofusers, '#id_username'));    
        }


    }

    function validation($data, $files) {

        $errors = parent::validation($data, $files);
        $errors += sge::validate_username($data, 'username');
        $errors += candidate::validate_one_office_per_candidate_per_election($data, 'username');

        return $errors;
    }
}
