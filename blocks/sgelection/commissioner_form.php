<?php
//require_once $CFG->libdir . '/formslib.php';

require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot.'/blocks/sgelection/lib.php');

class commissioner_form extends moodleform {

    function definition() {

        $mform =& $this->_form;

        $datedefaults = $this->_customdata['datedefaults'];

        //add group for text areas
        $mform->addElement('header', 'displayinfo', get_string('new_election_options', 'block_sgelection'));

        // id field for editing.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // census_complete field for editing.
        $mform->addElement('hidden', 'hours_census_complete');
        $mform->setType('hours_census_complete', PARAM_INT);

        $mform->addELement('select', 'semesterid', get_string('semester', 'block_sgelection'), $this->_customdata['semesters']);
        $mform->setType('semesterid', PARAM_INT);
        $mform->addRule('semesterid', null, 'required', null, 'client');

        $mform->addELement('text', 'name', get_string('name', 'block_sgelection'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'hours_census_start', get_string('hours_census_start', 'block_sgelection'), $datedefaults);
        $mform->addRule('hours_census_start', null, 'required', null, 'client');
        $mform->addHelpButton('hours_census_start', 'hours_census_start', 'block_sgelection');

        $mform->addElement('date_time_selector', 'start_date', get_string('start_date', 'block_sgelection'), $datedefaults);
        $mform->addRule('start_date', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'end_date', get_string('end_date', 'block_sgelection'), $datedefaults);
        $mform->addRule('end_date', null, 'required', null, 'client');

        $mform->addElement('editor', 'thanksforvoting_editor', get_string('thanks_for_voting_message', 'block_sgelection'));
        $mform->setType('thanksforvoting', PARAM_RAW);

        $mform->addElement('textarea', 'common_college_offices', get_string('common_college_offices', 'block_sgelection'), array('rows'=>'10'));
        $mform->setType('common_college_offices', PARAM_TEXT);
        $common_offices_defaults = sge::config('common_college_offices');
        $common_offices_defaults = $common_offices_defaults ? $common_offices_defaults : implode(',', array(
            'College Council President', 'College Council Vice-President', 'Council Member-at-Large', 'Senate Full', 'Senate Half'));
        $mform->setDefault('common_college_offices', $common_offices_defaults);

        $mform->addElement('textarea', 'test_users', get_string('test_users', 'block_sgelection'), array('rows'=>'10'));
        $mform->setType('test_users', PARAM_TEXT);

        $this->add_action_buttons();
    }

    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        $errors += election::validate_unique($data, $files);
        $errors += election::validate_start_end($data, $files);
        $errors += election::validate_census_start($data, $files);
        $errors += election::validate_times_in_bounds($data, $files);
        $errors += election::validate_future_start($data, $files);
        $errors += sge::validate_csv_usernames($data, 'test_users');
        return $errors;
    }
}