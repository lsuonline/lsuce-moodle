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

    /**
     * Convert the XML file to an array of objects to be processed.
     *
     * @param array $xml xml file.
     * @return array user accounts.
     */
    public function objectify($xml) {
        if (isset($xml)) {
            $objects = simplexml_load_string($xml);
            return $objects;
        } else {
            $error = 'Failed to open data stream.';
            return $error;
        }
    }

    /**
     * Loop through the XML and match against the user records to find any possible
     * incomplete accounts.
     * @param array $xml a loaded xml file ready to be processed.
     * @param bool $hidetrace let's mtrace when running the task.
     * @return array of duplicate accounts.
     */
    public function finddupes($xml, $hidetrace = true) {
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
                $hidetrace ?: mtrace("Found duplicate student: $student->PRIMARY_ACCESS_ID at $counter.");
                $dupes[] = array_merge($dupe);
            }
            $sqls[] = $sql;
            $userelapsedtime = round(microtime(true) - $userstarttime, 3);
        }
        return $dupes;
    }

    /**
     * Test data to use while debugging and testing.
     *
     * @return @string $xml
     */
    public function gettestdata() {
        // This is a sample from my machine.
        $xml = simplexml_load_file("/Users/davidlowe/Sites/scp_temp_transfer/20221S-HIST.xml");
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
        $url = get_config('block_dupfinder', 'dataurl');

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

    /**
     * Grabs the xml from DAS.
     *
     * @return @string $xml
     */
    public function emailduplicates($dupes = array()) {
        global $PAGE;

        // All admins.
        $alladmins   = get_config('block_dupfinder', 'emailalladmins');

        // Send emails from the noreply user.
        $emailfrom = core_user::get_noreply_user();

        // Add Moodle Administrators.
        if ($alladmins) {
            $admins = get_admins();
        } else {
            // Only main Moodle Administrator.
            $admins = [get_admin()];
        }

        $message = "Hello Administrator,".PHP_EOL."The following users were found in the system with missing data.". PHP_EOL;
        $message .= PHP_EOL;
        $templatedata = array();

        $subject = "Dup Finder anomolies";
        $output = $PAGE->get_renderer('block_dupfinder');
        $renderable = new \block_dupfinder\output\manual_view($dupes, false);
        $message = $output->render($renderable);

        // Send email message to the desired Moodle administrators.
        if (!empty($admins)) {
            // For each admin...
            foreach ($admins as $admin) {
                if (debugging()) {
                    // Let's not send emails in debug mode.
                    mtrace(PHP_EOL.PHP_EOL);
                    mtrace(PHP_EOL. $admin->email);
                    mtrace(PHP_EOL. $emailfrom->email);
                    mtrace(PHP_EOL. "SUBJECT: $subject");
                    mtrace(PHP_EOL. "TEXT MESSAGE: ". html_to_text($message));
                    mtrace(PHP_EOL. "HTML MESSAGE: $message");
                    mtrace(PHP_EOL.PHP_EOL);
                } else {
                    return email_to_user($admin, $emailfrom, $subject, html_to_text($message), $message);
                }
            }
        }
    }
}
