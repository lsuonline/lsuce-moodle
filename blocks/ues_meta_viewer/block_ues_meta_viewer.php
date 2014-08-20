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
 * @package    block_ues_meta_viewer
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ues_meta_viewer extends block_list {
    function init() {
        $this->title= get_string('pluginname', 'block_ues_meta_viewer');
    }

    function applicable_formats() {
        return array('site' => true, 'my' => true, 'course' => false);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $USER;

        $content = new stdClass;

        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        require_once $CFG->dirroot . '/blocks/ues_meta_viewer/lib.php';

        $meta_types = ues_meta_viewer::supported_types();

        $base = '/blocks/ues_meta_viewer/viewer.php';

        foreach ($meta_types as $type => $support) {
            if (!$support->can_use()) {
                continue;
            }

            $url = new moodle_url($base, array('type' => $type));

            $str = get_string('viewer', 'block_ues_meta_viewer', $support->name());

            $content->items[] = html_writer::link($url, $str);
        }

        $this->content = $content;

        return $this->content;
    }
}
