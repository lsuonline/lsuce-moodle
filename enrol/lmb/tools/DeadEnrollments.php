<?php
// This file is part of the Banner/LMB plugin for Moodle - http://moodle.org/
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

// require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
// require_once($CFG->libdir.'/adminlib.php');
// require_login();
// require_capability('moodle/site:config', context_system::instance());
// require_once('../enrollib.php');

class DeadEnrollments
{

    private $ulethlib = null;
    private $id = null;
    private $idnumber = null;
    private $fullname = null;
    private $studentid = null;
    private $courseid = null;
    private $coursename = null;

    public function __construct($enrol = null)
    {
        if ($enrol == null) {
            return;
        } else {
            $this->idnumber = isset($enrol->personsourcedid) ? $enrol->personsourcedid : 0;
            $this->courseid = isset($enrol->coursesourcedid) ? $enrol->coursesourcedid : 0;
        }
    }
    
    public function addUser()
    {
        global $DB;

        $user = $DB->get_records_sql(
            "SELECT * FROM {enrol_lmb_people} WHERE sourcedid = ?",
            array($this->idnumber)
        );
        
        // there should only be one object
        $user_count = count($user);
        if ($user_count > 0) {
            $arrayKeys = array_keys($user);
        } else {
            return false;
        }

        $foundit = $DB->get_records_sql(
            "SELECT * FROM {enrol_lmb_dead_enrolments} WHERE idnumber = ? AND courseid = ?",
            array($this->idnumber, $this->courseid)
        );

        $date = new DateTime();

        if ($foundit) {
            // reset the array keys
            $founditKeys = array_keys($foundit);
            // update the time the LMG last hit the server for this user
            $foundit[$founditKeys[0]]->last_modified = $date->getTimestamp();
            $did_it_work = $DB->update_record('enrol_lmb_dead_enrolments', $foundit[$founditKeys[0]], false);
            return false;
        } else {
            // get the course name
            $coursename = $DB->get_field('course', 'fullname', array('idnumber' => $this->courseid));
            
            $dead = new stdClass();
            $dead->idnumber = $this->idnumber;
            $dead->fullname = $user[$arrayKeys[0]]->givenname . " " . $user[$arrayKeys[0]]->familyname;
            $dead->studentid = $user[$arrayKeys[0]]->uofl_id;
            $dead->courseid = $this->courseid;
            $dead->coursename = isset($coursename) ? $coursename : "No Name";
            $dead->last_modified = $date->getTimestamp();

            if ($DB->insert_record('enrol_lmb_dead_enrolments', $dead, true)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function getUsers()
    {
        global $DB;
        
        $users = $DB->get_records_sql(
            "SELECT * FROM {enrol_lmb_dead_enrolments}"
        );
        
        return $users;
    }

    public function refreshUsers()
    {
        global $DB;
        
        $users = $this->getUsers();

        foreach ($users as $zombie) {
            // need to check this user to see if they are in the system
            $username = $DB->get_field('user', 'uofl_id', array('uofl_id' => $zombie->studentid));
            
            if ($username) {
                // this user is now in the users table with their email and username,
                // need to remove this user from the dead_enrollments table
                $DB->delete_records_select('enrol_lmb_dead_enrolments', "id=?", array('id' => $zombie->id));
            }
        }

        return $this->getUsers();
    }
}
