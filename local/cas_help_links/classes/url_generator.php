<?php
 
class local_cas_help_links_url_generator {

    /**
     * Returns an array that includes data about the appropriate CAS Help link to be displayed for this course/user
     * 
     * @param  object $course  moodle course object
     * @param  bool $editLinkForInstructor  if true, will return a link to edit this setting
     * @return array  display|url|label
     */
    public static function getUrlArrayForCourse($course, $editLinkForInstructor = true)
    {
        // if this plugin is disabled, do not display
        if ( ! \local_cas_help_links_utility::isPluginEnabled())
            return self::getEmptyHelpUrlArray();
        
        $course_id = $course->id;
        $category_id = $course->category;

        // if we can't find a primary instructor for the given course, do not display
        if ( ! $primary_instructor_user_id = \local_cas_help_links_utility::getPrimaryInstructorId($course->idnumber)) {
            return self::getEmptyHelpUrlArray();
        }

        // if primary instructor is requesting
        if ($primary_instructor_user_id == \local_cas_help_links_utility::getAuthUserId() && $editLinkForInstructor) {
            // return edit link
            return self::getCourseEditHelpUrlArray($course);
        } else {
            //  otherwise return link pref data
            return self::getDisplayHelpUrlArray($course_id, $category_id, $primary_instructor_user_id);
        }
    }

    /**
     * Returns a appropriate URL for editting CAS help link settings
     * 
     * @param  object $course  moodle course object
     * @return string
     */
    private static function getCourseEditHelpUrlArray($course)
    {
        global $CFG;
        
        $urlArray = [
            'display' => true,
            'url' => $CFG->wwwroot . '/local/cas_help_links/user_settings.php?id=' . \local_cas_help_links_utility::getAuthUserId(),
            'label' => get_string('settings_button_label', 'local_cas_help_links'),
        ];

        return $urlArray;
    }

    /**
     * Returns the preferred help link URL array for the given parameters
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return array
     */
    private static function getDisplayHelpUrlArray($course_id, $category_id, $primary_instructor_user_id)
    {
        // get appropriate pref from db
        if ( ! $selectedPref = \local_cas_help_links_utility::getSelectedPref($course_id, $category_id, $primary_instructor_user_id)) {
            // if no pref can be resolved, return default settings using system config
            $urlArray = self::getDefaultHelpUrlArray();
        } else {
            // otherwise, convert the selected pref result to a single object
            $selectedPref = reset($selectedPref); // @WATCH - should be no multiple results confusion here

            $urlArray = [
                'display' => $selectedPref->display,
                'url' => $selectedPref->link,
                'label' => get_string('help_button_label', 'local_cas_help_links'),
                'link_id' => $selectedPref->id,
            ];
        }

        return $urlArray;
    }

    /**
     * Returns the default help url settings as array
     * 
     * @return array
     */
    private static function getDefaultHelpUrlArray()
    {
        return [
            'display' => \local_cas_help_links_utility::isPluginEnabled(),
            'url' => get_config('local_cas_help_links', 'default_help_link'),
            'label' => get_string('help_button_label', 'local_cas_help_links'),
            'link_id' => ''
        ];
    }

    /**
     * Returns a default, "empty" URL array
     * 
     * @return array
     */
    private static function getEmptyHelpUrlArray()
    {
        return [
            'display' => false,
            'url' => '',
            'label' => '',
            'link_id' => 0
        ];
    }
}
