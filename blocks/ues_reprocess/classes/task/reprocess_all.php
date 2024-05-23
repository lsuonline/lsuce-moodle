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

namespace block_ues_reprocess\task;

/**
 * reprocess_all courses
 *
 * @package   block_ues reprocess_all 
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reprocess_all extends \core\task\scheduled_task {

    /**
     * Get task name
     */
    public function get_name() {
        return get_string('reprocess_all_courses', 'block_ues_reprocess');
    }

    /**
     * Execute task
     */
    public function execute() {
        global $CFG;
        mtrace("\nReprocessing Task Starting.....\n");

        require_once(dirname(dirname(__DIR__)). '/classes/repall.php');

        $year = get_config('moodle', "ues_reprocess_task_year");
        $semester = get_config('moodle', "ues_reprocess_task_semester");
        $department = get_config('moodle', "ues_reprocess_task_department");

        if ($year == '') {
            // Get the current year.
            $year = date("Y");
        }

        $data = new \stdClass();
        $data->ues_year = $year;
        $data->ues_semesters = $semester;
        $data->ues_departments = $department;
        $data->scheduled_task = true;

        $repall = new \repall();
        $repall->run_it_all($data);
    }
}