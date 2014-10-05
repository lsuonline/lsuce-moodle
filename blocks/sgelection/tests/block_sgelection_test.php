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
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot.'/blocks/sgelection/tests/sgetestbase.php');
require_once $CFG->dirroot.'/blocks/sgelection/lib.php';
sge::require_db_classes();
require_once $CFG->dirroot.'/blocks/moodleblock.class.php';
require_once $CFG->dirroot.'/blocks/sgelection/block_sgelection.php';



class block_sgelection_testcase extends block_sgelection_base {

    public function test_cron(){
        $block = new block_sgelection();
        $this->assertTrue($block->has_config());
    }
}
