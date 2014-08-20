<?php
/**
 * Copyright (c) 2011 bfwpub.com (R) <http://support.bfwpub.com/>
 *
 * This file is part of bfwpub Moodle LMS integration.
 *
 * bfwpub Sakai LMS integration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * bfwpub Sakai LMS integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with bfwpub Sakai LMS integration.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: block_bfwpub.php 1798 2012-07-10 19:12:08Z aaronz $ */

defined('MOODLE_INTERNAL') || die();

global $CFG,$db_user,$COURSE;
// link in external libraries
require_once ($CFG->dirroot.'/blocks/bfwpub/bfwpub_service.php');

class block_bfwpub extends block_base {

    /**
     * Executed on block startup for setting up these variables:
     * $this->title is the title displayed in the header of our block.
     * We can set it to whatever we like; in this case it's set to read the actual title
     * from a language file we are presumably distributing together with the block.
     * If you want your block to display no title at all,
     * then you should set this to any descriptive value you want
     * (but not make it an empty string, it must be set) and
     * then disable title display:
     * function hide_header() {
     *   return true;
     * }
     *
     * $this->version is the version of our block.
     * This actually would only make a difference if your block wanted to keep its own
     * data in special tables in the database (i.e. for very complex blocks).
     * In that case the version number is used exactly as it's used in activities; an upgrade script
     * uses it to incrementally upgrade an "old" version of the block's data to the latest.
     */
    function init() {
        $this->title = get_string('app.bfwpub', bfwpub_service::BLOCK_NAME);
        //$this->cron = 86400; // in seconds
        //$this->version = 2011111600; // version.php
    }

    /**
     * Executed by Moodle as soon as our instance configuration is loaded and available
     * (that is, immediately after init() is called).
     * NOTE: we can use $this->config in all methods except init()
     */
    function specialization() {
    }

    /**
     * Determines the content to display in a block
     *
     * Blocks use two properties of $this->content: "text" and "footer".
     * The text is displayed as-is as the block content, and the footer is displayed below the content in a smaller font size.
     *
     * List blocks use $this->content->footer in the exact same way,
     * but they ignore $this->content->text.
     * Moodle expects such blocks to set two other properties when the get_content() method is called:
     * $this->content->items and $this->content->icons.
     * $this->content->items should be a numerically indexed array containing elements that
     * represent the HTML for each item in the list that is going to be displayed.
     * Usually these items will be HTML anchor tags which provide links to some page.
     * $this->content->icons should also be a numerically indexed array, with exactly as many items
     * as $this->content->items has. Each of these items should be a fully qualified HTML <img> tag,
     * with "src", "height", "width" and "alt" attributes. Obviously, it makes sense to keep the images
     * small and of a uniform size.
     * In order to tell Moodle that we want to have a list block instead of the standard text block,
     * we need to make a small change to our block class declaration.
     * Instead of extending class block_base, our block will extend class block_list.
     *
     * You can hide the block by displaying nothing. That means that both
     * $this->content->text and $this->content->footer are each equal to the
     * empty string (''). Moodle performs this check by calling the block's
     * is_empty() method, and if the block is indeed empty then it is not
     * displayed at all.
     *
     * @return string the content to display in the block
     */
    function get_content() {
        // for bfwpub there is really nothing to show here
        if ($this->content !== NULL) {
            return $this->content;
        } else {
            $this->content = new stdClass();
            // CONTENT
            $this->content->text = '';
            // FOOTER
            $this->content->footer = '';
        }
        return $this->content;
    }

    /**
     * Execute this method when the cron runs
     */
/*
    function cron() {
        return true;
    }
*/

    /**
     * Allows us to use the persistent instance configuration for this block,
     * this allows storage of values in $this->config for each instance of the block.
     *
     * Requires the config_instance.html file to be present
     *
     * @return bool true if this block has some persistent storage in the instance configuration
     */
    function instance_allow_config() {
        return false;
    }

/* DEFAULT instance config save
    function instance_config_save($data) {
        $data = stripslashes_recursive($data);
        $this->config = $data;
        return set_field('block_instance', 'configdata',
            base64_encode(serialize($data)), 'id', $this->instance->id);
    }
*/

    /**
     * Cleanup of the block instance data when the instance is removed
     */
    function instance_delete() {
        //delete_records('bfwpub', 'blockid', $this->instance->id);
    }

    /**
     * @return bool true if this block allows multiple instances per install/course
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * Requires a config_global.html file
     *
     * @return bool true if this block is configurable
     */
    function has_config() {
        return true;
    }

/* DEFAULT config save
    function config_save($data) {
        // Default behavior: save all variables as $CFG properties
        foreach ($data as $name=>$value) {
            set_config($name, $value);
        }
        return true;
    }
*/
    function config_save($data) {
        // store the values in the plugin config instead of global
        foreach ($data as $name=>$value) {
            if (empty($value)) {
                $value = NULL;
            }
            set_config($name, $value, bfwpub_service::BLOCK_NAME);
        }
        return true;
    }

    /**
     * Called when the block is deleted
     */
    function before_delete() {
    }

    /**
     * @return bool true to hide the header or false to show it
     */
    function hide_header() {
        return false;
    }

/* Control the width of the block
    function preferred_width() {
        // The preferred value is in pixels - default 200
        return 200;
    }
*/
/* Control the html attributes on the block container
    function html_attributes() {
        $attrs = parent::html_attributes();
        // an array of html attributes to add to the container which holds the block
        $myAttrs = array(
            'id' => 'inst'.$this->instance->id,
            'class' => 'sideblock block_'.$this->name(),
            'onmouseover' => "alert('Mouseover on our block!');"
        );
        return array_merge($myAttrs, $attrs);
    }
*/
    /**
     * Valid format names are:
     * site-index - The format name for the front page of Moodle
     * course-view - the main course page
     * The format name for course types: course-view-weeks, course-view-topics, course-view-social, etc.
     * mod - a module, (mod-quiz is a specific module)
     *
     * @return array an array of all course areas this block should be allowed to appear
     */
    function applicable_formats() {
        return array('site' => false, 'my' => false, 'course-view' => true);
    }

}
