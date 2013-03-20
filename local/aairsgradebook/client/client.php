<?php
// This client for local_aairsgradebook is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//

/**
 * XMLRPC client for Moodle 2 - local_aaiitersgradebook
 *
 * This script does not depend of any Moodle code,
 * and it can be called from a browser.
 *
 * @authorr Jerome Mouneyrac
 */

/// MOODLE ADMINISTRATION SETUP STEPS
// 1- Install the plugin
// 2- Enable web service advance feature (Admin > Advanced features)
// 3- Enable XMLRPC protocol (Admin > Plugins > Web services > Manage protocols)
// 4- Create a token for a specific user and for the service 'My service' (Admin > Plugins > Web services > Manage tokens)
// 5- Run this script directly from your browser: you should see 'Hello, FIRSTNAME'

/// SETUP - NEED TO BE CHANGED
$token = '4d3b836336ec6f37818d14d9e6a9ee4b';
$domainname = 'http://ec2-107-22-142-166.compute-1.amazonaws.com/moodle';
//http://ec2-107-22-142-166.compute-1.amazonaws.com/moodle/webservice/rest/server.php?wstoken=4d3b836336ec6f37818d14d9e6a9ee4b&wsfunction=local_aairsgradebook_gradebookservice&source={"source":"testing"}
/// FUNCTION NAME
$functionname = 'local_aairsgradebook_gradebookservice';

/// PARAMETERS creating grade item
$source = 'AAIRS GradeBook';
$courseid = '33';
$itemtype = 'mod';
$itemmodule = 'quiz';
$iteminstance = '2354455';
$itemnumber = '1';
$grades = array(
		//'itemid' => '323'
		'userid' => 'liorstudent'
		,'rawgrade' => '80'
		//,'finalgrade' => 70
		

);


$itemdetails = array(
	'courseid' => '33' //courseID
	,'categoryid' => 'TestCat1234' 
	,'itemname' => 'Test Grade 7' //description
	,'itemtype' => 'AAIRS Gradebook'  
	//,'itemmodule' => 'quiz'
	,'idnumber' => '2354455' //AssignmentID
	,'gradetype' => 1
	,'grademax' => 100 //Points Possible
	//,'scaleid' => NULL 
	//,'outcomeid' => NULL 
	//,'locked' => 0
	//,'locktime' => 0
	,'needsupdate' => 1
	
);


///// XML-RPC CALL
header('Content-Type: text/plain');
$serverurl = $domainname . '/webservice/xmlrpc/server.php'. '?wstoken=' . $token;
require_once('./curl.php');
$curl = new curl;
$post = xmlrpc_encode_request($functionname, array($source, $courseid, $itemtype, $itemmodule, $iteminstance, $itemnumber, json_encode($grades), json_encode($itemdetails)));
$resp = xmlrpc_decode($curl->post($serverurl, $post));
print_r($resp);
