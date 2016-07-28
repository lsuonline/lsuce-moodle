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
/* $Id: rest.php 1890 2012-08-25 15:09:12Z aaronz $ */

// this includes lib/setup.php and the standard set:
//setup.php : setup which creates the globals
//'/textlib.class.php');   // Functions to handle multibyte strings
//'/weblib.php');          // Functions for producing HTML
//'/dmllib.php');          // Functions to handle DB data (DML) - inserting, updating, and retrieving data from the database
//'/datalib.php');         // Legacy lib with a big-mix of functions. - user, course, etc. data lookup functions
//'/accesslib.php');       // Access control functions - context, roles, and permission related functions
//'/deprecatedlib.php');   // Deprecated functions included for backward compatibility
//'/moodlelib.php');       // general-purpose (login, getparams, getconfig, cache, data/time)
//'/eventslib.php');       // Events functions
//'/grouplib.php');        // Groups functions

//ddlib.php : modifying, creating, or deleting database schema
//blocklib.php : functions to use blocks in a typical course page
//formslib.php : classes for creating forms in Moodle, based on PEAR QuickForms

require_once (dirname(__FILE__).'/../../config.php');
global $CFG,$db_user,$COURSE;
require_once ('bfwpub_service.php');
require_once ('controller.php');


// INTERNAL METHODS
/**
 * This will check for a user and return the user_id if one can be found
 * @param string $msg the error message
 * @return int the user_id
 * @throws SecurityBfwException if no user can be found
 */
function bfw_get_and_check_current_user($msg) {
    $user_id = bfwpub_service::get_current_user_id();
    if (! $user_id) {
        throw new SecurityBfwException('Valid security credentials required in order to ' . $msg);
    }
    /*
    if (! bfwpub_service::is_admin($user_id) && ! bfwpub_service::is_instructor($user_id)) {
        throw new SecurityException('Only instructors can ' . $msg);
    }
    */
    return $user_id;
}

/**
 * Attempt to authenticate the current request based on request params and basic auth
 * @param bfwpub_controller $controller the controller instance
 * @throws SecurityBfwException if authentication is impossible given the request values
 */
function bfw_handle_authn($controller) {
    /**
     * NOTE for Moodlerooms:
     * $key, $auth_username, $auth_password are not stored in the DB or anywhere else
     * and are never output onscreen so they are not injection risks.
     */
    $key = optional_param(bfwpub_controller::SHARED_KEY, NULL, PARAM_NOTAGS);
    if (!empty($key)) {
        if (! bfwpub_service::authenticate_shared_key($key)) {
            $msg = "Invalid shared key ($key), unable to process request";
            error_log($msg);
            throw new SecurityBfwException($msg);
        }
    } else {
        // extract the authn params
        $auth_username = optional_param(bfwpub_controller::LOGIN, NULL, PARAM_NOTAGS);
        $auth_password = optional_param(bfwpub_controller::PASSWORD, NULL, PARAM_NOTAGS);
        if (empty($auth_username) && isset($_SERVER['PHP_AUTH_USER'])) {
            // no username found in normal params so try to get basic auth
            $auth_username = $_SERVER['PHP_AUTH_USER'];
            $auth_password = $_SERVER['PHP_AUTH_PW'];
            if (empty($auth_username)) {
                // attempt to get it from the header as a final try
                list($auth_username, $auth_password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
        }
        //$session_id = optional_param(bfwpub_controller::SESSION_ID, NULL, PARAM_NOTAGS);
        if (!empty($auth_username)) {
            bfwpub_service::authenticate_user($auth_username, $auth_password); // throws exception if fails
        //} else if ($session_id) {
        //    $valid = FALSE; // validate the session key
        //    if (! $valid) {
        //        throw new SecurityException("Invalid "+bfwpub_controller::SESSION_ID+" provided, session may have expired, send new login credentials");
        //    }
        }
    }
    $current_user_id = bfwpub_service::get_current_user_id();
    if (isset($current_user_id)) {
        $controller->setHeader(bfwpub_controller::SESSION_ID, sesskey());
        $controller->setHeader('_userId', $current_user_id);
    }
}

/**
 * Extracts the JSON data from the request
 * @param object $controller the controller instance
 * @return string the JSON data OR null if none can be found
 */
function bfw_get_json_data($controller) {
    /**
     * NOTE for Moodlerooms:
     * We need the raw data in order to process the incoming JSON string,
     * stripping parts out will almost surely corrupt the incoming JSON data.
     * Other parts of core moodle json_decode data without any processing at all.
     */
    $json = optional_param(bfwpub_controller::JSON_DATA, NULL, PARAM_RAW_TRIMMED);
    if (empty($json)) {
        $json = $controller->body;
    } else {
        $json = stripslashes($json);
    }
    return $json;
}


// REST HANDLING

//require_login();
//echo "me=".me().", qualified=".qualified_me();
//echo "user: id=".$USER->id.", auth=".$USER->auth.", username=".$USER->username.", lastlogin=".$USER->lastlogin."\n";
//echo "course: id=".$COURSE->id.", title=".$COURSE->fullname."\n";
//echo "CFG: wwwroot=".$CFG->wwwroot.", httpswwwroot=".$CFG->httpswwwroot.", dirroot=".$CFG->dirroot.", libdir=".$CFG->libdir."\n";

// activate the controller
$controller = new bfwpub_controller(true); // with body

// init the vars to success
$valid = true;
$status = 200; // ok
$output = '';

// check to see if this is one of the paths we understand
if (! $controller->path) {
    $valid = false;
    $output = "Unknown path ($controller->path) specified";
    $status = 404; // not found
}
if ($valid
        && 'POST' != $controller->method
        && 'GET' != $controller->method) {
    $valid = false;
    $output = 'Only POST and GET methods are supported';
    $status = 405; // method not allowed
}
if ($valid) {
    // check against the ones we know and process
    $parts = explode('/', $controller->path);
    $pathSeg0 = count($parts) > 0 ? $parts[0] : NULL;
    $pathSeg1 = count($parts) > 1 ? $parts[1] : NULL;
    try {
        if ('GET' == $controller->method) {
            // handle the request authn if needed
            //handle_authn($controller); // NO AUTH FOR PING
            if ('ping' == $pathSeg0) {
                // ping test to see if things are working
                $user_id = bfwpub_service::get_current_user_id();
                $output = bfwpub_service::encode_ping($user_id);
                if (!isset($output)) {
                    $controller->setStatus(400); //Invalid
                    $controller->sendResponse('ERROR - plugin not configured');
                    return; // SHORT CIRCUIT
                }

            } else {
                // UNKNOWN
                $valid = false;
                $output = "Unknown path ($controller->path) specified for method GET";
                $status = 404; //NOT_FOUND
            }
        } else {
            // POST
            // handle the request authn if needed
            bfw_handle_authn($controller);
            if ('gradebook' == $pathSeg0) {
                // import of the gradebook data
                $course_id = $pathSeg1;
                if ($course_id == null) {
                    $msg = "valid course_id must be included in the URL /gradebook/{course_id}";
                    error_log($msg);
                    throw new InvalidArgumentException($msg);
                }
                bfw_get_and_check_current_user("upload grades into the gradebook");
                if (! bfwpub_service::get_course($course_id)) {
                    $controller->setStatus(404); // course not found
                    $msg = 'Invalid course, course ('.$course_id.') could not be found';
                    error_log($msg);
                    $controller->sendResponse($msg);
                    return; // SHORT CIRCUIT
                }
                $json = bfw_get_json_data($controller);
                try {
                    $gradebook = bfwpub_service::decode_gradebook($json);
                    // process gradebook data
                    $results = bfwpub_service::save_gradebook($gradebook);
                    // generate the output
                    $output = bfwpub_service::encode_gradebook_results($results);
                    if (! $output) {
                        // special RETURN, non-XML, no failures in save
                        $controller->setStatus(200);
                        $controller->setContentType('text/json');
                        $output = '{"success", true}';
                        $controller->sendResponse($output);
                        return; // SHORT CIRCUIT
                    } else {
                        // failures occurred during save
                        $status = 200; //OK;
                    }
                } catch (InvalidArgumentException $e) {
                    // invalid JSON
                    $valid = false;
                    $output = 'Invalid gradebook JSON in request, unable to process: '.$e->getMessage().' :: '.$e->getFile().' ('.$e->getLine().')'.PHP_EOL.$json;
                    error_log("BFW gradebook: ".$output);
                    $status = 400; //BAD_REQUEST;
                }

            } else {
                // UNKNOWN
                $valid = false;
                $output = "Unknown path ($controller->path) specified for method POST";
                error_log("BFW rest: ".$output);
                $status = 404; //NOT_FOUND;
            }
        }
    } catch (SecurityBfwException $e) {
        $valid = false;
        $current_user_id = bfwpub_service::get_current_user_id();
        if (! $current_user_id) {
            $output = 'User must be logged in to perform this action: ' . $e->getMessage().' :: '.$e->getFile().' ('.$e->getLine().')';
            error_log("SecurityBfwException: ".$output);
            $status = 403; //UNAUTHORIZED;
        } else {
            $output = "User ($current_user_id) is not allowed to perform this action: " . $e->getMessage().' :: '.$e->getFile().' ('.$e->getLine().')';
            error_log("SecurityBfwException: ".$output);
            $status = 401; //FORBIDDEN;
        }
    } catch (InvalidArgumentException $e) {
        $valid = false;
        $output = 'Invalid request: ' . $e->getMessage().' :: '.$e->getFile().' ('.$e->getLine().')';
        error_log("InvalidArgumentException: ".$output);
        $status = 400; //BAD_REQUEST;
    } catch (Exception $e) {
        $valid = false;
        $output = 'Failure occurred: ' . $e->getMessage().' :: '.$e->getFile().' ('.$e->getLine().')';
        error_log("Exception: ".$output);
        $status = 500; //INTERNAL_SERVER_ERROR;
    }
}
if ($valid) {
    // send the response
    $controller->setStatus(200);
    $controller->setContentType('application/json');
    /*
    $controller->setContentType('application/xml');
    $output = bfwpub_controller::XML_HEADER . $output;
    */
    $controller->sendResponse($output);
} else {
    // error with info about how to do it right
    $controller->setStatus($status);
    $controller->setContentType('text/plain');
    // add helpful info to the output
    $msg = "ERROR $status: Invalid request (".$controller->method." /".$controller->path.")" .
        "\n\n=INFO========================================================================================\n".
        $output.
        "\n\n-HELP----------------------------------------------------------------------------------------\n".
        "Valid request paths include the following (not including the block prefix: ".bfwpub_service::block_url('rest.php')."):\n".
        "GET  /ping                     - returns 200 if the plugin is configured OR 400 otherwise, \n".
        "                                 returns 404 if the plugin is not installed (or URL is otherwise bad) \n".
        "POST /gradebook/{course_id}    - send the gradebook data into the system, \n".
        "                                 returns 400 and errors (invalid course/data) on failure (JSON) \n".
        "\n".
        " - Authenticate by sending the shared key (".bfwpub_controller::SHARED_KEY.") in the request parameters \n".
        " - Invalid key will result in a 401 (invalid credentials) or 403 (not authorized) status \n".
        " - Use ".bfwpub_controller::COMPENSATE_METHOD." param to override the http method being used (e.g. POST /courses?".bfwpub_controller::COMPENSATE_METHOD."=GET will force the method to be a GET despite sending as a POST) \n".
        " - Send data as the http request BODY or as a form parameter called ".bfwpub_controller::JSON_DATA." \n".
        " \n".
        " -- Version: ".bfwpub_service::VERSION."\n".
        " \n";
    $controller->sendResponse($msg);
}
