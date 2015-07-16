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
 * mod_panopto generator tests
 *
 * @package    mod_panopto
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Genarator tests class for mod_panopto.
 *
 * @package    mod_panopto
 * @category   test
 * @copyright  2013 Marina Glancy
 * @copyright  2015 Robert Russo and Louisiana State University {@link http://www.lsu.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_panopto_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('panopto', array('course' => $course->id)));
        $panopto = $this->getDataGenerator()->create_module('panopto', array('course' => $course));
        $records = $DB->get_records('panopto', array('course' => $course->id), 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($panopto->id, $records));

        $params = array('course' => $course->id, 'name' => 'Another panopto');
        $panopto = $this->getDataGenerator()->create_module('panopto', $params);
        $records = $DB->get_records('panopto', array('course' => $course->id), 'id');
        $this->assertEquals(2, count($records));
        $this->assertEquals('Another panopto', $records[$panopto->id]->name);
    }
}
