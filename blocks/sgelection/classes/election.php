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
require_once $CFG->dirroot.'/blocks/sgelection/classes/sgedatabaseobject.php';
require_once($CFG->dirroot.'/enrol/ues/publiclib.php');
ues::require_daos();

class election extends sge_database_object {
    // @TODO rename 'semester' field to 'semesterid', in line with other fk fields.
    public  $hours_census_start,
            $hours_census_complete,
            $semesterid,
            $name,
            $start_date,
            $end_date,
            $id,
            $ballot,
            $thanksforvoting,
            $test_users;

    public static $tablename = 'block_sgelection_election';

    public function get_ballot(){

    }

    /**
     * Return currently active elections.
     * @global type $DB
     * @return election[]
     */
    public static function get_active() {
        global $DB;
        $now = $then = time();
        $select    = 'end_date >= :now AND start_date <= :then';
        $params    = array('now' => $now, 'then' => $then);
        $elections = $DB->get_records_select(self::$tablename, $select, $params);
        return self::classify_rows($elections);
    }

    public static function get_links($activeonly = true, $useshortname = true){
        $elections = $activeonly ? self::get_active() : self::get_all();
        $links = array();
        foreach($elections as $election){
            $url  = new moodle_url('/blocks/sgelection/ballot.php', array('election_id' => $election->id));
            $text = $useshortname ? $election->shortname() : $election->fullname();
            $links[] = html_writer::link($url, $text);
        }

        return $links;
    }

    public static function get_urls($page, $activeonly = true, $useshortname = true){
        $elections = $activeonly ? self::get_active() : self::get_all();
        $urls = array();
        foreach($elections as $election){
            $name = $useshortname ? $election->shortname() : $election->fullname();
            $url  = new moodle_url("/blocks/sgelection/{$page}.php", array('election_id' => $election->id));
            $urls[$election->id] = array('name' => $name, 'url' => $url);
        }

        return $urls;
    }

    public static function validate_unique($data, $files){
        $update = !empty($data['id']);
        $found  = array();
        $elections = election::get_all(array('semesterid' => $data['semesterid']));
        foreach($elections as $election){
            if($election->name == $data['name']){
                $found[] = $election->fullname();
            }
        }
        if(count($found) > 0 && !$update){
            return array('sem_code' => get_string('err_election_nonunique', 'block_sgelection', implode(',',$found)));
        }
        return array();
    }

    public static function validate_start_end($data, $files){
        $start = $data['start_date'];
        $end   = $data['end_date'];

        if($end > $start){
            return array();
        }
        $a = new stdClass();
        $fmt = self::get_date_format();
        $a->start = strftime($fmt, $start);
        $a->end   = strftime($fmt, $end);

        $msg = get_string('err_start_end_disorder', 'block_sgelection', $a);
        return array('start_date' => $msg);
    }

    public static function validate_census_start($data, $files){
        $start  = $data['start_date'];
        $cstart = $data['hours_census_start'];

        // If the census has already run, we're good.
        if(!empty($data['id'])){
            $e = election::get_by_id($data['id']);
            if(!empty($e->hours_census_complete)){
                return array();
            }
        }

        $semester = ues_semester::by_id($data['semesterid']);
        $earliest = sge::config('earliest_start') * 86400 + $semester->classes_start;
        // Perhaps let the window be user-configurable.
        $window = sge::config('census_window');
        if($cstart <= $start - ($window * 60 * 60) && $cstart > $earliest){
            return array();
        }

        $a = new stdClass();
        $a->start  = strftime('%F %T', $start);
        $a->cstart = strftime('%F %T', $cstart);
        $a->earliest = strftime('%F %T', $earliest);
        $a->window = $window;

        $msg = get_string('err_census_start_too_soon', 'block_sgelection', $a);
        return array('hours_census_start' => $msg);
    }

    public static function validate_times_in_bounds($data, $files) {
        $semester = ues_semester::by_id($data['semesterid']);
        $earliest = $semester->classes_start + sge::config('earliest_start') * 86400;
        $latest   = $semester->grades_due    - sge::config('latest_end') * 86400;
        if($data['start_date'] < $earliest){
            $a = new stdClass();
            $a->earliest = strftime('%F %T', $earliest);
            $a->latest   = strftime('%F %T', $latest);

            $msg = get_string('err_start_end_outofbounds', 'block_sgelection', $a);
            return array('start_date' => $msg);
        }elseif($data['end_date'] > $latest){
            $a = new stdClass();
            $a->earliest = strftime('%F %T', $earliest);
            $a->latest   = strftime('%F %T', $latest);

            $msg = get_string('err_start_end_outofbounds', 'block_sgelection', $a);
            return array('end_date' => $msg);
        }else{
            return array();
        }

    }

    public static function validate_future_start($data, $files) {
        $soonest = sge::config('census_window') * 3600 + $data['hours_census_start'];
        if($data['start_date'] <= $soonest){
            $msg = get_string('err_election_future_start', 'block_sgelection', strftime('%F %T', $soonest));
            return array('start_date' => $msg);
        }elseif($data['hours_census_start'] < time()){
            if(!empty($data['id'])){
                $election = Election::get_by_id($data['id']);
                if(!empty($election->hours_census_complete)){
                    return array();
                }
            }
            $msg = get_string('err_census_future_start', 'block_sgelection');
            return array('hours_census_start' => $msg);
        }else{
            return array();
        }
    }

    public static function get_date_format(){
        return "%F";
    }

    public function polls_are_open() {
        $time = time();
        $open = $this->start_date <= $time && $this->end_date >= $time;
        return $open;
    }

    /**
     * Get the fullname for an election.
     * Provides an easy and consistent way to convert an election to a string.
     *
     * @return string
     */
    public function fullname(){
        $semester = ues_semester::by_id($this->semesterid);
        $a = new stdClass();
        $a->sem  = (string)$semester;
        $a->name = $this->name;
        return get_string('election_fullname', 'block_sgelection', $a);
    }

    /**
     * Get the shortname for an election.
     * Provides an easy and consistent way to convert an election to a short string.
     *
     * @return string
     */
    public function shortname(){
        $semester = ues_semester::by_id($this->semesterid);
        $a = new stdClass();
        $a->sem  = $semester->name;
        $a->name = $this->name;
        return get_string('election_shortname', 'block_sgelection', $a);
    }

    public function get_candidate_votes(office $office){
        global $DB;
        $sql = 'SELECT c.id as cid, typeid, count(*) '
                . 'AS count FROM {block_sgelection_votes} AS v '
            . 'JOIN {block_sgelection_candidate} AS c on c.id = v.typeid '
            . 'JOIN {block_sgelection_office} AS o on o.id = c.office '
            . 'WHERE type = "'.candidate::$type.'" '
                . 'AND o.id = :oid '
                . 'AND c.election_id = :eid '
                . 'AND v.finalvote = 1 '
            . 'GROUP BY typeid;';
        $params = array('oid'=>$office->id, 'eid'=>$this->id);

        return $DB->get_records_sql($sql, $params);
    }

    public function get_resolution_votes(){
        global $DB;
        $sql = 'SELECT res.title, '
        . '(SELECT count(id) FROM {block_sgelection_votes} as v WHERE v.typeid = res.id AND v.finalvote = 1 AND v.type = "'.resolution::$type.'" AND vote = 2) AS yes, '
        . '(SELECT count(id) FROM {block_sgelection_votes} as v WHERE v.typeid = res.id AND v.finalvote = 1 AND v.type = "'.resolution::$type.'" AND vote = 1) AS against, '
        . '(SELECT count(id) FROM {block_sgelection_votes} as v WHERE v.typeid = res.id AND v.finalvote = 1 AND v.type = "'.resolution::$type.'" AND vote = 3) AS abstain '
        . 'FROM {block_sgelection_resolution} AS res WHERE res.election_id = :eid';
        $params = array('eid' => $this->id);
        return $DB->get_records_sql($sql, $params);
    }

    public function get_summary(){
        global $CFG;
        require_once $CFG->dirroot.'/blocks/sgelection/renderer.php';
        return block_sgelection_renderer::office_results($this).block_sgelection_renderer::resolution_results($this);

    }

    public function email_results() {
        global $DB;
        $summary = $this->get_summary();
        foreach(explode(',', sge::config('results_recipients')) as $admin){
            $user = $DB->get_record('user', array('username'=>$admin));
            email_to_user($user, 'no-reply', get_string("election_summary", 'block_sgelection', $this->shortname()), $summary, $summary);
        }
    }

    public function readonly(){
        return $this->end_date < time();
    }

    public function is_test_election(){
        return !empty($this->test_users);
    }

    public function is_test_user($username){
        $usernames = explode(',',$this->test_users);
        return in_array($username, $usernames);
    }
}
