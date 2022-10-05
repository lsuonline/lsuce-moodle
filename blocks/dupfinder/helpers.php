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

defined('MOODLE_INTERNAL') || die();

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
        $userstarttime = "";

        foreach ($xml as $student) {
            $userstarttime = microtime(true);
            $counter++;
            $sql = "SELECT id, u.username, \"$student->PRIMARY_ACCESS_ID\" AS mismatch,
                    u.email, u.lastname, u.firstname, u.idnumber, \"$student->DEPT_CODE\" AS department,
                    \"$student->COURSE_NBR\" AS number, \"$student->SECTION_NBR\" AS section,
                    \"$student->INDIV_NAME\" AS fullname
                FROM mdl_user u
                WHERE (u.idnumber = \"$student->LSU_ID\" AND u.username <> \"$student->PRIMARY_ACCESS_ID\")
                    OR (u.username = \"$student->PRIMARY_ACCESS_ID\" AND u.idnumber <> \"$student->LSU_ID\")";

            $dupe = $DB->get_records_sql($sql);
            if (!empty($dupe)) {
                // echo("Found duplicate student: $student->PRIMARY_ACCESS_ID at $counter.\n");
                mtrace("Found duplicate student: $student->PRIMARY_ACCESS_ID at $counter.\n");
                $dupes[] = array_merge($dupe);
            }
            $sqls[] = $sql;
            $userelapsedtime = round(microtime(true) - $userstarttime, 3);
            // mtrace("User #$count ($user->username) took " . $userelapsedtime . " seconds to process.\n");
        }
        return $dupes;
    }

    public function gettestdata() {
        error_log(" \n\n ");
        error_log(" \n gettestdata() -> loading up the test XML file. \n ");
        error_log(" \n\n ");
        $xml = simplexml_load_file("/Users/davidlowe/Sites/scp_temp_transfer/20221S-HIST.xml") or die("Error: Cannot create object");
        return $xml;
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

    public function emailduplicates($dupes = array()) {
        global $PAGE;

        $dupcount = count($dupes);
        if ($dupcount > 1) {
            error_log("\n\n there are $dupcount duplicates that need fixing, need to email admin \n");
        } else {
            error_log("\n\n there are NO DUPES to report........ \n ");
        }

        // All admins.
        $alladmins   = get_config('block_dupfinder', 'emailalladmins');

        // Send emails from the noreply user.
        $emailFrom = core_user::get_noreply_user();

        if ($alladmins) { // Add Moodle Administrators.
            $admins = get_admins();
        } else {          // Only main Moodle Administrator.
            $admins = [get_admin()];
        }


        // Get To: users.
        if ($alladmins) { // Add Moodle Administrators.
            $admins = get_admins();
        } else {          // Only main Moodle Administrator.
            $admins = [get_admin()];
        }

        $message = "Hello Administrator,".PHP_EOL."The following users were found in the system with missing data.". PHP_EOL;
        $message .= PHP_EOL;
        $templatedata = array();
        error_log(PHP_EOL. " Build the message......". PHP_EOL);
        $subject = "Dup Finder anomolies";
        $output = $PAGE->get_renderer('block_dupfinder');
        $renderable = new \block_dupfinder\output\manual_view($dupes, false);
        $message = $output->render($renderable);

        $debug = false;

        // Send email message to the desired Moodle administrators.
        if (!empty($admins)) { // For each admin...
            foreach ($admins as $admin) {
                if ($debug) { // Debug mode - Does not actually send emails.
                    // Just display what we got.                    
                    error_log(PHP_EOL.PHP_EOL);
                    error_log(PHP_EOL. $admin->email);
                    error_log(PHP_EOL. $emailFrom->email);
                    error_log(PHP_EOL. "SUBJECT: $subject");
                    error_log(PHP_EOL. "TEXT MESSAGE: ". html_to_text($message));
                    error_log(PHP_EOL. "HTML MESSAGE: $message");
                    error_log(PHP_EOL.PHP_EOL);
                } else {      // Actually send the email.
                    email_to_user($admin, $emailFrom, $subject, html_to_text($message), $message);
                }
            }    
        }
    }
}
