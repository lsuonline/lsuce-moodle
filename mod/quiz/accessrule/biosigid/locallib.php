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
 * locallib code for the quizaccess_biosigid plugin.
 *
 * @package    quizaccess_biosigid
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/biosigid/lib.php');
require_once($CFG->dirroot . '/mod/biosigid/AESEncryption.php');

function biosig_parse_return($r) {
    // get BioSig-ID config
    $config = get_config('biosigid');

    $cipher = new AESEncryption($config->passphrase, $config->salt, $config->vector, 128);

    $data = $cipher->decrypt($r);

    return substr($data, 0, 3) === "yes";
}

function biosigid_inbound_sso($id, &$message) {
    global $USER, $CFG, $PAGE;

    // get BioSig-ID config
    $config = get_config('biosigid');

    $bsi_rd = new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/biosigid/return.php');

    // are we running for an NMLS user
    $isNMLS = (!empty($config->custom1) && (substr($config->custom1, 0, 4) == 'NMLS'));
    $isADRE = ((strtoupper($config->systemid) == 'ADRE'));

    profile_load_custom_fields($USER);
    $nmls = array();
    $adre = array();

    if ($isNMLS) {
        $nmls['provider_id'] = $config->custom1;
        $nmls['user_id'] = $USER->profile["NMLSID"];
        if (empty($nmls['user_id'])) {
            $message = "An NMLS-ID is required.";
            return null;
        }

        if (empty($PAGE->cm->idnumber)) {
            $message = "The BioSig-ID™ instance is misconfigured.";
            return null;
        }

        $data = explode(',', $PAGE->cm->idnumber);
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

    $cipher = new AESEncryption($config->passphrase, $config->salt, $config->vector, 128);

    date_default_timezone_set('UTC');
    $data = 'ts=' . date(BIOSIGID_DATE_FORMAT);
    $data .= "&sc={$config->sharedcode}";
    $data .= "&sid={$config->systemid}";
    $data .= "&cid={$config->customerid}";
    $locale = $CFG->locale;
    if (empty($locale)) {
        $locale = $config->locale;
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
        $prefix = strtoupper(str_replace('_', '', $config->customerid));
        $data .= "&uid={$prefix}{$USER->id}";
    }
    $title = substr($PAGE->course->fullname, 0, BIOSIGID_MAX_NAME_LENGTH);
    if ($isNMLS) {
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
    } else {
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
        $data .= "&d5=Quiz: {$PAGE->cm->name}";
    }
    $data .= "&moodle_uid={$USER->id}";
    $data .= "&moodle_qid={$id}";
    $data .= "&moodle_version={$CFG->release}";

    $plugin_version = get_config('quizaccess_biosigid', 'version');
    $data .= "&bsi_version={$plugin_version}";
    $data .= "&bsi_rd={$bsi_rd}";

    $encrypteddata = urlencode($cipher->encrypt($data));

    $url = "{$config->url}?args={$encrypteddata}";

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

    $redirecturl = NULL;
    $status = $doc->getElementsByTagName('STATUS')->item(0)->nodeValue;
    if ($status == 'Success') {
        $redirecturl = $doc->getElementsByTagName('REDIRECT')->item(0)->nodeValue;
    } else {
        $message = $doc->getElementsByTagName('MESSAGE')->item(0)->nodeValue;
    }

    return $redirecturl;
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
