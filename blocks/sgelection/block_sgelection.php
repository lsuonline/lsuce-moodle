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
 * Student government election block
 * @package    block_sgelection
 * @copyright  2014 onwards Louisiana State University (http://www.lsu.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once ($CFG->dirroot . '/blocks/sgelection/lib.php');
require_once ($CFG->dirroot . '/enrol/ues/publiclib.php');
require_once ($CFG->dirroot . '/blocks/sgelection/classes/voter.php');

ues::require_daos();

class block_sgelection extends block_list {
    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('sgelection', 'block_sgelection');
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('site' => true, 'my-index' => true, 'course' => false);
    }

    /**
     * Return contents of the sgelection block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG, $COURSE, $OUTPUT, $DB;

        $wwwroot = $CFG->wwwroot;

        $voter = new voter($USER->id);

        // See if this user should be allowed to view the block at all.
        if(!isloggedin()){
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();

        $voter->is_privileged_user = $voter->is_privileged_user();
        $elections = $voter->is_privileged_user ? election::get_all() : election::get_active();
        foreach($elections as $ae){

            // If user courseload is not at least part-time for the current election semester, add nothing to the output.
            $ues_semester = ues_semester::by_id($ae->semesterid);
            if(!$voter->eligible($ae) && !$voter->is_privileged_user){
                continue;
            } else {
                $semester = $ae->shortname();
                $numberOfVotesTotal = $DB->count_records('block_sgelection_voted', array('election_id'=>$ae->id));
                $numberOfVotesTotalString =  html_writer::tag('p', 'Ballots cast so far ' . $numberOfVotesTotal);
                if(!$voter->already_voted($ae) || $voter->is_privileged_user()){
                    $this->content->items[] = html_writer::link( new moodle_url('/blocks/sgelection/ballot.php', array('election_id' => $ae->id)), 'Ballot for ' . $semester, array('class'=>'election')) . ' ' . $numberOfVotesTotalString;
                    $this->content->icons[] = html_writer::empty_tag('img', array('src'=>$wwwroot . '/blocks/sgelection/pix/w_check.svg', 'class' => 'icon'));
                } else {
                    $this->content->items[] = html_writer::tag('p','Ballot for ' . $semester . ' ' . $numberOfVotesTotalString, array('class'=>'election'));
                    $this->content->icons[] = html_writer::empty_tag('img', array('src'=>$wwwroot .'/blocks/sgelection/pix/w_check.svg', 'class' => 'icon'));

                }
            }
        }

        $issgadmin = $voter->is_faculty_advisor() || is_siteadmin();
        if($issgadmin){
            $administrate = html_writer::link(new moodle_url('/blocks/sgelection/admin.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id)), get_string('configure', 'block_sgelection'));
            $this->content->items[] = $administrate;
            $this->content->icons[] = html_writer::empty_tag('img', array('src'=>$wwwroot .'/blocks/sgelection/pix/w_edit.svg', 'class' => 'icon'));
        }

        if($voter->is_privileged_user){
            $commissioner = html_writer::link(new moodle_url('/blocks/sgelection/commissioner.php'), get_string('create_election', 'block_sgelection'));
            $this->content->items[] = $commissioner;
            $this->content->icons[] = html_writer::empty_tag('img', array('src'=>$wwwroot .'/blocks/sgelection/pix/w_edit.svg', 'class' => 'icon'));
        }


        return $this->content;
    }

    /**
     * Can we load multiple instances of the block on a single page?
     *
     * @return array
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * @TODO add some logic to ensure that this only runs in the week before the election.
     * @global type $DB
     * @return boolean
     */
    public function cron() {
        global $DB;

        // Iterate over each semester which is ready for eligibility calculation
        // creating block_sgelection_hours rows for each student enrolled.
        $semesters_complete = array();
        foreach(sge::semesters_eligible_for_census() as $eid => $s){
            if(in_array($s->id, $semesters_complete)){
                continue;
            }
            // If any hours rows exist for this semester, remove them- we want fresh data.
            $DB->delete_records('block_sgelection_hours', array('semesterid' => $s->id));

            // Get user enrolled hours for the given semester.
            $hours = sge::calculate_all_enrolled_hours_for_semester($s);

            // If we get no results (should never happen, provided
            // ues users are enrolled), continue to the next one.
            if(false === $hours){
                $semesters_complete[] = $s->id;
                continue;
            }

            // Log it.
            $event = \block_sgelection\event\census_completed::create(array(
                        'objectid' => $eid,
                        'context' => context_system::instance()
                            ));
            $event->trigger();
            // Insert each row.
            // @TODO consider doing this using with a moodle batch
            // insert or a transaction (include the delete too...)
            foreach($hours as $row){
                $DB->insert_record('block_sgelection_hours', $row);
            }

            // Mark complete.
            $semesters_complete[] = $s->id;
            $election = Election::get_by_id($eid);
            $election->hours_census_complete = time();
            $election->save();
        }


        $elections = Election::get_active();
        if(count($elections) > 0){
            $results_last_sent = sge::config('results_last_sent');
            $results_last_sent = $results_last_sent ? $results_last_sent : 0;
            $interval   = sge::config('results_interval') * 60;
            if((time() - $results_last_sent) > $interval){
                foreach($elections as $election){
                    $election->email_results();
                }
                sge::config('results_last_sent', time());
            }
        }
    return true;
    }
}
