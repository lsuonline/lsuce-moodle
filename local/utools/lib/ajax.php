<?php

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
 * @ $ _ GET class, ex. [utools_Ajax] The class name we are going to load. This class name should be the filename as well.
 * @ $ _ GET REQUEST_METHOD, ex. [GET] - What method is being used.
 * @ $ _ GET call, ex. [getUsersFromTestList] - What method is being called.
 * @ $ _ GET params - Is a string of variables being sent to php script.
 *
 */

// let's get our Utools Library for logging
include_once('UtoolsLib.php');
$ulethlib = new UtoolsLib();

$ulethlib->printToLog("\n");
$ulethlib->printToLog("\n======================================================");

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$ulethlib->printToLog("\n\n==============  *******************   ========================================");
$ulethlib->printToLog("\nCHECKING GET, POST, REQUEST");
$ulethlib->printToLog("\nWhat is GET: ", $_GET, true);
$ulethlib->printToLog("\nWhat is POST: ", $_POST, true);
// $ulethlib->printToLog("\nWhat is REQUEST: ", $_REQUEST, true);
$ulethlib->printToLog("\n==============  *******************   ========================================\n\n");

$input_set = false;
$post_set = false;
$get_set = false;

// * string - the message
// * string - what app is wanting to write to log?
// * any - print this variable
// * bool - do we need a print_r()?
/*
AXIOS is being stupid and is sending the data as an array, the structure is as follows:
Array
(
    [params] => Array
        (
            [call] => getCurrentStudentCount
            [class] => TcmsAjax
            [to_dispatch] => updateTCMSUserStat
        )

    [xtra_data] => Array
        (
            [ax] => true
            [page] => 0
            [total] => 0
        )
)
*/

if (isset($input)) {
    if (gettype($input) == "array" && count($input) > 0 && isset($input['params'])) {
        $input_set = true;
        $class_obj = isset($input['params']['class']) ? $input['params']['class'] : null;
        $function = isset($input['params']['call']) ? $input['params']['call'] : null;
        $params = isset($input['xtra_data']) ? $input['xtra_data'] : null;
    }
    $ulethlib->printToLog("\ninput IS set, here's what's passed: ", $input, true);
} else {
    $ulethlib->printToLog("\nFAIL -> input IS NOT set");
}
$ulethlib->printToLog("\ninput type: ". gettype($input));

if (isset($_GET)) {
    if (gettype($_GET) == "array" && count($_GET) > 0 && isset($_GET['params'])) {
        $post_set = true;
        $class_obj = isset($_GET['params']['class']) ? $_GET['params']['class'] : null;
        $function = isset($_GET['params']['call']) ? $_GET['params']['call'] : null;
        $params = isset($_GET['xtra_data']) ? $_GET['xtra_data'] : null;
    }
    $ulethlib->printToLog("\nGET IS set, here's what's passed: ", $_GET, true);
} else {
    $ulethlib->printToLog("\nFAIL -> GET IS NOT set");
}
$ulethlib->printToLog("\nGET type: ". gettype($_GET));

if (isset($_POST)) {
    if (gettype($_POST) == "array" && count($_POST) > 0 && isset($_POST['params'])) {
        $get_set = true;
        $class_obj = isset($_POST['params']['class']) ? $_POST['params']['class'] : null;
        $function = isset($_POST['params']['call']) ? $_POST['params']['call'] : null;
        $params = isset($_POST['xtra_data']) ? $_POST['xtra_data'] : null;
    }
    $ulethlib->printToLog("\nPOST IS set, here's what's passed: ", $_POST, true);
} else {
    $ulethlib->printToLog("\nFAIL -> POST IS NOT set");
}


// if class_obj is null then we must check POST data....
if (!isset($class_obj)) {
    $ulethlib->printToLog("\nClass obj IS NOT set FAIL");
    die();
}

$ulethlib->printToLog("Here's what we know......");
$ulethlib->printToLog("Do we have a class obj: ", $class_obj, true);
$ulethlib->printToLog("what about a function: ", $function);
$ulethlib->printToLog("what about params: ", $params, true);


if (isset($params)) {
    $ulethlib->printToLog("Utools -> ajax.php -> The params: ", $params, true);
} else {
    $ulethlib->printToLog("Utools -> ajax.php -> No params have been sent!");
    $params = array("empty" => "true");
}


// it could be either GET or POST, let's check......
if (isset($class_obj)) {
    $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => include this file: ".$class_obj.".php");
    include_once($class_obj.'.php');
    $utools_ajax = new $class_obj();
    $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => object is ready");
} else {
    $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => Rejected, no file specified!!!");
    die (json_encode(array("success" => "false")));
}

// now let's call the method
if (method_exists($utools_ajax, $function)) {
    $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => Success, now calling ". $function." from ".get_class($utools_ajax).".php");
    // if there are any uploaded files, add them to the param list.
//    if ($files) {
//        $params['files'] = $files;
//    }
    call_user_func(array($utools_ajax, $function), $params);
    $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => Done\n");
} else {
    $ulethlib->printToLog("Utools -> ajax.php -> \nAJAX.php => Rejected, method does not exist!!!");
    die (json_encode(array("success" => "false")));
}
