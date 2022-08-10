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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// TODO: This file was used for testing via POSTMAN.....at one point, can be removed.
require_once($CFG->dirroot . '/config.php');

// Authentication.
// require_login();
// This application is for admins only (atm).
// if (!is_siteadmin()) {
//     error_log("\n NOT ADMIN, redirecting......... \n");
//     $helpers->redirect_to_url('/my');
// }

$class_obj = required_param('class', PARAM_ALPHAEXT);
$function = required_param('call', PARAM_ALPHAEXT);
$params = optional_param('params', PARAM_ALPHAEXT);
// This is for development with POSTMAN
$salt_baby = optional_param('salt_baby');

if (isset($class_obj)) {
    include_once($class_obj.'.php');
    $lsuxejax = new $class_obj();
} else {
    error_log("\n FAIL: could not include the class being called. \n");
    die (json_encode(array("success" => "false")));
}

// now let's call the method
if (method_exists($lsuxejax, $function)) {
    // if using POSTMAN then this comes as an array, need to convert to object to match browser sent data.
    if (isset($salt_baby)) {
        $params = (object) $params;
    }
    call_user_func(array($lsuxejax, $function), $params);
} else {
    error_log("\n FAIL: could not CALL the class. \n");
    die (json_encode(array("success" => "false")));
}
