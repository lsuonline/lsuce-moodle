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

class UserSelectorExisting extends user_selector_base
{
    private $ulethlib = null;
    
    /**
    * @param string $name control name
    * @param array $options should have two elements with keys groupid and courseid.
    */
    public function __construct()
    {
        global $CFG, $USER;
        parent::__construct('remove_user', array('multiselect'=>true));
        
        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();

    }
  
    public function find_users($search)
    {
        global $DB, $CFG;

        if (!isset($search)) {
            $search = "";
        }
        
        $this->ulethlib->printToLog("\n ");
        $this->ulethlib->printToLog("\n UserSelectorExisting -> find_users() -> CP - 1");

        list($wherecondition, $params) = $this->search_sql($search, '');
    
        $fields = 'SELECT ' . $this->required_fields_sql('') . ',email';

        // $fields = 'SELECT id, firstname, lastname, username, email';
        $countfields = 'SELECT COUNT(1)';
        $utools_admin_sql = "SELECT userid AS id FROM {utools_user_access}";
        
        if ($wherecondition) {
            $wherecondition = "$wherecondition AND id IN ($utools_admin_sql)";
        } else {
            $wherecondition = "id IN ($utools_admin_sql)";
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
        
        $this->ulethlib->printToLog("\n UserSelectorExisting -> what is availableusers: ", "", $availableusers, true);
        $this->ulethlib->printToLog("\n UserSelectorExisting -> what is groupname: ", "", $groupname, true);
        
        return array($groupname => $availableusers);
    }
  
    protected function get_options()
    {
        global $CFG;
        $options = parent::get_options();
        // $options['file'] = $CFG->dirroot . '/local/evaluations/lib.php';
        $options['file'] = '/local/utools/lib/UserSelector.php';
        return $options;
    }
}
