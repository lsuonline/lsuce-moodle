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
 * Adhoc task for background grade posting to Workday Student.
 *
 * @package    block_wds_postgrades
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_wds_postgrades\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class post_grades_adhoc
 *
 * This task is queued when the number of grades to post exceeds the
 * configured background posting threshold.
 */
class post_grades_adhoc extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('postgradesadhoctask', 'block_wds_postgrades');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;

        $customdata = $this->get_custom_data();
        $grades = $customdata->grades;
        $gradetype = $customdata->gradetype;
        $sectionlistingid = $customdata->sectionlistingid;
        $courseid = $customdata->courseid;
        $sectionid = $customdata->sectionid;
        $sectiontitle = $customdata->sectiontitle;
        $posteruserid = $customdata->posteruserid;

        // Ensure poster user exists.
        $posteruser = \core_user::get_user($posteruserid);
        if (!$posteruser) {
            mtrace("Error: Poster user not found. Cannot proceed.");
            return;
        }

        // Perform the posting based on grade type.
        if ($gradetype === 'final') {
            $resultdata = \block_wds_postgrades\wdspg::post_grades_with_method_extended(
                $grades, $gradetype, $sectionlistingid, $courseid, $sectionid
            );
        } else {
            $resultdata = \block_wds_postgrades\wdspg::post_grades_with_method(
                $grades, $gradetype, $sectionlistingid
            );
        }

        mtrace("Finished posting grades for course {$courseid}, section {$sectionid}.");
        if (isset($resultdata->successes)) {
            mtrace("Successes: " . count($resultdata->successes));
        }
        if (isset($resultdata->failures)) {
            mtrace("Failures: " . count($resultdata->failures));
        }

        // Now we need to notify the poster and primary instructors.
        $this->notify_users($posteruserid, $courseid, $sectionid, $gradetype, $resultdata, $sectiontitle);
    }

    /**
     * Notify relevant users about the grade posting results.
     */
    private function notify_users($posteruserid, $courseid, $sectionid, $gradetype, $resultdata, $sectiontitle) {
        global $DB;

        // Find users to notify: the poster and anyone with 'editingteacher' / 'teacher' role in the course context.
        $context = \context_course::instance($courseid);

        $userstonotify = [];
        $userstonotify[$posteruserid] = \core_user::get_user($posteruserid);

        $teachers = get_enrolled_users($context, 'moodle/course:update');
        if ($teachers) {
            foreach ($teachers as $t) {
                if (!isset($userstonotify[$t->id])) {
                    $userstonotify[$t->id] = $t;
                }
            }
        }

        // Generate message content based on grade type.
        $successcount = isset($resultdata->successes) ? count($resultdata->successes) : 0;
        $failurecount = isset($resultdata->failures) ? count($resultdata->failures) : 0;

        $subject = get_string('postgradesnotification_subject', 'block_wds_postgrades', ['sectiontitle' => $sectiontitle]);
        
        $a = new \stdClass();
        $a->sectiontitle = $sectiontitle;
        $a->successcount = $successcount;
        $a->failurecount = $failurecount;
        $a->failures = '';
        $a->failureshtml = '';

        if ($gradetype === 'final') {
            $resultsurl = new \moodle_url('/blocks/wds_postgrades/view.php', ['courseid' => $courseid, 'sectionid' => $sectionid, 'gradetype' => $gradetype]);
            $a->resultsurl = $resultsurl->out(false);

            if ($failurecount > 0 && isset($resultdata->failures)) {
                $failedstudents = [];
                foreach ($resultdata->failures as $failure) {

                    if (isset($failure->student_fullname)) {
                        $failedstudents[] = $failure->student_fullname;
                        $failedstudentdetails[] = 'The grade of '
                            . $failure->grade_display
                            . ' was not posted for '
                            . $failure->student_fullname
                            . ' with the following WorkDay error message: "'
                            . $failure->errormessage . '"';
                    }
                }

                if (!empty($failedstudents)) {
                    $failedlist = implode(', ', $failedstudents);
                    $a->failures = get_string('failedstudents', 'block_wds_postgrades', $failedlist);
                    $a->failureshtml = '<p>' . get_string('failedstudents', 'block_wds_postgrades', $failedlist) . '</p>';
                    foreach($failedstudentdetails as $failedstudentdetail) {
                        $a->failureshtml .= '<p>' . $failedstudentdetail . '</p>';
                        $a->failures .= "\n" . $failedstudentdetail;
                    }
                }
            }

            $messagehtml = get_string('postgradesnotification_final_html', 'block_wds_postgrades', $a);
            $messagetext = get_string('postgradesnotification_final_text', 'block_wds_postgrades', $a);

        } else {
            if ($failurecount > 0 && isset($resultdata->failures)) {
                $failedstudents = [];
                foreach ($resultdata->failures as $failure) {

                    if (isset($failure->student_fullname)) {
                        $failedstudents[] = $failure->student_fullname;
                        $failedstudentdetails[] = 'The grade of ' 
                            . $failure->grade_display
                            . ' was not posted for '
                            . $failure->student_fullname
                            . ' with the following WorkDay error message: "'
                            . $failure->errormessage . '"';
                    }
                }

                if (!empty($failedstudents)) {
                    $failedlist = implode(', ', $failedstudents);
                    $a->failures = get_string('failedstudents', 'block_wds_postgrades', $failedlist);
                    $a->failureshtml = '<p>' . get_string('failedstudents', 'block_wds_postgrades', $failedlist) . '</p>';
                    foreach($failedstudentdetails as $failedstudentdetail) {
                        $a->failureshtml .= '<p>' . $failedstudentdetail . '</p>';
                        $a->failures .= "\n" . $failedstudentdetail;
                    }
                }
            }

            $messagehtml = get_string('postgradesnotification_interim_html', 'block_wds_postgrades', $a);
            $messagetext = get_string('postgradesnotification_interim_text', 'block_wds_postgrades', $a);
        }

        $noreplyuser = \core_user::get_noreply_user();

        foreach ($userstonotify as $userid => $user) {
            $message = new \core\message\message();
            $message->component = 'block_wds_postgrades';
            $message->name = 'postgrades';
            $message->userfrom = $noreplyuser;
            $message->userto = $user;
            $message->subject = $subject;
            $message->fullmessage = $messagetext;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = $messagehtml;
            $message->smallmessage = $messagetext;
            $message->notification = 1;
            $message->contexturl = (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
            $message->contexturlname = $sectiontitle;

            message_send($message);
        }
    }
}
