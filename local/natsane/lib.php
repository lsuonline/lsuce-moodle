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

// Building the class for the task to be run during scheduled tasks.
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

        // Grabs all natural extra credit grade items which are weighted.
        // LSU does not want any weighting for extra credit items.
        // Limits based on configured values for isemester ids.
        $itemsql = 'SELECT DISTINCT(gi.id), gi.courseid FROM {course} c
                                       INNER JOIN {grade_items} gi on c.id = gi.courseid
                                       INNER JOIN {grade_categories} gc ON gi.categoryid = gc.id
                                       LEFT JOIN {enrol_ues_sections} sec ON sec.idnumber = c.idnumber
                                                AND c.idnumber IS NOT NULL
                                                AND c.idnumber <> ""
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

        // Set the start time so we can log how long this takes.
        $starttime = microtime();

        // Start feeding data into the logger.
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
                $DB->set_field('grade_items', 'aggregationcoef', 1.00000, array('id' => $itemid));
                $this->log("            Setting aggregationcoef2 to 0.00000 for " . $itemid . ".");
                $DB->set_field('grade_items', 'aggregationcoef2', 0.00000, array('id' => $itemid));
                $this->log("            Setting weightoverride to 1 for " . $itemid . ".");
                $DB->set_field('grade_items', 'weightoverride', 1, array('id' => $itemid));
                $this->log("        Itemid: " . $itemid . " is fixed.");
            }
            $this->log("    Completed fixing grade items.");
            $this->log("    Updating needsupdate flags.");

            // Loops through and sets the needsupdate flags for all grade items in courses impacted by the issue.
            foreach ($courseids as $courseid) {
                $this->log("        Setting needsupdate to 1 for the course: " . $courseid . ".");
                $DB->set_field('grade_items', 'needsupdate', 1, array('courseid' => $courseid));
            }

            $this->log("    Completed setting needsupdate flags.");
            $this->log("Finished fixing grade items.");

            // How long in hundreths of a second did this job take.
            $elapsedtime = round(microtime() - $starttime, 2);
            $this->log("The process to fix weighted natural extra-credit grades took " . $elapsedtime . " seconds.");
        } else {

            // We did not have anything to do.
            $this->log("No grade items to fix.");
        }

        // Send an email to administrators regarding this.
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
        $kalcount = 'SELECT (SELECT COUNT(id)
                              FROM {kalvidres} res
                              WHERE (res.source IS NULL OR res.source = "")
                                    AND res.entry_id <> "") AS "num_res_source",
                             (SELECT COUNT(id)
                              FROM {kalvidpres} pres
                              WHERE (pres.source IS NULL OR pres.source = "")
                                    AND pres.entry_id <> "") AS "num_pres_source",
                             (SELECT COUNT(id)
                              FROM {kalvidassign_submission} sub
                              WHERE (sub.source IS NULL OR sub.source = "")
                                    AND sub.entry_id <> "") AS "num_sub_source",
                             (SELECT COUNT(id) FROM {kalvidres} res
                              WHERE res.uiconf_id <> "1"
                                    AND res.uiconf_id <> "30928192") AS "num_uiconf_res",
                             (SELECT COUNT(id)
                              FROM {kalvidpres} pres
                              WHERE pres.uiconf_id <> "1"
                                    AND pres.uiconf_id <> "30928192") AS "num_uiconf_pres"';

        // Fix restored kaltura resources. Updates DB to ensure the source url is appropriate for restored.
        $sourceupdatesres = 'UPDATE {kalvidres} res
                             SET res.source = CONCAT("http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/"
                                , res.entry_id
                                , "/showDescription/true/showTitle/true/showTags/true/showDuration/true/showOwner/true/showUploadDate/false/playerSize/400x365/playerSkin/30928192/")
                             WHERE (res.source IS NULL OR res.source = "")
                                AND res.entry_id <> ""';
        $sourceupdatespres = 'UPDATE {kalvidpres} pres
                              SET pres.source = CONCAT("http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/"
                                , pres.entry_id
                                , "/showDescription/false/showTitle/false/showTags/false/showDuration/false/showOwner/false/showUploadDate/false/playerSize/400x365/playerSkin/30928192/")
                              WHERE (pres.source IS NULL OR pres.source = "")
                                AND pres.entry_id <> ""';
        $sourceupdatessub = 'UPDATE {kalvidassign_submission} sub
                             SET sub.source = CONCAT("http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/"
                                , sub.entry_id
                                , "/showDescription/true/showTitle/true/showTags/true/showDuration/true/showOwner/true/showUploadDate/false/embedType/oldEmbed/playerSize/800x600/playerSkin/35393992/")
                             WHERE (sub.source IS NULL OR sub.source = "")
                                AND sub.entry_id <> ""';

        // Updates DB to ensure the uiconf_id is appropriate for restored videos.
        $uiconfupdatesres = 'UPDATE {kalvidres} res
                             SET res.uiconf_id = "30928192"
                             WHERE res.uiconf_id <> "1"
                                AND res.uiconf_id <> "30928192"';
        $uiconfupdatespres = 'UPDATE {kalvidpres} pres
                              SET pres.uiconf_id = "30928192"
                              WHERE pres.uiconf_id <> "1"
                                AND pres.uiconf_id <> "30928192"';

        // Get the count of records needing to be fixed.
        $count = $DB->get_record_sql($kalcount);
        $totalcount = ($count->num_res_source
                        + $count->num_pres_source
                        + $count->num_sub_source
                        + $count->num_uiconf_res
                        + $count->num_uiconf_pres);

        // Short circuit the scheduled task if there's nothing to fix.
        if ($totalcount == 0) {
            return true;
        }

        // Now that we know we're going to fix some stuff, let's begin.
        // Set the start time so we can log how long this takes.
        $starttime = microtime();

        // Logs for email and Fixes kaltura resource source links.
        $this->log("Beginning the process of fixing kaltura videos.");
        // Make sure we have resources to fix.
        if ($count->num_res_source > 0) {
            $this->log("    Fixing Kaltura resources.");
            $this->log("        Setting Kaltura resource source values appropriately.");
            $DB->execute($sourceupdatesres, null);
            $this->log("        All " . $count->num_res_source . " Kaltura resource source values have been fixed.");
            $this->log("    All Kaltura resources fixed.");
        }
        // Logs for email and Fixes kaltura presentation source links.
        $this->log("Beginning the process of fixing kaltura video presentations.");
        // Make sure we have presentations to fix.
        if ($count->num_pres_source > 0) {
            $this->log("    Fixing Kaltura presentations.");
            $this->log("        Setting Kaltura presentation source values appropriately.");
            $DB->execute($sourceupdatespres, null);
            $this->log("        All " . $count->num_pres_source . " Kaltura presentation source values have been fixed.");
            $this->log("    All Kaltura presentations fixed.");
        }
        // Make sure we have submissions to fix.
        if ($count->num_sub_source > 0) {
            $this->log("    Fixing Kaltura assigment submissions.");
            $this->log("        Setting Kaltura assignment submission source values appropriately.");
            $DB->execute($sourceupdatessub, null);
            $this->log("        All " . $count->num_sub_source . " Kaltura assignment submission source values have been fixed.");
            $this->log("    All Kaltura assignment submissions fixed.");
        }

        // Fixes the uiconf_id for errant kaltura items.
        // Make sure we have resource uiconf_ids to fix (these should be relatively rare).
        if ($count->num_uiconf_res > 0) {
            $this->log("    Fixing kaltura uiconf resource entries.");
            $DB->execute($uiconfupdatesres, null);
            $this->log("    All " . $count->num_uiconf_res . " Kaltura uiconf resource entries have been updated.");
        }
        // Make sure we have presentation uiconf_ids to fix (these should be VERY rare now that Kaltura has abandoned them).
        if ($count->num_uiconf_pres > 0) {
            $this->log("    Fixing kaltura uiconf presentation entries.");
            $DB->execute($uiconfupdatespres, null);
            $this->log("    All " . $count->num_uiconf_pres . " Kaltura uiconf presentation entries have been updated.");
        }
        $this->log("Completed fixing Kaltura items.");

        // How long in hundreths of a second did this job take?
        $elapsedtime = round(microtime() - $starttime, 3);
        $this->log("The process to fix kaltura items took " . $elapsedtime . " seconds.");

        // Send an email to administrators regarding the status of the job.
        $this->email_klog_report_to_admins();
    }

    /**
     * Master function for moving kaltura video assignments to urls.
     *
     * For every kalvidassign, the following will be created:
     * A new url in the same course section.
     * A link to the corresponding panopto video.
     *
     * @return boolean
     */
    public function run_convert_kalvidres() {
        global $CFG, $DB;

        // Let's be sure the table exists before we do anything.
        $tableexists = ($DB->get_manager()->table_exists('local_kalpanmaps'));

        // We need this for controlling item visibility.
        if (!function_exists('set_coursemodule_visible')) {
            require_once($CFG->dirroot . "/course/lib.php");
        }

        // SQL to grab kaltura items to convert..
        $kpsql = 'SELECT km.id AS kmid,
            km.kaltura_id AS "kalturaid",
            km.panopto_id AS "panoptoid",
            kr.course AS "courseid",
            kr.id AS krid,
            kr.name AS "itemname",
            kr.video_title AS "videotitle",
            kr.intro AS "intro",
            kr.height AS "itemheight",
            kr.width AS "itemwidth",
            cm.id AS cmid,
            cm.visible AS "modvis",
            cm.groupmode AS "groupmode",
            cm.groupingid AS "groupingid",
            cs.section AS "coursesection"
        FROM {local_kalpanmaps} km
            INNER JOIN {kalvidres} kr ON km.kaltura_id = kr.entry_id
            INNER JOIN {course_modules} cm ON cm.course = kr.course AND cm.instance = kr.id
            INNER JOIN {modules} m ON m.name = "kalvidres" AND cm.module = m.id
            INNER JOIN mdl_course_sections cs ON cs.course = kr.course AND cs.id = cm.section
        WHERE km.converted = 0';

        $donesql = 'SELECT u.id AS urlid,
                    u.course AS courseid,
                    km.id AS kmid
        FROM {local_kalpanmaps} km
            INNER JOIN {url} u ON u.externalurl LIKE CONCAT("%", km.panopto_id , "%")
        WHERE u.externalurl LIKE :exturl
            AND u.course = :cid';

        // If the table exists, use a standard moodle function to get records from the above SQL.
        $kpdata = $tableexists ? $DB->get_records_sql($kpsql) : null;

        // Set the start time so we can log how long this takes.
        $starttime = microtime(true);

        // Start feeding data into the logger.
        $this->log("Beginning the process of converting Kaltura Video Resources to Panopto urls.");

        // Don't do anything if we don't have any items to work with.
        if ($kpdata) {
            // Loops through and actually does the conversions.
            $this->log("    Converting Kaltura Video Resource to Panoptp url.");
            foreach ($kpdata as $kalturaitem) {
                // Grab an array of objects with previously converted kaltura item's courseids.
                $dones = $DB->get_records_sql($donesql,
                         ['cid' => $kalturaitem->courseid, 'exturl' => '%' . $kalturaitem->panoptoid . '%']);

                // Set this to false so we don't break unless needed.
                $found = false;

                // Loop through these previously converted items and exit if we've already converted this course.
                foreach ($dones as $done) {
                    // Check to see if we've converted this kmid for this course already.
                    if($done->courseid == $kalturaitem->courseid && $done->kmid == $kalturaitem->kmid) {
                        $this->log("        Skipping already converted Kaltura itemid: " . 
                                   $kalturaitem->kalturaid . " for courseid: " . 
                                   $kalturaitem->courseid . " with existing urlid: " . $done->urlid . ".");
                        $found = true;

                        // Hide the corresponding kalura item if configured to do so and it's not already hidden.
                        if ($kalturaitem->modvis == 1 && $CFG->local_natsane_kalvidres_postconv_hide == 1) {
		            set_coursemodule_visible($kalturaitem->cmid, 0, $visibleoncoursepage = 1);
                            $this->log("            Hiding old kaltura item: " . $kalturaitem->kalturaid . 
                                       " with already existing url: " . $done->urlid . " in courseid: " .
                                       $kalturaitem->courseid . ".");
                        }

                        // Get outta here.
                        break;
                    }
                }

                // If we've already converted this course, exit the loop.
                if ($found) {
                    break;
                }

                $this->log("        Converting Kaltura itemid: " . $kalturaitem->kalturaid . ".");
                $this->log("            Ceating new url for Kaltura itemid: " . $kalturaitem->kalturaid . ".");

                // We have not yet converted all kaltura items in this course, convert the next one.
                self::build_url($kalturaitem);

                // Hide the corresponding kalura item if configured to do so and it's not already hidden.
                if ($kalturaitem->modvis == 1 && $CFG->local_natsane_kalvidres_conv_hide == 1) {
		    set_coursemodule_visible($kalturaitem->cmid, 0, $visibleoncoursepage = 1);
                    $this->log("                Hiding old kaltura item: " . $kalturaitem->kalturaid . 
                               " with already existing url in courseid: " . $kalturaitem->courseid . ".");
                }

                // Rebuild the course cache JUST IN CASE.
                // rebuild_course_cache($kalturaitem->courseid, true);

                $this->log("            Finished creating the new url with panopto id: " . 
                           $kalturaitem->panoptoid . " and hiding the old kaltura item with id: " . 
                           $kalturaitem->krid  . ".");
                $this->log("        Panopto url itemid: " . $kalturaitem->panoptoid . " has been created.");
            }

            // We're done.
            $this->log("    Completed converting Kaltura Video Resource items to Panopto urls.");
            $this->log("Finished converting outstanding Kaltura Video Resources to panopto urls.");

            // How long in seconds did this job take.
            $elapsedtime = round(microtime(true) - $starttime, 3);
            $this->log("The process to convert Kaltura Video Resources to Panopto urls took " . 
                       $elapsedtime . " seconds.");
        } else {

            // We did not have anything to do.
            $this->log("No outstanding Kaltura Video Resources.");
        }

        // Send an email to administrators regarding this.
        $this->email_clog_report_to_admins();
    }

    public static function build_course_module($kalturaitem) {
        global $DB;

        $course = get_course($kalturaitem->courseid);
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'url'));

        $newcm = new stdClass;
        $newcm->course = $course->id;
        $newcm->module = $moduleid;
        $newcm->instance = 0;
        $newcm->section = 0;
        $newcm->idnumber = '';
        $newcm->visible = $kalturaitem->modvis;
        $newcm->visibleoncoursepage = $kalturaitem->modvis;
        $newcm->visibleold = $kalturaitem->modvis;
        $newcm->groupmode = $kalturaitem->groupmode;
        $newcm->groupmembersonly = 0;
        $newcm->groupingid = $kalturaitem->groupingid;
        $newcm->completion = 0;
        $newcm->completionview = 0;
        $newcm->completionexpected = 0;
        $newcm->showdescription = 0;
        $newcm->availability = null;

        $newcm->id = self::add_cm($newcm);

        return $newcm;
    }

    public static function add_cm($newcm) {
        global $DB;

        $newcm->added = time();
        unset($newcm->id);

        $cmid = $DB->insert_record("course_modules", $newcm);
        // rebuild_course_cache($newcm->course, true);
        return $cmid;
    }


    public static function build_url($kalturaitem) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $panoptourl = get_config('block_panopto', 'server_name1');
        $config = get_config('url');
        $cm = self::build_course_module($kalturaitem);
        $parms = '" width="' . $kalturaitem->itemwidth . '" height="' . $kalturaitem->itemheight . '"';
        $link = '/Panopto/Pages/Viewer.aspx?id=';

        $module = new stdClass;
        $module->course = $kalturaitem->courseid;
        $module->name = $kalturaitem->itemname;
        $module->intro = '<p>' . $kalturaitem->videotitle . '</p>' . $kalturaitem->intro;
        $module->externalurl = 'https://' . $panoptourl . $link . $kalturaitem->panoptoid;
        $module->introformat = FORMAT_HTML;
        $module->coursemodule = $cm->id;
        $module->section = $kalturaitem->coursesection;

        $module->display = $config->display;
        $module->popupwidth = $config->popupwidth;
        $module->popupheight = $config->popupheight;
        $module->printintro = $config->printintro;

        if (!function_exists('url_add_instance')) {
            require_once($CFG->dirroot . '/mod/url/lib.php');
        }

        $module->id = url_add_instance($module, null);
        $cm->instance = $module->id;
        $cm->section = course_add_cm_to_section($module->course,$module->coursemodule,$module->section);

        $DB->update_record('course_modules', $cm);

        return $module;
    }




    /**
     * Emails a kalvidres conversion log to admin users
     *
     * @return void
     */
    private function email_clog_report_to_admins() {
        global $CFG;

        // Get email content from email log.
        $emailcontent = implode("\n", $this->emaillog);

        // Send to each admin.
        $users = get_admins();
        foreach ($users as $user) {
            $replyto = '';
            email_to_user($user, "Kaltura Video Resource conversion", sprintf('Converting KalVidRes for [%s]', $CFG->wwwroot), $emailcontent);
        }
    }

    /**
     * Emails a natural log report to admin users
     *
     * @return void
     */
    private function email_nlog_report_to_admins() {
        global $CFG;

        // Get email content from email log.
        $emailcontent = implode("\n", $this->emaillog);

        // Send to each admin.
        $users = get_admins();
        foreach ($users as $user) {
            $replyto = '';
            email_to_user($user, "Fix Natural Grades", sprintf('Natural EC grade fixes for [%s]', $CFG->wwwroot), $emailcontent);
        }
    }

    /**
     * Emails a kaltura log report to admin users
     *
     * @return void
     */
    private function email_klog_report_to_admins() {
        global $CFG;

        // Get email content from email log.
        $emailcontent = implode("\n", $this->emaillog);

        // Send to each admin.
        $users = get_admins();
        foreach ($users as $user) {
            $replyto = '';
            email_to_user($user, "Fix Kaltura items", sprintf('Kaltura item fixes for [%s]', $CFG->wwwroot), $emailcontent);
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
