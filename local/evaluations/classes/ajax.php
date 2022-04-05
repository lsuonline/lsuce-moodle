<?php

/**
 * ************************************************************************
 * *                         Course Eval Ajax                            **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Custom Tools                                             **
 * @author      David Lowe                                				 **
 * ************************************************************************
 * ********************************************************************** */

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
