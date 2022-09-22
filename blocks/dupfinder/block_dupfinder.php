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
 * @package    block_dupfinder
 * @copyright  2022 onwards LSU Online & Continuing Education
 * @copyright  2008 onwards Robert Russo
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_dupfinder extends block_list {
    public $user;
    public $content;
    public $systemcontext;

    public function init() {
        $this->title = get_string('pluginname', 'block_dupfinder');
        $this->set_user();
        $this->set_system_context();
    }

    /**
     * Returns the user object
     *
     * @return @object
     */
    public function set_user() {
        global $USER;

        $this->user = $USER;
    }

    /**
     * Returns the system context
     *
     * @return context
     */
    private function set_system_context() {
        $this->system_context = context_system::instance();
    }

    /**
     * Indicates which pages types this block may be added to
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
             'site-index' => true,
            'course-view' => false
        );
    }

    /**
     * Indicates that this block has its own configuration settings
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets the content to be rendered when displaying this block
     *
     * @return object
     */
    public function get_content() {
        if (!empty($this->content)) {
            return $this->content;
        }

        // Create a fresh content container.
        $this->content = $this->get_new_content_container();
        $systemcontext = context_system::instance();

        // BROADCAST (site-scoped admin message).
        $this->add_item_to_content([
            'lang_key' => get_string('get_dupes', 'block_dupfinder'),
            'icon_key' => 't/users',
            'page' => 'dupes',
        ]);

        return $this->content;
    }

    /**
     * Builds and adds an item to the content container for the given params
     *
     * @param  array $params  [lang_key, icon_key, page, query_string]
     * @return void
     */
    private function add_item_to_content($params) {
        if (!array_key_exists('query_string', $params)) {
            $params['query_string'] = [];
        }

        $item = $this->build_item($params);

        $this->content->items[] = $item;
    }

    /**
     * Builds a content item (link) for the given params
     *
     * @param  array $params  [lang_key, icon_key, page, query_string]
     * @return string
     */
    private function build_item($params) {
        global $OUTPUT;

        $label = $params['lang_key'];
        $icon = $OUTPUT->pix_icon($params['icon_key'], $label, 'moodle', ['class' => 'icon']);

        return html_writer::link(
            new moodle_url('/blocks/dupfinder/' . $params['page'] . '.php'),
            $icon . $label
        );
    }

    /**
     * Returns an empty "block list" content container to be filled with content
     *
     * @return object
     */
    private function get_new_content_container() {
        $content = new stdClass;
        $content->items = [];
        $content->icons = [];
        $content->footer = '';

        return $content;
    }

    /**
     * Reports whether or not this is a site-level course
     *
     * @return boolean
     */
    private function is_site_course() {
        return $this->course->id == SITEID;
    }
}
