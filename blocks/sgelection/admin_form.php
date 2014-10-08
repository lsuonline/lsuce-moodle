<?php
//require_once $CFG->libdir . '/formslib.php';

require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot.'/blocks/sgelection/lib.php');

class sg_admin_form extends moodleform {

    function definition() {
        global $DB;
        $mform =& $this->_form;

        //add group for text areas
        $mform->addElement('header', 'displayinfo', get_string('election_tool_administration', 'block_sgelection'));

        $mform->addElement('html', '<div class="yui3-skin-sam" >');

        $mform->addElement('text', 'commissioner', get_string('commissioner', 'block_sgelection'));
        $mform->setType('commissioner', PARAM_ALPHANUM);
        $mform->addRule('commissioner', null, 'required', null, 'client');

        $mform->addElement('text', 'fulltime', get_string('fulltime', 'block_sgelection'), 12);
        $mform->setType('fulltime', PARAM_INT);
        $mform->addRule('fulltime', null, 'required', null, 'client');

        $mform->addElement('text', 'parttime', get_string('parttime', 'block_sgelection'), 6);
        $mform->setType('parttime', PARAM_INT);
        $mform->addRule('parttime', null, 'required', null, 'client');

        $mform->addElement('text', 'results_recipients', get_string('results_recips', 'block_sgelection'));
        $mform->setType('results_recipients', PARAM_TEXT);

        $mform->addElement('text', 'results_interval', get_string('results_interval', 'block_sgelection'));
        $mform->setType('results_interval', PARAM_INT);
        $mform->setDefault('results_interval', $this->_customdata['default_results_interval']);
        $mform->addHelpButton('results_interval', 'results_interval', 'block_sgelection');

        $curriculum_codes = $DB->get_records_sql_menu("select id, value from mdl_enrol_ues_usermeta WHERE name = 'user_major' GROUP BY value;");
        $currCodesArray = array();

        foreach($curriculum_codes as $k => $v){
            $currCodesArray[$v] = $v;
        }

        $select = $mform->addElement('select', 'excluded_curr_codes', get_string('excluded_curriculum_code', 'block_sgelection'), $currCodesArray);
        $select->setMultiple(true);
        $mform->setDefault('excluded_curr_codes', array('CCUR', 'LLM'));
        $mform->addElement('html', '</div>');
        $this->add_action_buttons();
    }

    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        //$errors += sge::validate_commisioner($data, 'commissioner');
        $errors += sge::validate_csv_usernames($data, 'results_recipients');
        return $errors;
    }
}
