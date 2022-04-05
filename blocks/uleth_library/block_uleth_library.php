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
 * UofL Library Search Block.
 *
 * @package    block_uleth_library
 * @copyright  David Lowe <david.lowe@uleth.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_uleth_library extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_uleth_library');
    }

    function get_content() {
        global $CFG, $OUTPUT;


        if (isset($this->content)) {
            return $this->content;
        }
        // $group = get_user_preferences('block_myoverview_user_grouping_preference');
        // $sort = get_user_preferences('block_myoverview_user_sort_preference');
        // $view = get_user_preferences('block_myoverview_user_view_preference');
        // $paging = get_user_preferences('block_myoverview_user_paging_preference');
        // $customfieldvalue = get_user_preferences('block_myoverview_user_grouping_customfieldvalue_preference');
        // $renderable = new \block_myoverview\output\main($group, $sort, $view, $paging, $customfieldvalue);

        $renderable = new \block_uleth_library\output\main();
        $renderer = $this->page->get_renderer('block_uleth_library');

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;

        /* DEFAULT CODE BELOW
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        // user/index.php expect course context, so get one if page has module context.
        $currentcontext = $this->page->context->get_course_context(false);

        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }

        $this->content = '';
        if (empty($currentcontext)) {
            return $this->content;
        }
        if ($this->page->course->id == SITEID) {
            $this->content->text .= "site context";
        }

        if (! empty($this->config->text)) {
            $this->content->text .= $this->config->text;
        }

        return $this->content;
        */
    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'all' => false,
            'my' => true,
            'site' => true,
            'site-index' => true,
            'course-view' => true, 
            'course-view-social' => false,
            'mod' => true, 
            'mod-quiz' => false
        );
    }

    /**
     * Allow the block to have a configuration page.
     *
     * @return boolean
     */
    function has_config() {
        return true;
    }

    public function instance_allow_multiple() {
          return true;
    }


    // public function cron() {
    //         mtrace( "Hey, my cron script is running" );
             
    //              // do something
                  
    //                   return true;
    // }
}
