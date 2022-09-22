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
 *
 * @copyright 2021 onwards LSUOnline & Continuing Education
 * @copyright 2021 onwards Robert Russo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

// Require config and CLIlib.
// require_once('/var/www/html/39/config.php');
require_once('../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Make sure we are not in maintenance mode.
if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active, import execution suspended.\n";
    exit(1);
}

class helpers {

    public function objectify($xml) {
        if (isset($xml)) {
            $objects = simplexml_load_string($xml);
            return $objects;
        } else {
            $error = 'Failed to open data stream.';
            return $error;
        }
    }

/*
      ["LSU_ID"]=>
      ["DEPT_CODE"]=>
      ["COURSE_NBR"]=>
      ["SECTION_NBR"]=>
      ["CREDIT_HRS"]=>
      ["INDIV_NAME"]=>
      ["PRIMARY_ACCESS_ID"]=>
      ["WITHHOLD_DIR_FLG"]=>
*/
    public function finddupes($xml) {
        global $DB;

        $counter = 0;
        $sqls = array();
        $dupes = array();
        foreach ($xml as $student) {
            $counter++;
            $sql = "SELECT u.username, \"$student->PRIMARY_ACCESS_ID\" AS mismatch, u.email, u.lastname, u.firstname, u.idnumber, \"$student->DEPT_CODE\" AS department, \"$student->COURSE_NBR\" AS number, \"$student->SECTION_NBR\" AS section, \"$student->INDIV_NAME\" AS fullname  FROM mdl_user u WHERE (u.idnumber = \"$student->LSU_ID\" AND u.username <> \"$student->PRIMARY_ACCESS_ID\") OR (u.username = \"$student->PRIMARY_ACCESS_ID\" AND u.idnumber <> \"$student->LSU_ID\")";
            $dupe = $DB->get_records_sql($sql);
            if (!empty($dupe)) {
                echo("Found duplicate student: $student->PRIMARY_ACCESS_ID at $counter.\n");
                $dupes[] = array_merge($dupe);
            }
            $sqls[] = $sql;
        }
        return $dupes;
    }

    /**
     * Grabs the xml from DAS.
     *
     * @return @string $xml
     */
    public function getdata() {
        global $CFG;

        // Get the data needed.
        $semester   = get_config('block_dupfinder', 'semester');
        $department = get_config('block_dupfinder', 'department');
        $session    = get_config('block_dupfinder', 'session');
        $username   = get_config('block_dupfinder', 'username');
        $password   = get_config('block_dupfinder', 'password');
        $debugloc   = get_config('block_dupfinder', 'debugloc');
        $debugging  = $CFG->debugdisplay == 1 ? 1 : 0;

        // Set the URL for the REST command to get our enrollment data.
        $url = "https://das.lsu.edu/data_access_service/DynamicSqlServlet?widget1=$username&widget2=$password&serviceId=MOODLE_STUDENTS_BY_DEPT&1=01&2=$semester&3=$department&4=1590&5=$session";

        // Set up the CURL handler.
        $curl = curl_init($url);

        // Set the CURL options.
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, false);

        // Grab the response.
        $xml = curl_exec($curl);

        // Set the HTTP code for debugging.
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close the curl handler.
        curl_close($curl);

        if ($debugging == 1) {
            // If there is a location set, use it.
            $loc = isset($debugloc) ? $debugloc : $CFG->dataroot;

            // Write this out to a file for debugging.
            $fp = fopen($loc . '/' . $semester . '-' . $department . '.xml', 'w');
            fwrite($fp, $xml);
            fclose($fp);
        }

        // Return the response.
        return($xml);
    }

}

$df = new helpers();

$data = $df->getdata();
$xml = $df->objectify($data);
$dupes = $df->finddupes($xml);

echo"\n";
var_dump($dupes);
echo"\n";
