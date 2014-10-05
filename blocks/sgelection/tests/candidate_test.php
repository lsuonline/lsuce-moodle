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
 * Tests for candidate class
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;

require_once 'classes/candidate.php';
require_once 'classes/election.php';
require_once 'classes/office.php';
require_once 'tests/sgetestbase.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/enrol/ues/publiclib.php');
ues::require_daos();

class candidate_class_testcase extends block_sgelection_base {

    public function setup(){
        $this->resetAfterTest();
        $this->scenario();
    }

    public function test_construct() {

        $user = $this->getDataGenerator()->create_user(array('username'=>'ima-winna'));
        $eid = 1;
        $username = 'ima-winna';
        $office = 4;
        $affiliation = 'Lions';


        $params = array(
            'userid' => $user->id,
            'election_id'      => $eid,
            'office'   => $office,
            'affiliation' => $affiliation,
        );
        $candidate = new candidate($params);
        $this->assertEquals($eid, $candidate->election_id);
        $this->assertEquals($user->id, $candidate->userid);
        $this->assertEquals($office, $candidate->office);
        $this->assertEquals($affiliation, $candidate->affiliation);
    }

    public function test_get_full_candidates_election(){
        $test1 = candidate::get_full_candidates($this->oldelection);
        $this->assertEquals(1, count($test1));
        $testcand1 = array_pop($test1);
        $this->assertEquals($this->cand1->userid, $testcand1->uid);
        $this->assertEquals($this->user1->firstname, $testcand1->firstname);

        $test2 = candidate::get_full_candidates($this->currentelection);
        $this->assertEquals(4, count($test2));

        $eid  = $this->currentelection->id;
        $this->assertNotEmpty($test2[$this->full_candidate_key_helper($this->cand2).$eid]);
        $this->assertNotEmpty($test2[$this->full_candidate_key_helper($this->cand3).$eid]);
    }

    private function full_candidate_key_helper($candidate){
        return $candidate->userid.$candidate->id;
    }

    private function scenario(){

        // current election
        $eparams = new stdClass();
        $eparams->name      = 'current';
        $eparams->semesterid = 1;
        $eparams->start_date = 2014;
        $eparams->end_date = 2015;
        $eparams->hours_census_start = $eparams->start_date - 86400;

        $this->currentelection = new election($eparams);
        $this->currentelection->save();

        // not current election
        $eparams = new stdClass();
        $eparams->name      = 'past';
        $eparams->semesterid = 2;
        $eparams->start_date = 2014;
        $eparams->end_date = 2015;
        $eparams->hours_census_start = $eparams->start_date - 86400;

        $this->oldelection = new election($eparams);
        $this->oldelection->save();

        $this->office1 = new office(array(
            'name' => 'sweeper',
            'number' => 2,
            'college' => 'Ag'
        ));
        $this->office1->save();

        $this->office2 = new office(array(
            'name' => 'striker',
            'number' => 1,
            'college' => 'HUEC'
        ));
        $this->office2->save();

        //users
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->user4 = $this->getDataGenerator()->create_user();
        $this->user5 = $this->getDataGenerator()->create_user();

        // candidate in old election
        $candparams1 = array(
            'election_id' => $this->oldelection->id,
            'userid'      => $this->user1->id,
            'office'      => $this->office1->id,
            'affiliation' => 'Lions'
        );
        $this->cand1 = new candidate($candparams1);
        $this->cand1->save();

        // candidate in current election
        $candparams2 = array(
            'election_id' => $this->currentelection->id,
            'userid'      => $this->user2->id,
            'office'      => $this->office1->id,
            'affiliation' => 'Lions'
        );
        $this->cand2 = new candidate($candparams2);
        $this->cand2->save();

        // candidate in current election
        $candparams3 = array(
            'election_id' => $this->currentelection->id,
            'userid'      => $this->user3->id,
            'office'      => $this->office2->id,
            'affiliation' => 'Lions'
        );
        $this->cand3 = new candidate($candparams3);
        $this->cand3->save();

        // candidate in current election
        $candparams4 = array(
            'election_id' => $this->currentelection->id,
            'userid'      => $this->user4->id,
            'office'      => $this->office2->id,
            'affiliation' => 'Tigers'
        );
        $this->cand4 = new candidate($candparams4);
        $this->cand4->save();

        $candparams5 = array(
            'election_id' => $this->currentelection->id,
            'userid'      => $this->user1->id,
            'office'      => $this->office2->id,
            'affiliation' => 'Lions'
        );
        $this->cand5 = new candidate($candparams5);
        $this->cand5->save();
    }

    public function test_validate_username(){
        $username = "cannot possibly exist";
        $election = $this->create_election();
        $form = new candidate_form(null, array('election'=>$election));
        $data = array('username'=>$username, 'election_id' => 1);
        $files= array();

        $result   = $form->validation($data, $files);
        $this->assertNotEmpty($result);

        $message = get_string('err_user_nonexist', 'block_sgelection',  $username);
        $this->assertEquals($message, $result['username']);

        $user = $this->getDataGenerator()->create_user();
        $data['username'] = $user->username;
        $validresult = $form->validation($data, $files);
        $this->assertEmpty($validresult);
    }

    public function test_validate_one_office_per_candidate_per_election(){
        $user     = $this->getDataGenerator()->create_user();
        $election = $this->create_election(true);

        $office   = $this->create_office();
        $cand1    = $this->create_candidate($user, $election, $office);

        $office2  = $this->create_office();
        $cand2    = $this->create_candidate($user, $election, $office2);

        $form     = new candidate_form(null, array('election'=>$election));
        $data     = array('username'=>$user->username, 'election_id' => $election->id);
        $files    = array();

        $result   = $form->validation($data, $files);

        $this->assertNotEmpty($result);
        $a = new stdClass();
        $a->username = $user->username;
        $a->eid = $election->id;
        $a->semestername = $election->fullname();
        $a->office = sprintf("%s [id: %d] ", $office->name, $office->id);
        $a->office .= sprintf(" and %s [id: %d] ", $office2->name, $office2->id);
        $expectedmsg = get_string('err_user_nonunique', 'block_sgelection', $a);
        $this->assertEquals($expectedmsg, $result['username']);

        $user3 = $this->getDataGenerator()->create_user();
        $cand3 = $this->create_candidate($user3, $election);

        $data['username'] = $user3->username;
        $data['id']       = $cand3->id;

        $result2 = $form->validation($data, $files);
        $this->assertEmpty($result2);
    }
}