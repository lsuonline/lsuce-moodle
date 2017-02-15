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

        return $help_url_array['display'] ? '<a class="' . $class . '" href="' . $interstitial_url . '" target="_blank">' . $help_url_array['label'] . '</a>' : '';
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
    
}
