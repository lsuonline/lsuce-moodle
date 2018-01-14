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
 * natsane lib.
 *
 * Class for building scheduled task functions
 * for fixing core and third party issues
 *
 * @package    local_natsane
 * @copyright  2017 Robert Russo, Louisiana State University
 */

defined('MOODLE_INTERNAL') or die();

// Building the class for the task to be run during scheduled tasks
class natsane {

    public $emaillog;

    /**
     * Master function for natural EC weight fixing called in the scheduled task
     *
     * For every item that is weighted NATURAL extra credit in a non-excluded semester.
     * Sets the aggregationcoef to 1
     * Sets the aggregationcoef2 to 0
     * Sets the weightoverride to 1
     * Sets the needsupdate flag to 1
     *
     * @return boolean
     */
    public function run_fix_courses() {
        global $CFG, $DB;

        // Maybe convert this into a setting to avoid hardcoding the value. Revisit if it becomes an issue.
        $startdate = 1502686800;

        // Grabs all natural extra credit grade items which are weighted
        // LSU does not want any weighting for extra credit items
        // Limits based on configured values for isemester ids
        $itemsql = 'SELECT DISTINCT(gi.id), gi.courseid FROM {course} c
                                       INNER JOIN {grade_items} gi on c.id = gi.courseid
                                       INNER JOIN {grade_categories} gc ON gi.categoryid = gc.id
                                       LEFT JOIN {enrol_ues_sections} sec ON sec.idnumber = c.idnumber AND c.idnumber IS NOT NULL and c.idnumber <> ""
                                       LEFT JOIN {enrol_ues_semesters} sem ON sec.semesterid = sem.id 
                                       WHERE gc.aggregation = 13
                                       AND gi.gradetype = 1
                                       AND gi.itemtype <> "course"
                                       AND gi.itemtype <> "category"
                                       AND gi.aggregationcoef = 1
                                       AND gi.aggregationcoef2 <> 0
                                       AND (sem.classes_start >= ' . $startdate . ' OR sem.id IS NULL)';

        // Standard moodle function to get records from the above SQL.
        $items = $DB->get_records_sql($itemsql);

        // Setting up the arrays to use later.
        $itemids = array();
        $courseids = array();

        //Set the start time so we can log how long this takes.
        $start_time = microtime();

        //Start feeding data into the logger
        $this->log("Beginning the process of fixing grade items.");

        // Don't do anything if we don't have any items to work with.
        if ($items) {
            // Creates arrays from the list of Grade Item ids and Course ids.
            foreach ($items as $itemid) {
                $itemids[] = $itemid->id; 
                $courseids[] = $itemid->courseid; 
            }

            // Loops through and fixes the weighting for the EC grade items with questionable weights.
            $this->log("    Fixing grade items.");
            foreach ($itemids as $itemid) {
                $this->log("        Fixing itemid: " . $itemid . ".");
                $this->log("            Setting aggregationcoef to 1.00000 for " . $itemid . ".");
                $DB->set_field('grade_items', 'aggregationcoef', 1.00000, array('id'=>$itemid));
                $this->log("            Setting aggregationcoef2 to 0.00000 for " . $itemid . ".");
                $DB->set_field('grade_items', 'aggregationcoef2', 0.00000, array('id'=>$itemid));
                $this->log("            Setting weightoverride to 1 for " . $itemid . ".");
                $DB->set_field('grade_items', 'weightoverride', 1, array('id'=>$itemid));
                $this->log("        Itemid: " . $itemid . " is fixed.");
            }
            $this->log("    Completed fixing grade items.");
            $this->log("    Updating needsupdate flags.");

            // Loops through and sets the needsupdate flags for all grade items in courses impacted by the issue.
            foreach ($courseids as $courseid) {
                $this->log("        Setting needsupdate to 1 for the course: " . $courseid . ".");
                $DB->set_field('grade_items', 'needsupdate', 1, array('courseid'=>$courseid));
            }

            $this->log("    Completed setting needsupdate flags.");
            $this->log("Finished fixing grade items.");

            //How long in hundreths of a second did this job take
            $elapsed_time = round(microtime()-$start_time,2);
            $this->log("The process to fix weighted natural extra-credit grades took " . $elapsed_time . " seconds.");
        } else {

            //We did not have anything to do
            $this->log("No grade items to fix.");
        }

        //Send an email to administrators regarding this
        $this->email_nlog_report_to_admins();
    }


    /**
     * Master function for fixing restored kaltura videos and submissions
     *
     * fixes link in kaltura video resources with missing sources
     * fixes link in kaltura assignment submissions with missing sources
     * fixes errant uiconf_id for restored resources and presentations
     *
     * @return boolean
     */
    public function run_fix_kaltura() {
        global $CFG, $DB;

        // Get a count of each of the problem kaltura items.
        $kal_count = 'SELECT (SELECT COUNT(id) FROM {kalvidres} res WHERE (res.source IS NULL OR res.source = "") AND res.entry_id <> "") AS "num_res_source",
                             (SELECT COUNT(id) FROM {kalvidpres} pres WHERE (pres.source IS NULL OR pres.source = "") AND pres.entry_id <> "") AS "num_pres_source",
                             (SELECT COUNT(id) FROM {kalvidassign_submission} sub WHERE (sub.source IS NULL OR sub.source = "") AND sub.entry_id <> "") AS "num_sub_source",
                             (SELECT COUNT(id) FROM {kalvidres} res WHERE res.uiconf_id <> "1" AND res.uiconf_id <> "30928192") AS "num_uiconf_res",
                             (SELECT COUNT(id) FROM {kalvidpres} pres WHERE pres.uiconf_id <> "1" AND pres.uiconf_id <> "30928192") AS "num_uiconf_pres"';

        // Fix restored kaltura resources
        // Updates DB to ensure the source url is appropriate for restored
        $source_updates_res = 'UPDATE {kalvidres} res SET res.source = CONCAT("http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/", res.entry_id, "/showDescription/true/showTitle/true/showTags/true/showDuration/true/showOwner/true/showUploadDate/false/playerSize/400x365/playerSkin/30928192/") WHERE (res.source IS NULL OR res.source = "") AND res.entry_id <> ""';
        $source_updates_pres = 'UPDATE {kalvidpres} pres SET pres.source = CONCAT("http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/", pres.entry_id, "/showDescription/false/showTitle/false/showTags/false/showDuration/false/showOwner/false/showUploadDate/false/playerSize/400x365/playerSkin/30928192/") WHERE (pres.source IS NULL OR pres.source = "") AND pres.entry_id <> ""';
        $source_updates_sub = 'UPDATE {kalvidassign_submission} sub SET sub.source = CONCAT("http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/", sub.entry_id, "/showDescription/true/showTitle/true/showTags/true/showDuration/true/showOwner/true/showUploadDate/false/embedType/oldEmbed/playerSize/800x600/playerSkin/35393992/") WHERE (sub.source IS NULL OR sub.source = "") AND sub.entry_id <> ""';

        // Updates DB to ensure the uiconf_id is appropriate for restored videos
        $uiconf_updates_res = 'UPDATE {kalvidres} res SET res.uiconf_id = "30928192" WHERE res.uiconf_id <> "1" AND res.uiconf_id <> "30928192"';
        $uiconf_updates_pres = 'UPDATE {kalvidpres} pres SET pres.uiconf_id = "30928192" WHERE pres.uiconf_id <> "1" AND pres.uiconf_id <> "30928192"';

        
        // Get the count of records needing to be fixed.
        $count = $DB->get_record_sql($kal_count);
        $totalcount = ($count->num_res_source + $count->num_pres_source + $count->num_sub_source + $count->num_uiconf_res + $count->num_uiconf_pres);

        // Short circuit the scheduled task if there's nothing to fix.
        if ($totalcount == 0) {return true;}

        // Now that we know we're going to fix some stuff, let's begin.
        // Set the start time so we can log how long this takes.
        $start_time = microtime();

        //Logs for email and Fixes kaltura resource source links
        $this->log("Beginning the process of fixing kaltura videos.");
        // Make sure we have resources to fix.
        if ($count->num_res_source > 0) {
            $this->log("    Fixing Kaltura resources.");
            $this->log("        Setting Kaltura resource source values appropriately.");
            $DB->execute($source_updates_res, null);
            $this->log("        All " . $count->num_res_source . " Kaltura resource source values have been fixed.");
            $this->log("    All Kaltura resources fixed.");
        }
        //Logs for email and Fixes kaltura presentation source links
        $this->log("Beginning the process of fixing kaltura video presentations.");
        // Make sure we have presentations to fix.
        if ($count->num_pres_source > 0) {
            $this->log("    Fixing Kaltura presentations.");
            $this->log("        Setting Kaltura presentation source values appropriately.");
            $DB->execute($source_updates_pres, null);
            $this->log("        All " . $count->num_pres_source . " Kaltura presentation source values have been fixed.");
            $this->log("    All Kaltura presentations fixed.");
        }
        // Make sure we have submissions to fix.
        if ($count->num_sub_source > 0) {
            $this->log("    Fixing Kaltura assigment submissions.");
            $this->log("        Setting Kaltura assignment submission source values appropriately.");
            $DB->execute($source_updates_sub, null);
            $this->log("        All " . $count->num_sub_source . " Kaltura assignment submission source values have been fixed.");
            $this->log("    All Kaltura assignment submissions fixed.");
        }

        //Fixes the uiconf_id for errant kaltura items
        // Make sure we have resource uiconf_ids to fix (these should be relatively rare).
        if ($count->num_uiconf_res > 0) {
            $this->log("    Fixing kaltura uiconf resource entries.");
            $DB->execute($uiconf_updates_res, null);
            $this->log("    All " . $count->num_uiconf_res . " Kaltura uiconf resource entries have been updated.");
        }
        // Make sure we have presentation uiconf_ids to fix (these should be VERY rare now that Kaltura has abandoned them).
        if ($count->num_uiconf_pres > 0) {
            $this->log("    Fixing kaltura uiconf presentation entries.");
            $DB->execute($uiconf_updates_pres, null);
            $this->log("    All " . $count->num_uiconf_pres . " Kaltura uiconf presentation entries have been updated.");
        }
        $this->log("Completed fixing Kaltura items.");

        // How long in hundreths of a second did this job take?
        $elapsed_time = round(microtime()-$start_time,3);
        $this->log("The process to fix kaltura items took " . $elapsed_time . " seconds.");

        //Send an email to administrators regarding the status of the job.
        $this->email_klog_report_to_admins();
    }


    /**
     * Emails a natural log report to admin users
     *
     * @return void
     */
    private function email_nlog_report_to_admins() {
        global $CFG;

        // get email content from email log
        $email_content = implode("\n", $this->emaillog);

        // send to each admin
        $users = get_admins();
        foreach ($users as $user) {
            $replyto = '';
            email_to_user($user, "Fix Natural Grades", sprintf('Natural EC grade fixes for [%s]', $CFG->wwwroot), $email_content);
        }
    }

    /**
     * Emails a kaltura log report to admin users
     *
     * @return void
     */
    private function email_klog_report_to_admins() {
        global $CFG;

        // get email content from email log
        $email_content = implode("\n", $this->emaillog);

        // send to each admin
        $users = get_admins();
        foreach ($users as $user) {
            $replyto = '';
            email_to_user($user, "Fix Kaltura items", sprintf('Kaltura item fixes for [%s]', $CFG->wwwroot), $email_content);
        }
    }

    /**
     * print during cron run and prep log data for emailling
     *
     * @param $what: data being sent to $this->log
     */
    private function log($what) {
        mtrace($what);

        $this->emaillog[] = $what;
    }
}
