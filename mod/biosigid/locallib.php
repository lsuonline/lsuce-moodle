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
 * Library of additional functions for module biosigid
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2018 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('AESEncryption.php');

/**
 * Generate a redirect URL for a user identity verification session
 *
 * @param object $biosigid An object from the form in mod_form.php
 * @param int $id Id of the module instance
 * @param string $message Error message returned from BSI Server
 * @return string url of session
 */
function biosigid_inbound_sso(stdClass $biosigid, $id, &$message) {
    global $CFG, $USER, $PAGE, $COURSE, $cm, $version;

    if ($CFG->branch < 36) {
	// Include core Moodle file to support older branch functionality
        require_once(dirname(dirname(dirname(__FILE__))).'/lib/coursecatlib.php');
    }

    // Load custom profile field to obtain NMLS-ID number
    require_once(dirname(dirname(dirname(__FILE__))).'/user/profile/lib.php');

    // are we running for an NMLS user
    $isNMLS = (!empty($biosigid->custom1) && (substr($biosigid->custom1, 0, 4) == 'NMLS'));
    $isADRE = ((strtoupper($biosigid->systemid) == 'ADRE'));

    profile_load_custom_fields($USER);
    $nmls = array();
    $adre = array();

    if ($isNMLS) {
        $nmls['provider_id'] = $biosigid->custom1;
        $nmls['user_id'] = $USER->profile["NMLSID"];
        if (empty($nmls['user_id'])) {
            $message = "An NMLS-ID is required.";
            return null;
        }

        if (empty($cm->idnumber)) {
            $message = "The BioSig-ID™ instance is misconfigured.";
            return null;
        }
        
        $data = explode(',', $cm->idnumber);
        $nmls['course_id'] = $data[0];
        $nmls['course_duration'] = $data[1];
        $nmls['progress'] = $data[2];
    } else if ($isADRE) {
        if (empty($PAGE->cm->idnumber)) {
            $message = "The BioSig-ID™ instance is misconfigured.";
            return null;
        }

        $data = explode(',', $PAGE->cm->idnumber);
        $adre['course_duration'] = $data[0];
    }

    $session = NULL;

    $passphrase = $biosigid->passphrase;
    $salt = $biosigid->salt;
    $vector = $biosigid->vector;
    $keySize = 128;

    $cipher = new AESEncryption($passphrase, $salt,  $vector, $keySize);

    date_default_timezone_set('UTC');
    $data = 'ts=' . date(BIOSIGID_DATE_FORMAT);
    $data .= "&sc={$biosigid->sharedcode}";
    $data .= "&sid={$biosigid->systemid}";
    $data .= "&cid={$biosigid->customerid}";
    $locale = $CFG->locale;
    if (empty($locale)) {
        $locale = $biosigid->locale;
    }
    $data .= "&lc={$locale}";
    $data .= '&nw=false';
    $data .= "&em={$USER->email}";
    $data .= "&fn={$USER->firstname}";
    $data .= "&ln={$USER->lastname}";
    $data .= "&lid={$USER->username}";
    if ($isNMLS) {
        $data .= "&ls={$nmls['provider_id']}";
        $data .= "&uid={$nmls['user_id']}";
    } else if ($isADRE) {
        $prefix = strtoupper(str_replace('_', '', $biosigid->customerid));
        $data .= "&uid={$prefix}{$USER->id}";
    }
    $title = substr($PAGE->course->fullname, 0, BIOSIGID_MAX_NAME_LENGTH);
    if ($isNMLS) { // NMLS based instance
        $data .= "&d1=" . date('Y');
        $data .= "&d2={$nmls['course_id']}";
        $data .= "&d3={$nmls['progress']}";
        $data .= "&d4={$title}";
        $data .= "&d5={$nmls['course_duration']}";
    } else if ($isADRE) { // ADRE based instance
        $data .= "&d1=" . date('Y');
        $data .= "&d2={$PAGE->course->id}-{$title}";
        $data .= "&d3={$PAGE->cm->name}";
        $data .= "&d5={$adre['course_duration']}";
    } else { // regular instance
        $category = '';
		if ($CFG->branch >= 36) {
			$category = core_course_category::get($PAGE->course->category, MUST_EXIST);
        } elseif ($CFG->branch >= 22) {
            $category = coursecat::get($PAGE->course->category, MUST_EXIST);
        } else {
            $category = get_course_category($PAGE->course->category);
        }
        $teachers = biosig_get_teachers_for_course($PAGE->course->id);
        $teacherlist = join(',', $teachers);
        $data .= "&d1={$category->name}";
        $data .= "&d2={$title}";
        $data .= "&d3={$PAGE->course->id}";
        $data .= "&d4={$teacherlist}";
        $data .= "&d5=Tool: {$PAGE->cm->name}";
    }
    $data .= "&moodle_uid={$USER->id}";
    $data .= "&moodle_cid={$id}";
    $data .= "&moodle_version={$CFG->release}";

    $plugin_version = get_config('mod_biosigid', 'version');
    $data .= "&bsi_version={$plugin_version}";

    $encrypteddata = urlencode($cipher->encrypt($data));

    $url = "{$biosigid->url}?args={$encrypteddata}";

    $resp = biosigid_do_http_request('GET', $url);

    if (empty($resp)) {
        $message = "Unable to communicate with the BioSig-ID™ server";
        return null;
    }
    
    $doc = new DOMDocument();
    if (!$doc->loadXML($resp)) {
        $message = "The BioSig-ID™ server encountered an unexpected failure";
        return null;
    }

    $status = $doc->getElementsByTagName('STATUS')->item(0)->nodeValue;
    if ($status == 'Success') {
        $session = $doc->getElementsByTagName('REDIRECT')->item(0)->nodeValue;
    } else {
        $message = $doc->getElementsByTagName('MESSAGE')->item(0)->nodeValue;
    }

    return $session;
}

/**
 * Get an array of teaches for the course
 *
 * @param int $courseid ID of the course to get teachers for
 * @return array() array of teacher names as string
 */
function biosig_get_teachers_for_course($courseid) {
    global $DB, $CFG;

    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $context = '';
    if ($CFG->branch >= 22) {
        $context = context_course::instance($courseid);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
    }
    $teachers = get_role_users($role->id, $context);

    $t = array();
    foreach ($teachers as $teacher) {
        array_push($t, fullname($teacher));
    }
    return $t;
}

/**
 * Get the response from an HTTP request
 *
 * @param string $method Method of HTTP request ('GET' or 'POST')
 * @param string $url URL to send request to
 * @param string optional $data Array of post data parameters
 * @return string Response from request
 */
function biosigid_do_http_request($method, $url, $data = NULL) {
    $response = '';
    
    if (function_exists('curl_version')) {
        // Replacement code using cURL which is more reliable/secure than allow_url_fopen
        // Moodle uses fopen, so needs more investigation if it is wise to force cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Return back HTTP error-code on failure
        if ($httpcode != 200) $response = $httpcode;
        curl_close($ch);
    } else {
        $opts = array('http' => array(
                      'method' => $method,
                      'content' => $data
                      ));
        $ctx = stream_context_create($opts);
        $fp = @fopen($url, 'rb', false, $ctx);
        if ($fp) {
            $resp = @stream_get_contents($fp);
            if ($resp !== FALSE) {
                $response = $resp;
            }
        }
    }
    
    return $response;
}

/**
 * Create a frameset in which to display the BSI Identity verification page
 *
 * @param object $biosigid An object from the form in mod_form.php
 * @param string $exteurl URL to send request to
 * @param int $id Id of the module instance
 * @param object $course Course module object
 */
function biosigid_display_frame(stdClass $biosigid, $exteurl, $id, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $title = strip_tags($courseshortname.': '.format_string($biosigid->name));
    if ($frame === 'top') {  // display Moodle header in top frame
        $PAGE->set_url(new moodle_url('/mod/biosigid/view.php', array('frameset' => 'top', 'id' => $id)));
        $PAGE->set_pagelayout('frametop');
        $PAGE->set_title($title);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->footer();
    } else {    // create a frameset for redirecting the user to BSI in the bottom frame
        $url_config = get_config('url');
        $navurl = "$CFG->wwwroot/mod/biosigid/view.php?id={$id}&amp;frameset=top";
        $framesize = $url_config->framesize;
        $modulename = s(get_string('modulename', BIOSIGID_MODULE_NAME));
        $dir = get_string('thisdirection', 'langconfig');
        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$modulename"/>
  </frameset>
</html>
EOF;
        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
    }
    die;
}

/**
 * Update a user's grade
 *
 * @param object $biosigid An object from the form in mod_form.php
 * @param int $uid User ID
 * @param int $value Grade value
 * @param string optional $timestamp Timestamp of current time
 * @return int Response from grade_update
 */
function biosigid_update_grade(stdClass $biosigid, $uid, $value, $timestamp = null) {
    if (!$timestamp) {
        $timestamp = date(BIOSIGID_DATE_FORMAT, time());
    }

    $grade = new stdClass();
    $grade->userid = $uid;
    $grade->rawgrade = $value;
    $grade->feedback = $timestamp;

    $details = array();
    $details['itemname'] = $biosigid->name;
    $details['hidden'] = true;

    return grade_update(BIOSIGID_MODULE_SOURCE, $biosigid->course, BIOSIGID_MODULE_TYPE, BIOSIGID_MODULE_NAME, $biosigid->id, 0, $grade, $details);
}