<?php
/**
 * Copyright (c) 2011 bfwpub.com (R) <http://support.bfwpub.com/>
 *
 * This file is part of bfwpub Moodle LMS integration.
 *
 * bfwpub Sakai LMS integration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * bfwpub Sakai LMS integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with bfwpub Sakai LMS integration.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: test_bfwpub_service.php 1756 2012-06-18 22:51:37Z aaronz $ */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once (dirname(__FILE__).'/../../../config.php');
global $CFG,$db_user,$COURSE;
// link in external libraries
require_once ($CFG->dirroot.'/blocks/bfwpub/bfwpub_service.php');

/**
 * Unit tests for the bfwpub services
 * Execute tests at:
 * moodle/admin/report/unittest/index.php?path=blocks/bfwpub
 *
 * You MUST have 3 users in the system and 1 course or these tests cannot be executed
 */
class bfwpub_services_test extends UnitTestCase {

    var $courseid = 1;

    var $studentid1 = 3;
    var $studentid2 = 4;
    var $studentid3 = 5;

    var $cat_name = 'az_category';
    var $item_name = 'az_gradeitem';
    var $grade_score = 91;

    public function cleanup() {
        // cleanup the test grades
        $def_grade_cats = grade_category::fetch_all( array(
            'courseid' => $this->courseid,
            'fullname' => bfwpub_service::GRADE_CATEGORY_NAME
            )
        );
        $stuff_grade_cats = grade_category::fetch_all( array(
            'courseid' => $this->courseid,
            'fullname' => 'stuff'
            )
        );
        $grade_cats = $def_grade_cats;
        if (is_array($def_grade_cats) && is_array($stuff_grade_cats)) {
            $grade_cats = array_merge($def_grade_cats, $stuff_grade_cats);
        } else if (is_array($stuff_grade_cats)) {
            $grade_cats = $stuff_grade_cats;
        }
        if ($grade_cats) {
            foreach ($grade_cats as $cat) {
                $grade_items = grade_item::fetch_all(array(
                    'courseid' => $this->courseid,
                    'categoryid' => $cat->id
                    )
                );
                if ($grade_items) {
                    foreach ($grade_items as $item) {
                        $grades = grade_grade::fetch_all(array(
                            'itemid'=>$item->id
                            )
                        );
                        if ($grades) {
                            foreach ($grades as $grade) {
                                $grade->delete("cleanup");
                            }
                        }
                        $item->delete("cleanup");
                    }
                }
                $cat->delete("cleanup");
            }
        }
    }

    public function setUp() {
        $this->cleanup();
        bfwpub_service::$test_mode = true;
    }

    public function tearDown() {
        bfwpub_service::$test_mode = false;
        $this->cleanup();
    }

    function test_course_users() {
        $course = bfwpub_service::get_course($this->courseid);
        $this->assertFalse(empty($course));
        $user = bfwpub_service::get_users($this->studentid1);
        $this->assertFalse(empty($user));
        $user = bfwpub_service::get_users($this->studentid2);
        $this->assertFalse(empty($user));
        $user = bfwpub_service::get_users($this->studentid3);
        $this->assertFalse(empty($user));
    }

    function test_arrays_subtract() {
        // array_diff and array_diff_key are the same as a subtract when used with 2 arrays -- array_diff(A1, A2) => A1 - A2
        $a1 = array(1,2,3,4,5);
        $a2 = array(3,4,5,6,7);

        $result = array_values(array_diff($a1, $a2));
        $this->assertEqual(2, count($result));
        $this->assertTrue(in_array(1, $result));
        $this->assertTrue(in_array(2, $result));
        $result = array_values(array_diff($a2, $a1));
        $this->assertEqual(2, count($result));
        $this->assertTrue(in_array(6, $result));
        $this->assertTrue(in_array(7, $result));

        $result = array_values(array_intersect($a1, $a2));
        $this->assertEqual(3, count($result));
        $this->assertTrue(in_array(3, $result));
        $this->assertTrue(in_array(4, $result));
        $this->assertTrue(in_array(5, $result));
        $result = array_values(array_intersect($a2, $a1));
        $this->assertEqual(3, count($result));
        $this->assertTrue(in_array(3, $result));
        $this->assertTrue(in_array(4, $result));
        $this->assertTrue(in_array(5, $result));

        $a1 = array('A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5);
        $a2 = array('C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7);

        $result = array_values(array_diff_key($a1, $a2));
        $this->assertEqual(2, count($result));
        $this->assertTrue(in_array(1, $result));
        $this->assertTrue(in_array(2, $result));
        $result = array_values(array_diff_key($a2, $a1));
        $this->assertEqual(2, count($result));
        $this->assertTrue(in_array(6, $result));
        $this->assertTrue(in_array(7, $result));

        $result = array_values(array_intersect_key($a1, $a2));
        $this->assertEqual(3, count($result));
        $this->assertTrue(in_array(3, $result));
        $this->assertTrue(in_array(4, $result));
        $this->assertTrue(in_array(5, $result));
        $result = array_values(array_intersect_key($a2, $a1));
        $this->assertEqual(3, count($result));
        $this->assertTrue(in_array(3, $result));
        $this->assertTrue(in_array(4, $result));
        $this->assertTrue(in_array(5, $result));
    }

    function test_assert() {
        $this->assertEqual("AZ", "AZ");
        $this->assertEqual(bfwpub_service::$test_mode, true);
    }

    function test_require_user() {
        $user_id = bfwpub_service::require_user();
        $this->assertTrue($user_id);
    }

    function test_get_users() {
        $user_id = bfwpub_service::require_user();
        $this->assertTrue($user_id);
        $results = bfwpub_service::get_users(array($user_id));
        $this->assertTrue($results);
        $this->assertTrue(count($results) == 1);
        $this->assertEqual($results[$user_id]->id, $user_id);
    }

    function test_save_grades() {
        $test_item_name1 = 'testing-bfwpub-item1';

        $test_item_name2 = 'testing-bfwpub-item2';
        $test_item_name3 = 'testing-bfwpub-item3';

        $gradebook = new stdClass();

        // saving a gradebook with no course_id not allowed
        try {
            bfwpub_service::save_gradebook($gradebook);
            $this->fail("should have died");
        } catch (Exception $e) {
            $this->assertNotNull($e);
        }

        $gradebook->course_id = $this->courseid;
        $gradebook->items = array();

        // saving an empty gradebook not allowed
        try {
            bfwpub_service::save_gradebook($gradebook);
            $this->fail("should have died");
        } catch (Exception $e) {
            $this->assertNotNull($e);
        }

        // saving one with one valid item
        $score = new stdClass();
        $score->user_id = $this->studentid1;
        $score->score = 75.0;

        $grade_item = new stdClass();
        $grade_item->name = $test_item_name1;
        $grade_item->points_possible = 90;
        $grade_item->type = bfwpub_service::GRADE_CATEGORY_NAME;
        $grade_item->scores = array();

        $grade_item->scores[] = $score;
        $gradebook->items[] = $grade_item;

        $result = bfwpub_service::save_gradebook($gradebook);
        $this->assertNotNull($result);
        $this->assertNotNull($result->course_id);
        $this->assertNotNull($result->course);
        $this->assertNotNull($result->default_category_id);
        $this->assertNotNull($result->items);
        $this->assertEqual($result->course_id, $this->courseid);
        $this->assertEqual(count($result->items), 1);
        $this->assertNotNull($result->items[0]);
        $this->assertNotNull($result->items[0]->id);
        $this->assertNotNull($result->items[0]->scores);
        $this->assertEqual(count($result->items[0]->scores), 1);
        $this->assertFalse(isset($result->items[0]->errors));
        $this->assertEqual($result->items[0]->grademax, 90);
        $this->assertEqual($result->items[0]->iteminfo, bfwpub_service::GRADE_CATEGORY_NAME);
        $this->assertNotNull($result->items[0]->categoryid);
        $this->assertEqual($result->items[0]->courseid, $result->course_id);
        $this->assertEqual($result->items[0]->itemname, $test_item_name1);
        $this->assertNotNull($result->items[0]->scores[0]);
        $this->assertFalse(isset($result->items[0]->scores[0]->error));

        // saving one with multiple items, some invalid
        $grade_item->type = 'stuff'; // update category
        $score->score = 50; // SCORE_UPDATE_ERRORS

        $score1 = new stdClass();
        $score1->user_id = 'xxxxxx'; // USER_DOES_NOT_EXIST_ERROR
        $score1->score = 80;
        $grade_item->scores[] = $score1;

        $score2 = new stdClass();
        $score2->user_id = $this->studentid2;
        //$score2->score = 101; // POINTS POSSIBLE
        $score2->score = 25; // This one is OK
        $grade_item->scores[] = $score2;

        $score3 = new stdClass();
        $score3->user_id = $this->studentid3;
        $score3->score = 'XX'; // GENERAL_ERRORS
        $grade_item->scores[] = $score3;

        $result = bfwpub_service::save_gradebook($gradebook);
        $this->assertNotNull($result);
        $this->assertNotNull($result->course_id);
        $this->assertNotNull($result->course);
        //$this->assertNotNull($result->default_category_id);
        $this->assertNotNull($result->items);
        $this->assertEqual($result->course_id, $this->courseid);
        $this->assertEqual(count($result->items), 1);
        $this->assertNotNull($result->items[0]);
        $this->assertNotNull($result->items[0]->id);
        $this->assertEqual($result->items[0]->iteminfo, 'stuff');
        $this->assertNotNull($result->items[0]->scores);
        $this->assertEqual(count($result->items[0]->scores), 4);
        $this->assertTrue(isset($result->items[0]->errors));
        $this->assertEqual(count($result->items[0]->errors), 2);
        $this->assertEqual($result->items[0]->grademax, 90);
        $this->assertNotNull($result->items[0]->categoryid);
        $this->assertEqual($result->items[0]->courseid, $result->course_id);
        $this->assertEqual($result->items[0]->itemname, $test_item_name1);
        $this->assertNotNull($result->items[0]->scores[0]);
        $this->assertFalse(isset($result->items[0]->scores[0]->error));
        //$this->assertEqual($result->items[0]->scores[0]->error, bfwpub_service::SCORE_UPDATE_ERRORS);
        $this->assertNotNull($result->items[0]->scores[1]);
        $this->assertTrue(isset($result->items[0]->scores[1]->error));
        $this->assertEqual(substr($result->items[0]->scores[1]->error,0,14), 'USER_NOT_FOUND');
        $this->assertNotNull($result->items[0]->scores[2]);
        $this->assertFalse(isset($result->items[0]->scores[2]->error));
        //$this->assertEqual($result->items[0]->scores[2]->error, bfwpub_service::POINTS_POSSIBLE_UPDATE_ERRORS);
        $this->assertNotNull($result->items[0]->scores[3]);
        $this->assertTrue(isset($result->items[0]->scores[3]->error));
        $this->assertEqual(substr($result->items[0]->scores[3]->error,0,13), 'SCORE_INVALID');

        $json = bfwpub_service::encode_gradebook_results($result);
        $this->assertNotNull($json);
        $this->assertTrue(stripos($json, '"course"') > 0);
        $this->assertTrue(stripos($json, '"success":false') > 0);
        $this->assertTrue(stripos($json, '"errors":') > 0);
        $this->assertTrue(stripos($json, 'USER_NOT_FOUND') > 0);
        $this->assertTrue(stripos($json, 'SCORE_INVALID') > 0);
        //$this->assertTrue(stripos($json, bfwpub_service::POINTS_POSSIBLE_UPDATE_ERRORS) > 0);
        //$this->assertTrue(stripos($json, bfwpub_service::GENERAL_ERRORS) > 0);
        //echo "<xmp>$xml</xmp>";

        // Save 1 update and 1 new grades
        $score3 = new stdClass();
        $score3->user_id = $this->studentid3;
        $score3->score = 85;

        $score2 = new stdClass();
        $score2->user_id = $this->studentid2;
        $score2->score = 50;

        $grade_item->scores = array($score3, $score2);

        $result = bfwpub_service::save_gradebook($gradebook);
        $this->assertNotNull($result);
        $this->assertNotNull($result->course_id);
        $this->assertNotNull($result->items);
        $this->assertEqual($result->course_id, $this->courseid);
        $this->assertEqual(count($result->items), 1);
        $this->assertNotNull($result->items[0]);
        $this->assertNotNull($result->items[0]->id);
        $this->assertNotNull($result->items[0]->scores);
        $this->assertEqual(count($result->items[0]->scores), 2);
        $this->assertFalse(isset($result->items[0]->errors));
        $this->assertEqual($result->items[0]->grademax, 90);
        $this->assertNotNull($result->items[0]->scores[0]);
        $this->assertEqual($result->items[0]->scores[0]->rawgrade, 85);
        $this->assertNotNull($result->items[0]->scores[1]);
        $this->assertEqual($result->items[0]->scores[1]->rawgrade, 50);
        //$this->assertNotNull($result->items[0]->scores[2]);
        //$this->assertEqual($result->items[0]->scores[2]->rawgrade, 0);
/*
echo "<pre>";
var_export($result->items[0]);
echo "</pre>";
*/
        $json = bfwpub_service::encode_gradebook_results($result);
        $this->assertNotNull($json); // no errors still should output
        $this->assertTrue(stripos($json, '"success":true') > 0);

        // test saving multiple items at once
        $gradebook->course_id = $this->courseid;
        $gradebook->items = array();

        $grade_item1 = new stdClass();
        $grade_item1->name = $test_item_name2;
        $grade_item1->points_possible = 100;
        $grade_item1->type = NULL; // default
        $grade_item1->scores = array();
        $gradebook->items[] = $grade_item1;

        $grade_item2 = new stdClass();
        $grade_item2->name = $test_item_name3;
        $grade_item2->points_possible = 50;
        $grade_item2->type = 'stuff';
        $grade_item2->scores = array();
        $gradebook->items[] = $grade_item2;

        $score = new stdClass();
        $score->user_id = $this->studentid1;
        $score->score = 80.0;
        $grade_item1->scores[] = $score;

        $score = new stdClass();
        $score->user_id = 2;
        $score->score = 90.0;
        $grade_item1->scores[] = $score;

        $score = new stdClass();
        $score->user_id = $this->studentid2;
        $score->score = 45.0;
        $grade_item2->scores[] = $score;

        $score = new stdClass();
        $score->user_id = $this->studentid3;
        $score->score = 40.0;
        $grade_item2->scores[] = $score;

        $result = bfwpub_service::save_gradebook($gradebook);
        $this->assertNotNull($result);
        $this->assertNotNull($result->course_id);
        $this->assertNotNull($result->items);
        $this->assertNotNull($result->default_category_id);
        $this->assertEqual($result->course_id, $this->courseid);
        $this->assertEqual(count($result->items), 2);
        $this->assertNotNull($result->items[0]);
        $this->assertNotNull($result->items[0]->id);
        $this->assertNotNull($result->items[0]->scores);
        $this->assertEqual(count($result->items[0]->scores), 2);
        $this->assertFalse(isset($result->items[0]->errors));
        $this->assertEqual($result->items[0]->grademax, 100);
        $this->assertNotNull($result->items[0]->scores[0]);
        $this->assertEqual($result->items[0]->scores[0]->rawgrade, 80);
        $this->assertNotNull($result->items[0]->scores[1]);
        $this->assertEqual($result->items[0]->scores[1]->rawgrade, 90);
        $this->assertNotNull($result->items[1]);
        $this->assertNotNull($result->items[1]->id);
        $this->assertNotNull($result->items[1]->scores);
        $this->assertEqual(count($result->items[1]->scores), 2);
        $this->assertFalse(isset($result->items[1]->errors));
        $this->assertEqual($result->items[1]->grademax, 50);
        $this->assertNotNull($result->items[1]->scores[0]);
        $this->assertEqual($result->items[1]->scores[0]->rawgrade, 45);
        $this->assertNotNull($result->items[1]->scores[1]);
        $this->assertEqual($result->items[1]->scores[1]->rawgrade, 40);

        $json = bfwpub_service::encode_gradebook_results($result);
        $this->assertNotNull($json);
        // {"course":"1","success":true,"errors":"","items":2,"grades_count":4,"grades_failed":0}
        $this->assertTrue(stripos($json, '"success":true') > 0);

    }

}
