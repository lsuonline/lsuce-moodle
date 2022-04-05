<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

class UserAdmin
{
    public function __construct()
    {
        global $CFG;
        $CFG->local_tcs_logging ? error_log("\n UserAdmin -> constructor()") : null;
    }

    /**
     * Description Remove user out of the system
     * @param none
     * @return json encoded array with number of current users
     */

    public function getUsers($params = null)
    {
        // error_log("\n UserAdmin.php -> getUsers() -> ================ START ================");
        global $DB;

        $tcms_admins = $DB->get_records_sql(
            'SELECT id,userid,name, username, access_level
            FROM mdl_local_tcms_user_admin'
        );

        // $tcms_admins = array_values($tcms_admins);
        // error_log("\n How many user admins: ". count($tcms_admins));

        foreach ($tcms_admins as &$get_user_info) {
            $get_user_courses = $DB->get_records_sql(
                'SELECT  mq.id as id, fullname||\'-\'||mq.name as examname
                FROM mdl_course mc, mdl_user mu,mdl_enrol me,mdl_user_enrolments mue, mdl_quiz mq
                WHERE mu.username = ? 
                AND mu.id = mue.userid
                AND mue.enrolid = me.id
                AND me.courseid = mc.id
                AND mq.course = mc.id
               
                AND (select extract(epoch from now())) <= mq.timeclose
                ORDER BY examname ASC',
                array($get_user_info->username)
            );
            // $list_of_exams="<select class='form-control'>";
            //$list_of_courses='<option>1</option>';
            $exams = array();
            foreach ($get_user_courses as $get_user_course) {
                // $list_of_exams.="<option>";
                $exams[] = $get_user_course->examname;
                // $list_of_exams.="</option>";

            }
            // $list_of_exams.="</select>";
            // $mform->addElement('html',
            //     "<tr id = $get_user_info->id>
            //         <td>$get_user_info->id</td>
            //         <td><input type= 'checkbox' name = 'selectCheckBox' value = $get_user_info->id></td>
            //         <td>$get_user_info->name</td>
            //         <td>$get_user_info->username</td>
            //         <td>$get_user_info->access_level</td>
            //         <td>$list_of_exams</td>
            //     </tr>"
            // );
            $get_user_info->exams = $exams;
        }
     

        // $mform->addElement('html', '</tbody>') ;
        // $mform->addElement('html', '</table>');
        // $mform->addElement('html', '</div>');
        // //Creating delete button
        // $mform->addElement('button', 'tc_delete_user', get_string('delete_user', 'local_tcms'));
        // error_log("Here is the full list of users: ". print_r($tcms_admins, 1));
        return array(
            'success' => true,
            // 'show_msg' => array(
            //     'title' => 'OK',
            //     "position" => "topRight",
            //     'message' => "The list of users has been retrieved"
            // ),
            'data' => $tcms_admins
        );
    }

    public function addUser($params = null) {
        global $DB, $USER;

        $id = isset($params->id) ? $params->id : null;
        $username = isset($params->username) ? $params->username : null;
        $access_level = isset($params->access_level) ? $params->access_level : null;
        
        //find if this username really exists or not
        $user_exist = $DB->get_record('user', array('username'=> $username));
        $msg_type = "error";
        $msg_title = "Ooops";
        $row = null;

        if ($user_exist) {
            //check if this user is already in the record
            $user_is_in_record = $DB->get_record('local_tcms_user_admin', array('username'=> $username));
            if (!$user_is_in_record) {
                //insert a row
                $record = new stdClass();
                $record->userid = $user_exist->id;
                $record->name  =  $user_exist->lastname.','.$user_exist->firstname;
                $record->username = $user_exist->username;
                $record->access_level = $access_level;
                $inserted_id = $DB->insert_record('local_tcms_user_admin', $record, true);
                
                //Grab the inserted record and send to the calling ajax function
                $user_record = $DB->get_record('local_tcms_user_admin', array('id'=> $inserted_id));

                $get_user_courses_db = $DB->get_records_sql(
                    'SELECT  mq.id as id, fullname||\'-\'||mq.name as examname
                    FROM mdl_course mc, mdl_user mu,mdl_enrol me,mdl_user_enrolments mue, mdl_quiz mq
                    WHERE mu.username = ? 
                    AND mu.id = mue.userid
                    AND mue.enrolid = me.id
                    AND me.courseid = mc.id
                    AND mq.course = mc.id
                
                    AND (select extract(epoch from now())) <= mq.timeclose
                    ORDER BY examname ASC',
                    array($username)
                );
                $get_user_courses = array();
                foreach ($get_user_courses_db as $get_user_course_db) {
                    array_push($get_user_courses, $get_user_course_db->examname);
                }
                
                $row = array(
                    'id' => $inserted_id,
                    'userid' => $user_exist->id,
                    'name' => $user_record->name,
                    'username' => $user_record->username,
                    'access_level' => $user_record->access_level,
                    'exams' => $get_user_courses
                );
                $msg_type = "success";
                $msg_title = "Success";
                $msg = $username." has successfully been added";
                // echo json_encode($row);
            } else {
                $msg = 'Error: This user already has an assigned role!';
            }
        } else {
            $msg = 'Error: There is no such user!';
        }

        return array(
            'msg_type' => $msg_type,
            'show_msg' => array(
                'title' => $msg_title,
                "position" => "topRight",
                'message' => $msg
            ),
            'data' => $row
        );
    }

    public function removeUser($params = null) {

        global $DB, $USER;
        //query out all the users and send their lastname,firstname and uleth username
        $rowid = isset($params->rowid) ? $params->rowid : null;
        $userid = isset($params->userid) ? $params->userid : null;
        $username = isset($params->username) ? $params->username : null;
        // $access_level = isset($params->access_level) ? $params->access_level : null;
        
        // $userids_to_be_deleted = $_POST['checkboxVals'];

        // foreach ($userids_to_be_deleted as $userid) {
        $select = 'userid = '. $userid;
        $params = null;

        if ($DB->delete_records_select('local_tcms_user_admin', $select, $params)) {
            $msg_type = "success";
            $msg_title = "Success";
            $msg = $username. " has been removed.";
        } else {
            $msg_type = "fail";
            $msg_title = "Ooops";
            $msg = "Please report this to the Teaching Centre";
        }
        // }

        return array(
            'msg_type' => $msg_type,
            'show_msg' => array(
                'title' => $msg_title,
                "position" => "topRight",
                'message' => $msg
            ),
            'rowid' => $rowid 
        );
    }
}
