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
 * The only natsane function.
 * For every item that is weighted NATURAL extra credit in a non-excluded semester.
 * Sets the aggregationcoef to 1
 * Sets the aggregationcoef2 to 0
 * Sets the weightoverride to 1
 * Sets the needsupdate flag to 1
 *
 * @package    local_natsane
 * @copyright  2017 Robert Russo, Louisiana State University
 */

defined('MOODLE_INTERNAL') or die();

// Building the class for the task to be run during scheduled tasks
class natsane {

    /**
     * Master function for natural EC weight fixing called in the scheduled task
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
            $this->email_log_report_to_admins();
    }

    /**
     * Emails a log report to admin users
     *
     * @return void
     */
    private function email_log_report_to_admins() {
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
     * print during cron run and prep log data for emailling
     *
     * @param $what: data being sent to $this->log
     */
    public function log($what) {
        mtrace($what);

        $this->emaillog[] = $what;
    }
}
