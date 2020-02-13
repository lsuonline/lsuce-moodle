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

class local_cas_help_links_utility {

    /**
     * Returns an array of this primary instructor user's course settings data
     *
     * @param  int  $userid, old name: user_id
     * @return array
     */
    public static function get_primary_instructor_course_settings($userid) {
        // Old variable name: courseData.
        $coursedata = self::get_primary_instructor_course_data($userid);

        // Old variable name: transformedCourseData.
        $transformedcoursedata = self::transform_course_data($coursedata, $userid);

        return $transformedcoursedata;
    }

    /**
     * Returns an array of this primary instructor user's category settings data
     *
     * @param  int  $userid, old name: user_id
     * @return array
     */
    public static function get_primary_instructor_category_settings($userid) {
        // Old variable name: categoryData.
        $categorydata = self::get_primary_instructor_category_data($userid);

        // Old variable name: transformedCategoryData.
        $transformedcategorydata = self::transform_category_data($categorydata, $userid);

        return $transformedcategorydata;
    }

    /**
     * Returns an array of this primary instructor user's personal settings data
     *
     * @param  int  $userid, old name: user_id
     * @return array
     */
    public static function get_primary_instructor_user_settings($userid) {
        // Old variable name: userLink.
        $userlink = self::get_user_link_data($userid);

        // Old variable name: transformedUserData.
        $transformeduserdata = self::transform_user_data($userlink, $userid);

        return $transformeduserdata;
    }

    /**
     * Returns an array of all category settings data
     *
     * @return array
     */
    public static function get_all_category_settings() {
        // Old variable name: categoryData.
        $categorydata = self::get_category_data();

        // Old variable name: transformedCategoryData.
        $transformedcategorydata = self::transform_category_data($categorydata);

        return $transformedcategorydata;
    }

    /**
     * Returns an array of all existing "coursematch" settings data
     *
     * @return array
     */
    public static function get_all_coursematch_settings() {
        global $DB;

        $results = $DB->get_records('local_cas_help_links', ['type' => 'coursematch']);

        return $results;
    }

    /**
     * Returns an array of the given teacher user's course ids and shortnames
     *
     * @param  int $userid, old name: user_id
     * @param  bool $idsonly, old name: idsOnly
     * @return array
     */
    public static function get_teacher_course_selection_array($userid, $idsonly = false) {
        // Old variable name: courseData.
        $coursedata = self::get_primary_instructor_course_data($userid);

        $output = [];

        foreach ($coursedata as $course_id => $course) {
            $output[$course_id] = $course->shortname;
        }

        return ! $idsonly ? $output : array_keys($output);
    }

    /**
     * Fetches the given primary's current course data
     *
     * @param  int $userid, old name: user_id
     * @return array
     */
    private static function get_primary_instructor_course_data($userid) {
        global $DB;

        // TODO: make cou_number variable.
        $result = $DB->get_records_sql('SELECT DISTINCT u.id
                                        , c.id
                                        , c.fullname
                                        , c.shortname
                                        , c.idnumber
                                        , c.category
                                        , cc.name FROM {enrol_ues_teachers} t
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
            AND u.id = ?', array($userid));

        return $result;
    }

    /**
     * Fetches the given primary's current category data
     *
     * @param  int $userid, old name: user_id
     * @return array
     */
    private static function get_primary_instructor_category_data($userid) {
        global $DB;

        // TODO: make cou_number variable.
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
            AND u.id = ?', array($userid));

        return $result;
    }

    /**
     * Fetches a cas_help_link object as an array for the given user
     *
     * @param  int $user_id, old name: user_id
     * @return array
     */
    private static function get_user_link_data($userid) {
        $result = self::get_links('user', $userid);

        $result = count($result) ? current($result) : [];

        return $result;
    }

    /**
     * Fetches category data
     *
     * @param  bool $forSelectList, old name: forSelectList
     * @return array
     */
    public static function get_category_data($forselectlist = false) {
        global $DB;

        $result = $DB->get_records_sql('SELECT DISTINCT id, name FROM {course_categories}');

        if ( ! $forselectlist) {
            return $result;
        }

        $output = [];

        foreach ($result as $category) {
            if ($category->id == 1) {
                continue;
            }

            $output[$category->name] = $category->name;
        }
        return $output;
    }

    /**
     * Returns an array of the given course data array but including 'cas_help_link' information
     *
     * @param  array $coursedata, old name: courseData
     * TODO: document userid (old name: user_id)
     * @return array
     */
    private static function transform_course_data($coursedata, $userid) {
        $output = [];

        // Old variable name: userCourseLinks.
        $usercourselinks = self::get_user_course_link_data($userid);

        // Old variable name: courseArray.
        foreach ($coursedata as $coursearray) {
            // Old variable name: linkExistsForCourse.
            $linkexistsforcourse = array_key_exists($coursearray->id, $usercourselinks);

            // If a link record exists for the user/course, show/hide depending on 'display',
            // Otherwise, do not hide.
            // Old variable name: hideLink.
            $hidelink = $linkexistsforcourse ? ! $usercourselinks[$coursearray->id]->display : false;

            // Old variable name: linkId.
            $linkid = $linkexistsforcourse ? $usercourselinks[$coursearray->id]->id : '0';

            $output[$coursearray->id] = [
                'user_id' => $userid,
                'course_id' => $coursearray->id,
                'course_fullname' => $coursearray->fullname,
                'course_shortname' => $coursearray->shortname,
                'course_idnumber' => $coursearray->idnumber,
                'course_category_id' => $coursearray->category,
                'course_category_name' => $coursearray->name,
                'link_id' => $linkid,
                'link_display' => $linkexistsforcourse ? $usercourselinks[$coursearray->id]->display : '0',
                'hide_link' => $hidelink ? 1 : 0,
                'link_url' => $linkexistsforcourse ? $usercourselinks[$coursearray->id]->link : '',
                'display_input_name' => \local_cas_help_links_input_handler::encode_input_name('display'
                                                                                                , 'course'
                                                                                                , $linkid
                                                                                                , $coursearray->id
                                                                                            ),
                'link_input_name' => \local_cas_help_links_input_handler::encode_input_name('link'
                                                                                            , 'course'
                                                                                            , $linkid
                                                                                            , $coursearray->id
                                                                                            )
            ];
        }
        return $output;
    }

    /**
     * Returns an array of the given category data array but including 'cas_help_link' information
     *
     * @param  array $categorydata, old name: categoryData
     * TODO: document userid (old name: user_id)
     * @return array
     */
    private static function transform_category_data($categorydata, $userid = 0) {
        $output = [];

        // Old variable name: categoryLinks.
        $categorylinks = $userid ? self::get_user_category_link_data($userid) : self::get_category_link_data();

        // Old variable name: courseArray.
        foreach ($categorydata as $categoryarray) {
            // Old variable name: linkExistsForCategory.
            $linkexistsforcategory = array_key_exists($categoryarray->id, $categorylinks);

            // If a link record exists for the user/category, show/hide depending on 'display',
            // Otherwise, do not hide.
            // Old variable name: hideLink.
            $hidelink = $linkexistsforcategory ? ! $categorylinks[$categoryarray->id]->display : false;

            // Old variable name: linkId.
            $linkid = $linkexistsforcategory ? $categorylinks[$categoryarray->id]->id : '0';

            $output[$categoryarray->id] = [
                'user_id' => $userid,
                'category_id' => $categoryarray->id,
                'category_name' => $categoryarray->name,
                'link_id' => $linkid,
                'link_display' => $linkexistsforcategory ? $categorylinks[$categoryarray->id]->display : '0',
                'hide_link' => $hidelink ? 1 : 0,
                'link_url' => $linkexistsforcategory ? $categorylinks[$categoryarray->id]->link : '',
                'display_input_name' => \local_cas_help_links_input_handler::encode_input_name('display'
                                                                                                , 'category'
                                                                                                , $linkid
                                                                                                , $categoryarray->id
                                                                                            ),
                'link_input_name' => \local_cas_help_links_input_handler::encode_input_name('link'
                                                                                            , 'category'
                                                                                            , $linkid
                                                                                            , $categoryarray->id
                                                                                            )
            ];
        }
        return $output;
    }

    /**
     * Returns an array of the given user data but including 'cas_help_link' information
     *
     * @param  mixed $link
     * TODO: document userid (old name: user_id)
     * @return array
     */
    private static function transform_user_data($link, $userid) {
        // If a link record exists for the user, show/hide depending on 'display',
        // Otherwise, do not hide.

        // Old variable name: hideLink.
        $hidelink = is_object($link) ? ! $link->display : false;

        // Old variable name: linkId.
        $linkid = is_object($link) ? $link->id : '0';

        return [
            'user_id' => $userid,
            'link_id' => is_object($link) ? $link->id : '',
            'link_display' => is_object($link) ? $link->display : '',
            'hide_link' => $hidelink ? 1 : 0,
            'link_url' => is_object($link) ? $link->link : '',
            'display_input_name' => \local_cas_help_links_input_handler::encode_input_name('display', 'user', $linkid, $userid),
            'link_input_name' => \local_cas_help_links_input_handler::encode_input_name('link', 'user', $linkid, $userid)
        ];
    }

    /**
     * Returns an array of this user's course link preferences, if any, keyed by the course_id
     *
     * @param  int $userid, old variable name: user_id.
     * @return array
     */
    private static function get_user_course_link_data($userid) {
        // Pull raw cas_help_links records.
        $output = [];

        // Old variable name: userCourseLinks.
        $usercourselinks = self::get_user_course_links($userid);

        // Re-key array with course_id instead of link record id.
        // Old variable name: linkId.
        // Old variable name: linkData.
        foreach ($usercourselinks as $linkid => $linkdata) {
            $output[$linkdata->course_id] = $linkdata;
        }

        return $output;
    }

    /**
     * Returns an array of this user's category link preferences, if any, keyed by the category_id
     *
     * @param  int $userid, old variable name: user_id.
     * @return array
     */
    private static function get_user_category_link_data($userid) {
        // Pull raw cas_help_links records.
        $output = [];

        // Old variable name: userCategoryLinks.
        $usercategorylinks = self::get_user_category_links($userid);

        // Re-key array with category_id instead of link record id.
        // Old variable name: linkId.
        // Old variable name: linkData.
        foreach ($usercategorylinks as $linkid => $linkdata) {
            $output[$linkdata->category_id] = $linkdata;
        }

        return $output;
    }

    /**
     * Returns an array of this category's link preferences, if any, keyed by the category_id
     *
     * @return array
     */
    private static function get_category_link_data() {
        // Pull raw cas_help_links records.

        $output = [];

        // Old variable name: categoryLinks.
        $categorylinks = self::get_category_links();

        // Re-key array with category_id instead of link record id.
        // Old variable name: linkId.
        // Old variable name: linkData.
        foreach ($categorylinks as $linkid => $linkdata) {
            $output[$linkdata->category_id] = $linkdata;
        }

        return $output;
    }

    /**
     * Fetches an array of cas_help_link objects for the given user's courses
     *
     * @param  int $userid, old variable name: user_id.
     * @return array
     */
    private static function get_user_course_links($userid) {
        return self::get_links('course', $userid);
    }

    /**
     * Fetches an array of cas_help_link objects for the given user's categories
     *
     * @param  int $userid, old variable name: user_id.
     * @return array
     */
    private static function get_user_category_links($userid) {
        return self::get_links('category', $userid);
    }

    /**
     * Fetches an array of cas_help_link objects for all categories
     *
     * @return array
     */
    private static function get_category_links() {
        return self::get_links('category');
    }

    /**
     * Fetches an array of cas_help_link objects of a given type
     *
     * Optionally, scopes to the given user id
     *
     * @param  string $type
     * @param  int $userid, old variable name: user_id.
     * @return object
     */
    private static function get_links($type, $userid = 0) {
        global $DB;

        $params['type'] = $type;
        $params['user_id'] = $userid ?: 0;

        $result = $DB->get_records('local_cas_help_links', $params);

        return $result;
    }

    /**
     * Fetches a cas_help_link object
     *
     * @param  int $linkid, old variable name: link_id.
     * @return object
     */
    public static function get_link($linkid) {
        global $DB;

        $result = $DB->get_record('local_cas_help_links', ['id' => $linkid]);

        return $result;
    }

    /**
     * Returns whether or not this plugin is enabled based off plugin config
     *
     * @return boolean
     */
    public static function isPluginEnabled() {
        return (bool) get_config('local_cas_help_links', 'show_links_global');
    }

    /**
     * Returns a "primary instructor" user id given a course id number
     *
     * @param  string $idnumber
     * @return int
     */
    public static function getPrimaryInstructorId($idnumber) {
        global $DB;

        // TODO: make cou_number variable.
        $result = $DB->get_records_sql('SELECT DISTINCT(t.userid), cts.requesterid FROM {enrol_ues_sections} sec
            INNER JOIN {enrol_ues_semesters} sem ON sem.id = sec.semesterid
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {enrol_ues_teachers} t ON t.sectionid = sec.id
            LEFT JOIN {enrol_cps_team_sections} cts ON cts.sectionid = sec.id
            WHERE t.primary_flag = 1
            AND sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND t.status = "enrolled"
            AND cou.cou_number < "5000"
            AND sem.classes_start < ' . self::get_course_start_time() . '
            AND sem.grades_due > ' . self::get_course_end_time() . '
            AND sec.idnumber = ?', array($idnumber));

        // If no query results, assume there is no primary user id.
        if ( ! count($result)) {
            return 0;
        }

        // Get the key (column name) in which we'll look up the primary from the query results.
        $key = count($result) > 1 ? 'requesterid' : 'userid';

        // Get the first record from the results.
        $first = array_values($result)[0];

        // Get the user id from the results.
        // Old variable name: userId.
        $userid = property_exists($first, $key) ? $first->$key : 0;

        // Be sure to return 0 if still no user id can be determined.
        return ! is_null($userid) ? (int) $userid : 0;
    }

    /**
     * Fetches select data from a UES course record given a moodle course id
     *
     * @param  int $courseid, old variable name: course_id.
     * @return array
     */
    public static function get_ues_course_data($courseid) {
        global $DB;

        // TODO: make cou_number variable.
        $result = $DB->get_record_sql('SELECT DISTINCT uesc.department, uesc.cou_number, c.id FROM {enrol_ues_courses} uesc
            INNER JOIN {enrol_ues_sections} sec ON sec.courseid = uesc.id
            INNER JOIN {enrol_ues_semesters} sem ON sem.id = sec.semesterid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND uesc.cou_number < "5000"
            AND sem.classes_start < ' . self::get_course_start_time() . '
            AND sem.grades_due > ' . self::get_course_end_time() . '
            AND c.id = ?', array($courseid));

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
     * @param  int  $courseid, old variable name: course_id.
     * @param  int  $categoryid, old variable name: category_id.
     * @param  int  $primaryinstructoruserid, old variable name: primary_instructor_user_id.
     * @return mixed array|bool
     */
    public static function getSelectedPref($courseid, $categoryid, $primaryinstructoruserid) {
        // Pull all of the preference data relative to the course, category, user.
        $prefs = self::getRelatedPrefData($courseid, $categoryid, $primaryinstructoruserid);

        // Old variable name: selectedPref.
        $selectedpref = false;

        // Old variable name: coursematch_dept.
        $coursematchdept = self::get_coursematch_dept_from_name($courseid);

        // Old variable name: coursematch_number.
        $coursematchnumber = self::get_coursematch_number_from_name($courseid);

        // First, keep only prefs with this primary associated.
        // Old variable name: primaryUserPrefs.
        if ($primaryuserprefs = array_where($prefs
                                            , function ($key, $pref) use ($primaryinstructoruserid) {
                                                return $pref->user_id == $primaryinstructoruserid;
                                            })) {
            // If so, keep only primary "hide" prefs, if any.
            // Old variable name: primaryUserHidePrefs.
            if ($primaryuserhideprefs = array_where($primaryuserprefs, function ($key, $pref) { return ! $pref->display;
            })) {
                // Get any "hide" pref for this primary user.
                $selectedpref = array_where($primaryuserhideprefs, function ($key, $pref) {
                    return $pref->type == 'user';
                });

                if ( ! $selectedpref) {
                    // Get any "hide" pref for this primary user & category.
                    $selectedpref = array_where($primaryuserhideprefs, function ($key, $pref) use ($categoryid) {
                        return $pref->type == 'category' && $pref->category_id == $categoryid;
                    });
                }

                if ( ! $selectedpref) {
                    // Get any "hide" pref for this primary user & course.
                    $selectedpref = array_where($primaryuserhideprefs, function ($key, $pref) use ($courseid) {
                        return $pref->type == 'course' && $pref->course_id == $courseid;
                    });
                }
                // Otherwise, keep only "show" prefs, if any.
                // Old variable name: primaryUserShowPrefs.
            } else if ($primaryusershowprefs = array_where($primaryuserprefs, function ($key, $pref) { return $pref->display;
            })) {
                // Get any "show" pref for this primary user & course.
                $selectedpref = array_where($primaryusershowprefs, function ($key, $pref) use ($courseid) {
                    return $pref->type == 'course' && $pref->course_id == $courseid;
                });

                // Get any "show" pref for this primary user & category.
                if ( ! $selectedpref) {
                    $selectedpref = array_where($primaryusershowprefs, function ($key, $pref) use ($categoryid) {
                        return $pref->type == 'category' && $pref->category_id == $categoryid;
                    });
                }

                // Get any "show" pref for this primary user.
                if ( ! $selectedpref) {
                    $selectedpref = array_where($primaryusershowprefs, function ($key, $pref) {
                        return $pref->type == 'user';
                    });
                }
            }
            // Otherwise, attempt to find a "coursematch".
        } else if ($selectedpref = array_where($prefs, function ($key, $pref) use ($coursematchdept, $coursematchnumber) {
                return $pref->type == 'coursematch' && $pref->dept == $coursematchdept && $pref->number == $coursematchnumber;
        })) {

            // Otherwise, keep only this category's prefs.
            // Old variable name: categoryPrefs.
        } else if ($categoryprefs = array_where($prefs, function ($key, $pref) use ($categoryid) {
                return $pref->type == 'category' && $pref->category_id == $categoryid && $pref->user_id == 0;
        })) {
            // Get any "hide" pref for this category.
            $selectedpref = array_where($categoryprefs, function ($key, $pref) {
                return ! $pref->display;
            });

            if ( ! $selectedpref) {
                // Get any "show" pref for this category.
                $selectedpref = array_where($categoryprefs, function ($key, $pref) {
                    return $pref->display;
                });
            }
        }
        return $selectedpref;
    }

    /**
     * Retrieves all pref data related to the given parameters
     *
     * @param  int  $, old name: course_id
     * @param  int  $categoryid, old name: category_id
     * @param  int  $primaryinstructoruserid, old name: primary_instructor_user_id
     * @return array
     */
    private static function getRelatedPrefData($courseid, $categoryid, $primaryinstructoruserid = 0) {
        global $DB;
        // Old variable name: whereClaus.
        $whereclause = self::buildPrefsWhereClause($courseid, $categoryid, $primaryinstructoruserid);

        $result = $DB->get_records_sql("SELECT * FROM {local_cas_help_links} links WHERE " . $whereclause);

        return $result;
    }

    /**
     * Returns an appropriate sql where clause string given specific parameters
     *
     * @param  int  $courseid, old name: course_id
     * @param  int  $categoryid, old name: category_id
     * @param  int  $primary_instructor_user_id, old name: primary_instructor_user_id
     * @return string
     */
    private static function buildPrefsWhereClause($courseid, $categoryid, $primaryinstructoruserid = 0) {
        $wheres = [];

        // Include this category in the results.
        $wheres[] = "links.type = 'category' AND links.category_id = " . $categoryid;

        // If a primary user was specified, include their link prefs.
        if ($primaryinstructoruserid) {
            // Include this user's personal settings.
            $wheres[] = "links.type = 'user' AND links.user_id = " . $primaryinstructoruserid;

            // Include this user's specific course settings.
            $wheres[] = "links.type = 'course' AND links.user_id = "
                        . $primaryinstructoruserid
                        . " AND links.course_id = "
                        . $courseid;

            // Include this uer's specific category settings.
            $wheres[] = "links.type = 'category' AND links.user_id = "
                        . $primaryinstructoruserid
                        . " AND links.category_id = "
                        . $categoryid;
        }

        // Flatten the where clause array.
        // Old variable name: whereClaus.
        $whereclause = array_reduce($wheres, function ($carry, $item) {
            $carry .= '(' . $item . ') OR ';
            return $carry;
        });

        // Include all 'coursematch' prefs.
        $whereclause .= "(links.type = 'coursematch')";

        return $whereclause;
    }

    /**
     * Returns a start time for use in filtering courses
     *
     * @return int
     */
    private static function get_course_start_time() {
        $offset = get_config('enrol_ues', 'sub_days') * 86400;

        return time() + $offset;
    }

    /**
     * Returns an end time for use in filtering courses
     *
     * @return int
     */
    private static function get_course_end_time() {
        return time();
    }

    /**
     * Returns a "department number" string given a moodle course id
     *
     * @param  int $courseid, old variable name: course_id
     * @return string
     */
    private static function get_coursematch_dept_from_name($courseid) {
        global $DB;
        $result = $DB->get_record_sql('SELECT DISTINCT cou.department AS dept FROM {enrol_ues_sections} sec
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND c.id = ?', array($courseid));
        return $result->dept;
    }

    /**
     * Returns a "department number" string given a moodle course id
     *
     * @param  int $courseid, old variable name: course_id
     * @return string
     */
    private static function get_coursematch_number_from_name($courseid) {
        global $DB;
        $result = $DB->get_record_sql('SELECT DISTINCT cou.cou_number AS number FROM {enrol_ues_sections} sec
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            INNER JOIN {course} c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND c.id = ?', array($courseid));
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
function array_where($array, Closure $callback) {
    $filtered = [];

    foreach ($array as $key => $value) {
        if (call_user_func($callback, $key, $value)) {
            $filtered[$key] = $value;
        }
    }

    return $filtered;
}