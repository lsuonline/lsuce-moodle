<?php
 
class local_cas_help_links_utility {

    /**
     * Returns an array of this primary instructor user's course settings data
     * 
     * @param  int  $user_id
     * @return array
     */
    public static function get_primary_instructor_course_settings($user_id)
    {
        $courseData = self::get_primary_instructor_course_data($user_id);

        $transformedCourseData = self::transform_course_data($courseData, $user_id);

        return $transformedCourseData;
    }
    
    /**
     * Returns an array of this primary instructor user's category settings data
     * 
     * @param  int  $user_id
     * @return array
     */
    public static function get_primary_instructor_category_settings($user_id)
    {
        $categoryData = self::get_primary_instructor_category_data($user_id);

        $transformedCategoryData = self::transform_category_data($categoryData, $user_id);

        return $transformedCategoryData;
    }

   /**
     * Returns an array of this primary instructor user's personal settings data
     * 
     * @param  int  $user_id
     * @return array
     */
    public static function get_primary_instructor_user_settings($user_id)
    {
        $userLink = self::get_user_link_data($user_id);

        $transformedUserData = self::transform_user_data($userLink, $user_id);

        return $transformedUserData;
    }

    /**
     * Returns an array of all category settings data
     * 
     * @return array
     */
    public static function get_all_category_settings()
    {
        $categoryData = self::get_category_data();

        $transformedCategoryData = self::transform_category_data($categoryData);

        return $transformedCategoryData;
    }

    /**
     * Returns an array of all existing "coursematch" settings data
     * 
     * @return array
     */
    public static function get_all_coursematch_settings()
    {
        global $DB;

        $results = $DB->get_records('local_cas_help_links', ['type' => 'coursematch']);

        return $results;
    }

    /**
     * Returns an array of the given teacher user's course ids and shortnames
     * 
     * @param  int $user_id
     * @param  bool $idsOnly
     * @return array
     */
    public static function get_teacher_course_selection_array($user_id, $idsOnly = false)
    {
        $courseData = self::get_primary_instructor_course_data($user_id);

        $output = [];

        foreach ($courseData as $course_id => $course) {
            $output[$course_id] = $course->shortname;
        }

        return ! $idsOnly ? $output : array_keys($output);
    }

    /**
     * Fetches the given primary's current course data
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_primary_instructor_course_data($user_id)
    {
        global $DB;

        // @TODO: make cou_number variable
        $result = $DB->get_records_sql('SELECT DISTINCT u.id, c.id, c.fullname, c.shortname, c.idnumber, c.category, cc.name FROM {enrol_ues_teachers} t
            INNER JOIN {user} u ON u.id = t.userid
            INNER JOIN {enrol_ues_sections} sec ON sec.id = t.sectionid
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {enrol_ues_semesters} sem ON sem.id = sec.semesterid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            INNER JOIN {course_categories} cc ON cc.id = c.category
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND cou.cou_number < "5000"
            AND t.primary_flag = "1"
            AND t.status = "enrolled"
            AND sem.classes_start < ' . self::get_course_start_time() . ' 
            AND sem.grades_due > ' . self::get_course_end_time() . ' 
            AND u.id = ?', array($user_id));

        return $result;
    }

    /**
     * Fetches the given primary's current category data
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_primary_instructor_category_data($user_id)
    {
        global $DB;
        
        // @TODO: make cou_number variable
        $result = $DB->get_records_sql('SELECT DISTINCT u.id, cc.id, cc.name FROM {enrol_ues_teachers} t
            INNER JOIN {user} u ON u.id = t.userid
            INNER JOIN {enrol_ues_sections} sec ON sec.id = t.sectionid
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {enrol_ues_semesters} sem ON sem.id = sec.semesterid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            INNER JOIN {course_categories} cc ON cc.id = c.category
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND t.primary_flag = "1"
            AND t.status = "enrolled"
            AND cou.cou_number < "5000"
            AND sem.classes_start < ' . self::get_course_start_time() . '
            AND sem.grades_due > ' . self::get_course_end_time() . '
            AND u.id = ?', array($user_id));

        return $result;
    }

    /**
     * Fetches a cas_help_link object as an array for the given user
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_link_data($user_id)
    {
        $result = self::get_links('user', $user_id);

        $result = count($result) ? current($result) : [];

        return $result;
    }

    /**
     * Fetches category data
     * 
     * @param  bool $forSelectList
     * @return array
     */
    public static function get_category_data($forSelectList = false)
    {
        global $DB;

        $result = $DB->get_records_sql('SELECT DISTINCT id, name FROM {course_categories}');

        if ( ! $forSelectList)
            return $result;

        $output = [];

        foreach ($result as $category) {
            if ($category->id == 1)
                continue;

            $output[$category->name] = $category->name;
        }

        return $output;
    }

    /**
     * Returns an array of the given course data array but including 'cas_help_link' information
     * 
     * @param  array $courseData
     * @return array
     */
    private static function transform_course_data($courseData, $user_id)
    {
        $output = [];

        $userCourseLinks = self::get_user_course_link_data($user_id);

        foreach ($courseData as $courseArray) {
            $linkExistsForCourse = array_key_exists($courseArray->id, $userCourseLinks);

            // if a link record exists for the user/course, show/hide depending on 'display',
            // otherwise, do not hide
            $hideLink = $linkExistsForCourse ? ! $userCourseLinks[$courseArray->id]->display : false;

            $linkId = $linkExistsForCourse ? $userCourseLinks[$courseArray->id]->id : '0';

            $output[$courseArray->id] = [
                'user_id' => $user_id,
                'course_id' => $courseArray->id,
                'course_fullname' => $courseArray->fullname,
                'course_shortname' => $courseArray->shortname,
                'course_idnumber' => $courseArray->idnumber,
                'course_category_id' => $courseArray->category,
                'course_category_name' => $courseArray->name,
                'link_id' => $linkId,
                'link_display' => $linkExistsForCourse ? $userCourseLinks[$courseArray->id]->display : '0',
                'hide_link' => $hideLink ? 1 : 0,
                'link_url' => $linkExistsForCourse ? $userCourseLinks[$courseArray->id]->link : '',
                'display_input_name' => \local_cas_help_links_input_handler::encode_input_name('display', 'course', $linkId, $courseArray->id),
                'link_input_name' => \local_cas_help_links_input_handler::encode_input_name('link', 'course', $linkId, $courseArray->id)
            ];
        }

        return $output;
    }

    /**
     * Returns an array of the given category data array but including 'cas_help_link' information
     * 
     * @param  array $categoryData
     * @return array
     */
    private static function transform_category_data($categoryData, $user_id = 0)
    {
        $output = [];

        $categoryLinks = $user_id ? self::get_user_category_link_data($user_id) : self::get_category_link_data();

        foreach ($categoryData as $categoryArray) {
            $linkExistsForCategory = array_key_exists($categoryArray->id, $categoryLinks);

            // if a link record exists for the user/category, show/hide depending on 'display',
            // otherwise, do not hide
            $hideLink = $linkExistsForCategory ? ! $categoryLinks[$categoryArray->id]->display : false;

            $linkId = $linkExistsForCategory ? $categoryLinks[$categoryArray->id]->id : '0';

            $output[$categoryArray->id] = [
                'user_id' => $user_id,
                'category_id' => $categoryArray->id,
                'category_name' => $categoryArray->name,
                'link_id' => $linkId,
                'link_display' => $linkExistsForCategory ? $categoryLinks[$categoryArray->id]->display : '0',
                'hide_link' => $hideLink ? 1 : 0,
                'link_url' => $linkExistsForCategory ? $categoryLinks[$categoryArray->id]->link : '',
                'display_input_name' => \local_cas_help_links_input_handler::encode_input_name('display', 'category', $linkId, $categoryArray->id),
                'link_input_name' => \local_cas_help_links_input_handler::encode_input_name('link', 'category', $linkId, $categoryArray->id)
            ];
        }

        return $output;
    }

    /**
     * Returns an array of the given user data but including 'cas_help_link' information
     * 
     * @param  mixed $link
     * @return array
     */
    private static function transform_user_data($link, $user_id)
    {
        // if a link record exists for the user, show/hide depending on 'display',
        // otherwise, do not hide
        $hideLink = is_object($link) ? ! $link->display : false;

        $linkId = is_object($link) ? $link->id : '0';

        return [
            'user_id' => $user_id,
            'link_id' => is_object($link) ? $link->id : '',
            'link_display' => is_object($link) ? $link->display : '',
            'hide_link' => $hideLink ? 1 : 0,
            'link_url' => is_object($link) ? $link->link : '',
            'display_input_name' => \local_cas_help_links_input_handler::encode_input_name('display', 'user', $linkId, $user_id),
            'link_input_name' => \local_cas_help_links_input_handler::encode_input_name('link', 'user', $linkId, $user_id)
        ];
    }

    /**
     * Returns an array of this user's course link preferences, if any, keyed by the course_id
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_course_link_data($user_id)
    {
        // pull raw cas_help_links records
        $userCourseLinks = self::get_user_course_links($user_id);

        $output = [];

        // re-key array with course_id instead of link record id
        foreach ($userCourseLinks as $linkId => $linkData) {
            $output[$linkData->course_id] = $linkData;
        }

        return $output;
    }

    /**
     * Returns an array of this user's category link preferences, if any, keyed by the category_id
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_category_link_data($user_id)
    {
        // pull raw cas_help_links records
        $userCategoryLinks = self::get_user_category_links($user_id);

        $output = [];

        // re-key array with category_id instead of link record id
        foreach ($userCategoryLinks as $linkId => $linkData) {
            $output[$linkData->category_id] = $linkData;
        }

        return $output;
    }

    /**
     * Returns an array of this category's link preferences, if any, keyed by the category_id
     * 
     * @return array
     */
    private static function get_category_link_data()
    {
        // pull raw cas_help_links records
        $categoryLinks = self::get_category_links();

        $output = [];

        // re-key array with category_id instead of link record id
        foreach ($categoryLinks as $linkId => $linkData) {
            $output[$linkData->category_id] = $linkData;
        }

        return $output;
    }

    /**
     * Fetches an array of cas_help_link objects for the given user's courses
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_course_links($user_id)
    {
        return self::get_links('course', $user_id);
    }

    /**
     * Fetches an array of cas_help_link objects for the given user's categories
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_category_links($user_id)
    {
        return self::get_links('category', $user_id);
    }

    /**
     * Fetches an array of cas_help_link objects for all categories
     * 
     * @return array
     */
    private static function get_category_links()
    {
        return self::get_links('category');
    }

    /**
     * Fetches an array of cas_help_link objects of a given type
     *
     * Optionally, scopes to the given user id
     * 
     * @param  string $type
     * @param  int $user_id
     * @return object
     */
    private static function get_links($type, $user_id = 0)
    {
        global $DB;

        $params['type'] = $type;
        $params['user_id'] = $user_id ?: 0;

        $result = $DB->get_records('local_cas_help_links', $params);

        return $result;
    }

    /**
     * Fetches a cas_help_link object
     *
     * @param  int $link_id
     * @return object
     */
    public static function get_link($link_id)
    {
        global $DB;

        $result = $DB->get_record('local_cas_help_links', ['id' => $link_id]);

        return $result;
    }

    /**
     * Returns whether or not this plugin is enabled based off plugin config
     * 
     * @return boolean
     */
    public static function isPluginEnabled()
    {
        return (bool) get_config('local_cas_help_links', 'show_links_global');
    }

    /**
     * Returns a "primary instructor" user id given a course id number
     * 
     * @param  string $idnumber
     * @return int
     */
    public static function getPrimaryInstructorId($idnumber)
    {
        global $DB;

        // @TODO: make cou_number variable
        $result = $DB->get_records_sql('SELECT DISTINCT(t.userid), cts.requesterid FROM {enrol_ues_sections} sec
            INNER JOIN {enrol_ues_semesters} sem ON sem.id = sec.semesterid
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {enrol_ues_teachers} t ON t.sectionid = sec.id
            LEFT JOIN {enrol_cps_team_sections} cts ON cts.sectionid = sec.id
            WHERE t.primary_flag = 1
            AND sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND cou.cou_number < "5000"
            AND sem.classes_start < ' . self::get_course_start_time() . '
            AND sem.grades_due > ' . self::get_course_end_time() . '
            AND sec.idnumber = ?', array($idnumber));

        // if no query results, assume there is no primary user id
        if ( ! count($result))
            return 0;

        // get the key (column name) in which we'll look up the primary from the query results
        $key = count($result) > 1 ? 'requesterid' : 'userid';

        // get the first record from the results
        $first = array_values($result)[0];

        // get the user id from the results
        $userId = property_exists($first, $key) ? $first->$key : 0;

        // be sure to return 0 if still no user id can be determined
        return ! is_null($userId) ? (int) $userId : 0;
    }

    /**
     * Fetches select data from a UES course record given a moodle course id
     * 
     * @param  int $course_id
     * @return array
     */
    public static function get_ues_course_data($course_id)
    {
        global $DB;

        // @TODO: make cou_number variable
        $result = $DB->get_record_sql('SELECT DISTINCT uesc.department, uesc.cou_number, c.id FROM {enrol_ues_courses} uesc
            INNER JOIN {enrol_ues_sections} sec ON sec.courseid = uesc.id
            INNER JOIN {enrol_ues_semesters} sem ON sem.id = sec.semesterid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND uesc.cou_number < "5000"
            AND sem.classes_start < ' . self::get_course_start_time() . ' 
            AND sem.grades_due > ' . self::get_course_end_time() . ' 
            AND c.id = ?', array($course_id));

        return $result;
    }

    /**
     * Returns the currently authenticated user id
     * 
     * @return int
     */
    public static function getAuthUserId() {
        global $USER;
        
        return $USER->id;
    }

    /**
     * Retrieves the appropriate pref according to override hierarchy
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return mixed array|bool
     */
    public static function getSelectedPref($course_id, $category_id, $primary_instructor_user_id)
    {
        // pull all of the preference data relative to the course, category, user
        $prefs = self::getRelatedPrefData($course_id, $category_id, $primary_instructor_user_id);

        $selectedPref = false;

        $coursematch_dept = self::get_coursematch_dept_from_name($course_id);
        $coursematch_number = self::get_coursematch_number_from_name($course_id);

        // first, keep only prefs with this primary associated
        if ($primaryUserPrefs = array_where($prefs, function ($key, $pref) use ($primary_instructor_user_id) {
            return $pref->user_id == $primary_instructor_user_id;
        }))
        {
            // if so, keep only primary "hide" prefs, if any
            if ($primaryUserHidePrefs = array_where($primaryUserPrefs, function ($key, $pref) {
                return ! $pref->display;
            }))
            {
                // get any "hide" pref for this primary user
                $selectedPref = array_where($primaryUserHidePrefs, function ($key, $pref) {
                    return $pref->type == 'user';
                });

                if ( ! $selectedPref) {
                    // get any "hide" pref for this primary user & category
                    $selectedPref = array_where($primaryUserHidePrefs, function ($key, $pref) use ($category_id) {
                        return $pref->type == 'category' && $pref->category_id == $category_id;
                    });
                }

                if ( ! $selectedPref) {
                    // get any "hide" pref for this primary user & course
                    $selectedPref = array_where($primaryUserHidePrefs, function ($key, $pref) use ($course_id) {
                        return $pref->type == 'course' && $pref->course_id == $course_id;
                    });
                }
            // otherwise, keep only "show" prefs, if any
            } else if ($primaryUserShowPrefs = array_where($primaryUserPrefs, function ($key, $pref) {
                return $pref->display;
            }))
            {
                // get any "show" pref for this primary user & course
                $selectedPref = array_where($primaryUserShowPrefs, function ($key, $pref) use ($course_id) {
                    return $pref->type == 'course' && $pref->course_id == $course_id;
                });

                // get any "show" pref for this primary user & category
                if ( ! $selectedPref) {
                    $selectedPref = array_where($primaryUserShowPrefs, function ($key, $pref) use ($category_id) {
                        return $pref->type == 'category' && $pref->category_id == $category_id;
                    });
                }

                // get any "show" pref for this primary user
                if ( ! $selectedPref) {
                    $selectedPref = array_where($primaryUserShowPrefs, function ($key, $pref) {
                        return $pref->type == 'user';
                    });
                }
            }
        // otherwise, attempt to find a "coursematch"
        } else if ($selectedPref = array_where($prefs, function ($key, $pref) use ($coursematch_dept, $coursematch_number) {
                return $pref->type == 'coursematch' && $pref->dept == $coursematch_dept && $pref->number == $coursematch_number;
            })) {

        // otherwise, keep only this category's prefs
        } else if ($categoryPrefs = array_where($prefs, function ($key, $pref) use ($category_id) {
                return $pref->type == 'category' && $pref->category_id == $category_id && $pref->user_id == 0;
            })) {

            // get any "hide" pref for this category
            $selectedPref = array_where($categoryPrefs, function ($key, $pref) {
                return ! $pref->display;
            });

            if ( ! $selectedPref) {
                // get any "show" pref for this category
                $selectedPref = array_where($categoryPrefs, function ($key, $pref) {
                    return $pref->display;
                });
            }
        }
        
        return $selectedPref;
    }

    /**
     * Retrieves all pref data related to the given parameters
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return array
     */
    private static function getRelatedPrefData($course_id, $category_id, $primary_instructor_user_id = 0)
    {
        global $DB;
        
        $whereClause = self::buildPrefsWhereClause($course_id, $category_id, $primary_instructor_user_id);

        $result = $DB->get_records_sql("SELECT * FROM {local_cas_help_links} links WHERE " . $whereClause);

        return $result;
    }

    /**
     * Returns an appropriate sql where clause string given specific parameters
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return string
     */
    private static function buildPrefsWhereClause($course_id, $category_id, $primary_instructor_user_id = 0)
    {
        $wheres = [];
        
        // include this category in the results
        $wheres[] = "links.type = 'category' AND links.category_id = " . $category_id;

        // if a primary user was specified, include their link prefs
        if ($primary_instructor_user_id) {
            // include this user's personal settings
            $wheres[] = "links.type = 'user' AND links.user_id = " . $primary_instructor_user_id;
            
            // include this user's specific course settings
            $wheres[] = "links.type = 'course' AND links.user_id = " . $primary_instructor_user_id . " AND links.course_id = " . $course_id;
            
            // include this uer's specific category settings
            $wheres[] = "links.type = 'category' AND links.user_id = " . $primary_instructor_user_id . " AND links.category_id = " . $category_id;
        }

        // flatten the where clause array
        $whereClause = array_reduce($wheres, function ($carry, $item) {
            $carry .= '(' . $item . ') OR ';
            return $carry;
        });
        
        // include all 'coursematch' prefs
        $whereClause .= "(links.type = 'coursematch')";

        return $whereClause;
    }

    /**
     * Returns a start time for use in filtering courses
     * 
     * @return int
     */
    private static function get_course_start_time()
    {
        $offset = get_config('enrol_ues', 'sub_days') * 86400;

        return time() + $offset;
    }

    /**
     * Returns an end time for use in filtering courses
     * 
     * @return int
     */
    private static function get_course_end_time()
    {
        return time();
    }

    /**
     * Returns a "department number" string given a moodle course id
     * 
     * @param  int $course_id
     * @return string
     */
    private static function get_coursematch_dept_from_name($course_id)
    {
        global $DB;
        $result = $DB->get_record_sql('SELECT DISTINCT cou.department AS dept FROM {enrol_ues_sections} sec
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND c.id = ?', array($course_id));
        return $result->dept;
    }
    
    /**
     * Returns a "department number" string given a moodle course id
     * 
     * @param  int $course_id
     * @return string
     */
    private static function get_coursematch_number_from_name($course_id)
    {
        global $DB;
        $result = $DB->get_record_sql('SELECT DISTINCT cou.cou_number AS number FROM {enrol_ues_sections} sec
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND c.id = ?', array($course_id));
        return $result->number;
    }

}

/**
 * Helper function: Filter the array using the given Closure.
 *
 * @param  array     $array
 * @param  \Closure  $callback
 * @return array
 */
function array_where($array, Closure $callback)
{
    $filtered = [];
    
    foreach ($array as $key => $value) {
        if (call_user_func($callback, $key, $value)) $filtered[$key] = $value;
    }
    
    return $filtered;
}
