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
 * CSV import of ProctorU coupon codes and GUILD user mapping.
 *
 * @package   block_pu
 * @copyright 2021 onwards LSUOnline & Continuing Education
 * @copyright 2021 onwards Robert Russo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/pu/classes/helpers.php');

/**
 * Building the class for the task to be run during scheduled tasks.
 */
class pu_import_helper {

    /**
     * Base function for importing the coupon code data.
     *
     * @package   block_pu
     * @return    @bool
     *
     */
    public static function block_pu_codeimport() {
        // For later.
        global $CFG;

        // Set the filename variable from CFG.
        $filename = $CFG->block_pu_ccfile;
    
        // Load the content based on the filename / location.
        $content = self::block_pu_getcccontent($filename);
    
        // Import the CSV into the DB.
        $success = self::block_pu_ccimport($content);
    
        return $success;
    }

    /**
     * Base function for importing the coupon code data.
     *
     * @package   block_pu
     * @return    @bool
     *
     */
    public static function block_pu_guildimporter() {
        // For later.
        global $CFG;

        // Set the filename variable from CFG.
        $filename = $CFG->block_pu_guildfile;
    
        // Load the content based on the filename / location.
        $content = self::block_pu_getguildcontent($filename);
    
        // Import the CSV into the DB.
        $success = self::block_pu_guildimport($content);
    
        return $success;
    }

    /**
     * Loops through data and calls block_pu_ccfield2db.
     *
     * @package   block_pu
     * @param     @array $content
     *
     */
    public static function block_pu_ccimport($content) {
    
        // Set the counter for later.
        $counter = 0;
    
        // Set the start time for later.
        $starttime = microtime(true);
    
        // Start the cli log.
        echo("Importing coupon code data\n");
    
        // Loop through the content.
        foreach ($content as $line) {
    
            // Set the fields based on data from the line.
            $fields = array_map('trim', $line);

            // If we have an empty bit, skip it.
            if (!empty($fields[0])) {
    
                // Add the data to the DB.
                $success = self::block_pu_ccfield2db($fields);
    
                if ($success) {
                    // Increment the counter by one.
                    $counter++;
                }
            }
        }
    
        // Calculate the elapsed time.
        $elapsedtime = round(microtime(true) - $starttime, 1);
    
        // Finish the log, letting me know how many we did and how long it took.
        echo("Completed importing " . $counter . " rows of data in " . $elapsedtime . " seconds.\n");
    
        return $success;
    }

    /**
     * Marks everybody inactive so we can re-import them 
     * and set their status accordingly.
     *
     * @package   block_pu
     * @return    @bool
     *
     */
    public static function block_pu_guild_inactivate() {
        global $DB;

        // Set up the SQL for updating the table.
        $sql = 'UPDATE {block_pu_guildmaps}
                SET current = 0';

        // Run it.
        $return = $DB->execute($sql);

        return $return;
    }

    /**
     * Reset unused ProctorU coupon codes for invalid users.
     *
     * @package   block_pu
     * @return    @bool
     *
     */
    public static function block_pu_code_unmap() {
        global $DB;

        // Set the table.
        $maptable = 'mdl_block_pu_guildmaps';
        $cmtable = 'mdl_block_pu_codemaps';
        $codetable = 'mdl_block_pu_codes';

        // SQL to grab data.
        $sql = 'SELECT pcm.id AS pcmid,
                       pgm.user AS pgmuser,
                       pgm.course AS pgmcourse,
                       pc.id AS pcid,
                       pc.couponcode AS pccode,
                       pc.used AS pcused,
                       pc.valid AS pcvalid
                FROM mdl_block_pu_guildmaps pgm
                    INNER JOIN mdl_block_pu_codemaps pcm ON pgm.id = pcm.guild
                    INNER JOIN mdl_block_pu_codes pc ON pc.id = pcm.code
                WHERE pgm.current = 0
                    AND pc.valid = 1
                    AND pc.used = 0';


        // Get array of userids who are not current GUILD students.
        $orphans = $DB->get_records_sql($sql);
 
        // Set up the SQL for updating the table.
        foreach ($orphans as $orphan) {

            // Delete code mapping table rows with this.
            $dsql = "DELETE FROM {$cmtable} WHERE id = $orphan->pcmid";

            // Update codes table rows with this.
            $usql = "UPDATE {$codetable} SET valid = 1 WHERE id = $orphan->pcid";

            if ($orphan->pcvalid == 0) {
                // We should never be here, but if we are, add this back to the pool.
                $freeme = $DB->execute($usql);

                // If we've updated any rows, log it.
                if (isset($freeme)) {
                    echo("Dissacociated ProctorU coupon code: $orphan->pccode with id: $orphan->pcid and marked it valid.\n");
                }
            }

            // Delete any unused coupon code mappings for non-current GUILD students.
            $deleteme = $DB->execute($dsql);

            if ($deleteme) {
                echo("Deleted non-current code mapping: $orphan->pcmid for userid: $orphan->pgmuser in course: $orphan->pgmcourse.\n");
            }
        }

        return true;
    }

    /**
     * Loops through data and calls block_pu_guildfield2db.
     *
     * @package   block_pu
     * @param     @array $content
     *
     */
    public static function block_pu_guildimport($content) {
        // Set the counter for later.
        $counter = 0;
    
        // Set the start time for later.
        $starttime = microtime(true);

        // Deactivate everybody prior to import.
        $inactivate = self::block_pu_guild_inactivate();

        // Let the log know what we've done.
        if ($inactivate) {
            echo("Deactivated existing GUILD mappings\n");
        }
    
        // Start the log.
        echo("Importing GUILD mapping data\n");
    
        // Loop through the content.
        foreach ($content as $line) {
    
            // Set the fields based on data from the line.
            $fields = array_map('trim', $line);
    
            // If we have an empty bit, skip it.
            if (!empty($fields[0]) && !empty($fields[1])) {
    
                // Add the data to the DB.
                $success = self::block_pu_guildfield2db($fields);
    
                if ($success) {
                    // Increment the counter by one.
                    $counter++;
                }
            }
        }
    
        // Calculate the elapsed time.
        $elapsedtime = round(microtime(true) - $starttime, 1);
    
        // Finish the log, letting me know how many we did and how long it took.
        echo("Completed importing " . $counter . " rows of data in " . $elapsedtime . " seconds.\n");
    
        return $success;
    }

    /**
     * Gets the content from the filename and location.
     *
     * @package   block_pu
     * @param     @string $filename
     * @return    @array $content
     *
     */
    public static function block_pu_getcccontent($filename) {
            // Grab the CSV from the file specified.
            $content = array_map('str_getcsv', file($filename));
    
            return $content;
    }
    
    /**
     * Gets the content from the filename and location.
     *
     * @package   block_pu
     * @param     @string $filename
     * @return    @array $content
     *
     */
    public static function block_pu_getguildcontent($filename) {
            // Grab the CSV from the file specified.
            $content = array_map('str_getcsv', file($filename));
    
            return $content;
    }

    /**
     * Maps the fields to the data object for insert_record.
     *
     * @package   block_pu
     * @param     @array $fields
     * @return    @int $return
     *
     */
    public static function block_pu_ccfield2db($fields) {
        global $DB;
    
        $return = '';
        // Set this up for later.
        $data = array();

        // Short circuit this if we find a header row.
        if ($fields[1] == 'Code') {
            echo("We found a header row and skipped it.\n");
            return false;
        }
    
        // Populate the data.
        $data['couponcode'] = $fields[1];

        // What table do we want the data in.
        $table = 'block_pu_codes';
    
        $exists = $DB->get_record($table, $data);
    
        if (!$exists) {
            // Insert the data and return the id of the newly inserted row.
            $return = $DB->insert_record($table, $data, $returnid = true, $bulk = false);
    
            // Log the imports.
            echo("  Imported coupon code: " .
                $data['couponcode'] .
                " into block_pu_codes id: " .
                $return .
                ".\n");
        } else {
    
            // Log the skipped ones too.
            echo("  Skipped existing coupon code: " .
                $data['couponcode'] .
                "\n");
        }
    
        // Return the block_pu_codes row id even though we don't use it.
        return $return;
    }

    /**
     * Maps the fields to the data object for insert_record.
     *
     * @package   block_pu
     * @param     @array $fields
     * @return    @int
     *
     */
    public static function block_pu_guildfield2db($fields) {
        global $CFG, $DB;

        // Set this up for later.
        $d = array();
        $data = array();

        // Short circuit this if we find a header row.
        if ($fields[1] == 'PersonID') {
            echo("We found a header row and skipped it.\n");
            return false;
        }

        // Populate the data.
        $d['courseidnumber'] = $fields[0];
        $d['useridnumber'] = $fields[1];
    
        if ($CFG->block_pu_profile_field == 'pu_idnumber') {

            $usersql = 'SELECT u.id AS userid
                        FROM mdl_user u
                        WHERE u.idnumber = ' . $d['useridnumber'];

        } else {
            $field = $DB->get_record('user_info_field', array('shortname' => $CFG->block_pu_profile_field));

        // Build some sql for grabbing users with a custom profile field based identifier.
            $usersql = 'SELECT u.id AS userid
                            FROM mdl_user u
                        INNER JOIN mdl_user_info_data ud ON ud.userid = u.id
                            AND ud.fieldid = ' . $field->id .
                          ' AND ud.data <> ""
                        WHERE ud.data = ' . $d['useridnumber'];
        }

        $return = '';
    
        // Set this for later. 
        $coursetable = 'course';
    
        // Get the course object based on the identifier.
        $course = $DB->get_record($coursetable, array('shortname' => $d['courseidnumber']));

        // Get the user object based on the identifier.
        $user = $DB->get_record_sql($usersql);

        if (!isset($user->userid)) {
            return false;
        }
    
        // Start to build the $data array.
        $data['user'] = $user->userid;
        $data['course'] = $course->id;
    
        // What table do we want the data in.
        $table = 'block_pu_guildmaps';
    
        // Check to see if the course / user pair exists in the table.
        $exists = $DB->get_record($table, $data);

        // If they do not exist.
        if (!$exists) {
            // Insert the data and return the id of the newly inserted row.
            $return = $DB->insert_record($table, $data, $returnid = true, $bulk = false);
    
            // Log the imports.
            echo("  Imported GUILD course: " .
                $data['course'] .
                " with student: " .
                $data['user'] .
                " into block_pu_guildmaps id: " .
                $return .
                ".\n");

        // If they do exist.
        } else {
            // They exist but will not be current. Here, we make sure they are.
            $data['id'] = $exists->id;
            $data['current'] = 1;
    
            // Update the record.
            $return = $DB->update_record($table, $data, $bulk = false);
    
            // Log the updated ones too.
            echo("  Updated existing GUILD course: " .
                $data['course'] .
                " with student: " .
                $data['user'] .
                " and marked them current." .
                "\n");
        }

        // Return the block_pu_guildmaps row id even though we don't use it.
        return $return;
    }

    /**
     * Gets the numnber of coupon codes left.
     *
     * @package   block_pu
     * @return    @int
     *
     */
    public static function pu_codesleft() {
        global $DB;

        // Set up the SQL.
        $sql = 'SELECT COUNT(pc.id) AS codesleft
                FROM mdl_block_pu_codes pc
                  LEFT JOIN mdl_block_pu_codemaps pcm ON pcm.code = pc.id
                WHERE pc.valid = 1
                  AND pc.used = 0
                  AND pcm.id IS NULL';

        // Get the data object.
        $codesleft = $DB->get_record_sql($sql);

        // Return the relevant data.
        return $codesleft->codesleft;
    }

    /**
     * Emails admins when the number of coupon codes drops below
     * a predetermined threshold.
     *
     * @package   block_pu
     *
     */
    public static function block_pu_codeslow() {
        global $CFG;

        // Get the code count.
        $codesleft = (int)self::pu_codesleft();

        // Get the minimum number of codes allowed.
        $mincodes = (int)$CFG->block_pu_mincodes;

        $threshold = $codesleft - $mincodes;
        $absv = abs($threshold);

        // Log the data.
        $emailalert = '';
        $emailalert .= "There are $codesleft codes left with a minimum of $mincodes specified. \n";

        // Check to see fi we need to request more codes.
        if ($threshold < 1) {

            // Add some qualifiers if we need codes.
            $emailalert .= "We have used $absv more codes than expected. \n\n";
            $emailalert .= "Please add more codes as soon as possible. \n";

            // Email the alert.
            self::email_ccalert($emailalert);
        }

        // Log the alert.
        echo($emailalert . "\n");
    }

    /**
     * Contructs and send the email using Moodle functionality.
     *
     * @package   block_pu
     * @param     @string $emailalert.
     *
     */
    public static function email_ccalert($emailalert) {
        global $CFG, $DB;

        // Get email content from email log.
        $emailcontent = $emailalert;

        // Grab the list of usernames from Moodle.
        $usernames = explode(",", $CFG->block_pu_code_admin);

        // Set up the users array.
        $users = array();

        // Loop through the usernames and add each user object to the user array.
        foreach ($usernames as $username) {

            // Make sure we have no spaces.
            $username = trim($username);

            // Add the user object to the array.
            $users[] = $DB->get_record('user', array('username' => $username));
        }

        // Send an email to each of the above users.
        foreach ($users as $user) {

            // Email the message.
            email_to_user($user,
                "ProctorU Code Administrator",
                sprintf('!!!Minumum # of codes exceeded for %s!!!',
                $CFG->wwwroot),
                $emailcontent);
        }
    }
}
