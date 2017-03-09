<?php
 
class local_cas_help_links_button_renderer {

    /**
     * Returns an appropriately generated HTML link for a CAS help link given a moodle course
     * 
     * @param  object $course  moodle course object
     * @param  array $attributes  an array of attributes to be applied to the link (optional)
     * @return string
     */
    public static function get_html_for_course($course, $attributes = [])
    {
        $help_url_array = \local_cas_help_links_url_generator::getUrlArrayForCourse($course);

        if ( ! $help_url_array['display'])
            return '';

        $class = array_key_exists('class', $attributes) ? $attributes['class'] : '';

        $interstitial_url = new moodle_url('/local/cas_help_links/interstitial.php', [
            'u' => $help_url_array['url'],
            'c' => $help_url_array['course_id'],
            'l' => $help_url_array['link_id']
        ]);

        $tooltip_text = self::build_tooltip_text($course, $help_url_array);

        return $help_url_array['display'] ? '<a class="' . $class . '" href="' . $interstitial_url . '" target="_blank" title="' . $tooltip_text . '">' . $help_url_array['label'] . '</a>' : '';
    }

    /**
     * Returns an appropriately generated HTML link for a CAS help link given a user id
     * 
     * @param  int $user_id
     * @param  array $attributes  an array of attributes to be applied to the link (optional)
     * @return string
     */
    public static function get_html_for_user_id($user_id, $attributes = [])
    {
        $help_url_array = \local_cas_help_links_url_generator::getUrlForUser($user_id);

        if ( ! $help_url_array['display'])
            return '';

        $class = array_key_exists('class', $attributes) ? $attributes['class'] : '';

        return $help_url_array['display'] ? '<a class="' . $class . '" href="' . $help_url_array['url'] . '" target="_blank">' . $help_url_array['label'] . '</a>' : '';
    }

    /**
     * Generates tooltip text for this course based on the viewed instance
     * 
     * @param  object $course   moodle course
     * @param  array $helpUrlArray
     * @return string
     */
    private static function build_tooltip_text($course, $helpUrlArray)
    {
        // first, if this is a customized link preference, attempt to get the teacher's name from the course object
        if ( ! $helpUrlArray['is_default_display'] && $courseName = self::get_course_name_from_course($course))
            return $courseName . ' ' . get_string('study_help', 'local_cas_help_links');

        // otherwise, attempt to get the department name
        if ($deptName = self::get_department_name_from_course($course))
            return get_string('cas_study_help', 'local_cas_help_links') . ' ' . $deptName;

        return self::get_default_tooltip_text();
    }

    /**
     * Returns the primary instructor's name from a given course
     * 
     * @param  object $course   moodle course
     * @return string
     */
    private static function get_course_name_from_course($course)
    {
        // if we can't get this course's full name, display default text as tooltip
        if ( ! property_exists($course, 'fullname'))
            return '';

        $courseName = $course->fullname;

        if ( ! $courseName)
            return '';

        return $courseName;
    }

    /**
     * Returns the department's name from a given course
     * 
     * @param  object $course   moodle course
     * @return string
     */
    private static function get_department_name_from_course($course)
    {
        global $CFG;
        require_once($CFG->libdir.'/coursecatlib.php');
        
        if ($category = coursecat::get($course->category, IGNORE_MISSING))
            return $category->name;

        return '';
    }

    /**
     * Returns default tooltip text
     * 
     * @return string
     */
    private static function get_default_tooltip_text()
    {
        return get_string('help_button_label', 'local_cas_help_links');
    }
    
}
