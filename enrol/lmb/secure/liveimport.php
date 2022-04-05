<?php
// This file is part of the Banner/LMB plugin for Moodle - http://moodle.org/
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
 * This file receives individual XML message from Luminis Message Broker,
 * stores some info in the database, and passes it on to the module to be
 * processed.
 *
 * @author Eric Merrill (merrill@oakland.edu)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package enrol_lmb
 * Based on enrol_imsenterprise from Dan Stowell.
 */

function process_this_forgotten_record($xml)
{
    $this_update = "";
    $frack = simplexml_load_string($xml);

    // check to see if there is a person chunk
    if (isset($frack->person)) {
        // the person field is being updated.......i think?
        $this_update .= "<br/>Update from Banner failed from a user update push. <br/><br/><h2>Username: " . $frack->person->userid[0] . "<br/>User Id: " . $frack->person->userid[1] . "</h2>";
    }

    // check to see if there is a course chunk
    if (isset($frack->group)) {
        $this_update .= "<br/>Update from Banner failed for course update push. <br/><br/><h2>Course: " . $frack->group->description->long . "<br/>CRN: " . $frack->group->sourcedid->id . "</h2>";
    }
    
    // check to see if there is an enrolment chunk
    if (isset($frack->membership)) {
        $this_update .= "<br/>Update from Banner failed for User Enrollment push.<br/><br/><h2>User Sourcedid: " . $frack->sourcedid->id . "<br/>CRN: " . $frack->membership->sourcedid->id . "</h2>";

    }
    return $this_update;
}

//====================================================================================


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

if (!isset($CFG)) {
    header("HTTP/1.1 501 - LMG BANNER Data has failed to send");

    error_log("\nLMB -> liveimport.php -> Trying to live import while DB is inaccessible");
    
    // This allows sites w/o PHP Apache Module to get header info.
    if (!function_exists('getallheaders')) {
        function getallheaders()
        {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
    }
    
    $headers = serialize(getallheaders());
    $xml = file_get_contents('php://input');

    $this_transfer = process_this_forgotten_record($xml);
    
    function send_mail($to, $subject, $body)
    {
        $headers = "From: Moodle Banner LMG FAIL\r\n";
        $headers .= "Reply-To: david.lowe@uleth.ca\r\n";
        $headers .= "Return-Path: david.lowe@uleth.ca\r\n";
        $headers .= "X-Mailer: PHP5\n";
        $headers .= 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        mail($to, $subject, $body, $headers);
    }

    $to = "david.lowe@uleth.ca";
    $subject = "Moodle Banner LMG FAIL";
    $body = "There was an issue with Moodle and the CFG variable did not initiate, possibly DB issue?";

    $body .= $this_transfer;

    send_mail($to, $subject, $body);
    
    error_log("\nLMB -> liveimport.php -> placeholder = ". $this_transfer);

    die();
}

require_once('../lib.php');


$config = enrol_lmb_get_config();

$enrol = new enrol_lmb_plugin();
$enrol->open_log_file();
$enrol->islmb = true;

enrol_lmb_authenticate_http($enrol);

// This allows sites w/o PHP Apache Module to get header info.
if (!function_exists('getallheaders')) {
    function getallheaders() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$headers = serialize(getallheaders());
$xml = file_get_contents('php://input');


// Dont proceed if there is no xml present.
if (!$xml) {
    header("HTTP/1.0 400 Bad Request");
    header("Status: 400 Bad Request");
    exit;
}


set_config('lastlmbmessagetime', time(), 'enrol_lmb');

// Place the XML if not set to 'Never'.
if ($config->storexml != 'never') {
    $xmlstorage = new stdClass();
    $xmlstorage->headers = addslashes($headers);
    $xmlstorage->timereceived = time();

    $xmlstorage->xml = addslashes($xml);
    $xmlstorage->id = $DB->insert_record('enrol_lmb_raw_xml', $xmlstorage, true);

}


$result = $enrol->process_xml_line($xml);

// If we have a good result, update the processed flag.
if ($result) {
    switch ($config->storexml) {
        case "always":
            $xmlupdate = new stdClass();

            $xmlupdate->id = $xmlstorage->id;
            $xmlupdate->processed = 1;
            $DB->update_record('enrol_lmb_raw_xml', $xmlupdate);
            break;
        case "onerror":
            // Delete the good record.
            $DB->delete_records('enrol_lmb_raw_xml', array('id' => $xmlstorage->id));
            break;

        default:
            break;
    }
}

