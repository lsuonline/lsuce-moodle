<?php
/**
* landing block
*
* @date Nov 26 14
* @name David Lowe
* @package   blocks
*/

// The links that get provided are to other instances
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

require_once($CFG->dirroot . '/mod/assignment/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');

require_once($CFG->dirroot . '/blocks/landing_block/lib/browser.php');
// require_once($CFG->dirroot . '/local/utools/umoodle.php');

require_once($CFG->dirroot . '/blocks/landing_block/lib/LBLib.php');
require_once($CFG->dirroot . '/blocks/landing_block/lib/LBTemplates.php');

class block_landing_block extends block_base
{

    /** @var string */
    public $blockname = null;

    /** @var bool */
    protected $contentgenerated = false;

    /** @var bool|null */
    protected $docked = null;

    /* record if we have already generated the content */
    private $generated = false;

    /* ====================================================== */
    /* ====================================================== */
    /* ====================================================== */
    /* ====================================================== */

    public function init()
    {
        $this->title = get_string('pluginname', 'block_landing_block');
        $this->blockname = get_class($this);
    }

    /**
     * All multiple instances of this block
     * @return bool Returns false
     */
    function instance_allow_multiple()
    {
        return false;
    }

    /**
    * locations where block can be displayed
    *
    * @return array
    */
    public function applicable_formats()
    {
        if (has_capability('moodle/site:config', context_system::instance())) {
            return array('all' => true);
        } else {
            return array('site' => true);
        }
    }

    /**
     * Gets Javascript that may be required for navigation
     */
    function get_required_javascript()
    {
        parent::get_required_javascript();
        $arguments = array(
            'instanceid' => $this->instance->id
        );
        // $this->page->requires->string_for_js('viewallcourses', 'moodle');
        $this->page->requires->js_call_amd('block_landing_block/landingmain', 'init', $arguments);
    }

    /**
    * block contents
    *
    * @return object
    */
    public function get_content()
    {
        global $USER, $CFG, $OUTPUT, $DB, $PAGE;
        

        // this block get's called twice, let's remove this bloated double call
        if (isset($this->content)) {
            return $this->content;
        }

        // $PAGE->requires->js('/blocks/landing_block/js/main.js');
        $PAGE->requires->css('/blocks/landing_block/styles/main.css');

        $landing_info = new LBLib();
        $template = new LBTemplates();

        /* First check if we have already generated, don't waste cycles */
        if ($this->generated === true) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        // $renderer = $this->page->get_renderer($this->blockname);
        // Let's determine if we are using crappy IE
        $browser_info = new Browser;
        $is_explorer = false;
        if ($browser_info->getBrowser() == "Internet Explorer") {
            if ($browser_info->getVersion() < 10) {
                $is_explorer = true;
            }
        }
        
        // grab urls from Landing Block Settings page
        $landings = array(
            'current_title' => array(
                $CFG->block_landing_block_showCurrUrl,
                $CFG->block_landing_block_currentUrl,
                $CFG->block_landing_block_current_title
            ),
            'long_term_title' => array(
                $CFG->block_landing_block_showLongTermUrl,
                $CFG->block_landing_block_longTermUrl,
                $CFG->block_landing_block_longterm_title
            ),
            'past_title' => array(
                $CFG->block_landing_block_showPastUrl,
                $CFG->block_landing_block_pastUrl,
                $CFG->block_landing_block_past_title
            ),
            'future_title' => array(
                $CFG->block_landing_block_showFutureUrl,
                $CFG->block_landing_block_futureUrl,
                $CFG->block_landing_block_future_title
            ),
        );

        // print the main title bar, this is NEEDED for the AJAX calls as it
        // injects the username into the DOM
        $this->content->text = $template->printLandingBar($USER->username);


        foreach ($landings as $name => $landing) {
            // break the landing object into pieces
            list($show, $url, $url_title) = $landing;
            $this_loop_site_url = null;
            if (!$show || !$url) {
                continue;
            }

            // build the full url based on the landing block settings
            if ($landing_info->isLocal()) {
                $this_loop_site_url = "http://".$url;
            } else {
                $this_loop_site_url = "https://".$url;
            }

            /* ================================================================== */
            /* ============== IF INTERNET EXPLORER GO THROUGH HERE ============== */
            /* ================================================================== */
            // if ($is_explorer) {
            //     error_log("Going this this muckity muck");
            //     $template->getIEDisplay($name, $this_loop_site_url, $url_title);
            //     continue;
            // }
            /* ================================================================== */

            // we want to expand the current semester
            $coll = false;
            $html_all_courses_this_term = "<i class='fa fa-spinner fa-spin fa-2' aria-hidden='true'></i> One Moment Please......";

            // if ($name == "current_title") {
            // }

            // $this_loop_site_url will be based on landing block settings
            if ($landing_info->getCurrentSite() == $this_loop_site_url) {
                // we always want to expand the current server your on. So if your on long_term then it should
                // open the long term courses. If current semester then those courses should show......and so on.
                $coll = true;

                // if (debugging()) {
                //     error_log("\n\n");
                //     error_log("\n================================================================================");
                //     error_log("\nThis loop_url: ". $this_loop_site_url. " IS local");
                //     error_log("\n================================================================================\n\n");
                // }

                // stopped here
                // user is on current term server so just grab data directly from DB, no AJAZ HERE
                if ($DB->record_exists('user', array('username' => $USER->username))) {
                    $uid = $DB->get_field('user', 'id', array('username' => $USER->username));
                    // get full user object so that when we query the DB from now on, we
                    // are doing it as the user.  This means we'll only see things the
                    // user can, but also that we'll get the status of their courses,
                    // not some non-existant user.
                    // **** NOTE, this line below breaks the "Customize this page" feature ****
                    // $USER = get_complete_user_data('id', $uid);
                    $tab = "timeline";
                    $renderable = new \block_landing_block\output\main($tab);
                    $renderer = $this->page->get_renderer('block_landing_block');

                    if (isset($CFG->block_landing_block_use_new_course_overview) && $CFG->block_landing_block_use_new_course_overview == 1) {
                        $this->content->text .= $template->printNewShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll, true, $renderer, $renderable);
                    } else {
                        try {
                            $courses = enrol_get_users_courses($uid);
                            $html_all_courses_this_term = $template->printCourses($courses, $name, $coll, $this_loop_site_url);
                            $this->content->text .= $template->printShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll, true);

                        } catch (Exception $ex) {
                            $this->content->text .= $template->printShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll);
                        }
                    }

                } else {
                    $this->content->text .= $template->printShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll);
                }
            } else {
                // This will require an AJAX call for the other instances
                // if (debugging()) {
                //     error_log("\n\n");
                //     error_log("\n================================================================================");
                //     error_log("\nThis loop_url: ". $this_loop_site_url. " is NOT local");
                //     error_log("\n================================================================================\n\n");
                // }

                if ($CFG->block_landing_block_use_ajax_to_load) {
                    // if (debugging()) {
                    //     error_log("\n");
                    //     error_log("\nGoing to use AJAX to fetch course data.\n");
                    // }
                    $this->content->text .= $template->printShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll);
                } else {
                    // if (debugging()) {
                    //     error_log("\n");
                    //     error_log("\nGoing to try and use cURL.\n");
                    // }

                    $uid = $DB->get_field('user', 'id', array('username' => $USER->username));
                    $instance_data = array($name, $coll, $this_loop_site_url, $url_title, $uid, $USER->username);

                    try {
                        $resulting_html = $landing_info->get_landing_content($instance_data, $url);
                        if ($resulting_html != false) {
                            $this->content->text .= $resulting_html;
                        } else {
                            // if (debugging()) {
                            //     error_log("\n");
                            //     error_log("\ncURL complained that $this_loop_site_url isn't up to date so we are going to use AJAX as a last resort........\n");
                            // }
                            $this->content->text .= $template->printShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll);
                        }

                    } catch (Exception $ex) {
                        // if (debugging()) {
                        //     error_log("\nTry catch FAILED, going to use AJAX as a last resort........\n\n");
                        // }
                        $this->content->text .= $template->printShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll);
                    }
                }
            }
        }
        return $this->content;
    }


    /*
    * allow the block to have a configuration page
    *
    * @return boolean
    */
    function has_config()
    {
        return true;
    }

    /**
     * Used to no display the header
     * @return bool
     */
    function hide_header()
    {
        return true;
    }

    /**
     * The block cannot be hidden by default as it is integral to
     * the navigation of Moodle.
     *
     * @return false
     */
    function instance_can_be_hidden()
    {
        return false;
    }

    /**
     * The block cannot be edited by the user as it is integral to the
     * navigation of UofL's Moodle.
     *
     * @return false
     */
    function user_can_edit()
    {
        return false;
    }
}
