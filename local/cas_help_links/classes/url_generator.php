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

defined('MOODLE_INTERNAL') || die;

class local_cas_help_links_url_generator {

    /**
     * Returns an array that includes data about the appropriate CAS Help link to be displayed for this course/user
     *
     * @param  object $course  moodle course object
     * @param  bool $editLinkForInstructor  if true, will return a link to edit this setting
     * @return array  display|url|label
     */
    public static function getUrlArrayForCourse($course, $editLinkForInstructor = false) {
        // If this plugin is disabled, do not display.
        if ( ! \local_cas_help_links_utility::isPluginEnabled()) {
            return self::getEmptyHelpUrlArray();
        }

        // Old variable name: course_id.
        $courseid = $course->id;
        // Old variable name: category_id.
        $categoryid = $course->category;

        // If we can't find a primary instructor for the given course, do not display.
        // Old variable name: primary_instructor_user_id.
        if ( ! $primaryinstructoruserid = \local_cas_help_links_utility::getPrimaryInstructorId($course->idnumber)) {
            return self::getEmptyHelpUrlArray();
        } else {
            // Otherwise return link pref data.
            return self::getDisplayHelpUrlArray($courseid, $categoryid, $primaryinstructoruserid);
        }
    }

    /**
     * Returns an array that includes data about the appropriate CAS Help link to be displayed for this <course>
     * User may be a primary instructor or CAS help administrator, but not both (instructor role takes precedence).
     *
     * @param  $userid
     * @return array  display|url|label
     */
    public static function getUrlForUser($userid) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/cps/classes/lib.php');
        // If this plugin is disabled, do not display.
        if ( ! \local_cas_help_links_utility::isPluginEnabled()) {
            return self::getEmptyHelpUrlArray();
        }

        // If primary instructor is requesting.
        if (\ues_user::is_teacher($userid)) {
            // Return edit link.
            return self::getCourseEditHelpUrl();
        } else if (has_capability('local/cas_help_links:editcategorysettings', context_system::instance())) {
            return self::getCategoryEditHelpUrl();
        } else {
            // Otherwise rdo not display.
            return self::getEmptyHelpUrlArray();
        }
    }

    /**
     * Returns an appropriate URL for editing CAS help link settings
     * TODO UNUSED - REMOVE CHAD PLEASE LOOK AT
     * @param  object $course  moodle course object
     * @return string
     */
    private static function getCourseEditHelpUrlArray($course) {
        global $CFG;

        // Old variable name: urlArray.
        $urlarray = [
            'display' => true,
            'url' => $CFG->wwwroot . '/local/cas_help_links/user_settings.php?id=' . \local_cas_help_links_utility::getAuthUserId(),
            'label' => get_string('settings_button_label', 'local_cas_help_links'),
        ];

        return $urlarray;
    }

    /**
     * Returns an appropriate URL for editing CAS course links
     *
     * @param  object $USER  moodle USER object
     * @return string
     */
    private static function getCourseEditHelpUrl() {
        global $CFG, $USER;

        $urlarray = [ // Old variable name: urlArray.
            'display' => true,
            'url' => $CFG->wwwroot . '/local/cas_help_links/user_settings.php?id=' . $USER->id,
            'label' => get_string('settings_button_label', 'local_cas_help_links'),
        ];

        return $urlarray;
    }

    /**
     * Returns an appropriate URL for editing CAS category links
     *
     * @return string
     */
    private static function getCategoryEditHelpUrl() {
        global $CFG;

        // Old variable name: urlArray.
        $urlarray = [
            'display' => true,
            'url' => $CFG->wwwroot . '/local/cas_help_links/category_settings.php',
            'label' => get_string('cas_help_links:editcategorysettings', 'local_cas_help_links'),
        ];

        return $urlarray;
    }

    /**
     * Returns the preferred help link URL array for the given parameters
     *
     * @param  int  $courseid, old variable name: course_id
     * @param  int  $categoryid, old variable name: category_id
     * @param  int  $primaryinstructoruserid, old variable name: primary_instructor_user_id
     * @return array
     */
    private static function getDisplayHelpUrlArray($courseid, $categoryid, $primaryinstructoruserid) {
        // Get appropriate pref from db.
        // Old variable name: selectedPref.
        if ( ! $selectedpref = \local_cas_help_links_utility::getSelectedPref($courseid
                                                                              , $categoryid
                                                                              , $primaryinstructoruserid)) {
            // If no pref can be resolved, return default settings using system config.
            // Old variable name: urlArray.
            $urlarray = self::getDefaultHelpUrlArray($courseid);
        } else {
            // Otherwise, convert the selected pref result to a single object.
            $selectedpref = reset($selectedpref); // WATCH - should be no multiple results confusion here.

            $urlarray = [
                'display' => $selectedpref->display,
                'url' => $selectedpref->link,
                'label' => get_string('help_button_label', 'local_cas_help_links'),
                'course_id' => $courseid,
                'link_id' => $selectedpref->id,
                'is_default_display' => (bool) ! $selectedpref->user_id
            ];
        }

        return $urlarray;
    }

    /**
     * Returns the default help url settings as array
     *
     * @param  int  $courseid, old variable name: $course_id
     * @return array
     */
    private static function getDefaultHelpUrlArray($courseid) {
        return [
            'display' => \local_cas_help_links_utility::isPluginEnabled()
            , 'url' => get_config('local_cas_help_links', 'default_help_link')
            , 'label' => get_string('help_button_label', 'local_cas_help_links')
            , 'course_id' => $courseid
            , 'link_id' => '0'
            , 'is_default_display' => true
        ];
    }

    /**
     * Returns a default, "empty" URL array
     *
     * @return array
     */
    private static function getEmptyHelpUrlArray() {
        return [
            'display' => false
            , 'url' => ''
            , 'label' => ''
            , 'course_id' => 0
            , 'link_id' => 0
            , 'is_default_display' => true
        ];
    }
}