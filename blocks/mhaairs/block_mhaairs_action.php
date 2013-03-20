<?php
require_once('../../config.php');
require_once($CFG->libdir .'/accesslib.php');
require_once($CFG->libdir .'/datalib.php');
require_once($CFG->libdir .'/moodlelib.php');
require_once($CFG->dirroot.'/blocks/mhaairs/block_mhaairs_util.php');

global $CFG, $COURSE, $USER, $_SERVER;

$token = 'test';

$secure = filter_var($_SERVER['HTTPS'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$token = filter_var($_REQUEST['token'], FILTER_SANITIZE_STRING);
$action = filter_var($_REQUEST['action'], FILTER_SANITIZE_STRING);
$username = filter_var($_REQUEST['username'], FILTER_SANITIZE_STRING); 
$userid = filter_var($_REQUEST['userid'], FILTER_SANITIZE_STRING); 
$password = filter_var($_REQUEST['password'], FILTER_SANITIZE_STRING);

$secret = $CFG->block_mhaairs_shared_secret;

if($secure !== 'on' && $CFG->block_mhaairs_sslonly) 
{
	print 'conncetion must be secured with SSL'; die;
}

$result = NULL;

switch($action){
	case "test" 			: 	$result = "OK"; break;
	case "ValidateLogin" 	: 	$result = mhaairs_validate_login($token, $secret, $username, $password); break;
	case "GetUserInfo" 		: 	$result = mhaairs_get_user_info($token, $secret); break;
	default : break;
}
print mhaairs_var2json($result);
?>
