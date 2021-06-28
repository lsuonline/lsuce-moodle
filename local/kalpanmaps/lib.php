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
 * kalpanmaps lib.
 *
 * Class for building scheduled task functions
 * for fixing core and third party issues
 *
 * @package    local_kalpanmaps
 * @copyright  2021 onwards LSUOnline & Continuing Education
 * @copyright  2021 onwards Robert Russo
 */

defined('MOODLE_INTERNAL') or die();

// Building the class for the task to be run during scheduled tasks.
class kalpanmaps {

    public $emaillog;

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

        // Set up verbose logging preference.
        $verbose = $CFG->local_kalpanmaps_verbose;

        // Let's be sure the table exists before we do anything.
        $tableexists = ($DB->get_manager()->table_exists('local_kalpanmaps'));

        // We need this for controlling item visibility.
        if (!function_exists('set_coursemodule_visible')) {
            require_once($CFG->dirroot . "/course/lib.php");
        }

        // SQL to grab remaining kaltura items to convert
        $kpsql = 'SELECT kr.id AS krid,
            km.kaltura_id AS "kalturaid",
            km.panopto_id AS "panoptoid",
            kr.course AS "courseid",
            km.id AS kmid,
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
            INNER JOIN {course_sections} cs ON cs.course = kr.course AND cs.id = cm.section
            LEFT JOIN {url} u ON kr.course = u.course AND kr.name = u.name AND u.externalurl LIKE CONCAT("%", km.panopto_id , "%")
	WHERE u.id IS NULL
        GROUP BY kr.id, kr.course';
          

        // SQL to grab visible previously converted kaltura video resources for future hiding.
        $donesql = 'SELECT cm.id AS cmid
        FROM {local_kalpanmaps} km
            INNER JOIN {kalvidres} kr ON km.kaltura_id = kr.entry_id
            INNER JOIN {course_modules} cm ON cm.course = kr.course AND cm.instance = kr.id
            INNER JOIN {modules} m ON m.name = "kalvidres" AND cm.module = m.id
            INNER JOIN {url} u ON kr.course = u.course AND kr.name = u.name AND u.externalurl LIKE CONCAT("%", km.panopto_id , "%")
	WHERE cm.visible = 1
        GROUP BY cm.id';

        // If the table exists, use a standard moodle function to get records from the above SQL.
        $kpdata = $tableexists ? $DB->get_records_sql($kpsql) : null;

        // Set the start time so we can log how long this takes.
        $starttime = microtime(true);

        // Start feeding data into the logger.
        $this->log("Beginning the process of converting Kaltura Video Resources to Panopto urls.");

        // Set up some counts.
        $converted = 0;
        $hidden = 0;

        // Don't do anything if we don't have any items to work with.
        if ($kpdata) {
            $this->log("    Converting Kaltura Video Resource to Panoptp url.");

            // Loops through and actually does the conversions.
            foreach ($kpdata as $kalturaitem) {
                // Increment the converted count.
                $converted++;

                // Log stuff depending on the verbosity preferences.
                if ($verbose) {
                    $this->log("        Converting Kaltura itemid: " . $kalturaitem->kalturaid . ".");
                    $this->log("            Ceating new url for Kaltura itemid: " . $kalturaitem->kalturaid . ".");
                } else {
                    $eol = ($converted % 50) == 0 ? PHP_EOL : " ";
                    if ($eol == PHP_EOL) {
                        mtrace("Created " . $converted . " entries.", $eol);
                    } else {
                        mtrace(".", $eol);
                    }
                }

                // We have not yet converted all kaltura items in this course, convert the next one.
                self::build_url($kalturaitem);

                // Hide the corresponding kalura item if configured to do so and it's not already hidden.
                if ($kalturaitem->modvis == 1 && $CFG->local_kalpanmaps_kalvidres_conv_hide == 1) {

                    // Actually hide the item.
		    set_coursemodule_visible($kalturaitem->cmid, 0, $visibleoncoursepage = 1);

                    // Increment the hidden count.
                    $hidden++;

                    if ($verbose) {
                        $this->log("                Hiding old kaltura item: " . $kalturaitem->kalturaid . 
                                   " with already existing url in courseid: " . $kalturaitem->courseid . ".");
                    }
                }

                if ($verbose) {
                    $this->log("            Finished creating the new url with panopto id: " . 
                               $kalturaitem->panoptoid . " and hiding the old kaltura item with id: " . 
                               $kalturaitem->krid  . ".");
                    $this->log("        Panopto url itemid: " . $kalturaitem->panoptoid . " has been created.");
                }
            }

            // We're done with conversions.
            $this->log("\n    Completed converting Kaltura Video Resource items to Panopto urls.");
            $this->log("Finished converting outstanding Kaltura Video Resources to panopto urls.");

            // How long in seconds did this conversion job take.
            $elapsedtime = round(microtime(true) - $starttime, 3);
            $this->log("The process to convert Kaltura Video Resources to Panopto urls took " . 
                       $elapsedtime . " seconds.");

        } else {
            // We did not have anything to do.
            $this->log("No outstanding Kaltura Video Resources.");
        }

        // Grab an array of objects with previously converted kaltura item's courseids.
        $dones = $DB->get_records_sql($donesql);

        // If we're hiding previously converted kalvidres, let's do it.
        if ($CFG->local_kalpanmaps_kalvidres_postconv_hide == 1 && $dones) {

            // Loop through the converted visible items.
            foreach ($dones as $done) {

                // Hide them.
                set_coursemodule_visible($done->cmid, 0, $visibleoncoursepage = 1);

                // Increment the hidden value for our count later.
                $hidden++;
            }
        }

        // Get some counts in the logs depending if we hide KalVidRes items or not.
        if (($CFG->local_kalpanmaps_kalvidres_conv_hide == 1 || $CFG->local_kalpanmaps_kalvidres_postconv_hide == 1) && ($hidden - $converted > 0)) {
            $this->log("Converted " . $converted . " KalVidRes items and hid " . $hidden . " KalVidRes items.");
        } else if ($CFG->local_kalpanmaps_kalvidres_conv_hide == 1) {
            $this->log("Converted " . $converted . " Kaltura Video Resources and hid them.");
        } else {
           $this->log("Converted " . $converted . " Kaltura Video Resources.");
        }

        // Send an email to administrators regarding this.
        if ($converted + $hidden > 0) {
            $this->email_clog_report_to_admins();
        }
    }

    /**
     * Function for building the cm for the new url.
     *
     * For every url created, a new course module
     * will be built here.
     *
     * @return $newcm
     */
    public static function build_course_module($kalturaitem) {
        global $DB;

        // Gets the course object from the courseid.
        $course = get_course($kalturaitem->courseid);

        // Get the id for the url module.
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'url'));

        // Build the course module info.
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

        // Build the course module itself.
        $newcm->id = self::add_cm($newcm);

        return $newcm;
    }

    /**
     * Function for adding the cm to moodle for the new url.
     *
     * For every url created, a new course module
     * will be added here.
     *
     * @return $cmid
     */
    public static function add_cm($newcm) {
        global $DB;

        // Set the time for the new course module.
        $newcm->added = time();

        // Make sure we have no preconceptions about a cmid.
        unset($newcm->id);

        // Add the record and set / store the id.
        $cmid = $DB->insert_record("course_modules", $newcm);

        // Rebuild the course cache.
        rebuild_course_cache($newcm->course, true);
        return $cmid;
    }

    /**
     * Function for building and adding the new url to moodle.
     *
     * @return $module
     */
    public static function build_url($kalturaitem) {
        global $CFG, $DB;

        // Prerequisites.
        if (!function_exists('url_add_instance')) {
            require_once($CFG->dirroot . '/mod/url/lib.php');
        }
        if (!function_exists('set_coursemodule_visible')) {
            require_once($CFG->dirroot . "/course/lib.php");
        }

        // Set some variables up for later.
        $panoptourl = get_config('block_panopto', 'server_name1');
        $config = get_config('url');
        $parms = '" width="' . $kalturaitem->itemwidth . '" height="' . $kalturaitem->itemheight . '"';
        $link = '/Panopto/Pages/Viewer.aspx?id=';

        // Build the course module and set the cmid.
        $cm = self::build_course_module($kalturaitem);

        // Build the module here.
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

        // Build the url and set the url id.
        $module->id = url_add_instance($module, null);

        // Now that we have the url, we can finish setting up the cm.
        $cm->instance = $module->id;

        // Add the course module to a specific section matching the old kalvidres.
        $cm->section = course_add_cm_to_section($module->course,$module->coursemodule,$module->section);

        // Update the cm.
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
     * print during cron run and prep log data for emailling
     *
     * @param $what: data being sent to $this->log
     */
    private function log($what) {
        mtrace($what);

        $this->emaillog[] = $what;
    }
}
