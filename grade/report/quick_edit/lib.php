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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->dirroot . '/grade/report/quick_edit/classes/lib.php');

class grade_report_quick_edit extends grade_report {

    public static function valid_screens() {
        $screendir = dirname(__FILE__) . '/screens';

        $isvalid = function($filename) use ($screendir) {
            if (preg_match('/^\./', $filename)) {
                return false;
            }

            $file = $screendir . '/' . $filename;

            if (is_file($file)) {
                return false;
            }

            $plugin = $file . '/lib.php';
            return file_exists($plugin);
        };

        return array_filter(scandir($screendir), $isvalid);
    }

    public static function classname($screen) {
        $screendir = dirname(__FILE__) . '/screens/' . $screen;

        require_once($screendir . '/lib.php');

        return 'quick_edit_' . $screen;
    }

    public static function filters() {
        $classnames = array('grade_report_quick_edit', 'classname');
        $classes = array_map($classnames, self::valid_screens());

        $screens = array_filter($classes, function($screen) {
            return method_exists($screen, 'filter');
        });

        return function($item) use ($screens) {
            $reduced = function($in, $screen) use ($item) {
                return $in && $screen::filter($item);
            };

            return array_reduce($screens, $reduced, true);
        };
    }

    public function process_data($data) {
        return $this->screen->process($data);
    }

    public function process_action($target, $action) {
    }

    public function _s($key, $a = null) {
        return get_string($key, 'gradereport_quick_edit', $a);
    }

    public function __construct($courseid, $gpr, $context, $itemtype, $itemid, $groupid=null) {
        parent::__construct($courseid, $gpr, $context);

        $class = self::classname($itemtype);

        $this->screen = new $class($courseid, $itemid, $groupid);

        $eventdata = array(
            'contextid' => $context->id,
            'other' => $this->screen,
        );

        if ($class != 'quick_edit_anonymous' && $class != 'quick_edit_grade') {
            $eventbase = 'quick_edit_other';
        } else {
            $eventbase = $class;
        }
        
        global $CFG;
        // TODO: Refactor the if-statement below to use only Event 2 (with no if-stmt).
        if ($CFG->removeevent2triggers) {
            require_once($CFG->dirroot . '/blocks/post_grades/events.php');
            if ($eventbase == 'quick_edit_anonymous') {
                post_grades_handler::quick_edit_anonymous_instantiated($this->screen);
            } else if ($eventbase == 'quick_edit_grade') {
                post_grades_handler::quick_edit_grade_instantiated($this->screen);
            } else {
                post_grades_handler::quick_edit_other_instantiated($this->screen);
            }
        } else {
            require_once("classes/event/{$eventbase}_instantiated.php");
            if ($eventbase == 'quick_edit_anonymous') {
                $event = grade_report_quick_edit\event\quick_edit_anonymous_instantiated::create($eventdata);
            } else if ($eventbase == 'quick_edit_grade') {
                $event = grade_report_quick_edit\event\quick_edit_grade_instantiated::create($eventdata);
            } else {
                $event = grade_report_quick_edit\event\quick_edit_other_instantiated::create($eventdata);
            }
            $event->trigger();
        }

        // Load custom or predefined js.
        $this->screen->js();

        $base = '/grade/report/quick_edit/index.php';

        $idparams = array('id' => $courseid);

        $this->baseurl = new moodle_url($base, $idparams);

        $this->pbarurl = new moodle_url($base, $idparams + array(
            'item' => $itemtype,
            'itemid' => $itemid
        ));

        $this->setup_groups();
    }

    public function output() {
        global $OUTPUT;
        return $OUTPUT->box($this->screen->html());
    }
}

function grade_report_quick_edit_profilereport($course, $user) {
    global $CFG, $OUTPUT;

    if (!function_exists('grade_report_user_profilereport')) {
        require_once($CFG->dirroot . '/grade/report/user/lib.php');
    }

    $context = context_course::instance($course->id);

    $canuse = (
        has_capability('gradereport/quick_edit:view', $context) and
        has_capability('moodle/grade:viewall', $context) and
        has_capability('moodle/grade:edit', $context)
    );

    if (!$canuse) {
        grade_report_user_profilereport($course, $user);
    } else {
        $gpr = new grade_plugin_return(array(
            'type' => 'report',
            'plugin' => 'quick_edit',
            'courseid' => $course->id,
            'userid' => $user->id
        ));

        $report = new grade_report_quick_edit($course->id, $gpr, $context, 'user', $user->id);

        echo $OUTPUT->heading($report->screen->heading());
        echo $report->output();
    }
}