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
 * @package    enrol_d1
 * @copyright  2022 onwards LSUOnline & Continuing Education
 * @copyright  2022 onwards Robert Russo
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
    **********************************************************
    * This is only a test file and will not be used anywhere *
    **********************************************************
*/

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

global $CFG;

require_once("$CFG->libdir/clilib.php");

// TODO: Add config for this.
$startstring = '89';
$fieldid     = '3';

$users = idnumbers::fetch_all_idnumbers();
die();

// Do any outstanding LSUID updates for the field.
$updates = idnumbers::update_moodle_idnumbers($fieldid);

// Get the missing (in Moodle) LSUIDs.
$idnumbers = idnumbers::get_missing_idnumbers ($startstring, $fieldid);

// Loop through these and insert the new record.
foreach ($idnumbers as $lsuid) {
  // Actually insert the record.
  $inserter = idnumbers::insert_moodle_idnumber($lsuid->userid, $fieldid, $lsuid->idnumber);
}

class idnumbers {
  public static function fetch_all_idnumbers() {
      require_once('../classes/d1.php');

      $s = lsud1::get_d1_settings();
      $tokentime = microtime(true);
      $token = lsud1::get_token();
      echo"Token: $token";
      echo"\n";

      global $DB;
      $sql = 'SELECT d1s.id,
                     d1s.d1id,
                     d1s.lsuid
              FROM mdl_enrol_d1_students d1s';
      $d1s = $DB->get_records_sql($sql);
      foreach ($d1s as $student) {
          $instancetime = microtime(true);
          $remotestudent = self::fetch_remote_idnumbers($student, $s, $token);

	  $lsuid = isset($remotestudent->getStudentResult->student->schoolPersonnelNumber) ?
                   $remotestudent->getStudentResult->student->schoolPersonnelNumber : '';

          $lsuid = str_replace('-', '', $lsuid);

          if ($student->lsuid == $lsuid) {
              mtrace("$student->id: $student->d1id's LSUIDs match ($student->lsuid = $lsuid), moving on.");
          } else if ($student->lsuid == '' && $lsuid <> '') {
              mtrace("$student->id: $student->d1id's LSUID is missing ($lsuid), updating now.");
              $updated = self::update_interstitial_lsuids($student, $lsuid);
          } else if ($lsuid <> '') {
              mtrace("$student->id: $student->d1id's LSUIDs do not match ($student->lsuid =/= $lsuid), updating now.");
              $updated = self::update_interstitial_lsuids($student, $lsuid);
          } else {
              mtrace("$student->id: $student->d1id's Remote LSUID not set, skipping.");
          }
          $currenttime = microtime(true);
          $elapsed = round($currenttime - $instancetime, 1);
          mtrace("Took $elapsed seconds to process $student->d1id.");
	  if ($currenttime - $tokentime > 120) {
              $token = lsud1::get_token();
              $tokentime = microtime(true);
              echo"Token: $token";
              echo"\n";
          }
      }
  }

  public static function update_interstitial_lsuids($student, $lsuid) {
      global $DB;
      $sql = 'UPDATE mdl_enrol_d1_students s
                  SET s.lsuid = ' . $lsuid . ',
                      s.timemodified = UNIX_TIMESTAMP()
                  WHERE s.id = ' . $student->id;
      $return = $DB->execute($sql);
      return $return;
  } 

  public static function fetch_remote_idnumbers($student, $s, $token) {
      // Set the URL for the REST command to get our token.
      $url = "$s->wsurl/webservice/InternalViewRESTV2/student/objectId/$student->d1id?_type=json&informationLevel=full";

      $header = array('Content-Type: application/json',
                'sessionId: ' . $token);

      $curl = curl_init($url);

      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST, false);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

      $json_response = curl_exec($curl);

      // Set the HTTP code for debugging.
      $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      // Close the CURL handler.
      curl_close($curl);

      // Decode the response.
      $response = json_decode($json_response);
      return $response;
  }

  public static function get_missing_idnumbers($startstring, $fieldid) {
    global $DB;
    $sql = 'SELECT u.id AS userid,
              d1s.lsuid AS idnumber
            FROM mdl_user u
              INNER JOIN mdl_enrol_d1_students d1s ON u.id = d1s.userid AND d1s.lsuid LIKE "' . $startstring . '%"
              LEFT JOIN mdl_user_info_data uid ON d1s.userid = uid.userid
                AND u.id = uid.userid
                AND uid.fieldid = ' . $fieldid . '
            WHERE uid.id IS NULL';

    // Fetch the data.
    $data = $DB->get_records_sql($sql);

    // Return the data.
    return $data;
  }

  public static function get_moodle_idnumber($userid, $fieldid) {
    global $DB;

    // Set the table name.
    $table = 'user_info_data';

    // Build the conditions array.
    $conditions = array();
    $conditions['userid'] = $userid;
    $conditions['fieldid'] = $fieldid;

    // Fetch the data.
    $data = $DB->get_record($table, $conditions, $fields='*', $strictness=IGNORE_MISSING);

    // Return the data.
    return $data;
  }

  public static function update_moodle_idnumbers($fieldid) {
    global $DB;

    $sql = 'UPDATE mdl_user u
              INNER JOIN mdl_enrol_d1_students d1s ON u.id = d1s.userid AND d1s.lsuid LIKE "89%"
              INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                AND d1s.lsuid != uid.data
                AND uid.fieldid = ' . $fieldid . '
            SET uid.data = d1s.lsuid';

    $idnumbers = $DB->execute($sql);
    return $idnumbers;
    }

  public static function update_moodle_idnumber($dataid, $idnumber) {
    global $DB;

    // Set the table name.
    $table = 'user_info_data';

    // Build the data object.
    $dataobject = new stdClass();
    $dataobject->id = $dataid;
    $dataobject->data = $idnumber;

    // Update the data.
    $data = $DB->update_record($table, $dataobject, $bulk=false);

    // Return if we were successful or not.
    return $data;
  }

  public static function insert_moodle_idnumber($userid, $fieldid, $idnumber) {
    global $DB;

    // Set the table name.
    $table = 'user_info_data';

    // Build the data object.
    $dataobject = new stdClass();
    $dataobject->userid = $userid;
    $dataobject->fieldid = $fieldid;
    $dataobject->data = $idnumber;
    $dataobject->dataformat = 0;

    // Update the data.
    $data = $DB->insert_record($table, $dataobject, $returnid=true, $bulk=false);

    // Return the user_info_data.id if we were successful.
    return $data;
  }

}

?>
