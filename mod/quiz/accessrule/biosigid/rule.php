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
 * Implementation of the quizaccess_biosigid plugin.
 *
 * @package    quizaccess_biosigid
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/biosigid/locallib.php');

class quizaccess_biosigid extends quiz_access_rule_base
{
    public static function make(quiz $quizobj, $timenow, $canignorelimits) {
        // check if quiz intercept is required
        if (empty($quizobj->get_quiz()->biosigrequired)) {
            return null;
        }

        if (property_exists($quizobj->get_quiz(), "browsersecurity")) {
            if ($quizobj->get_quiz()->browsersecurity === "lockdownbrowser") {
                return null;
            }
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Sets up the attempt (review or summary) page with any special extra
     * properties required by this rule. securewindow rule is an example of where
     * this is used.
     *
     * @param moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page) {
        global $USER;

        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/microsoft-signalr/3.1.3/signalr.min.js"></script>';

        $stagingdomain = 'staging-sandbox.verifyexpress.com';
        $configdomain = get_config('biosigid', 'domain');
        $biodomain = (!empty($configdomain)) ? $configdomain : $stagingdomain;

        $page->requires->js_call_amd('quizaccess_biosigid/config', 'init', array($biodomain));
        $page->requires->js_call_amd('quizaccess_biosigid/eye', 'init', array($USER->email, $page->url->get_path(), $biodomain));

    }

    /**
     * cleanup left over session data for quiz
     */
    public function current_attempt_finished() {
        global $SESSION;

        // get the course module id
        $cmid = $this->quiz->cmid;

        // cleanup session data
        if (!empty($SESSION->biosigidquizaccess[$cmid])) {
            unset($SESSION->biosigidquizaccess[$cmid]);
        }
    }

    /**
     * Access rule check
     */
    public function prevent_access() {
        global $SESSION, $PAGE, $CFG;

        // get the coursemodule id
        $cmid = $this->quiz->cmid;

        // get quiz config for timeout value
        $quiz_config = get_config('quizaccess_biosigid');

        // get optional parameter data
        $bsi_return_val = optional_param('r', NULL, PARAM_RAW);

        // check if instructor or higher, and do not add rule to chain
        $context = '';
        if ($CFG->branch >= 22) {
            $context = context_module::instance($cmid);
        } else {
            $context = get_context_instance(CONTEXT_MODULE, $cmid);
        }

        if (has_capability('moodle/course:manageactivities', $context)) {
            return false;
        }

        // we rely on the page url being set, die if for any reason its not available
        if (!$PAGE->has_set_url()) {
            // catastrophic failure
            die(get_string('bsi_generic_error', 'quizaccess_biosigid'));
        }

        // check if session needs to be set
        if ($bsi_return_val !== NULL && $PAGE->has_set_url()) {
            if ($PAGE->url->compare(new moodle_url('/mod/quiz/view.php'), URL_MATCH_BASE)) {
                $s = new StdClass();
                $s->r = $bsi_return_val;
                $s->e = time() + $quiz_config->timeout;
                $SESSION->biosigidquizaccess[$cmid] = $s;
            }
        }

        // check if session is set and act accordingly
        if ($bsi_return_val == NULL && !empty($SESSION->biosigidquizaccess[$cmid])) {
            // get the session data
            $sess = $SESSION->biosigidquizaccess[$cmid];

            // check if within grace period
            if (time() < $sess->e) {
                // get value from session
                $bsi_return_val = $sess->r;

                // check if we're not on view page
                if (!$PAGE->url->compare(new moodle_url('/mod/quiz/view.php'), URL_MATCH_BASE)) {
                    // sliding expiry timer
                    $sess->e = time() + $quiz_config->timeout;

                    // update session
                    $SESSION->biosigidquizaccess[$cmid] = $sess;
                }
            } else {
                // completely remove session
                unset($SESSION->biosigidquizaccess[$cmid]);
            }
        }

        // if we're not accessing the attempt page, allow a pass through (this would cover, view, summary and review)
        if (!$PAGE->url->compare(new moodle_url('/mod/quiz/attempt.php'), URL_MATCH_BASE)) {
            return false;
        }

        // check if we have return arguments
        if ($bsi_return_val !== NULL) {

            // parse the return and if allowed, allow student through
            if (biosig_parse_return($bsi_return_val)) {
                return false;
            } else {
                if (!empty($SESSION->biosigidquizaccess[$cmid])) {
                    unset($SESSION->biosigidquizaccess[$cmid]);
                }
                return get_string('bsi_failure', 'quizaccess_biosigid');
            }

        } else {

            // do the magic
            $error = NULL;
            $url = biosigid_inbound_sso($cmid, $error);

            if (!is_null($url)) {
                redirect($url);
            } else {
                return sprintf(get_string('bsi_error', 'quizaccess_biosigid'), $error);
            }

            redirect($url);

        }

    }

    /**
     * Settings related functions
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {

        $biosigidarray = array();
        $biosigidarray[] = $mform->createElement('advcheckbox', 'biosigrequired', '', get_string('biosig_enabled', 'quizaccess_biosigid'), '', array(
            0,
            1
        ));
        $mform->addGroup($biosigidarray, 'enablebiosig',
            get_string('biosig_required', 'quizaccess_biosigid'), array(' '), false);
        $mform->addHelpButton('enablebiosig', 'biosig_required', 'quizaccess_biosigid');
    }

    public static function save_settings($quiz) {
        global $DB;
        if (empty($quiz->biosigrequired)) {
            $DB->delete_records('quizaccess_biosigid', array(
                'quizid' => $quiz->id
            ));
        } else {
            if (!$DB->record_exists('quizaccess_biosigid', array('quizid' => $quiz->id))) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->biosigrequired = $quiz->biosigrequired;
                $DB->insert_record('quizaccess_biosigid', $record);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_biosigid', array(
            'quizid' => $quiz->id
        ));
    }

    public static function get_settings_sql($quizid) {
        return array(
            'biosigrequired',
            'LEFT JOIN {quizaccess_biosigid} biosigid ON biosigid.quizid = quiz.id',
            array()
        );
    }
}
