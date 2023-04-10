<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local My Admin
 *
 * @package   local_myadmin
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class MyAdminLib
{
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
            FROM mdl_local_myadmin_user_admin
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
    public function checkGeneralUser()
    {

        global $DB, $USER;
        $testct_user = $DB->get_record_sql(
            'SELECT count(id) as is_user
            FROM mdl_local_myadmin_user_admin
            WHERE access_level=\'Moodle Staff\'
            AND userid = ?',
            array($USER->id)
        );

        if ($testct_user->is_user == 1) {
            return true;
        } else {
            return false;
        }
    }

    // ================================================================================
    // ================================================================================
    /**
     * Set the  value for My Admin settings
     * @param string, string - setting name and setting value
     * @return the hash value
     */
    // public function setSetting($set_id = 0, $set_name = "dead", $set_value = "dead")
    public function setSetting($set_name = "dead", $set_value = "dead")
    {
        global $DB;
        if ($set_name == "dead" || $set_value == "dead") {
            return;
        }

        $current_setting_value = $DB->get_record('local_myadmin_settings', array('t_name' => $set_name));
        if ($current_setting_value == false) {
            // new installation, need to add records
            // Update the hash value
            $record_insert = new stdClass();
            $record_insert->t_name = $set_name;
            $record_insert->t_value = $set_value;
            $result = $DB->insert_record('local_myadmin_settings', $record_insert, true);
        } else {

            $set_id = $current_setting_value->id;
            
            // Update the hash value
            $record_update = new stdClass();
            $record_update->id = $set_id;
            $record_update->t_value = $set_value;
            $result = $DB->update_record('local_myadmin_settings', $record_update);
        }
    }
    /**
     * Get the value for My Admin settings
     * @param string setting name to get
     * @return the setting object
     */
    public function getSetting($setting_name)
    {
        global $DB;

        if ($setting_name == "") {
            return;
        }


        if ($current_setting_value = $DB->get_record('local_myadmin_settings', array('t_name' => $setting_name))) {

            return $current_setting_value;

        } else {
            if ($current_setting_value == false) {
                // the setting name does not exist, we need to create one
                $last_changed = time();
                $new_dash_hash = md5($last_changed);

                $this->setSetting($setting_name, $new_dash_hash);
            }
            $empty = new stdClass();
            $empty->t_name = $setting_name;
            $empty->t_value = "";

            return $empty; 
        }
    }
}
