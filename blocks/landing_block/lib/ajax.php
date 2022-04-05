<?php
    header("Cache-Control: no-cache, must-revalidate");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: content-type");
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        UtoolsAJAX                                               **
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

/**
 *  There are a few different possible variables to come in that will direct where we are going
 * @ $ _ GET class, ex. [UtoolsAjax] The class name we are going to load.
 * @ This class name should be the filename as well.
 * @ $ _ GET REQUEST_METHOD, ex. [GET] - What method is being used.
 * @ $ _ GET call, ex. [getUsersFromTestList] - What method is being called.
 * @ $ _ GET params - Is a string of variables being sent to php script.
 *
 */

// security check

// error_log("\n");
// error_log("\nHave hit the landing ajax page");


$class_obj = null;
$lb_ajax = null;

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


if (!isset($params)) {
    $params = array("empty" => "true");
}


// it could be either GET or POST, let's check......
if (isset($class_obj)) {
    // error_log("\nclass_obj is set, going to include it.");
    include_once($class_obj.'.php');
    // error_log("\ncreating new object.........");
    $lb_ajax = new $class_obj();
    // error_log("\ncreated.");
} else {
    die (json_encode(array("success" => "false")));
}

// now let's call the method
if (method_exists($lb_ajax, $function)) {
    // error_log("\ndoes method exist...........YUP");
    // error_log("\nlb_ajax is: ". print_r($lb_ajax, 1));
    call_user_func(array($lb_ajax, $function), $params);
} else {
    // error_log("\ndoes method exist...........NOPE");
    die (json_encode(array("success" => "false")));
}

// error_log("\nIs this set? _GET['class'].......??");

if (isset($_GET['class'])) {
    // error_log("\n");
    // error_log("\nIs this set? _GET['class'].......YUP");
    // error_log("\nGET is set, going to include this file: ". $class_obj . ".php");
    // error_log("\n");
    $class_obj = $_GET['class'];
    include_once($class_obj.'.php');
    $ulethajax = new $class_obj();

} else {
    // $ulethajax = new UtoolsAJAX();
    // error_log("\nIs this set? _GET['class'].......NOPE");
    if (debugging()) {
        error_log("\nSomething FAILED, aborting ajax call");
    }
    die;
}

// error_log("\nIs this REQUEST_METHOD == GET.......??");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    // error_log("\n");
    // error_log("\nYUP........REQUEST_METHOD==GET");
    if (isset($_GET['call'])) {
        $function = $_GET['call'];
        if (isset($_GET['params'])) {
            $params = $_GET['params'];
        } else {
            $params = null;
        }

        // error_log("\nDoes method exist?????");
        if (method_exists($ulethajax, $function)) {
            // error_log("\nYup, calling method at the bottom.......");
            call_user_func(array($ulethajax, $function), $params);
        }
    }
}
