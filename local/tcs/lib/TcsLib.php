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

class TcsLib
{

    private $current_site;
    private $is_local;
    private $term;
    private $url_no_term;

    public function __construct()
    {
        global $CFG;
        $this->is_local = false;
        $this_current_site_is = $CFG->wwwroot;
        preg_match_all("/(uleth.ca)/", $this_current_site_is, $matches);

        $url_main = substr($this_current_site_is, 7);

        $this_current_site_is_ssl = null;
        if (@$matches[0][0]) {
            $this->current_site = $CFG->wwwroot."/";
            $this->is_local = false;
            
            $spot_it = strrpos($CFG->wwwroot, "uleth.ca");
            // get the term
            $spot_it = $spot_it + 9;
            $this->term = substr($CFG->wwwroot, $spot_it, 6);

            // get the raw url
            $this->url_no_term = substr($CFG->wwwroot, 0, $spot_it);

        } else {
            $url_main = substr($this_current_site_is, 7);
            $this->current_site = "http://".$url_main."/";
            $url_main = str_replace('u', '', $url_main); // Replaces all spaces with hyphens.
            $this->term = $url_main;
            $this->is_local = true;
        }
    }

    /**
     * return the site back so we know if we are local or remote
     * @return get the current site
     */
    public function getCurrentSite()
    {
        return $this->current_site;
    }

    /**
     * Return the term we are on, 201401, 201402....etc.
     * @return string
     */
    public function getCurrentTerm()
    {
        return $this->term;
    }

    /**
     * Return the raw URL with no term.
     * @return string
     */
    public function getRawURL()
    {
        return $this->url_no_term;
    }
    
    /**
     * return if this object is local or remote
     * @return bool, true if local
     */
    public function isLocal()
    {
        return $this->is_local;
    }

    /**
     * Is this an admin user?
     * @return bool
     */
    public function checkSiteAdminUser()
    {

        $context = context_system::instance();
        $admin_access = has_capability('moodle/site:config', $context);
        if ($admin_access) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is this a System Admin
     * @return bool
     */
    public function isSystemAdmin()
    {
        $context = context_system::instance();
        return has_capability('moodle/site:config', $context);
    }
    
    /**
     * Is this an admin user?
     * @return bool
     */
    public function checkAdminUser()
    {

        global $DB, $USER;
        $testct_user_admin = $DB->get_record_sql(
            'SELECT count(id) as is_admin
            FROM mdl_local_tcms_user_admin
            WHERE access_level=\'Administrator\'
            AND userid = ?',
            array($USER->id)
        );



        if ($testct_user_admin->is_admin == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Is this a tc user?
     * @return bool
     */
    public function checkTestCentreUser()
    {

        global $DB, $USER;
        $testct_user = $DB->get_record_sql(
            'SELECT count(id) as is_user
            FROM mdl_local_tcms_user_admin
            WHERE access_level=\'Test Centre\'
            AND userid = ?',
            array($USER->id)
        );

        if ($testct_user->is_user == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Is this a disability user?
     * @return bool
     */
    public function checkDisabilityUser()
    {

        global $DB, $USER;
        $testct_user_disability = $DB->get_record_sql(
            'SELECT count(id) as is_user
            FROM mdl_local_tcms_user_admin
            WHERE access_level=\'Disability\'
            AND userid = ?',
            array($USER->id)
        );

        if ($testct_user_disability->is_user == 1) {
            return true;
        } else {
            return false;
        }
    }

    // ================================================================================
    // ================================================================================
    /**
     * Description - Set the  value for TCS settings
     * @param string, string - setting name and setting value
     * @return the hash value
     */
    // public function setSetting($set_id = 0, $set_name = "dead", $set_value = "dead")
    public function setSetting($set_name = "dead", $set_value = "dead")
    {
        global $DB;
        if ($set_name == "dead" || $set_value == "dead") {
            // error_log("\nsetSetting() -> You stupid idiot!, there is no settings name or value, ABORT");
            return;
        }

        $current_setting_value = $DB->get_record('local_tcms_settings', array('t_name' => $set_name));
        if ($current_setting_value == false) {
            // new installation, need to add records
            // Update the hash value
            $record_insert = new stdClass();
            // $record_insert->id = $set_id;
            $record_insert->t_name = $set_name;
            $record_insert->t_value = $set_value;
            // $record_insert->comments = $editedValue;
            $result = $DB->insert_record('local_tcms_settings', $record_insert, true);
            // error_log("\n What is the result of the DB insert query: ". $result);
        } else {

            $set_id = $current_setting_value->id;
            
            // error_log("\nsetSetting() -> What is set_id: ". $set_id. 
            // "\nand set_name: ". $set_name.
            // " \nand old set_value: ". $current_setting_value->t_value. "\n".
            // " \nand new set_value: ". $set_value. "\n");
            
            // Update the hash value
            $record_update = new stdClass();
            $record_update->id = $set_id;
            // $record_update->t_name = $set_name;
            $record_update->t_value = $set_value;
            // $record_update->comments = $editedValue;
            $result = $DB->update_record('local_tcms_settings', $record_update);
            // error_log("\n What is the result of the DB update query: ". $result);
        }
    }
    /**
     * Description - Get the value for TCS settings
     * @param string setting name to get
     * @return the setting object
     */
    public function getSetting($setting_name)
    {
        global $DB;
        // error_log("\n\ngetHash() -> START\n");

        if ($setting_name == "") {
            return;
        }

        // $local_call = true;

        // $setting_value = $DB->get_record('local_tcms_settings', array(`t_name` => $setting_name));
        if ($current_setting_value = $DB->get_record('local_tcms_settings', array('t_name' => $setting_name))) {

            return $current_setting_value;

        } else {
            // error_log("\n\n getSetting() -> What is the current value: ". $current_setting_value. " \n");
            // error_log("\n\n getSetting() -> What is the current value gettype: ". gettype($current_setting_value). " \n");
            if ($current_setting_value == false) {
                // the setting name does not exist, we need to create one
                $last_changed = time();
                $new_dash_hash = md5($last_changed);

                $this->setSetting($setting_name, $new_dash_hash);
                // error_log("\n\n getSetting() -> current value is FALSE \n");
            // } else {
                // error_log("\n\n getSetting() -> current value is TRUE \n");
            }
            $empty = new stdClass();
            $empty->t_name = $setting_name;
            $empty->t_value = "";

            return $empty; 
        }
    }
    // ================================================================================
    // ================================================================================

    /**
     * This is just for testing puposes to get dummy data
     * @return json_encoded array of stuff.
     */
    public function getDummyData()
    {

        $data = array(
            array(
                "name" => "Peter Parker",
                "email" => "peterparker@mail.com"
            ),
            array(
                "name" => "Clark Kent",
                "email" => "clarkkent@mail.com"
            ),
            array(
                "name" => "Harry Potter",
                "email" => "harrypotter@mail.com"
            )
        );
        die(json_encode(array("success" => "true", "data" => $data, "msg" => "This is a sample of dummy data")));
    }

    /**
     * This is just for testing puposes to get dummy data
     * @return json_encoded array of stuff.
     */
    public function runQuery()
    {
        global $DB, $CFG;

        error_log("\n\n");
        // error_log("\nrunQuery -> What is CFG->local_tcs_quiz_ip_restriction: ". $CFG->local_tcs_quiz_ip_restriction);


        // -- AND (mq.subnet LIKE \'%\' || ? || \'%\' OR ? LIKE \'%\' || mq.subnet || \'%\')
        // $settings_ips = '142.66.30'; // ,142.66.112.53,142.66.112.52,127.0.0.1';

        // $crazy_sql = "SELECT *
        //     FROM mdl_quiz
        //     WHERE subnet LIKE '%$settings_ips%'";

        // $get_moodle_exams = $DB->get_records_sql($crazy_sql, array(
        //     // $username,
        //     // $username
        //     $CFG->local_tcs_quiz_ip_restriction,
        //     $CFG->local_tcs_quiz_ip_restriction
        // ));

        // error_log("\nrunQuery -> What is result: ". print_r($get_moodle_exams, 1));
        $get_moodle_exams = array();
        die(json_encode(array("success" => "true", "data" => $get_moodle_exams, "msg" => "Query result")));
    }

}
