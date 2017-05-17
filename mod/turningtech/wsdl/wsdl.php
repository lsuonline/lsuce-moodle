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
 * This file is used to Load moodle config.
 * @author jacob
 * @copyright  2012 Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');

// Possible options for service type.
$arrservicetype = array(
    "course",
    "func",
    "grades"
);

// Get the service after sanitization.
$service = required_param('service', PARAM_ALPHANUM);

// If the service type is not valid.
if (!in_array($service, $arrservicetype)) {
    echo "expecting parameter 'service' with value 'course', 'func' or 'grades'\n";
    die;
}

// The WSDL file to read, depending on the request.
$filename = '';

// Set filename depending on request.
switch ($service) {
    case 'course':
        $filename = 'CoursesService.wsdl';
        break;
    case 'func':
        $filename = 'FunctionalCapabilityService.wsdl';
        break;
    case 'grades':
        $filename = 'GradesService.wsdl';
        break;
    default:
        echo "expecting parameter 'service' with value 'course', 'func' or 'grades'\n";
        die;
}

// The URL of the module.
$url = $CFG->wwwroot . '/mod/turningtech';




$path = $url.'/wsdl/'.$filename;
    
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$path);
	curl_setopt($ch, CURLOPT_FAILONERROR,1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	$retValue = curl_exec($ch);	 
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	header('Content-type: text/xml');
    echo str_replace('@URL', $url, $retValue);
if($retValue == false) {

$opts = array('http' =>
    array(
        'method'  => 'GET',
        //'user_agent '  => "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2) Gecko/20100301 Ubuntu/9.10 (karmic) Firefox/3.6",
        'header' => array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*\/*;q=0.8
'
        ), 
    )
);
$context  = stream_context_create($opts);
$contents = @file_get_contents($filename,true, $context);
header('Content-type: text/xml');

echo str_replace('@URL', $url, $contents);
    
}
