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
 * Tests for sge_database_object class
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once 'classes/sgedatabaseobject.php';
require_once 'classes/candidate.php';
require_once 'tests/sgetestbase.php';

class myclass extends sge_database_object {

    public $a;
    public $b;
    public $c;

    static $tablename = "user";
}

class sge_database_object_testcase extends block_sgelection_base {

    public function setup(){
        $this->resetAfterTest();
    }

    public function test_construct() {
        $params = array('a'=>'hello', 'b'=>'world', 'c'=>'!');
        $test = new myclass($params);
        $this->assertEquals('hello', $test->a);
        $this->assertEquals('world', $test->b);
        $this->assertEquals('!', $test->c);
        $this->assertInstanceOf('myclass', $test);

        $testempty = new myclass();
        $this->assertEmpty($testempty->a);
        $this->assertEmpty($testempty->b);
        $this->assertEmpty($testempty->b);
        $this->assertInstanceOf('myclass', $testempty);

        $testempty->instantiate($params);
        $this->assertEquals('hello', $testempty->a);
        $this->assertEquals('world', $testempty->b);
        $this->assertEquals('!', $testempty->c);
        $this->assertInstanceOf('myclass', $testempty);

        $paramobj = new stdClass();
        $paramobj->a = 'hello';
        $paramobj->b = 'world';
        $paramobj->c = '!';

        $testobj = new myclass($paramobj);
        $this->assertEquals('hello', $testobj->a);
        $this->assertEquals('world', $testobj->b);
        $this->assertEquals('!', $testobj->c);
        $this->assertInstanceOf('myclass', $testobj);
    }
    
    public function test_save(){
        global $DB;
        $params = array(
            'userid' => "2",
            'election_id'      => 3,
            'office'   => 4,
            'affiliation' => "Lions",
        );
        $candidate = new candidate($params);
        
        $this->assertEmpty($candidate->id);
        $this->assertEquals(2, $candidate->userid);
        $this->assertEquals(3, $candidate->election_id);
        $this->assertEquals(4, $candidate->office);
        $this->assertEquals('Lions', $candidate->affiliation);
        
        $candidate->save();
        $this->assertNotEmpty($candidate->id);
        
        $test = $DB->get_record(candidate::$tablename, array('id'=>$candidate->id));
        $this->assertEquals(2, $test->userid);
        $this->assertEquals(3, $test->election_id);
        $this->assertEquals(4, $test->office);
        $this->assertEquals('Lions', $test->affiliation);
        $this->assertInstanceOf('stdClass', $test);
        
        // get an instance of candidate from the DB row.
        $instance = new candidate($test);
        $instance->affiliation = 'new affiliation';
        $this->assertInstanceOf('candidate', $candidate);

        // save with new value.
        $instance->save();
        
        // ensure save persisted the updated value
        $testupdate = $DB->get_record(candidate::$tablename, array('id'=>$instance->id));
        $this->assertEquals('new affiliation', $testupdate->affiliation);
    }
    
    public function test_get_by_id(){
        $params = array(
            'userid' => "2",
            'election_id' => 3,
            'office' => 4,
            'affiliation' => "Lions",
        );
        $candidate = new candidate($params);
        $candidate->save();
        $test = candidate::get_by_id($candidate->id);

        $this->assertInstanceOf('candidate', $test);
        $this->assertNotEmpty($test);
        $this->assertEquals(2, $test->userid);
        $this->assertEquals(3, $test->election_id);
        $this->assertEquals(4, $test->office);
        $this->assertEquals('Lions', $test->affiliation);
    }

    public function test_get_by_id_with_nulls(){
        $e = new stdClass();
        $e->semesterid = 55;
        $e->name               = "Spring";
        $e->hours_census_start = time() + 86400*2;
        $e->start_date         = time() + 86400*3;
        $e->end_date           = time() + 86400*4;

        // Instantiate.
        $election = new Election($e);
        $this->assertInstanceOf('Election', $election);

        // Ensure our NULL field is not present on the object.
        $this->assertNull($election->hours_census_complete,
                "Since it has not been set, this property should evaluate empty");
        $this->assertNull($election->id,
                "Since it has not been set, this property should evaluate empty");

        // Persist to DB.
        $election->save();
        $this->assertNotNull($election->id);
        $this->assertInternalType('int', $election->id);

        // Try to get our persisted object back from the DB.
        $objWithNullTableValues = Election::get_by_id($election->id);
        $this->assertInstanceOf('Election', $objWithNullTableValues);
        $this->assertEquals($election, $objWithNullTableValues);
    }

    public function test_get_by_id_not_found(){
        global $DB;
        // Ensure that the table is empty;
        $this->assertEquals(0, count($DB->get_records(Election::$tablename)));

        // Try to find election.id = 999363 in a completely empty table;
        $election = Election::get_by_id(999363);
        $this->assertFalse($election);
    }

    public function test_get_all_by_election_id(){
        $election1 = $this->create_election();
        $office1   = $this->create_office();
        $office2   = $this->create_office();

        $cand1     = $this->create_candidate(null, $election1, $office1);
        $cand2     = $this->create_candidate(null, $election1, $office2);
        $res1      = $this->create_resolution(null, $election1->id);
        $res2      = $this->create_resolution(null, $election1->id);

        $election2 = $this->create_election();
        $cand3     = $this->create_candidate(null, $election2, $office1);
        $cand4     = $this->create_candidate(null, $election2, $office2);
        $res3      = $this->create_resolution(null, $election2->id);
        $res4      = $this->create_resolution(null, $election2->id);

        $elec1cnds = candidate::get_all(array('election_id' => $election1->id));
        $this->assertContains($cand1->id, array_keys($elec1cnds));
        $this->assertContains($cand2->id, array_keys($elec1cnds));
        $this->assertNotContains($cand3->id, array_keys($elec1cnds));
        $this->assertNotContains($cand4->id, array_keys($elec1cnds));

        $elec1reso = resolution::get_all(array('election_id' => $election1->id));
        $this->assertContains($res1->id, array_keys($elec1reso));
        $this->assertContains($res2->id, array_keys($elec1reso));
        $this->assertNotContains($res3->id, array_keys($elec1reso));
        $this->assertNotContains($res4->id, array_keys($elec1reso));

        $elec2cnds = candidate::get_all(array('election_id' => $election2->id));
        $this->assertContains($cand3->id, array_keys($elec2cnds));
        $this->assertContains($cand4->id, array_keys($elec2cnds));
        $this->assertNotContains($cand1->id, array_keys($elec2cnds));
        $this->assertNotContains($cand2->id, array_keys($elec2cnds));

        $elec2reso = resolution::get_all(array('election_id' => $election2->id));
        $this->assertContains($res3->id, array_keys($elec2reso));
        $this->assertContains($res4->id, array_keys($elec2reso));
        $this->assertNotContains($res1->id, array_keys($elec2reso));
        $this->assertNotContains($res2->id, array_keys($elec2reso));
    }
}
