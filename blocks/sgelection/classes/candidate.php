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
 * Candidate class
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once('sgedatabaseobject.php');
require_once($CFG->dirroot.'/blocks/sgelection/classes/office.php');
require_once($CFG->dirroot.'/blocks/sgelection/classes/election.php');
require_once($CFG->dirroot.'/enrol/ues/publiclib.php');
ues::require_daos();

class candidate extends sge_database_object{

    public  $id,
            $election_id,
            $userid,
            $office,
            $affiliation;

    static $tablename = "block_sgelection_candidate";
    static $type = 'C';

    public static function get_full_candidates(election $election=null, voter $voter = null, $candid = null){
        global $DB;

        $eid      = $election ? 'e.id = ' . $election->id : '';
        $candid   = $candid   ? 'u.id = ' . $candid : '';
        $col      = $voter  ? sprintf('(o.college = \'%s\' OR o.college = \'\')', $voter->college) : '';

        $clauses = array();
        foreach(array($eid, $candid, $col) as $clause){
            if($clause != ''){
                $clauses[] = $clause;
            }
        }

        $wheres = count($clauses) > 0 ? "WHERE ".implode(' AND ', $clauses) : '';

        $query = 'SELECT CONCAT(u.id, c.id, e.id) AS uniq, u.id AS uid, c.id AS cid, e.id as eid,'
               . ' o.id AS oid, o.name as office, o.college as college, u.firstname, u.lastname, c.affiliation'
               . ' FROM {block_sgelection_candidate} c'
               . ' JOIN'
               . ' {block_sgelection_election} e on c.election_id = e.id'
               . ' JOIN'
               . ' {block_sgelection_office} o on o.id = c.office'
               . ' JOIN'
               . ' {user} u on c.userid = u.id '. $wheres . ' ORDER BY o.college, o.weight ASC';

        return $DB->get_records_sql($query);
    }

    public static function candidates_by_office(election $election = null, voter $voter = null, $candidates = array(), $preview = false){
        if(empty($candidates)){
            $voter = $voter->is_privileged_user() && !$preview ? null : $voter;
            $candidates = self::get_full_candidates($election, $voter);
        }

        $officetocandidates = array();
        foreach($candidates as $c){
            if(!isset($officetocandidates[$c->oid])){
                $officetocandidates[$c->oid] = office::get_by_id($c->oid);
                $officetocandidates[$c->oid]->candidates = array();
                assert(get_class($officetocandidates[$c->oid]) === 'office');
            }
            $officetocandidates[$c->oid]->candidates[$c->cid] = $c;
        }
        return $officetocandidates;
    }

    public static function validate_one_office_per_candidate_per_election($data, $fieldname){

        global $DB;

        // Record already exists, so this will be an update.
        $editmode = isset($data['id']) && $data['id'] > 0;
        $election = election::get_by_id($data['election_id']);
        $eid      = $election->id;
        $userid   = $DB->get_field('user', 'id', array('username'=>$data['username']));
        $count    = $DB->count_records(candidate::$tablename, array('election_id' => $eid, 'userid' => $userid));

        // Expected that one record will exist, if we are in edit mode.
        if($count > 0 && !$editmode){
            // @TODO helper method to get a fuller candidate record, incl. office, election, etc (maybe).
            $candidates = candidate::get_full_candidates($election, null, $userid);
            $a = new stdClass();
            $a->username = $data['username'];
            $a->eid      = $election->id;

            $a->semestername = $election->fullname();
            $offices = array();
            foreach($candidates as $c){
                $office = office::get_by_id($c->oid);
                $offices[] = $office->name . sprintf(" %s [id: %d] ", $office->college, $c->oid);
            }
            // @todo There should never be more than one office in the db per cand/election.
            $a->office = implode(' and ', $offices);
            $errmsg = get_string('err_user_nonunique', 'block_sgelection', $a);

            return array($fieldname => $errmsg);
        }
        return array();
    }

    /**
     * @override
     */
    public function delete(){
        global $DB;
        if($DB->record_exists(vote::$tablename, array('type'=>candidate::$type, 'typeid'=>$this->id))){
            print_error('Votes have been cast for this candidate, cannot delete.');
        }else{
            parent::delete();
        }
    }
}
