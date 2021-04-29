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
 * Return entry point for the quizaccess_biosigid plugin.
 *
 * @package    quizaccess_biosigid
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/gradelib.php');

require_once($CFG->dirroot . '/mod/biosigid/lib.php');
require_once($CFG->dirroot . '/mod/biosigid/AESEncryption.php');

// get BioSig-ID config
$config = get_config('biosigid');

$args = required_param('args', PARAM_RAW);

// Descrypt incoming data
$passphrase = $config->passphrase;
$salt = $config->salt;
$vector = $config->vector;
$cipher = new AESEncryption($passphrase, $salt,  $vector, BIOSIGID_KEY_SIZE);
$decrypted = $cipher->decrypt($args);

$argArray = explode('&', $decrypted);
$params = array();
foreach ($argArray as $arg) {
    $param = explode('=', $arg, 2);
    if (count($param) == 2) {
        $params[$param[0]] = $param[1];
    }
}

$quizid = '';
date_default_timezone_set('UTC');
$now = time();
$timestamp = date(BIOSIGID_DATE_FORMAT, $now);

// declare statusCode to prevent notice warnings
$statusCode = '';

// Validate incoming parameters
$required = ['ts', 'sc', 'sid', 'cid', 'lc', 'nw', 'em', 'fn', 'ln', 'vs', 'vl', 'moodle_qid', 'moodle_uid'];

// Set default to ok
$ok = true;

foreach ($required as $p) {
    if (!array_key_exists($p, $params)) {
        $ok = false;
        break;
    }
}

// Get quiz ID for use in return URLs
if ($ok) {
    $quizid = clean_param($params['moodle_qid'], PARAM_INT);
}

// Clean other parameters to be used with Moodle functions
if ($ok) {
    $uid = clean_param($params['moodle_uid'], PARAM_INT);
    $ts = clean_param($params['ts'], PARAM_TEXT);
// check timestamp
    $convertedDate = strtotime($ts);
    $ok = abs($convertedDate - $now) <= BIOSIGID_MAX_TIMESTAMP_DIFF;
    if (!$ok) {
        $statusCode = 250;
    }
}
if ($ok) {    // check shared code
    $ok = $params['sc'] == $config->sharedcode;
    if (!$ok) {
        $statusCode = 120;
    }
}
if ($ok) {    // check system ID
    $ok = $params['sid'] == $config->systemid;
    if (!$ok) {
        $statusCode = 140;
    }
}
if ($ok) {    // check customer ID
    $ok = $params['cid'] == $config->customerid;
    if (!$ok) {
        $statusCode = 150;
    }
}
if ($ok) {    // check verify level
    $ok = ($params['vl'] == '1') || ($params['vl'] == '2') || ($params['vl'] == '3') || ($params['vl'] == '4');
    if (!$ok) {
        $statusCode = 110;
    }
}
if ($ok) {    // check verify success
    $ok = strtolower($params['vs']) == 'true';
    if (!$ok) {
        $statusCode = 110;
    }
}

// generate randomness for encrypted string
$randomness = '';
if (function_exists('openssl_random_pseudo_bytes')) {
    $randomness = bin2hex(openssl_random_pseudo_bytes(16));
} else {
    $date = date_create();
    $randomness = date_timestamp_get($date);
}

// get return values
if ($ok) {
    $status = "Success";
    $statusCode = 100;
    $courseurl = new moodle_url('/mod/quiz/view.php', array('id' => $quizid, 'r' => $cipher->encrypt('yes-' . $randomness)));
    $redirecturl = $courseurl->out();
} else {
    $status = "Failure";
    $courseurl = new moodle_url('/mod/quiz/view.php', array('id' => $quizid, 'r' => $cipher->encrypt('no-' . $randomness)));
    $redirecturl = $courseurl->out();
}

// Generate XML to return to BSI
$message = get_string("error{$statusCode}", 'biosigid');

$xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<SSO_RESPONSE>
  <TIMESTAMP>{$timestamp}</TIMESTAMP>
  <STATUS>{$status}</STATUS>
  <CODE>{$statusCode}</CODE>
  <MESSAGE>{$message}</MESSAGE>
  <REDIRECT>{$redirecturl}</REDIRECT>
</SSO_RESPONSE>
EOF;

@header('Content-Type: text/xml; charset=utf-8');
echo $xml;