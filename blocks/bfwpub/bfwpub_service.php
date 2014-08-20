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
/* $Id: bfwpub_service.php 1890 2012-08-25 15:09:12Z aaronz $ */

require_once (dirname(__FILE__).'/../../config.php');
global $CFG,$USER,$COURSE;
// link in external libraries
require_once ($CFG->libdir.'/gradelib.php');
require_once ($CFG->libdir.'/dmllib.php');
require_once ($CFG->libdir.'/accesslib.php');
//require_once ($CFG->libdir.'/soap/nusoap.php');

/**
 * For XML error handling
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return bool
 * @throws DOMException
 */
function HandleBfwXmlError($errno, $errstr, $errfile, $errline) {
    if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0)) {
        throw new DOMException($errstr);
    } else {
        return false;
    }
}


/**
 * This marks an exception as being related to an authn or authz failure
 */
class SecurityBfwException extends Exception {
}

/**
 * This holds all the service logic for the bfwpub integrate plugin
 */
class bfwpub_service {

    const VERSION = '20120825'; // Should match version.php

    // CONSTANTS
    const BLOCK_NAME = 'block_bfwpub';
    const BLOCK_PATH = '/blocks/bfwpub';
    const GRADE_CATEGORY_NAME = 'bfwpub scores'; // default category
    const GRADE_ITEM_TYPE = 'blocks';
    const GRADE_ITEM_MODULE = 'bfwpub';
    const GRADE_LOCATION_STR = 'manual';

    // data types
    const TYPE_HTML = 'html';
    const TYPE_XML = 'xml';
    const TYPE_TEXT = 'txt';
    const TYPE_JSON = 'json';

    const DEFAULT_SERVER_URL = 'http://localhost';
    const SCORE_KEY = '${SCORE}';

    // CLASS VARIABLES

    // CONFIG
    public static $server_URL = self::DEFAULT_SERVER_URL;
    public static $domain_URL = self::DEFAULT_SERVER_URL;
    public static $shared_key = NULL;
    public static $test_mode = FALSE;

    // STATIC METHODS

    /**
     * @static
     * @param string $added the path to add on the end
     * @return string the path for this block
     */
    public static function block_path($added = NULL) {
        global $CFG;
        if (isset($added)) {
            $added = '/'.$added;
        } else {
            $added = '';
        }
        return $CFG->dirroot.self::BLOCK_PATH.$added;
    }

    /**
     * @static
     * @param string $added the path to add on the end
     * @return string the url for this block
     */
    public static function block_url($added = NULL) {
        global $CFG;
        if (isset($added)) {
            $added = '/'.$added;
        } else {
            $added = '';
        }
        return $CFG->wwwroot.self::BLOCK_PATH.$added;
    }

    /**
     * i18n message handling
     *
     * @param string $key i18 msg key
     * @param object $vars [optional] optional replacement variables
     * @return string the translated string
     */
    public static function msg($key, $vars = NULL) {
        return get_string_manager()->get_string($key, self::BLOCK_NAME, $vars); // get_string('alphabet', $component, $a, $lang);
    }

    public static function df($time) {
        return strftime('%Y/%m/%d', $time); //userdate($time, '%Y/%m/%d');
    }

    /**
     * Sends an email to an email address
     *
     * @param string $to email address to send email to
     * @param string $subject email subject
     * @param string $body email body
     * @return bool true if email sent, false otherwise
     */
    public static function send_email($to, $subject, $body) {
        // $user should be a fake user object with the email set to the correct value, $from should be a string
        $user = new stdClass();
        $user->email = $to;
        $user->firstname = 'ADMIN';
        $user->lastname = $to;
        $user->mailformat = 0; // plain
        $user->confirmed = 1;
        $user->deleted = 0;
        $user->emailstop = 0;
        $user->id = 1;
        if (email_to_user($user, "SYSTEM", $subject, $body) !== true) {
            error_log("Could not send email ($to) : $subject \n $body");
        }
    }

    /**
     * Sends a notification to the configured email addresses in the system about a failure
     *
     * @param string $message the message to send
     * @param object $exception [optional] the optional exception to notify the admins about
     * @return bool true if email sent, false otherwise
     */
    public static function send_notifications($message, $exception=NULL) {
        global $CFG;
        // load these on demand only - block_bfwpub_notify_emails
        $admin_emails = NULL;
        if (!empty($CFG->block_bfwpub_notify_emails)) {
            $email_string = $CFG->block_bfwpub_notify_emails;
            $admin_emails = explode(',', $email_string);
            array_walk($admin_emails, 'trim');
        }

        // add to failures record and trim it to 5
        $failures = self::get_failures();
        $msg = $message;
        if ($exception != null) {
            $msg .= " Failure: ".$exception->message." ".$exception;
        }
        array_unshift($failures, date('Y-m-d h:i:s').' :: '.substr($msg, 0, 300));
        while (count($failures) > 5) {
            array_pop($failures);
        }
        set_config('block_bfwpub_failures', implode('*****', $failures), self::BLOCK_NAME);

        if ($admin_emails) {
            $body = 'BFW LMS Moodle integrate plugin notification ('.date('d.m.Y h:i:s').')'.PHP_EOL.$message.PHP_EOL;
            if ($exception != null) {
                $body .= PHP_EOL.'Failure:'.PHP_EOL.$exception->message.PHP_EOL.PHP_EOL.$exception;
            }
            foreach ($admin_emails as $email) {
                self::send_email($email, 'i>clicker Moodle integrate plugin notification', $body);
            }
            $sent = true;
        } else {
            error_log('No emails set for sending notifications: logging notification: '.$message);
            $sent = false;
        }
        return $sent;
    }

    public static function get_failures() {
        $failures = array();
        $failure_string = get_config(self::BLOCK_NAME, 'block_bfwpub_failures');
        if (! empty($failure_string)) {
            $failures = explode('*****', $failure_string);
        }
        return $failures;
    }

    // USERS

    const USER_FIELDS = 'id,username,firstname,lastname,email';

    /**
     * Verify that a shared key is valid
     * @static
     * @param string $key the input key to check
     * @return bool true if the key is verified, false otherwise
     * @throws SecurityBfwException
     */
    public static function authenticate_shared_key($key) {
        $verified = false;
        if (isset($key)
            && isset(self::$shared_key)
            && !empty(self::$shared_key)
            && self::$shared_key === $key) {
            $u = get_complete_user_data('id', 1); // should be the default super admin
            if ($u === false) {
                throw new SecurityBfwException('Could not map to user with id = 0');
            }
            complete_user_login($u);
            $verified = true;
        }
        return $verified;
    }

    /**
     * Authenticate a user by username and password
     * @param string $username
     * @param string $password
     * @return bool true if the authentication is successful
     * @throws SecurityBfwException if auth invalid
     */
    public static function authenticate_user($username, $password) {
        global $USER;
        if (!isset($USER->id) || !$USER->id) {
            $u = authenticate_user_login($username, $password);
            if ($u === false) {
                $msg = 'Could not authenticate username ('.$username.')';
                error_log($msg);
                throw new SecurityBfwException($msg);
            }
            complete_user_login($u);
        }
        return true;
    }

    /**
     * Ensure user is logged in and return the current user id
     * @return string the current user id
     * @static
     * @throws SecurityBfwException
     */
    public static function require_user() {
        global $USER;
        if (!isset($USER->id) || !$USER->id) {
            $msg = 'User must be logged in';
            error_log($msg);
            throw new SecurityBfwException($msg);
        }
        return $USER->id;
    }

    /**
     * Gets the current user_id, return FALSE if none can be found
     * @return boolean the current user id OR null/false if no user
     */
    public static function get_current_user_id() {
        try {
            $current_user = self::require_user();
        } catch (SecurityBfwException $e) {
            $current_user = false;
        }
        return $current_user;
    }

    /**
     * Gets a user by their username
     * @param string $username the username (i.e. login name)
     * @return stdClass|bool the user object OR false if none can be found
     */
    public static function get_user_by_username($username) {
        global $DB;
        $user = false;
        if ($username) {
            $user = $DB->get_record('user', array('username' => $username), self::USER_FIELDS);
            // TESTING handling
            if (self::$test_mode && !$user) {
                // test users
                if ($username == 'student01') {
                    $user = new stdClass();
                    $user->id = 101;
                    $user->username = 'student01';
                    $user->firstname = 'Student';
                    $user->lastname = 'One';
                    $user->email = 'one@fail.com';
                } else if ($username == 'student02') {
                    $user = new stdClass();
                    $user->id = 102;
                    $user->username = 'student02';
                    $user->firstname = 'Student';
                    $user->lastname = 'Two';
                    $user->email = 'two@fail.com';
                } else if ($username == 'student03') {
                    $user = new stdClass();
                    $user->id = 103;
                    $user->username = 'student03';
                    $user->firstname = 'Student';
                    $user->lastname = 'Three';
                    $user->email = 'three@fail.com';
                } else if ($username == 'inst01') {
                    $user = new stdClass();
                    $user->id = 111;
                    $user->username = 'inst01';
                    $user->firstname = 'Instructor';
                    $user->lastname = 'One';
                    $user->email = 'uno_inst@fail.com';
                }
            }
        }
        return $user;
    }

    /**
     * Get user records for a set of user ids
     * @param object $user_ids an array of user ids OR a single user_id
     * @return stdClass|array a map of user_id -> user data OR single user object for single user_id OR empty array if no matches
     */
    public static function get_users($user_ids) {
        global $DB;
        $results = array(
        );
        if (isset($user_ids)) {
            if (is_array($user_ids)) {
                $users = false;
                if (! empty($user_ids)) {
                    //$ids = implode(',', $user_ids);
                    $users = $DB->get_records_list('user', 'id', $user_ids, 'id', self::USER_FIELDS);
                }
                if (!empty($users)) {
                    foreach ($users as $user) {
                        self::makeUserDisplayName($user);
                        $results[$user->id] = $user;
                    }
                }
            } else {
                // single user id
                $user = $DB->get_record('user', array('id' => $user_ids), self::USER_FIELDS);
                // TESTING handling
                if (self::$test_mode && !$user) {
                    if ($user_ids == 101) {
                        $user = new stdClass();
                        $user->id = 101;
                        $user->username = 'student01';
                        $user->firstname = 'Student';
                        $user->lastname = 'One';
                        $user->email = 'one@fail.com';
                    } else if ($user_ids == 102) {
                        $user = new stdClass();
                        $user->id = 102;
                        $user->username = 'student02';
                        $user->firstname = 'Student';
                        $user->lastname = 'Two';
                        $user->email = 'two@fail.com';
                    } else if ($user_ids == 103) {
                        $user = new stdClass();
                        $user->id = 103;
                        $user->username = 'student03';
                        $user->firstname = 'Student';
                        $user->lastname = 'Three';
                        $user->email = 'three@fail.com';
                    } else if ($user_ids == 111) {
                        $user = new stdClass();
                        $user->id = 111;
                        $user->username = 'inst01';
                        $user->firstname = 'Instructor';
                        $user->lastname = 'One';
                        $user->email = 'uno_inst@fail.com';
                    }
                }
                if ($user) {
                    self::makeUserDisplayName($user);
                    $results = $user;
                }
            }
        }
        return $results;
    }

    /**
     * Get a display name for a given user id
     * @param int $user_id id for a user
     * @return string the display name
     */
    public static function get_user_displayname($user_id) {
        $name = "UNKNOWN-".$user_id;
        $users = self::get_users($user_id);
        if ($users && array_key_exists($user_id, $users)) {
            $name = self::makeUserDisplayName($users[$user_id]);
        }
        return $name;
    }

    /**
     * @static
     * @param stdClass $user user object
     * @return string user display name
     */
    private static function makeUserDisplayName(&$user) {
        $display_name = fullname($user);
        $user->name = $display_name;
        $user->display_name = $display_name;
        return $display_name;
    }

    /**
     * @param int $user_id [optional] the user id
     * @return bool true if this user is an admin OR false if not
     * @static
     */
    public static function is_admin($user_id = NULL) {
        if (!isset($user_id)) {
            try {
                $user_id = self::require_user();
            }
            catch (SecurityBfwException $e) {
                return false;
            }
        }
        $result = is_siteadmin($user_id);
        return $result;
    }

    /**
     * Check if a user is an instructor in moodle
     *
     * @param int $user_id [optional] the user id to check (default to current user)
     * @return bool true if an instructor or false otherwise
     * @static
     */
    public static function is_instructor($user_id = NULL) {
        global $USER;
        if (!isset($user_id)) {
            try {
                $user_id = self::require_user();
            }
            catch (SecurityBfwException $e) {
                return false;
            }
        }
        // sadly this is the only way to do this check: http://moodle.org/mod/forum/discuss.php?d=140383
        if ($user_id === $USER->id && isset($USER->access)) {
            $accessInfo = $USER->access;
        } else {
            $accessInfo = get_user_access_sitewide($user_id);
        }
        $results = get_user_courses_bycap($user_id, 'moodle/course:update', $accessInfo, false,
            'c.sortorder', array(), 1);
        $result = count($results) > 0;
        return $result;
    }


    // COURSES METHODS

    /**
     * Get all the students for a course with their clicker registrations
     * @param int $course_id the course to get the students for
     * @return array the list of user objects for the students in the course
     */
    public static function get_students_for_course($course_id) {
        // get_users_by_capability - accesslib - moodle/grade:view
        // search_users - datalib
        $context = context_course::instance($course_id);
        $results = get_users_by_capability($context, 'moodle/grade:view', 'u.id, u.username, u.firstname, u.lastname, u.email', 'u.lastname', '', '', '', '', FALSE);
        if (isset($results) && !empty($results)) {
            // get the registrations related to these students
            foreach ($results as $user) {
                // setup display name
                self::makeUserDisplayName($user);
            }
        } else {
            // NO matches
            $results = array();
        }
        return $results;
    }

    /**
     * Get the listing of all courses for an instructor
     * @param int $user_id [optional] the unique user id for an instructor (default to current user)
     * @return array the list of courses (maybe be empty array)
     */
    public static function get_courses_for_instructor($user_id = NULL) {
        global $USER;
        // make this only get courses for this instructor
        // get_user_courses_bycap? - accesslib
        // http://docs.moodle.org/en/Category:Capabilities - moodle/course:update
        if (! isset($user_id)) {
            $user_id = self::get_current_user_id();
        }
        if ($user_id === $USER->id && isset($USER->access)) {
            $accessinfo = $USER->access;
        } else {
            $accessinfo = get_user_access_sitewide($user_id);
        }
        $results = get_user_courses_bycap($user_id, 'moodle/course:update', $accessinfo, false,
            'c.sortorder', array('fullname','summary','timecreated','visible'), 50);
        if (!$results) {
            $results = array();
        }
        return $results;
    }

    /**
     * Retrieve a single course by unique id
     * @param int $course_id the course
     * @return stdClass|bool the course object or FALSE
     */
    public static function get_course($course_id) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $course_id));
        // TESTING handling
        if (self::$test_mode && !$course) {
            if ($course_id == '11111111') {
                $course = new stdClass();
                $course->id = $course_id;
                $course->fullname = 'testing: '.$course_id;
            }
        }
        if (!$course) {
            $course = false;
        }
        return $course;
    }

/* Not needed right now
    public static function get_course_grade_item($course_id, $grade_item_name) {
        if (! $course_id) {
            throw new InvalidArgumentException("course_id must be set");
        }
        if (! $grade_item_name) {
            throw new InvalidArgumentException("grade_item_name must be set");
        }
        $grade_item_fetched = false;
        $bfwpub_category = grade_category::fetch(array(
            'courseid' => $course_id,
            'fullname' => self::GRADE_CATEGORY_NAME
            )
        );
        if ($bfwpub_category) {
            $grade_item_fetched = grade_item::fetch(array(
                'courseid' => $course_id,
                'categoryid' => $bfwpub_category->id,
                'itemname' => $grade_item_name
                )
            );
            if (! $grade_item_fetched) {
                $grade_item_fetched = false;
            }
        }
        return $grade_item_fetched;
    }
*/

    private static function save_grade_item($grade_item) {
        if (! $grade_item) {
            throw new InvalidArgumentException("grade_item must be set");
        }
        if (! $grade_item->courseid) {
            throw new InvalidArgumentException("grade_item->courseid must be set");
        }
        if (! $grade_item->categoryid) {
            throw new InvalidArgumentException("grade_item->categoryid must be set");
        }
        if (! isset($grade_item->name) && ! isset($grade_item->id)) {
            throw new InvalidArgumentException("grade_item->name OR grade_item->id must be set");
        }
        if (! isset($grade_item->item_number)) {
            $grade_item->item_number = 0;
        }

        // check for an existing item and update or create
        $grade_search = array(
            'courseid' => $grade_item->courseid,
            'itemmodule' => self::GRADE_ITEM_MODULE,
            //'categoryid' => $grade_item->categoryid,
            //'itemname' => $grade_item->name
        );
        // search by name or id if id is set
        if (isset($grade_item->id)) {
            $grade_search['idnumber'] = $grade_item->id;
        } else {
            $grade_search['itemname'] = $grade_item->name;
        }
        $grade_item_to_save = grade_item::fetch($grade_search);
        if (! $grade_item_to_save) {
            // create new one
            $grade_item_to_save = new grade_item();
            $grade_item_to_save->itemmodule = self::GRADE_ITEM_MODULE;
            $grade_item_to_save->courseid = $grade_item->courseid;
            $grade_item_to_save->categoryid = $grade_item->categoryid;
            $grade_item_to_save->iteminfo = $grade_item->typename;
            //$grade_item_tosave->iteminfo = $grade_item->name.' '.$grade_item->type.' '.self::GRADE_CATEGORY_NAME;
            $grade_item_to_save->itemnumber = $grade_item->item_number;
            if (isset($grade_item->id)) {
                // save the id if it is set
                $grade_item_to_save->idnumber = $grade_item->id;
            }
            // TODO lock the item?
            $grade_item_to_save->itemname = $grade_item->name;
            $grade_item_to_save->itemtype = self::GRADE_ITEM_TYPE;
            //$grade_item_to_save->itemmodule = self::GRADE_ITEM_MODULE;
            if (isset($grade_item->points_possible) && $grade_item->points_possible > 0) {
                $grade_item_to_save->grademax = $grade_item->points_possible;
            }
            $grade_item_to_save->insert(self::GRADE_LOCATION_STR);
        } else {
            // update
            if (isset($grade_item->points_possible) && $grade_item->points_possible > 0) {
                $grade_item_to_save->grademax = $grade_item->points_possible;
            }
            if (isset($grade_item->name) && $grade_item->name != $grade_item_to_save->itemname) {
                $grade_item_to_save->itemname = $grade_item->name;
            }
            $grade_item_to_save->categoryid = $grade_item->categoryid;
            $grade_item_to_save->iteminfo = $grade_item->typename;
            $grade_item_to_save->update(self::GRADE_LOCATION_STR);
        }
        $grade_item_id = $grade_item_to_save->id;
        $grade_item_pp = $grade_item_to_save->grademax;

        // now save the related scores
        if (isset($grade_item->scores) && !empty($grade_item->scores)) {
            // get the existing scores
            $current_scores = array();
            $existing_grades = grade_grade::fetch_all(array(
                'itemid' => $grade_item_id
                )
            );
            if ($existing_grades) {
                foreach ($existing_grades as $grade) {
                    $current_scores[$grade->userid] = $grade;
                }
            }

            // run through the scores in the gradeitem and try to save them
            $errors_count = 0;
            $processed_scores = array();
            foreach ($grade_item->scores as $score) {
                // handle username or user id
                $user = self::get_user_by_username($score->user_id);
                if (! $user) {
                    $user = self::get_users($score->user_id);
                }
                if (! $user) {
                    $score->error = 'USER_NOT_FOUND: user record ('.$score->user_id.') could not be found by id or username';
                    $processed_scores[] = $score;
                    $errors_count++;
                    continue;
                }
                $user_id = $user->id;
                // null/blank scores are not allowed
                if (! isset($score->score)) {
                    $score->error = 'NO_SCORE_ERROR: score is not set';
                    $processed_scores[] = $score;
                    $errors_count++;
                    continue;
                }
                if (! is_numeric($score->score)) {
                    $score->error = 'SCORE_INVALID: '.$score->score.' is not a number';
                    $processed_scores[] = $score;
                    $errors_count++;
                    continue;
                }
                $score->score = floatval($score->score);
                /*
                // Student Score should not be greater than the total points possible
                if ($score->score > $grade_item_pp) {
                    $score->error = self::POINTS_POSSIBLE_UPDATE_ERRORS;
                    $processed_scores[] = $score;
                    $errors_count++;
                    continue;
                }
                */
                try {
                    if (isset($current_scores[$user_id])) {
                        // existing score
                        $grade_to_save = $current_scores[$user_id];
                        /*
                        // check against existing score - Student Score should be greater than the previous score
                        if ($score->score < $grade_to_save->rawgrade) {
                            $score->error = 'SCORE_UPDATE: score less than than current';
                            $processed_scores[] = $score;
                            $errors_count++;
                            continue;
                        }
                        */
                        $grade_to_save->finalgrade = $score->score;
                        $grade_to_save->rawgrade = $score->score;
                        $grade_to_save->timemodified = time();
                        $grade_to_save->update(self::GRADE_LOCATION_STR);
                    } else {
                        // new score
                        $grade_to_save = new grade_grade();
                        $grade_to_save->itemid = $grade_item_id;
                        $grade_to_save->userid = $user_id;
                        $grade_to_save->finalgrade = $score->score;
                        $grade_to_save->rawgrade = $score->score;
                        $grade_to_save->rawgrademax = $grade_item_pp;
                        $now = time();
                        $grade_to_save->timecreated = $now;
                        $grade_to_save->timemodified = $now;
                        $grade_to_save->insert(self::GRADE_LOCATION_STR);
                    }
                    $grade_to_save->user_id = $score->user_id;
                    $processed_scores[] = $grade_to_save;
                } catch (Exception $e) {
                    // General errors, caused while performing updates (Tag: generalerrors)
                    $score->error = 'GENERAL EXCEPTION: '.$e->getMessage().' - line '.$e->getLine().' in file '.$e->getFile();
                    $processed_scores[] = $score;
                    $errors_count++;
                }
            }
            $grade_item_to_save->scores = $processed_scores;
            // put the errors in the item
            if ($errors_count > 0) {
                $errors = array();
                foreach ($processed_scores as $score) {
                    if (isset($score->error)) {
                        $errors[$score->user_id] = $score->error;
                    }
                }
                $grade_item_to_save->errors = $errors;
            }
            $grade_item_to_save->force_regrading();
        }
        return $grade_item_to_save;
    }

    /**
     * Saves a gradebook (a set of grade items and scores related to a course),
     * also creates the categories based on the item type
     *
     * @static
     * @param object $gradebook an object with at least course_id and items set
     *      items should contain grade_items (courseid. categoryid, name, scores)
     *      scores should contain grade_grade (user_id, score)
     * @return stdClass the saved gradebook with all items and scores in the same structure,
     *      errors are recorded as grade_item->errors and score->error
     * @throws InvalidArgumentException
     */
    public static function save_gradebook($gradebook) {
        if (! $gradebook) {
            throw new InvalidArgumentException("gradebook must be set");
        }
        if (! isset($gradebook->course_id)) {
            throw new InvalidArgumentException("gradebook->course_id must be set");
        }
        if (! isset($gradebook->items) || empty($gradebook->items)) {
            throw new InvalidArgumentException("gradebook->items must be set and include items");
        }
        $gb_saved = new stdClass();
        $gb_saved->items = array();
        $gb_saved->course_id = $gradebook->course_id;
        $course = self::get_course($gradebook->course_id);
        if (! $course) {
            throw new InvalidArgumentException("No course found with course_id ($gradebook->course_id)");
        }
        $gb_saved->course = $course;

        // attempt to get the default bfwpub category first
        $default_bfwpub_category = grade_category::fetch(array(
            'courseid' => $gradebook->course_id,
            'fullname' => self::GRADE_CATEGORY_NAME
            )
        );
        $default_bfwpub_category_id = $default_bfwpub_category ? $default_bfwpub_category->id : NULL;
        //echo "\n\nGRADEBOOK: ".var_export($gradebook);
        // iterate through and save grade items by calling other method
        if (! empty($gradebook->items)) {
            $saved_items = array();
            $number = 0;
            foreach ($gradebook->items as $grade_item) {
                // check for this category
                $item_category_id = $default_bfwpub_category_id;
                $item_category_name = self::GRADE_CATEGORY_NAME;
                if (! empty($grade_item->type) && self::GRADE_CATEGORY_NAME != $grade_item->type) {
                    $item_category_name = $grade_item->type;
                    $item_category = grade_category::fetch(array(
                        'courseid' => $gradebook->course_id,
                        'fullname' => $item_category_name
                        )
                    );
                    if (! $item_category) {
                        // create the category
                        $params = array(
                            'courseid' => $gradebook->course_id,
                            'fullname' => $item_category_name,
                        );
                        // TODO lock the category?
                        $grade_category = new grade_category($params, false);
                        $grade_category->insert(self::GRADE_LOCATION_STR);
                        $item_category_id = $grade_category->id;
                    } else {
                        $item_category_id = $item_category->id;
                    }
                } else {
                    // use default
                    if (! $default_bfwpub_category_id) {
                        // create the category
                        $params = array(
                            'courseid' => $gradebook->course_id,
                            'fullname' => self::GRADE_CATEGORY_NAME,
                        );
                        $grade_category = new grade_category($params, false);
                        $grade_category->insert(self::GRADE_LOCATION_STR);
                        $default_bfwpub_category_id = $grade_category->id;
                    }
                    $item_category_id = $default_bfwpub_category_id;
                }
                $grade_item->categoryid = $item_category_id;
                $grade_item->typename = $item_category_name;
                $grade_item->courseid = $gradebook->course_id;
                $grade_item->item_number = $number;
                $saved_grade_item = self::save_grade_item($grade_item);
                $saved_items[] = $saved_grade_item;
                $number++;
            }
            $gb_saved->items = $saved_items;
        }
        $gb_saved->default_category_id = $default_bfwpub_category_id;
        //echo "\n\nRESULT: ".var_export($gb_saved);
        return $gb_saved;
    }

    // DATA ENCODING METHODS


    /**
     * Encode a set of courses which a user is an instructor for into JSON or XML
     *
     * @static
     * @throws Exception|InvalidArgumentException
     * @param int $instructor_id the unique user id
     * @param string $dataType json or xml
     * @return string the encoded data as JSON or XML
     */
    public static function encode_courses($instructor_id, $dataType=self::TYPE_JSON) {
        if (! isset($instructor_id)) {
            throw new InvalidArgumentException("instructor_id must be set");
        }
        $instructor = self::get_users($instructor_id);
        if (! $instructor) {
            throw new InvalidArgumentException("Invalid instructor user id ($instructor_id)");
        }
        $courses = self::get_courses_for_instructor($instructor_id);
        return json_encode($courses);
    }

    /**
     * @static
     * @param int $user_id the current user id
     * @param string $dataType xml or json
     * @return string encoded ping value OR null if not configured
     */
    public static function encode_ping($user_id, $dataType=self::TYPE_JSON) {
        $configured = false;
        if (isset(self::$shared_key)) {
            $configured = true;
        }
        $encoded = NULL;
        if ($configured) {
            $username = '';
            $user = self::get_users($user_id);
            if ($user) {
                $username = $user->username;
            }
            if (self::TYPE_XML == $dataType) {
                $encoded = "<result><user>$username</user><configured>true</configured><version>".self::VERSION."</version></result>";
            } else {
                // JSON
                $encoded = json_encode(
                    array(
                        'configured' => true,
                        'user' => $username,
                        'version' => self::VERSION,
                    )
                );
            }
        }
        return $encoded;
    }

    /**
     * Encode a set of enrollments for a course into JSON
     *
     * @param int $course_id unique id for a course
     * @return string the JSON
     * @throws InvalidArgumentException if the id is invalid
     */
    public static function encode_enrollments($course_id) {
        if (! isset($course_id)) {
            throw new InvalidArgumentException("course_id must be set");
        }
        $course = self::get_course($course_id);
        if (! $course) {
            throw new InvalidArgumentException("No course found with course_id ($course_id)");
        }
        $students = self::get_students_for_course($course_id);
        $studentsArray = array();
        // the students may be an empty set
        if ($students) {
            // loop through students
            foreach ($students as $student) {
                $studentsArray[] = array(
                    'id' => $student->id,
                    'username' => $student->username,
                    'email' => $student->email ? $student->email : '',
                    'type' => 'student',
                    'displayName' => ($student->firstname ? $student->firstname : '').' '.($student->lastname ? $student->lastname : ''),
                    'firstName' => $student->firstname ? $student->firstname : '',
                    'lastName' => $student->lastname ? $student->lastname : '',
                );
            }
        }
        $data = array(
            'courseId' => $course->id,
            'courseName' => $course->fullname,
            'students' => $studentsArray,
        );
        $encoded = json_encode($data);
        return $encoded;
    }

    /**
     * Encodes the results of a gradebook save into JSON
     *
     * @static
     * @param object $gradebook_result the result from gradebook_save
     * @return string the JSON
     * @throws InvalidArgumentException
     */
    public static function encode_gradebook_results($gradebook_result) {
        if (! isset($gradebook_result->course)) {
            throw new InvalidArgumentException("course must be set");
        }
        $grades_count = 0;
        $grades_failed = 0;
        //$course = $gradebook_result->course;
        $course_id = $gradebook_result->course->id;
        // check for any errors
        $errors = array();
        foreach ($gradebook_result->items as $item) {
            $grades_count += count($item->scores);
            if (isset($item->errors) && !empty($item->errors)) {
                $errors[$item->id] = $item->errors;
                $grades_failed += count($item->errors);
            }
        }
        $results = array(
            'course' => $course_id,
            'success' => true,
            'errors' => '',
            'items' => count($gradebook_result->items),
            'grades_count' => $grades_count,
            'grades_failed' => $grades_failed,
        );
        if (!empty($errors)) {
            $results['errors'] = $errors;
            $results['success'] = false;
        }
        $output = json_encode($results);
        //$output = json_encode($gradebook_result); // debugging
        return $output;
    }

    /**
     * Decodes JSON or XML into a gradebook object
     *
     * @static
     * @param string $encoded the json or xml data
     * @param string $dataType 'json' or 'xml'
     * @return stdClass the gradebook object
     * @throws InvalidArgumentException if the data cannot be parsed or the data is invalid
     */
    public static function decode_gradebook($encoded, $dataType=self::TYPE_JSON) {
        $gradebook = new stdClass();
        $gradebook->students = array();
        $gradebook->items = array();
        // assume JSON otherwise
        /* json sample:
{
    "courseId": "id-string",
    "items": [
        {
            "itemId": "id-string",
            "title": "string",
            "description": "string",
            "points_possible": "number",
            "category": "string",
            "hidden": "boolean",
            "order": "number",
            "grades": [
                {
                    "userId": "id-string",
                    "score": "number",
                    "percent": "number"
                }
            ]
        }
    ]
}
         */
        $data = json_decode($encoded, true);
        $course_id = isset($data['courseId']) ? $data['courseId'] : null;
        if (! $course_id) {
            throw new InvalidArgumentException("Invalid JSON, no courseId at the root");
        }
        $items = isset($data['items']) ? $data['items'] : null;
        if (!isset($items) || sizeof($items) <= 0) {
            throw new InvalidArgumentException("Invalid JSON, no items found at root");
        }
        $gradebook->course_id = $course_id;
        foreach ($items as $item) {
            if (!isset($item['itemId'])) {
                throw new InvalidArgumentException("Invalid JSON, missing itemId in grade item");
            }
            if (!isset($item['grades'])) {
                throw new InvalidArgumentException("Invalid JSON, missing grades in grade item");
            }
            if (!isset($item['title'])) {
                $item['title'] = $item['itemId'];
            }
            if (isset($item['points_possible'])) {
                $item['points_possible'] = floatval($item['points_possible']);
            }
            if (!isset($item['points_possible']) || !$item['points_possible']) {
                $item['points_possible'] = 100.0;
            }
            if (!isset($item['category'])) {
                $item['category'] = 'BFW'; // default if not set
            }
            $grade_item = new stdClass();
            $grade_item->id = $item['itemId'];
            $grade_item->name = $item['title'];
            $grade_item->points_possible = $item['points_possible'];
            $grade_item->type = $item['category']; // defines the category name
            // TODO handle hidden and order
            $grade_item->scores = array();
            $gradebook->items[$item['itemId']] = $grade_item;
            if (is_array($item['grades']) && !empty($item['grades'])) {
                foreach ($item['grades'] as $grade) {
                    if (!isset($grade['userId'])) {
                        error_log("WARN: Gradebook import failure for course ($course_id), Invalid JSON for score, no user id in the user element (skipping this entry)");
                        continue;
                    }
                    $score = new stdClass();
                    $score->item_name = $grade_item->name;
                    $score->user_id = $grade['userId'];
                    $score->score = $grade['score'];
                    $grade_item->scores[] = $score;
                }
            }
        }

        return $gradebook;
    }

}

// load the config into the static vars from the global plugin config settings
bfwpub_service::$server_URL = $CFG->wwwroot;
$block_name = bfwpub_service::BLOCK_NAME; // null for global config
$block_bfwpub_shared_key = get_config($block_name, 'block_bfwpub_shared_key');
if ($block_bfwpub_shared_key) {
    bfwpub_service::$shared_key = $block_bfwpub_shared_key;
}
