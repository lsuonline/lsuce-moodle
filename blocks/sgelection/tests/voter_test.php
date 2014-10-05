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
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once 'classes/voter.php';

class block_sgelection_voter_testcase extends advanced_testcase {

    public function setup(){
        $this->resetAfterTest();
    }

    public function test_construct(){
        $user = $this->getDataGenerator()->create_user();
        global $DB;
        $college = 'USER-COLLEGE';
        $major   = 'USER-MAJOR';
        $year    = 'SOPH';

        $params = array(
            'user_college' => $college,
            'user_major'   => $major,
            'user_year'    => $year,
        );

        foreach($params as $name => $value){
            $ues = new stdClass();
            $ues->name = $name;
            $ues->value = $value;
            $ues->userid = $user->id;
            $DB->insert_record('enrol_ues_usermeta', $ues);
        }

        $voter = new voter($user->id);
        $this->assertEquals($college, $voter->college);
        $this->assertEquals($major, $voter->major);
        $this->assertEquals($year, $voter->year);
    }
}