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
 * Class containing data for UofL Library Search.
 *
 * @copyright  2021 David Lowe <david.lowe@uleth.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uleth_library\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
// use core_completion\progress;

// require_once($CFG->dirroot . '/blocks/landing_block/lib.php');
// require_once($CFG->libdir . '/completionlib.php');

class main implements renderable, templatable {

    /**
     * @var string The tab to display.
     */
    public $tab;

    /**
     * Constructor.
     *
     * @param string $tab The tab to display.
     */
    // public function __construct($tab) {
    public function __construct() {
        // $this->tab = $tab;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        /*
        $courses = enrol_get_my_courses('*', 'fullname ASC');
        $coursesprogress = [];

        foreach ($courses as $course) {

            $completion = new \completion_info($course);

            // First, let's make sure completion is enabled.
            if (!$completion->is_enabled()) {
                continue;
            }

            $percentage = progress::get_course_progress_percentage($course);
            if (!is_null($percentage)) {
                $percentage = floor($percentage);
            }

            $coursesprogress[$course->id]['completed'] = $completion->is_course_complete($USER->id);
            $coursesprogress[$course->id]['progress'] = $percentage;
        }

        $coursesview = new courses_view($courses, $coursesprogress);
        $nocoursesurl = $output->image_url('courses', 'block_landing_block')->out();
        $noeventsurl = $output->image_url('activities', 'block_landing_block')->out();

        // Now, set the tab we are going to be viewing.
        $viewingtimeline = false;
        $viewingcourses = false;
        if ($this->tab == BLOCK_LANDING_BLOCK_TIMELINE_VIEW) {
            $viewingtimeline = true;
        } else {
            $viewingcourses = true;
        }
        */
        $title = "Library Summon Search";

        return [
            'title' => $title
            // 'midnight' => usergetmidnight(time()),
            // 'coursesview' => $coursesview->export_for_template($output),
            // 'urls' => [
            //     'nocourses' => $nocoursesurl,
            //     'noevents' => $noeventsurl
            // ],
            // 'viewingtimeline' => $viewingtimeline,
            // 'viewingcourses' => $viewingcourses
        ];
    }
}
