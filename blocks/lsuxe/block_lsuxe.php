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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_lsuxe extends block_list {

    public $course;
    public $user;
    public $content;
    public $coursecontext;

    function init() {
        global $PAGE;

        $this->title = get_string('pluginname', 'block_lsuxe');
        // $this->set_course();
        // $this->set_user();
        // $this->set_course_context();
        // $extras = array();
        // $PAGE->requires->js_call_amd('block_lsuxe/main', 'init', $extras);
    }

   /**
     * Returns the course object
     *
     * @return @object
     */
    // public function set_course() {
    //     global $COURSE;
    //     $this->course = $COURSE;
    // }

    /**
     * Returns the user object
     *
     * @return @object
     */
    // public function set_user() {
    //     global $USER;
    //     $this->user = $USER;
    // }

    /**
     * Sets and returns this course's context
     *
     * @return @context
     */
    // private function set_course_context() {
    //     $this->course_context = context_course::instance($this->course->id);
    // }


    /**
     * Indicates that this block has its own configuration settings
     *
     * @return @bool
     */
    public function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = $this->get_new_content_container();

        // $coursecontext = context_course::instance($this->course->id);
        // $systemcontext = context_system::instance();

        if (is_siteadmin()) {

            $this->add_item_to_content([
                'lang_key' => get_string('mappings_view', 'block_lsuxe'),
                'icon_key' => 'i/mnethost',
                'page' => 'mappings'
            ]);

            $this->add_item_to_content([
                'lang_key' => get_string('mappings_create', 'block_lsuxe'),
                'icon_key' => 'i/mnethost',
                'page' => 'mappings',
                'query_string' => ['vform' => 1]
            ]);
            
            $this->add_item_to_content([
                'lang_key' => get_string('tokens_view', 'block_lsuxe'),
                'icon_key' => 't/unlock',
                'page' => 'tokens'
            ]);

            $this->add_item_to_content([
                'lang_key' => get_string('moodles_view', 'block_lsuxe'),
                'icon_key' => 't/calc',
                'page' => 'moodles'
            ]);

            $this->add_item_to_content([
                'lang_key' => get_string('moodles_create', 'block_lsuxe'),
                'icon_key' => 't/calc',
                'page' => 'moodles',
                'query_string' => ['vform' => 1]
            ]);
            
        }
        
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
        global $CFG, $OUTPUT;

        $label = $params['lang_key'];
        $icon = $OUTPUT->pix_icon($params['icon_key'], $label, 'moodle', ['class' => 'icon']);

        return html_writer::link(
            new moodle_url($CFG->wwwroot . '/blocks/lsuxe/' . $params['page'] . '.php', $params['query_string']),
            $icon . $label
        );
    }
    
    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array(
            'all' => true, // TODO: remove this once done dev
            'site' => true,
            'my' => true,
            'site-index' => true,
            'course-view' => false, 
            'course-view-social' => false,
            'mod' => false, 
            'mod-quiz' => false
        );
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function cron() {
        // mtrace( "Hey, my cron script is running" );
        // do something
        return true;
    }

    /**
     * Returns an empty "block list" content container to be filled with content
     *
     * @return @object
     */
    private function get_new_content_container() {
        $content = new stdClass;
        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        return $content;
    }
}
