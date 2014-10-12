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
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot.'/blocks/sgelection/classes/election.php');


class sge {

    const FACADVISOR    = 'facadvisor';
    const COMMISSIONER = 'commissioner';
    /**
     * Helper method called from forms' validation() methods; verifies existence of a user.
     *
     * @global type $DB
     * @param array $data key=>value array representing submitted form data; provided by moodle formslib.
     * @param string $fieldname name of the field to which the err message should be attached in the return array.
     * @return array empty if user exists, otherwise having the form array($fieldname => $message)
     */

    public static function validate_username($data, $fieldname){
        global $DB;
        $userexists = $DB->record_exists('user', array('username'=>trim($data[$fieldname])));
        if($userexists){
            return array();
        }else{
            return array($fieldname => get_string('err_user_nonexist', 'block_sgelection',  $data[$fieldname]));
        }
    }

    public static function validate_csv_usernames($data, $fieldname){
        $errors = array();
        if(empty($data[$fieldname])){
            return $errors;
        }

        foreach(explode(',', $data[$fieldname]) as $name){
            $username_check = self::validate_username(array($fieldname => $name), $fieldname);
            if(!empty($username_check)){
                $errors[] = $username_check[$fieldname];
            }
        }

        if(!empty($errors)){
            $errors = array($fieldname => implode(' ', $errors));
        }
        return $errors;
    }

    /**
     * Helper fn to make requiring many classes easier.
     *
     * @TODO consider scanning the directory instead of manually maintaining the list.
     * @global type $CFG
     */
    public static function require_db_classes(){
        global $CFG;
        $classesroot = $CFG->dirroot.'/blocks/sgelection/classes';
        $files = get_directory_list($classesroot, '', false, false);

        foreach($files as $f){
            require_once $classesroot.'/'.$f;
        }
    }

    /**
     * Helper function to easily build this commonly-used destination.
     * @param int $eid
     * @return \moodle_url
     */
    public static function ballot_url($eid){
        return new moodle_url('/blocks/sgelection/ballot.php', array('election_id'=>$eid));
    }

    /**
     * Strip the given prefix from the given word.
     *
     * Specifically designed as a helper method to
     * map friendly attribute names to ues db field names.
     *
     * @param string $word string to trim perfix from
     * @param $prefix prefix to trim from word
     * @return string
     */
    public static function trim_prefix($word, $prefix){
        $len = strlen($prefix);
        if(substr_compare($word, $prefix, 0, $len) == 0){
                $word = substr($word, $len);
        }
        return $word;
    }

    /**
     * Get all rows in the enrol_ues_semesters table having grades_due > now().
     *
     * These are used when creating a new election; a new election will either fall in
     * the current semester or in a future semester.
     *
     * Relatedly, when checking user eligibility, we need to know
     * their credit hour enrollment for a given semester.
     *
     * @global type $DB
     * @return ues_semester[]
     */
    public static function commissioner_form_available_semesters() {
        global $DB;
        $sql = "SELECT * FROM {enrol_ues_semesters} WHERE grades_due > :time";
        $raw = $DB->get_records_sql($sql, array('time'=>time()));
        $availablesemesters = array();
        foreach($raw as $sem){
            $availablesemesters[] = ues_semester::upgrade($sem);
        }
        return $availablesemesters;
    }

    /**
     * @param ues_semester[] $availablesemesters
     * @return array
     */
    public static function commissioner_form_available_semesters_menu(array $availablesemesters = array()){
        if(empty($availablesemesters)){
            $availablesemesters = self::commissioner_form_available_semesters();
        }
        $menu = array();
        foreach($availablesemesters as $s){
            $menu[$s->id] = (string)$s;
        }
        return $menu;
    }

    /**
     * Given an array of ues_semesters, determine the earliest year in which
     * a semester starts, and the latest year in which a semester ends.
     *
     * @param ues_semester[] $semesters
     * @return int[]
     */
    public static function commissioner_form_semester_year_range(array $semesters = array()){
        if(empty($semesters)){
            $semesters = self::commissioner_form_available_semesters();
        }
        $now = new DateTime();
        $yearnow = $now->format('Y');
        $min = $max = (int)$yearnow;

        foreach($semesters as $s){
            $start = (int)strftime('%Y', $s->classes_start);
            $end   = (int)strftime('%Y', $s->grades_due);
            $min = $start < $min ? $start : $min;
            $max = $end   > $max ? $end   : $max;
        }

        return array($min, $max);
    }

    /**
     * Helper method to generate a college selection form control.
     *
     * @TODO consider moving the $mform calls back into the form, using this method
     * only to generate the array required to make that control.
     * @global type $DB
     * @param type $mform
     * @param type $selected
     */
    public static function get_college_selection_box($mform, $selected = false){
        global $DB;
        $colleges = self::get_distinct_colleges();
        $attributes = array(''=>'none');
        $attributes += array_combine(array_keys($colleges), array_keys($colleges));
        $collegeselector = $mform->addElement('select', 'college', get_string('limit_to_college', 'block_sgelection'), $attributes);
        if($selected && in_array($selected, array_keys($colleges))){
            $collegeselector->setSelected($selected);
        }
    }

    public static function get_distinct_colleges(){
        global $DB;
        $sql = "SELECT DISTINCT value from {enrol_ues_usermeta} where name = 'user_college'";
        return $DB->get_records_sql($sql);
    }

    /**
     * Helper method to return plugin-specific config values with a shorter method call.
     * @param string $name config_plugins key
     * @return string
     */
    public static function config($name = null, $value = null){
        if(null !== $name && null !== $value){
            return set_config($name, $value, 'block_sgelection');
        }elseif(null !== $name){
            return get_config('block_sgelection', $name);
        }else{
            return get_config('block_sgelection');
        }
    }

    public static function voter_can_do_anything(voter $voter, election $election) {
        $is_editingcommissioner = $voter->is_commissioner() && !$election->polls_are_open();
        // NB: excluding Moodle site admins from this check.
        return $voter->is_faculty_advisor() || $is_editingcommissioner || is_siteadmin();
    }

    /**
     * Helper function for username autocopmlete controls.
     * @TODO this function should only pull UES users who do NOT have a directory hold.
     * @global type $DB
     * @return type
     */
    public static function get_list_of_usernames(){
        global $DB;
        $listofusers = array();
        $sql = "select u.username "
                . "from {user} u "
                . "RIGHT JOIN {enrol_ues_usermeta} um "
                .     "ON u.id = um.userid WHERE um.name = 'user_ferpa' and value = 0;";

        $users = $DB->get_records_sql($sql);
        foreach ($users as $user) {
            $listofusers[] = $user->username;
        }
        return $listofusers;
    }

    /**
     * This function can be called with a variable number of args,
     * one for each user (Fac. Adv, Commissioner) allowed. Siteadmins always pass.
     * @global type $USER
     * @return boolean
     */
    public static function allow_only(){
        require_once 'classes/voter.php';
        global $USER;

        if(is_siteadmin()){
            return true;
        }

        $voter = new Voter($USER->id);
        foreach(func_get_args() as $allowed){
            if($allowed === self::FACADVISOR && $voter->is_faculty_advisor()){
                return true;
            }
            if($allowed === self::COMMISSIONER && $voter->is_commissioner()){
                return true;
            }
        }
        redirect(new moodle_url('/my'));
    }

    /**
     * Since voter eligibility is based on the number of hours he/she has enrolled
     * in for a given semester, we need to calculate this before each election.
     *
     * The date when this can be calculated is set by the Commissioner of elections
     * https://trello.com/c/e0Idk1O2 and at cron(), we look for any elections for
     * which the following is true:
     * current time is before the election start time
     * current time is after the census_start time (as set by the commissioner).
     *
     * If we find any of these elections, the app will need to calculate enrolled hours for
     * the ues_semester in which the election occurs (block_sgelection_election.semesterid).
     * Therefore, we finish here by returning an array of those ues_semesters.
     *
     * @global stdClass $DB
     * @return ues_semester[] keyed by id, considered eligible for census.
     */
    public static function semesters_eligible_for_census(){
        global $DB;
        $result = array();
        $where  = "hours_census_start < :now "
                . "AND (hours_census_complete IS NULL "
                .    "OR hours_census_complete = 0) " // May never actually be null (@see commissioner_form).
                . "AND start_date > :then";
        $raw    = $DB->get_records_select(Election::$tablename, $where, array('now'=>time(), 'then'=>time()));
        foreach($raw as $r){
            $s = ues_semester::by_id($r->semesterid);
            if($s){
                $result[$r->id] = $s;
            }
        }
        return $result;
    }

    public static function calculate_all_enrolled_hours_for_semester(ues_semester $s){
        global $DB;
        $sql = "SELECT "
                . " ustu.userid as userid, sum(ustu.credit_hours) hours, usem.id as semesterid"
                . " FROM {enrol_ues_students} as ustu"
                . "    JOIN {enrol_ues_sections} usec ON usec.id = ustu.sectionid"
                . "    JOIN {enrol_ues_semesters} usem ON usem.id = usec.semesterid"
                . " WHERE ustu.status = 'enrolled'"
                . "    AND usem.id = :semid"
                . " GROUP BY ustu.userid;";

        return $DB->get_records_sql($sql, array('semid'=>$s->id));
    }

    public static function _str($key, $a=null){
       return get_string($key, 'block_sgelection', $a);
    }
}
