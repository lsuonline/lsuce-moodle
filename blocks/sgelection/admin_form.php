<?php
//require_once $CFG->libdir . '/formslib.php';

require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot.'/blocks/sgelection/lib.php');

class sg_admin_form extends moodleform {

    function definition() {
        global $DB;
        $mform =& $this->_form;

        //add group for text areas
        $mform->addElement('header', 'displayinfo', sge::_str('election_tool_administration'));

        $mform->addElement('html', '<div class="yui3-skin-sam" >');

        $mform->addElement('text', 'commissioner', sge::_str('commissioner'));
        $mform->setType('commissioner', PARAM_ALPHANUM);
        $mform->addRule('commissioner', null, 'required', null, 'client');

        $mform->addElement('text', 'fulltime', sge::_str('fulltime'), 12);
        $mform->setType('fulltime', PARAM_INT);
        $mform->addRule('fulltime', null, 'required', null, 'client');

        $mform->addElement('text', 'parttime', sge::_str('parttime'), 6);
        $mform->setType('parttime', PARAM_INT);
        $mform->addRule('parttime', null, 'required', null, 'client');

        $mform->addElement('text', 'results_recipients', sge::_str('results_recips'));
        $mform->setType('results_recipients', PARAM_TEXT);

        $mform->addElement('text', 'results_interval', sge::_str('results_interval'));
        $mform->setType('results_interval', PARAM_INT);
        $mform->setDefault('results_interval', $this->_customdata['default_results_interval']);
        $mform->addHelpButton('results_interval', 'results_interval', 'block_sgelection');

        $sql = "SELECT id, value "
                . "FROM {enrol_ues_usermeta} "
                . "WHERE name = :name "
                . "GROUP BY value;";
        $curriculum_codes = $DB->get_records_sql_menu($sql, array('name' => 'user_major'));
        $currCodesArray = array();

        foreach($curriculum_codes as $k => $v){
            $currCodesArray[$v] = $v;
        }

        $select = $mform->addElement('select', 'excluded_curr_codes', sge::_str('excluded_curriculum_code'), $currCodesArray);
        $select->setMultiple(true);
        $mform->setDefault('excluded_curr_codes', array('CCUR', 'LLM'));
        $mform->addElement('html', '</div>');
        $this->add_action_buttons();
    }

    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        $errors += sge::validate_csv_usernames($data, 'results_recipients');
        return $errors;
    }
}
