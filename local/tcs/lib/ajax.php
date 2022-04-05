<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */


// error_log("\n================    ajax.php   ======================================");

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once "TcsLib.php";
error_reporting(E_ALL);
// error_log("\n\nAJAX.PHP ---------->>>>>>> START <<<<<<--------------------\n\n");
// error_log("\n------going to try trigger_error log: \n");
// trigger_error("f-you trigger error", E_USER_NOTICE);
// error_log("\n\n\n------did the trigger_error work? \n");

// error_log("\nWhat is salt request: ". $_REQUEST['salt_baby']);
// error_log("\nWhat is CFG->passwordsalt: ". $CFG->passwordsalt);


/**
 *  There are a few different possible variables to come in that will direct where we are going
 * @ $ _ GET class, ex. [TCSAjax] The class name we are going to load. This class name should be the filename as well.
 * @ $ _ GET REQUEST_METHOD, ex. [GET] - What method is being used.
 * @ $ _ GET call, ex. [getUsersFromTestList] - What method is being called.
 * @ $ _ GET params - Is a string of variables being sent to php script.
 *
 */
$tcslib = new TcsLib;

// security check
// if ($tcslib->checkSiteAdminUser() || $tcslib->checkTestCentreUser() || $tcslib->checkDisabilityUser() || $_REQUEST['salt_baby'] == $CFG->passwordsalt) {
// if ($tcslib->checkSiteAdminUser() || $tcslib->checkTestCentreUser() || $tcslib->checkDisabilityUser()) {
if (1) {
    $CFG->local_tcs_logging ? error_log("\n") : null;
    // error_log('****************************************************************'); //, E_USER_ERROR);
    // error_log('****************************************************************'); //, E_USER_ERROR);
    // error_log('****************************************************************'); //, E_USER_WARNING);
    // error_log('*******************      AJAX START      ***********************'); //, E_USER_WARNING);
    // error_log('****************************************************************'); //, E_USER_NOTICE);
    // error_log('****************************************************************'); //, E_USER_NOTICE);
    // error_log('****************************************************************'); //, E_USER_NOTICE);
    
    $inputJSON = file_get_contents('php://input');
    // $CFG->local_tcs_logging ? error_log("\nWhat is inputJSON: ". print_r($inputJSON, 1)."\n") : null;

    $input = json_decode($inputJSON, TRUE);
    
    // $CFG->local_tcs_logging ? error_log("\nWhat is input: ". print_r($input, 1)."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\nis input set: ". isset($input)."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\ninput type: ". gettype($input)."\n") : null;
    
    // $CFG->local_tcs_logging ? error_log("\n\n==============  *******************   ========================================\n") : null;
    // $CFG->local_tcs_logging ? error_log("\nCHECKING GET, POST, REQUEST\n") : null;
    // $CFG->local_tcs_logging ? error_log("\nWhat is GET: ". print_r($_GET, 1)."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\nWhat is POST: ". print_r($_POST, 1)."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\nWhat is REQUEST: ". print_r($_REQUEST, 1)."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\n==============  *******************   ========================================\n\n") : null;

    $tcsajax = null;
    $params = null;
    $class_obj = null;
    $salt_baby = null;

    // check if we are sending GET data....
    // $class_obj = isset($_GET['class']) ? $_GET['class'] : null;
    // $function = isset($_GET['call']) ? $_GET['call'] : null;
    // $params = isset($_GET['params']) ? $_GET['params'] : null;

    $class_obj = isset($input['class']) ? $input['class'] : null;
    $function = isset($input['call']) ? $input['call'] : null;
    $params = isset($input['params']) ? $input['params'] : null;

    // error_log("\nAJAX -> PRE -> Ok......are we good to go: ");
    // error_log("\nAJAX -> PRE -> class_obj is: ". $class_obj. " \n");
    // error_log("\nAJAX -> PRE -> function is: ". $function. " \n");
    // error_log("\nAJAX -> PRE -> params is: ". $params. " \n");
    
    
    // if class_obj is null then we must check POST data....
    if (!isset($class_obj) || $class_obj == null) {
        // error_log("\n\nAJAX -> FAIL 1: ClassObj is NOT set, going to check POST\n");
        $class_obj = isset($_POST['class']) ? $_POST['class'] : null;
        $function = isset($_POST['call']) ? $_POST['call'] : null;
        $input = isset($_POST['params']) ? $_POST['params'] : 'none';
        $salt_baby = isset($_POST['salt_baby']) ? $_POST['salt_baby'] : null;
        $params = json_decode($input, TRUE);
        // $CFG->local_tcs_logging ? error_log("\nPOST -> what is class_obj: ". $class_obj) : null;
        // $CFG->local_tcs_logging ? error_log("\nPOST -> what is function: ". $function) : null;
        // $CFG->local_tcs_logging ? error_log("\nPOST -> what is params: ". print_r($params, 1)) : null;
    } else {
        error_log("\nClassObj IS set, what is class_obj: ". print_r($class_obj, 1));
    }

    // Alrighty, strike 2! Let's check GET now....
    if (!isset($class_obj)) {
        // error_log("\n\nAJAX -> FAIL 2: ClassObj is NOT set, going to check POST\n");
        $CFG->local_tcs_logging ? error_log("\n\nPOST FAILED, going to try GET........") : null;
        $class_obj = isset($_GET['class']) ? $_GET['class'] : null;
        $function = isset($_GET['call']) ? $_GET['call'] : null;
        $params = isset($_GET['params']) ? $_GET['params'] : 'none';
        $salt_baby = isset($_GET['salt_baby']) ? $_GET['salt_baby'] : null;
    }

    // $CFG->local_tcs_logging ? error_log("\n\n----------------------------------------------------------------------") : null;
    // $CFG->local_tcs_logging ? error_log("\n------------- DATA HAS BEEN COLLECTED -------------------: \n") : null;
    // $CFG->local_tcs_logging ? error_log("\nHeres what has been passed in: \n") : null;
    // $CFG->local_tcs_logging ? error_log("\nWhich php file (class): ".$class_obj."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\nwhat function: ".$function."\n") : null;
    // $CFG->local_tcs_logging ? error_log("\n\n----------------------------------------------------------------------") : null;
    
    if (isset($params)) {
        $CFG->local_tcs_logging ? error_log("\nThe params: ".print_r($params, 1)."\n") : null;
        $CFG->local_tcs_logging ? error_log("\nparams type: ".gettype($params)."\n") : null;
    } else {
        $CFG->local_tcs_logging ? error_log("\nNo params have been sent!\n") : null;
        $params = array("empty" => "true");
    }

    // error_log("\nAJAX -> POST -> Ok......are we good to go: ");
    // error_log("\nAJAX -> POST -> class_obj is: ". $class_obj. " \n");
    // error_log("\nAJAX -> POST -> function is: ". $function. " \n");
    // error_log("\nAJAX -> POST -> input is: ". $input. " \n");
    // error_log("\nAJAX -> POST -> params is: ". print_r($params, 1). " \n");
    // error_log("\nAJAX -> POST -> params type: ". gettype($params). " \n");
    // error_log("\nAJAX -> POST -> is salt_baby set?\n");
    // if (isset($salt_baby)) {
    //     error_log("\nAJAX -> POST -> YES salt_baby IS set\n");
    //     error_log("\nAJAX -> POST -> salt_baby is: ". $salt_baby. " \n");
    // } else {
    //     error_log("\nAJAX -> POST -> NOOOOOOOO salt_baby IS NOT set\n");
    // }


    // it could be either GET or POST, let's check......
    if (isset($class_obj)) {
        $CFG->local_tcs_logging ? error_log("\nAJAX.php => include this file: ".$class_obj.".php\n") : null;
        include_once($class_obj.'.php');
        $tcsajax = new $class_obj();
        $CFG->local_tcs_logging ? error_log("\nAJAX.php => object is ready\n") : null;
    } else {
        $CFG->local_tcs_logging ? error_log("\nAJAX.php => Rejected, no file specified!!!\n") : null;
        die (json_encode(array("success" => "false")));
    }
    $CFG->local_tcs_logging ? error_log("\n\n----------------------------------------------------------------------") : null;
    $CFG->local_tcs_logging ? error_log("\n------------- Does Method Exist?? -------------------: \n") : null;
    // $CFG->local_tcs_logging ? error_log("\nclass_obj: ". $class_obj) : null;
    $CFG->local_tcs_logging ? error_log("\nfunction: ". $function) : null;
    // $CFG->local_tcs_logging ? error_log("\nparams: ". print_r($params, 1)) : null;
    // now let's call the method
    if (method_exists($tcsajax, $function)) {
        $CFG->local_tcs_logging ? error_log("\nAJAX.php => Success METHOD DOES EXIST, now calling ".
            $function." from ".get_class($tcsajax).".php\n") : null;
        // $CFG->local_tcs_logging ? error_log("\n****************************************************************\n", 3) : null;
        // $CFG->local_tcs_logging ? error_log("\n****************************************************************\n", 3) : null;
        $CFG->local_tcs_logging ? error_log("\n****************************************************************\n", 3) : null;
        $CFG->local_tcs_logging ? error_log("\n*******************      AJAX DONE       ***********************\n", 3) : null;
        $CFG->local_tcs_logging ? error_log("\n****************************************************************\n", 3) : null;
        // $CFG->local_tcs_logging ? error_log("\n****************************************************************\n", 3) : null;
        // $CFG->local_tcs_logging ? error_log("\n****************************************************************\n", 3) : null;

        // if using POSTMAN then this comes as an array, need to convert to object to match browser sent data.
        if (isset($salt_baby)) {
            $params = (object) $params;
        }
        call_user_func(array($tcsajax, $function), $params);
        $CFG->local_tcs_logging ? error_log("\n**************  Called Method, COMPLETE    *********************\n", 3) : null;

        $CFG->local_tcs_logging ? error_log("\nAJAX.php => Done\n") : null;
    } else {
        $CFG->local_tcs_logging ? error_log("\nAJAX.php => Rejected, method does not exist!!!\n") : null;
        die (json_encode(array("success" => "false")));
    }

} else {
    $CFG->local_tcs_logging ? error_log("\nAJAX.php => Rejected, user not allowed!!!\n") : null;
    die (json_encode(array("success" => "false")));
}
