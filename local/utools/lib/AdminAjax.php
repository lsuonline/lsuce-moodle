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

class AdminAjax
{

    private $ulethlib = null;

    public function __construct()
    {
        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();
    }
    
    public function insertRemove($params)
    {
        global $DB, $OUTPUT, $CFG;

        $add_user = isset($params['add']) ? $params['add'] : null;
        $remove_user = isset($params['remove']) ? $params['remove'] : null;
        $userid = isset($params['userid']) ? $params['userid'] : null;
        $username = isset($params['name']) ? $params['name'] : null;
        // roles are admin, watcher, instructor
        $role = isset($params['role']) ? $params['role'] : 'watcher';
        
        $this->ulethlib->printToLog("\n");
        $this->ulethlib->printToLog("\n AdminAjax -> insert() -> what are the params: ", "", $params, true);

        //Check if the addbutton was pressed and users were selected.
        if ($add_user && $userid) {
            $this->ulethlib->printToLog("\n AdminAjax -> insert() -> going to insert user.");

            //Check if the user is already an admin.
            $records = $DB->get_records_select(
                'utools_user_access',
                'userid = ' . $userid
            );
                
            $this->ulethlib->printToLog("\n AdminAjax -> insert() -> WTF is records: ", "", $records, true);
            //If no records were returned then the user is not an admin for
            //this department.
            if (empty($records)) {
                $this->ulethlib->printToLog("\n AdminAjax -> insert() -> user does not exist so inserting now.");
                $user = new stdClass();
                $user->userid = $userid;
                $user->role = $role;
                // roles are admin, watcher, instructor

                if ($DB->insert_record('utools_user_access', $user)) {
                    die (json_encode(array("success" => "true", "msg" => $username. " has been added with role of ".$role)));
                } else {
                    die (json_encode(array("success" => "false", "msg" => "Ooops, the insert has failed. :-(")));
                }
            } else {
                $CFG->local_utools_logging ?
                    error_log("\n AdminAjax -> insert() -> user is already in the table.") : null;
                die (json_encode(array("success" => "false", "msg" => "User already exists, not inserting.")));
            }

        //Check if the remove button was pressed and users were selected.
        } elseif ($remove_user && $userid) {
            $CFG->local_utools_logging ?
                error_log("\n AdminAjax -> insert() -> going to remove user: ".$remove_user) : null;
        
            if ($DB->delete_records_select('utools_user_access', 'userid = ' . $userid)) {
                die (json_encode(array("success" => "true")));
            } else {
                die (json_encode(array("success" => "false", "msg" => "Did not delete the user, user not found.")));
            }
        } else {
            $CFG->local_utools_logging ?
                error_log("\n AdminAjax -> insert() -> going to remove user: ".$remove_user) : null;
        }
    }

    public function getExistingUsers()
    {
        global $DB, $OUTPUT, $CFG;
        include_once($CFG->dirroot . '/local/utools/lib/UserSelectorExisting.php');
        $blank = "";
        
        $user_obj = new UserSelectorExisting();

        $users = $user_obj->find_users($blank);
        
        // error_log("\n  AdminAjax -> getExistingUsers() -> what is users:". print_r($users, true));
        
        // $this->ulethlib->printToLog("\n  AdminAjax -> getExistingUsers() -> what is users:", "", $users, true);
        
        die (json_encode(array("success" => "true", "data" => $users['Existing users'], "count" => count($users['Existing users']))));
    }
}
