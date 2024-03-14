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
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * This is the main entry point for the utools plugin. It is a menu screen
 * that you will see when you first access the plugin.
 */

require_once('../../../config.php');

$evalajax = null;
$params = null;
$class_obj = null;

// check if we are sending GET data....
$class_obj = isset($_GET['class']) ? $_GET['class'] : null;
$function = isset($_GET['call']) ? $_GET['call'] : null;
$params = isset($_GET['params']) ? $_GET['params'] : null;

// if class_obj is null then we must check POST data....
if (!isset($class_obj)) {
    $class_obj = isset($_POST['class']) ? $_POST['class'] : null;
    $function = isset($_POST['call']) ? $_POST['call'] : null;
    $params = isset($_POST['params']) ? $_POST['params'] : 'none';
}

if (isset($params)) {
    // $ulethlib->printToLog("Utools -> ajax.php -> The params: ", "", $params, true);
} else {
    // $ulethlib->printToLog("Utools -> ajax.php -> No params have been sent!");
    $params = array("empty" => "true");
}

// it could be either GET or POST, let's check......
if (isset($class_obj)) {
    // $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => include this file: ".$class_obj.".php");
    include_once($class_obj.'.php');
    $evalajax = new $class_obj();
} else {
    die (json_encode(array("success" => "false")));
}

// now let's call the method
if (method_exists($evalajax, $function)) {
    call_user_func(array($evalajax, $function), $params);
} else {
    die (json_encode(array("success" => "false")));
}
