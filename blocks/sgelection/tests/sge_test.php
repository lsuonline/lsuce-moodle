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
require_once 'lib.php';
require_once 'tests/sgetestbase.php';
require_once 'tests/sgdatabaseobject_test.php';

class sge_testcase extends sge_database_object_testcase {

    public $t;

    public function setup(){
        parent::setup();
        $this->t = time();
    }

    public function test_validate_username(){
        $nosuchusername = 'nosuchusername0974354jkh;kjghgfh';
        $validusername  = $this->getDataGenerator()->create_user()->username;
        $fieldname      = 'uname';

        $baddata = array(
            $fieldname => $nosuchusername,
        );

        $guddata = array(
            $fieldname => $validusername,
        );

        $badresult = sge::validate_username($baddata, $fieldname);
        $gudresult = sge::validate_username($guddata, $fieldname);

        $this->assertEmpty($gudresult);
        $this->assertNotEmpty($badresult);

        $badmsg    = sge::_str('err_user_nonexist', $nosuchusername);
        $this->assertEquals($badmsg, $badresult['uname']);
    }

    public function test_trim_prefix(){
        $prefix   = 'user_';
        $word     = 'field';
        $totrim   = $word;
        $toignore = $word;

        $this->assertEquals($word, sge::trim_prefix($totrim, $prefix));
        $this->assertEquals($word, sge::trim_prefix($toignore, $prefix));
    }

    public function daysoffset($num){
        return $this->t + ($num*86400);
    }

    public function test_daysoffset(){
        // Sanity check for our time offset helper.
        $this->assertEquals(-86400, $this->t - $this->daysoffset(1));
        $this->assertEquals( 86400, $this->t - $this->daysoffset(-1));
    }

    public function test_semesters_eligible_for_census(){

        // Setup a semester for the election to be attached to.
        $d                       = new DateTime();
        $semester                = new ues_semester();
        $semester->year          = $d->format('Y');
        $semester->name          = "Spring";
        $semester->campus        = "Main";
        $semester->classes_start = $this->daysoffset(-50);
        $semester->grades_due    = $this->daysoffset(50);
        $semester->save();

        // 1.
        // Build an election that will definitely NOT be ready for census.
        // Since census_start is still in the future, it should not be considered
        // eligible for census.
        $election = new stdClass();
        $election->name = "General";
        $election->semesterid = $semester->id;
        $election->start_date = $this->daysoffset(6);
        $election->end_date   = $this->daysoffset(7);
        $election->hours_census_start = $this->daysoffset(5);

        $election = new election($election);

        // Just to be sure the double assignment is as we intend.
        $this->assertInstanceOf('Election', $election);
        $election->save();

        // Ensure this is considered empty.
        $this->assertEmpty($election->hours_census_complete,
                "Since hours_census_complete has not been set, it should evaluate as empty!");

        // This should be ineligible.
        // Though it has a null hours_census_complete property,
        // hours_census_start is still in the future.
        $emptyResult = sge::semesters_eligible_for_census();
        $this->assertEmpty($emptyResult,
                sprintf("Expected no results, but got %d elections\n", count($emptyResult)));



        // 2.
        // Alter the semester to be eligible.
        // With census_start one day in the past, and start_date still
        // 2 days in the future, this election is ready for the hours census.
        $election->start_date = $this->daysoffset(2);
        $election->hours_census_start = $this->daysoffset(-1);
        $election->save();

        // Ensure census_complete is still empty.
        $this->assertEmpty($election->hours_census_complete,
                "Since hours_census_complete has not been set, it should evaluate as empty!");

        // Get result.
        // There should be one element in this array with integer value == $semesterid
        $result = sge::semesters_eligible_for_census();
        global $DB;
        $this->assertNotEmpty($result);
        $this->assertEquals(1, count($result));
        $this->assertArrayHasKey($semester->id, $result);
        $this->assertInstanceOf('ues_semester', $result[$semester->id]);



        // 3.
        // Now set election start_date in the past, keeping census_complete NULL.
        // Since the election has started, don't run eligibility census;
        // @TODO we need to prevent the election opening if eligibility is not complete.
        $election->start_date = time() - 10;
        $election->save();
        // While census_complete is still null, census_start is now in the past.
        $this->assertEmpty($election->hours_census_complete,
                "Since hours_census_complete has not been set, it should evaluate as empty!");
        $anotherEmptyResult = sge::semesters_eligible_for_census();
        $this->assertEmpty($anotherEmptyResult);



        // 4.
        // Alter election to have a census_complete value, and persist.
        $election->hours_census_complete = $this->daysoffset(-1);
        $election->save();

        // For symetry, ensure census_complete is now NOT empty.
        $this->assertNotEmpty($election->hours_census_complete,
                "Since hours_census_complete has been set, it should NOT evaluate as empty!");

        $doneResult = sge::semesters_eligible_for_census();
        $this->assertEmpty($doneResult);
    }

    /**
     * Given that we construct some sample enrollment data for a few students
     * across a few semesters, the function under test should return the correct
     * total number of hours per student, per semester.
     */
    public function test_calculate_enrolled_hours_for_semester(){
        global $DB;

        // A. setup students.

        // Apart from 'admin' and 'guest', we should have no users yet.
        $where = "username NOT IN('admin', 'guest')";
        $this->assertEquals(0, $DB->count_records_select('user', $where));

        // Create and save users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        // store them for later use.
        $students = array();
        $students[$student1->id] = $student1;
        $students[$student2->id] = $student2;

        // We should now have only two users, apart from the 2 that phpunit
        // bootstraps for us.
        $this->assertEquals(2, $DB->count_records_select('user', $where));

        // B. Setup semesters.
        $d                       = new DateTime();
        $semester1                = new ues_semester();
        $semester1->year          = $d->format('Y');
        $semester1->name          = "Spring";
        $semester1->campus        = "Main";
        $semester1->classes_start = $this->daysoffset(-50);
        $semester1->grades_due    = $this->daysoffset(10);
        $semester1->save();

        $semester2                = new ues_semester();
        $semester2->year          = $d->format('Y');
        $semester2->name          = "Summer";
        $semester2->campus        = "Main";
        $semester2->classes_start = $this->daysoffset(-10);
        $semester2->grades_due    = $this->daysoffset(20);
        $semester2->save();

        $semester3                = new ues_semester();
        $semester3->year          = $d->format('Y');
        $semester3->name          = "Fall";
        $semester3->campus        = "Main";
        $semester3->classes_start = $this->daysoffset(-5);
        $semester3->grades_due    = $this->daysoffset(40);
        $semester3->save();

        $semesters = array($semester1, $semester2, $semester3);

        // Make sure these were persisted.
        $this->assertEquals(3, count($DB->get_records('enrol_ues_semesters')));

        // Ensure that they are all indeed active semesters.
        $this->assertEquals(3, count(ues_semester::in_session()));

        // C. Setup course sections.
        $courses = array(
            'BIOL' => 'Intro to Biology',
            'CHEM' => 'Intro to Chemistry',
            'ART'  => 'Intro to Art',
            'FREN' => 'Intro to French'
        );

        foreach($courses as $dept => $fullname){
            $$dept = new ues_course();
            $$dept->department = $dept;
            $$dept->cou_number = '1001';
            $$dept->fullname   = $fullname;
            $$dept->save();
        }

        foreach($semesters as $semester){
            foreach(array_keys($courses) as $coursevarname){
                $course  = $$coursevarname;
                $section = new ues_section();
                $section->courseid   = $course->id;
                $section->semesterid = $semester->id;
                $section->sec_number = '001';
                $section->idnumber   = (string)$semester.$course->fullname.$section->sec_number;
                $section->status     = 'enrolled';
                $section->save();

                // Create variables
                $identifier = $semester->name.$course->department;
                $$identifier = $section;
            }
        }

        /**
         * Using variable variables to shorten the code, we've just inserted
         * courses and sections into the appropriate tables.
         * The ues_section and ues_course objects are stored in variable variables.
         * ues_course   variable names are taken from the $courses array.
         * ues_sections variable names are a concatenation of the semester->name and $course->department.
         *
         * Let's make some assertions:
         * Given three semesters, four courses, one section each:
         * - there should be 12 sections
         * - there should be four sections where semesterid = $semester->id
         * - there should be three sections where courseid = $course->id
         */

        $this->assertEquals(12, $DB->count_records('enrol_ues_sections'));
        foreach($semesters as $semester){
            $sections = $DB->count_records('enrol_ues_sections', array('semesterid' => $semester->id));
            $this->assertEquals(4, $sections);

            foreach($courses as $dept => $fullname){
                $sections = $DB->count_records('enrol_ues_sections', array('courseid' => $$dept->id, 'semesterid' => $semester->id));
                $this->assertEquals(1, $sections);
            }
        }

        // With that taken care of, let's enrol students.
        // Helper method to insert rows into the enrol_ues_students table.
        // return value is the number of hours isnerted for the row.
        $enrol = function($userid, $sectionid, $hours){
            global $DB;
            $row = new stdClass();
            $row->userid       = $userid;
            $row->sectionid    = $sectionid;
            $row->credit_hours = $hours;
            if(!$DB->insert_record('enrol_ues_students', $row)){
                throw new Exception("Failed to insert record!");
            }
            return $hours;
        };

        // Expected values map.
        // Build this map while inserting student records,
        // accumulating the return value from $enrol(...)
        // in the map at userid => semestername => value.
        $enrolledhours = array();

        // Spring Courses.
        $enrolledhours[$student1->id]['Spring']  = $enrol($student1->id, $SpringBIOL->id, 2);
        $enrolledhours[$student1->id]['Spring'] += $enrol($student1->id, $SpringCHEM->id, 4);
        $enrolledhours[$student1->id]['Spring'] += $enrol($student1->id, $SpringART->id, 3);

        $enrolledhours[$student2->id]['Spring']  = $enrol($student2->id, $SpringART->id, 3);
        $enrolledhours[$student2->id]['Spring'] += $enrol($student2->id, $SpringFREN->id, 5);
        $enrolledhours[$student2->id]['Spring'] += $enrol($student2->id, $SpringBIOL->id, 4);

        // Test the function return values agains the Expected values map for 'Spring'.
        $springhours = sge::calculate_all_enrolled_hours_for_semester($semester1);
        foreach($springhours as $userid => $rec){
            $this->assertEquals($enrolledhours[$userid][$semester1->name], $rec->hours,
                    sprintf("Hours for userid %d did not match.\n", $userid));
        }

        // Summer courses.
        $enrolledhours[$student1->id]['Summer']  = $enrol($student1->id, $SummerFREN->id, 6);
        $enrolledhours[$student2->id]['Summer']  = $enrol($student2->id, $SummerCHEM->id, 5);

        // Test the function return values agains the Expected values map for 'Spring'.
        $summerhours = sge::calculate_all_enrolled_hours_for_semester($semester2);
        foreach($summerhours as $userid => $rec){
            $this->assertEquals($enrolledhours[$userid][$semester2->name], $rec->hours,
                    sprintf("Hours for userid %d did not match.\n", $userid));
        }

        // Notice that $semester3 (aka 'Fall') is never enrolled
    }
}
