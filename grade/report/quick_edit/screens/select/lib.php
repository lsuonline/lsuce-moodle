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

class quick_edit_select extends quick_edit_screen {
    public function init($selfitemisempty = false) {
        global $DB;

        $this->item = $DB->get_record('course', array('id' => $this->courseid));
    }

    public function html() {
        global $OUTPUT;

        $html = '';

        $types = grade_report_quick_edit::valid_screens();

        foreach ($types as $type) {
            $class = grade_report_quick_edit::classname($type);

            $screen = new $class($this->courseid, null, $this->groupid);

            if (!$screen instanceof selectable_items) {
                continue;
            }

            $options = $screen->options();

            if (empty($options)) {
                continue;
            }

            $params = array(
                'id' => $this->courseid,
                'item' => $screen->item_type(),
                'group' => $this->groupid
            );

            $url = new moodle_url('/grade/report/quick_edit/index.php', $params);

            $html .= $OUTPUT->heading($screen->description());

            $html .= $OUTPUT->single_select($url, 'itemid', $options);
        }

        if (empty($html)) {
            $OUTPUT->notification(get_string('no_screens', 'gradereport_quick_edit'));
        }

        return $html;
    }
}