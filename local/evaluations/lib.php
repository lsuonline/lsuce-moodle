<?php

/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/evaluations/locallib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->libdir  . '/adminlib.php');

function local_evaluations_cron() {
    global $DB;

//early_semester_messages();  

    $evaluations = $DB->get_records('evaluations', array('complete' => 0, 'deleted' => 0));

    foreach ($evaluations as $eval) {
        if (eval_check_status($eval) == 2) { //will complete evals, or return status
            //its inprogress - need to send reminders
            $course = $DB->get_record('course', array('id' => $eval->course));

            send_student_reminders($eval, $course);
        }
    }

    process_mail_que();
}

//event called when an eval is set as complete
function eval_complete_handler($event) {
    global $DB;
//send an email to the instructor informing them its complete
    //include a report with anonymous user responses
    //Tell them number of responses

    $eval = $DB->get_record('evaluations', array('id' => $event->eval_id));
//eval_complete_message($eval);
    return true;
}

//event called when an eval is created
function eval_created_handler($event) {
    //check if inviliators - send them an email with date

    return true;
}


class evaluation_admins_potential_selector extends user_selector_base {
    /**
    * @param string $name control name
    * @param array $options should have two elements with keys groupid and courseid.
    */
    public function __construct() {
        global $CFG, $USER;
        parent::__construct('add_user', array('multiselect'=>true));
    }
  
    public function find_users($search) {
        global $CFG, $DB;
        list($wherecondition, $params) = $this->search_sql($search, '');
        
        $fields = 'SELECT ' . $this->required_fields_sql('');
        $countfields = 'SELECT COUNT(1)';
        $sql = " FROM {user} WHERE $wherecondition";
        $order = ' ORDER BY lastname ASC, firstname ASC';
    
        // Check to see if there are too many to show sensibly.
        if (!$this->is_validating()) {
            $potentialcount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialcount > 100) {
                return $this->too_many_results($search, $potentialcount);
            }
        }
    
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availableusers)) {
            return array();
        }
    
        if ($search) {
            $groupname = get_string('potusersmatching', 'role', $search);
        } else {
            $groupname = get_string('potusers', 'role');
        }

        return array($groupname => $availableusers);
    }
  
    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        // $options['file'] = $CFG->dirroot . '/local/evaluations/lib.php';
        $options['file'] = '/local/evaluations/lib.php';
        return $options;
    }
}

class evaluation_admins_existing_selector extends user_selector_base {
    
    //The selected department.
    private $dept;
    /**
    * @param string $name control name
    * @param array $options should have two elements with keys groupid and courseid.
    */
    public function __construct($dept) {
        global $CFG, $USER;
        $this->dept = $dept;
        parent::__construct('remove_user', array('multiselect'=>true));
    }
  
    public function find_users($search) {
        global $DB, $CFG;
        list($wherecondition, $params) = $this->search_sql($search, '');
    
        $fields = 'SELECT ' . $this->required_fields_sql('');
        $countfields = 'SELECT COUNT(1)';
        $invigilator_sql = "SELECT userid AS id FROM {department_administrators} WHERE department = '$this->dept'";
        
        if ($wherecondition) {
            $wherecondition = "$wherecondition AND id IN ($invigilator_sql)";
        } else {
            $wherecondition = "id IN ($invigilator_sql)";
        }
        
        $sql   = " FROM {user} WHERE $wherecondition";
        $order = ' ORDER BY lastname ASC, firstname ASC';
    
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);
    
        if (empty($availableusers)) {
            return array();
        }
    
        if ($search) {
            $groupname = get_string('extusersmatching', 'role', $search);
        } else {
            $groupname = get_string('extusers', 'role');
        }
    
        return array($groupname => $availableusers);
    }
  
    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        // $options['file'] = $CFG->dirroot . '/local/evaluations/lib.php';
        $options['file'] = '/local/evaluations/lib.php';
        return $options;
    }
}
