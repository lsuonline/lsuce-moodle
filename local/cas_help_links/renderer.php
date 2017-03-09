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
 * Renderer for local cas_help_links
 *
 * @package    local_cas_help_links
 * @copyright  2016, William C. Mazilly, Robert Russo
 * @copyright  2016, Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once $CFG->libdir.'/outputcomponents.php';
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/cas_help_links/user_settings_form.php');
require_once($CFG->dirroot.'/local/cas_help_links/category_settings_form.php');
require_once($CFG->dirroot.'/local/cas_help_links/delete_coursematch_setting_form.php');

class local_cas_help_links_renderer extends plugin_renderer_base {

    public function link_to_analytics() {
        return $this->action_link('category_analytics.php', get_string('analytics_link_label', 'local_cas_help_links'));
    }

    public function link_to_category_settings() {
        return $this->action_link('category_settings.php', get_string('category_settings_link_label', 'local_cas_help_links'));
    }

    public function cas_help_links($courseSettingsData,$categorySettingsData,$userSettingsData) {
        global $USER;
        $mform = new cas_form(null, array('courseSettingsData' => $courseSettingsData,'categorySettingsData' => $categorySettingsData,'userSettingsData' => $userSettingsData));

        $out = $mform->display();
        return $out;
    }

    public function cas_category_links($categorySettingsData) {
        global $USER;
        $mform = new cas_cat_form(null, array('categorySettingsData' => $categorySettingsData));

        $out = $mform->display();
        return $out;
    }

    public function cas_delete_coursematch($coursematch) {
        $mform = new cas_delete_coursematch_form(null, [
            'coursematch_id' => $coursematch->id,
            'coursematch_dept' => $coursematch->dept,
            'coursematch_number' => $coursematch->number,
            'coursematch_link' => $coursematch->link,
        ]);

        $out = $mform->display();
        return $out;
    }

    public function semester_usage_chart() {
        $out = html_writer::tag('canvas', null, array('id'=>'chart'));
        return $out;
    }

}
