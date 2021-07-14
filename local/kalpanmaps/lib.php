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
        $cm->section = course_add_cm_to_section($module->course,$module->coursemodule,$module->section, $kalturaitem->cmid);

        // Update the cm.
        $DB->update_record('course_modules', $cm);

        return $module;
    }













    /**
     * Master function for moving kaltura video iframes panotpo.
     *
     * For every kaltura embed in the DB
     * A new panopto iframe will be created
     * To replace the existing kaltura iframe
     * In the same resource or activity.
     *
     * @return boolean $success
     */
    public function run_convert_kalembeds() {
        global $CFG, $DB;

        // Set up verbose logging preference.
        $verbose = $CFG->local_kalpanmaps_verbose;

        // Start the log.
        if ($verbose) {
            mtrace("We are in verbose mode and have begun converting kaltura iframe embeds.");
        } else {
            mtrace("Converting kaltura iframe embeds.");
        }

        // Let's be sure the table exists before we do anything.
        $tableexists = ($DB->get_manager()->table_exists('local_kalpanmaps'));

        // If the table exists, convert any outstanding embeds.
        $success = $tableexists ? self::conv_panitems($verbose) : false;

        // Log out what happened.
        if ($success) {
            mtrace("Successfully converted all remaining kaltura ifram embeds.");
        } else {
            mtrace("The process has completed. Any errors would be listed above.");
        }

        return $success;
    }

    /**
     * Function for grabbing label data where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_label($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT l.id AS id,
                       l.course AS courseid,
                       l.intro AS itemdata,
                       "label" AS tble,
                       "intro" AS dataitem
                   FROM mdl_label l
                   WHERE l.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing page data where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_page_content($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT p.id AS id,
                       p.course AS courseid,
                       p.content AS itemdata,
                       "page" AS tble,
                       "content" AS dataitem
                   FROM mdl_page p
                   WHERE p.content REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing page intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_page_intro($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT p.id AS id,
                       p.course AS courseid,
                       p.intro AS itemdata,
                       "page" AS tble,
                       "intro" AS dataitem
                   FROM mdl_page p
                   WHERE p.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing assignment intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_assign($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT a.id AS id,
                       a.course AS courseid,
                       a.intro AS itemdata,
                       "assign" AS tble,
                       "intro" AS dataitem
                   FROM mdl_assign a
                   WHERE a.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing course sections where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_course_sections($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT cs.id AS id,
                       cs.course AS courseid,
                       cs.summary AS itemdata,
                       "course_sections" AS tble,
                       "summary" AS dataitem
                   FROM mdl_course_sections cs
                   WHERE cs.summary REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing quiz intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_quiz($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT q.id AS id,
                       q.course AS courseid,
                       q.intro AS itemdata,
                       "quiz" AS tble,
                       "intro" AS dataitem
                   FROM mdl_quiz q
                   WHERE q.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing book intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_book_intro($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT b.id AS id,
                       b.course AS courseid,
                       b.intro AS itemdata,
                       "book" AS tble,
                       "intro" AS dataitem
                   FROM mdl_book b
                   WHERE b.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing book chapter content where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_book_chapters($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT bc.id AS id,
                       b.course AS courseid,
                       bc.content AS itemdata,
                       "book_chapters" AS tble,
                       "content" AS dataitem
                   FROM mdl_book_chapters bc
                       INNER JOIN mdl_book b ON b.id = bc.bookid
                   WHERE bc.content REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing forum intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_forum($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT f.id AS id,
                       f.course AS courseid,
                       f.intro AS itemdata,
                       "forum" AS tble,
                       "intro" AS dataitem
                   FROM mdl_forum f
                   WHERE f.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing lesson intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_lesson($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT l.id AS id,
                       l.course AS courseid,
                       l.intro AS itemdata,
                       "lesson" AS tble,
                       "intro" AS dataitem
                   FROM mdl_lesson l
                   WHERE l.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing lesson page contents where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_lesson_pages($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT lp.id AS id,
                       l.course AS courseid,
                       lp.contents AS itemdata,
                       "lesson_pages" AS tble,
                       "contents" AS dataitem
                   FROM mdl_lesson_pages lp
                       INNER JOIN mdl_lesson l ON l.id = lp.lessonid
                   WHERE lp.contents REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing journal intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_journal($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT j.id AS id,
                       j.course AS courseid,
                       j.intro AS itemdata,
                       "journal" AS tble,
                       "intro" AS dataitem
                   FROM mdl_journal j
                   WHERE j.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing choice intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_choice($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT c.id AS id,
                       c.course AS courseid,
                       c.intro AS itemdata,
                       "choice" AS tble,
                       "intro" AS dataitem
                   FROM mdl_choice c
                   WHERE c.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing feedback intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_feedback($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT f.id AS id,
                       f.course AS courseid,
                       f.intro AS itemdata,
                       "feedback" AS tble,
                       "intro" AS dataitem
                   FROM mdl_feedback f
                   WHERE f.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing glossary intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_glossary($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT g.id AS id,
                       g.course AS courseid,
                       g.intro AS itemdata,
                       "glossary" AS tble,
                       "intro" AS dataitem
                   FROM mdl_glossary g
                   WHERE g.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing group choice intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_choicegroup($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT gc.id AS id,
                       gc.course AS courseid,
                       gc.intro AS itemdata,
                       "choicegroup" AS tble,
                       "intro" AS dataitem
                   FROM mdl_choicegroup gc
                   WHERE gc.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing LTI intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_lti($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT l.id AS id,
                       l.course AS courseid,
                       l.intro AS itemdata,
                       "lti" AS tble,
                       "intro" AS dataitem
                   FROM mdl_lti gc
                   WHERE l.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing questionnaire intros where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_questionnaire($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT q.id AS id,
                       q.course AS courseid,
                       q.intro AS itemdata,
                       "questionnaire" AS tble,
                       "intro" AS dataitem
                   FROM mdl_questionnaire q
                   WHERE q.intro REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing question text where kaltura iframes are present.
     *
     * @return array $kalitems
     */
    public static function get_kal_question($limit=0) {
        global $DB;

        // Build the SQL to grab items that have kaltura iframes in them.
        $gksql = 'SELECT qq.id AS id,
                       c.id AS courseid,
                       qq.questiontext AS itemdata,
                       "question" AS tble,
                       "questiontext" AS dataitem
                   FROM mdl_question qq
                       INNER JOIN mdl_question_categories qc ON qc.id = qq.category
                       INNER JOIN mdl_context ctx ON ctx.id = qc.contextid
                       INNER JOIN mdl_course c ON c.id = ctx.instanceid
                   WHERE ctx.contextlevel = 50
                       AND qq.questiontext REGEXP "<iframe id=.+</iframe>"';

        // Build the array of objects.
        $kalitems = array();
        $kalitems = $DB->get_records_sql($gksql, array(null, $limitfrom=0, $limit));

        // Return the array of objects.
        return $kalitems;
    }

    /**
     * Function for grabbing the panoptoid for a corresponding kalpanmaps kaltura entry_id.
     *
     * @return string $panoptoid
     */
    public static function get_kalpanmaps($entryid, $verbose) {
        global $DB;

        // Build the panoptoid.
        $parms = array('kaltura_id' => $entryid);
        $panoptoid = $DB->get_record('local_kalpanmaps', $parms);


        // Log the entryid and panoptoid accordingly.
        if ($verbose) {
            mtrace("        Retreived $panoptoid->panopto_id from DB with matching entryid $entryid.");
        }

        // Return the panotoid.
        return $panoptoid;
    }

    /**
     * Function for updating the table specified in the kalitem.
     *
     * @return bool $success
     */
    public static function write_panitem($kalitem) {
        global $DB;

        // Build the SQL as generically as we can for use in any context.
        $item = $kalitem->dataitem;
        $dataitem = new stdClass();

        $dataitem->id = $kalitem->id;
        $dataitem->$item = $kalitem->newitemdata;

        // Run it and store the status.
        $success = false;
        $success = $DB->update_record($kalitem->tble, $dataitem);

        return $success;
    }

    /**
     * Function for grabbing the iframe and requisite data for a specific kaltura item.
     *
     * @return object $kalmatches
     */
    public static function get_panmatches($kalitem, $verbose) {
        global $CFG;

        // Instantiate the new object.
        $kalmatches = new stdClass();

        // Replace any line breaks so we can ensure regex will work.
        $kalitem->itemdata = preg_replace( "/\r|\n/", "", $kalitem->itemdata);

        // Grab the original Kaltura iframe in it's entirety and add it to the object.
        preg_match('/(<iframe id=.+?entry_id=.+?<\/iframe>)/', $kalitem->itemdata, $matches);
        $kalmatches->oldiframe = $matches[1];

        // Rename "iframe" to a nonsensical "noframe" tag so we don't show up in future searches.
        $kalmatches->noframe = preg_replace('/iframe/', 'noframe', $kalmatches->oldiframe);

        // Grab the Kaltura entry_id and add it to the object.
        preg_match('/\<iframe id=.+?entry_id=(.+?)&.+?\<\/iframe\>/', $kalmatches->oldiframe, $matches);
        $kalmatches->entryid = $matches[1];

        // Grab the width and add it to the object.
        preg_match('/\<iframe id=.+?width="(.+?)".+?\<\/iframe\>/', $kalmatches->oldiframe, $matches);
        $kalmatches->width = isset($matches[1]) ? $matches[1] : $CFG->local_kalpanmaps_width;

        // Grab the height and add it to the object.
        preg_match('/\<iframe id=.+?height="(.+?)".+?\<\/iframe\>/', $kalmatches->oldiframe, $matches);
        $kalmatches->height = isset($matches[1]) ? $matches[1] : $CFG->local_kalpanmaps_height;

        // Grab anything that might be extra and add it to the object.
        preg_match('/\<iframe id=.+?\>(.*?)\<\/iframe\>/', $kalmatches->oldiframe, $matches);
        $kalmatches->ifxtra = isset($matches[1]) ? $matches[1] : '';

        // Log the iframe info in verbose mode.
        if ($verbose) {
            mtrace("    Found $kalitem->tble $kalitem->dataitem with iframe and entryid: $kalmatches->entryid, width: $kalmatches->width, height: $kalmatches->height in course: $kalitem->courseid.");
        }

        return $kalmatches;
    }

    /**
     * Function where the work gets done.
     *
     * @return bool
     */
    public static function conv_panitems($verbose) {
        global $CFG;

        $fails = 0;
        $successes = 0;

        // Populate the kalitems array.
        $kalitems = self::get_kal_label($limit=0);
        $kalitems = array_merge($kalitems, self::get_kal_page_intro($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_page_content($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_assign($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_course_sections($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_quiz($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_book_intro($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_book_chapters($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_forum($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_lesson($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_lesson_pages($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_journal($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_choice($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_feedback($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_glossary($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_choicegroup($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_lti($limit=0));
        $kalitems = array_merge($kalitems, self::get_kal_questionnaire($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_scorm($limit=0))
        // $kalitems = array_merge($kalitems, self::get_kal_survey($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_turnitin($limit=0))
        // $kalitems = array_merge($kalitems, self::get_kal_wiki($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_workshops($limit=0));
        // NO? $kalitems = array_merge($kalitems, self::get_kal_database($limit=0));

        if ($CFG->kalprocessstudents) {
        // $kalitems = array_merge($kalitems, self::get_kal_choice_options($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_choice_answers($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_journal_entries($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_forum_discussions($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_forum_posts($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_assignsubmission_onlinetext($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_lesson_answers($limit=0));
        // $kalitems = array_merge($kalitems, self::get_kal_glossary_entries($limit=0));
	// TODO: CACHING!!!!
        // $kalitems = array_merge($kalitems, self::get_kal_question($limit=0));
        }

        // Grab the panopto server.
        $panoptourl = get_config('block_panopto', 'server_name1');

        // The link we're using. Should this be a config item?
        $link = '/Panopto/Pages/Viewer.aspx?id=';

        /*
        echo'<xmp>';
        print_r($kalitems);
        echo'</xmp>';
        */

        // Loop through the kaltura items and do stuff.
        foreach ($kalitems as $kalitem) {

            if (!isset($kalitem->itemdata)) { return; }

            // Get an object for future use in building the new data entry.
            $panmatches = self::get_panmatches($kalitem, $verbose);

            /*
            echo'<xmp>';
            print_r($panmatches);
            echo'</xmp>';
            */

            if (!isset($panmatches->entryid)) { return; }

            // Get the corresponding panopto_id.
            $panoptoid = self::get_kalpanmaps($panmatches->entryid, $verbose);

            // Build the URL for the new iframe.
            $kalframe = 'https://' . $panoptourl . $link . $panoptoid->panopto_id . '&showtitle=false' . '&captions=true';

            if ($verbose) {
                mtrace("      Found iframe with kaltura entryid: $panmatches->entryid.");
            }

            /*
            echo'<xmp>';
            print_r($kalitem->itemdata);
            echo'</xmp>';
            echo'<xmp>';
            print_r($panmatches->oldiframe);
            echo'</xmp>';
            */

            // Replace the old iframe with the new one and a hidden version of itself.
            $kalitem->newitemdata = preg_replace('/\<iframe id="kaltura_player".+?entry_id=' .
                                          $panmatches->entryid .
                                          '.+?\<\/iframe\>/',
                                          '<iframe src="' .
                                          $kalframe .
                                          '" width="' .
                                          $panmatches->width .
                                          '" height="' .
                                           $panmatches->height .
                                          '">' .
                                          $panmatches->ifxtra .
                                          '</iframe>' .
                                          '<!--HIDDEN ' .
                                          $panmatches->noframe .
                                          ' HIDDEN-->',
                                          $kalitem->itemdata, 1);

            /*
            echo'<xmp>';
            print_r($panmatches->noframe);
            echo'</xmp>';
            echo'<xmp>';
            print_r($kalitem->newitemdata);
            echo'</xmp>';
            */

            // Update the record with the new iframe and hidden noframe.
            if (self::write_panitem($kalitem)) {
                // increment our successes.
                $successes++;

                // Log that we've done it in verbose mode or just update the page with a period.
                if ($verbose) {
                    mtrace("    Replaced $kalitem->tble $kalitem->dataitem kaltura entry_id: $panmatches->entryid iframe with Panopto id: $panoptoid->panopto_id in course $kalitem->courseid.");
                } else {
                    mtrace(".");
                }
            } else {
                // Increment our failures.
                $fails++;

                // We have a failure, log it regardless of status.
                mtrace("  Conversion of $kalitem->tble.$kalitem->dataitem failed for kaltura entryid: $panmatches->entryid and panopto id: $panoptoid in courseid $kalitem->course.");
            }

        // Rebuild the course cache.
        rebuild_course_cache($kalitem->courseid, true);

        }


        // Log what we did.
        mtrace("\nSuccess: $successes\nFailures: $fails\nKaltura iframe conversion is complete for now.");
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
