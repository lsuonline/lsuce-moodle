<?php
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

class UserSelectorPotential extends user_selector_base
{
    private $ulethlib = null;

    /**
    * @param string $name control name
    * @param array $options should have two elements with keys groupid and courseid.
    */
    public function __construct()
    {
        global $CFG, $USER;
        parent::__construct('add_user', array('multiselect'=>true));

        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();
    }
  
    public function find_users($params)
    {

        global $CFG, $DB;

        $this->ulethlib->printToLog("\n ");
        $this->ulethlib->printToLog("\n UserSelectorPotential -> find_users() -> What is params: ", "", $params, true);

        $search = isset($params['search']) ? $params['search'] : null;

        list($wherecondition, $params) = $this->search_sql($search, '');
        
        $fields = 'SELECT ' . $this->required_fields_sql('') . ', email, username';
        $countfields = 'SELECT COUNT(1)';
        
        $sql = " FROM {user} WHERE $wherecondition";
        $order = ' ORDER BY lastname ASC, firstname ASC';
    
        // Check to see if there are too many to show sensibly.
        if (!$this->is_validating()) {
            $potentialcount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialcount > 100) {
                return $this->too_many_results($search, $potentialcount);
                die (json_encode(array("success" => "false", "msg" => "There are too many users to show.....")));
            }
        }
    
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);
        $this->ulethlib->printToLog("\n UserSelectorPotential -> find_users() -> What is availableusers: ", "", $availableusers, true);

        if (empty($availableusers)) {
            return array();
        }
    
        if ($search) {
            $groupname = get_string('potusersmatching', 'role', $search);
        } else {
            $groupname = get_string('potusers', 'role');
        }

        die (json_encode(array("success" => "true", "data" => $availableusers, 'count' => $potentialcount)));
    }
  
    protected function get_options()
    {
        global $CFG;
        $options = parent::get_options();
        // $options['file'] = $CFG->dirroot . '/local/evaluations/lib.php';
        $options['file'] = '/local/utools/lib/UserSelectorPotential.php';
        return $options;
    }
}
