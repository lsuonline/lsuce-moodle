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
require_once('classes/resolution.php');

class resolution_testcase extends block_sgelection_base {

    public function test_validate_unique_title(){
        $this->resetAfterTest();

        $params = array(
            'title' => 'title',
            'election_id' => 1,
            'text' => 'text',
            'restrict_fulltime' => 1,
        );
        $r1 = $this->create_resolution($params);
        $r2 = $this->create_resolution($params);

        $result = resolution::validate_unique_title((array)$r2);
        $this->assertNotEmpty($result);
        $this->assertEquals(
                get_string('err_resolution_title_nonunique', 'block_sgelection'),
                $result['title']
                );
        $r3 = $this->create_resolution(array('title' => 'uniq', 'text'=>'hello', 'election_id' => 2, 'restrict_fulltime'=>0));
        $cleanresult = resolution::validate_unique_title((array)$r3);
        $this->assertEmpty($cleanresult);
    }
}
