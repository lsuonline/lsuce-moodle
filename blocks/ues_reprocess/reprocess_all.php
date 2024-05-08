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
 * @package    block_ues_reprocess
 * @copyright  Louisiana State University
 * @copyright  The guy who did stuff: David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function run_it_all() {

    global $DB, $CFG;

    require_once($CFG->dirroot . '/enrol/ues/publiclib.php');
    require_once('lib.php');

    // Let's clock this.
    $starttime = microtime(true);

    require_login();

    ues::require_daos();
    $s = ues::gen_str('block_ues_reprocess');

    $blockname = $s('pluginname');

    // Do we only do visible?
    $courses = $DB->get_records('course', array('visible' => 1));

    foreach($courses as $course) {

        if ($course->id == 1) {
            continue;
        }

        ues::reprocess_course($course);
    }

    $endtime = microtime(true);
    $elapsed = round($starttime - $endtime, 1);

    mtrace("Total time to run reprocess_all is: ". $elapsed);
}