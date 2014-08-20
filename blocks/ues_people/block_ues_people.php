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
 * @package    block_ues_people
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ues_people extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_ues_people');
    }

    function applicable_formats() {
        return array('course' => true, 'site' => false, 'my' => false);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $PAGE, $COURSE, $OUTPUT, $CFG;

        $context = context_course::instance($COURSE->id);

        $permission = (
            has_capability('moodle/site:accessallgroups', $context) or
            has_capability('block/ues_people:viewmeta', $context)
        );

        if (!$permission) {
            require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
            ues::require_daos();
            $sections = ues_section::from_course($COURSE);

            if (empty($sections)) {
                $permission = false;
            } else {
                $permission = ues_user::is_teacher_in($sections);
            }
        }

        $content = new stdClass;
        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        $this->content = $content;

        $icon_class = array('class' => 'icon');

        if ($permission) {
            $str = get_string('canonicalname', 'block_ues_people');
            $url = new moodle_url('/blocks/ues_people/index.php', array(
                'id' => $COURSE->id
            ));

            $this->content->items[] = html_writer::link($url, $str);
            $this->content->icons[] = $OUTPUT->pix_icon('i/users', $str, 'moodle', $icon_class);
        }

        return $this->content;
    }
}
