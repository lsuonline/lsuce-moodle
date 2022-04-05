<?php

/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     Landing Block                                            **
 * @subpackage  University of Lethbridge Custom Landing Block            **
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

class LBLib
{

    private $current_site;
    private $is_local;
    private $term;
    private $url_no_term;

    public function __construct()
    {
        global $CFG;
        $this->is_local = false;
        $this_current_site_is = $CFG->wwwroot;

        preg_match_all("/(uleth.ca)/", $this_current_site_is, $matches);

        $url_main = substr($this_current_site_is, 7);

        $this_current_site_is_ssl = null;
        if (isset($matches[0][0]) && @$matches[0][0]) {
            $this->current_site = $CFG->wwwroot."/";
            $this->is_local = false;

            $spot_it = strrpos($CFG->wwwroot, "uleth.ca");
            // get the term
            $spot_it = $spot_it + 9;
            $this->term = substr($CFG->wwwroot, $spot_it, 6);

            // get the raw url
            $this->url_no_term = substr($CFG->wwwroot, 0, $spot_it);
        } else {
            $url_main = substr($this_current_site_is, 7);
            $this->current_site = "http://".$url_main."/";
            $url_main = str_replace('u', '', $url_main); // Replaces all spaces with hyphens.
            $this->term = $url_main;
            $this->is_local = true;
        }
    }

    /**
     * return the site back so we know if we are local or remote
     * @return get the current site
     */
    public function getCurrentSite()
    {
        return $this->current_site;
    }

    /**
     * Return the term we are on, 201401, 201402....etc.
     * @return string
     */
    public function getCurrentTerm()
    {
        return $this->term;
    }

    /**
     * Return the raw URL with no term.
     * @return string
     */
    public function getRawURL()
    {
        return $this->url_no_term;
    }

    /**
     * return if this object is local or remote
     * @return bool, true if local
     */
    public function isLocal()
    {
        return $this->is_local;
    }


    /**
     * Is this an admin user?
     * @return bool
     */
    public function checkAdminUser()
    {

        //require_once('../../../config.php');

        $context = context_system::instance();
        $admin_access = has_capability('moodle/site:config', $context);
        if ($admin_access) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Description
     * @param type string - indicates what instance we are currently looking at
     * @return string - message indicating there are no courses for this user.
     */
    public function getNoCourseMsg($name)
    {

        if ($name == "current_title") {
            return "You are not enrolled in any courses for this semester.";
        } elseif ($name == "long_term_title") {
            return "You are not enrolled in any long term courses.";
        } elseif ($name == "past_title") {
            return "You were not enrolled in any courses last semster.";
        } elseif ($name == "future_title") {
            return "You are not enrolled in any courses for next semster.";
        } else {
            return "- You are not enrolled in any courses -";
        }
    }

    /* Print the course name */
    public function lb_print_local_course($context, $course)
    {
        global $OUTPUT;

        /* output the course name as a link to the course page */
        $fullname = format_string($course->fullname, true, array('context' => $context));
        $attributes = array('title' => s($fullname));
        if (empty($course->visible)) {
            $attributes['class'] = 'dimmed';
        }

        return $OUTPUT->heading(html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $fullname, $attributes), 3);
    }

    /* Print all a courses instructors */
    public function lb_print_local_teachers($context, $course, $rawdata = "false")
    {
        global $CFG, $DB;
        $data = '';
        $raw_teacher_list = array();

        if (!empty($CFG->coursecontact)) {
            /* find all the names of instructors, etc. and the Moodle string
            that defines their role in the course */
            $names = array();
            $roles = explode(',', $CFG->coursecontact);
            $view_fullnames = has_capability('moodle/site:viewfullnames', $context);
            foreach ($roles as $roleid) {
                $role = $DB->get_record('role', array('id' => $roleid));
                if ($teachers = get_role_users((int) $roleid, $context, true)) {
                    foreach ($teachers as $teacher) {
                        $teacher_chunk = new stdClass();
                        $fullname = fullname($teacher, $view_fullnames);
                        $names[] = format_string(role_get_name($role, $context)).': '.
                        
                        $teacher_chunk->fullname = $fullname;
                        $teacher_chunk->id = $teacher->id;
                        html_writer::link(new moodle_url('/user/view.php', array('id' => $teacher->id, 'course' => SITEID)), $fullname);
                        $raw_teacher_list[] = $teacher_chunk;
                    }
                }
            }
            // $teacher_chunk->names = $names;

            /* if we found anything above, print it to the summary screen */
            if (!empty($names)) {
                $data = html_writer::start_tag('div', array('class' => 'teachers'));
                foreach ($names as $name) {
                    $data .= html_writer::tag('h5', $name);
                }
                $data .= html_writer::end_tag('div');
            }
        } else {
            $data = '';
        }

        // UofL - DALO - return raw data in class format
        if ($rawdata == "true") {
            return $raw_teacher_list;
        }

        return $data;
    }

    /* Print overview information for a course */
    public function lb_print_overview_array($course, $htmlarray)
    {
        /* the hard work was already done for us in lb_print_overview(),
         we just need to loop over the mod's preview results */
        $data = '';
        if (array_key_exists($course->id, $htmlarray)) {
            foreach ($htmlarray[$course->id] as $name => $html) {
                $data .= $html;
            }
        }
        return $data;
    }

    /**
     * Print an overview of all assignments
     * for the courses.
     *
     * @param mixed $courses The list of courses to print the overview for
     * @param array $htmlarray The array of html to return
     *
     * @return true
     */
    public function assign_print_overview($courses, &$htmlarray, $rawdata = "false")
    {
        
        global $CFG, $DB;
        // global $USER, $CFG, $DB;
        require_once($CFG->libdir.'/datalib.php');
        require_once($CFG->libdir.'/gradelib.php');


        if (empty($courses) || !is_array($courses) || count($courses) == 0) {
            return true;
        }

        if (!$assignments = get_all_instances_in_courses('assign', $courses)) {
            return true;
        }

        $assignmentids = array();

        // Do assignment_base::isopen() here without loading the whole thing for speed.
        foreach ($assignments as $key => $assignment) {
            $time = time();
            $isopen = false;
            if ($assignment->duedate) {
                $duedate = false;
                if ($assignment->cutoffdate) {
                    $duedate = $assignment->cutoffdate;
                }
                if ($duedate) {
                    $isopen = ($assignment->allowsubmissionsfromdate <= $time && $time <= $duedate);
                } else {
                    $isopen = ($assignment->allowsubmissionsfromdate <= $time);
                }
            }
            if ($isopen) {
                $assignmentids[] = $assignment->id;
            }
        }

        if (empty($assignmentids)) {
            // No assignments to look at - we're done.
            return true;
        }

        // Definitely something to print, now include the constants we need.
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $strduedate = get_string('duedate', 'assign');
        $strcutoffdate = get_string('nosubmissionsacceptedafter', 'assign');
        $strnolatesubmissions = get_string('nolatesubmissions', 'assign');
        $strduedateno = get_string('duedateno', 'assign');
        $strassignment = get_string('modulename', 'assign');

        // We do all possible database work here *outside* of the loop to ensure this scales.
        list($sqlassignmentids, $assignmentidparams) = $DB->get_in_or_equal($assignmentids);

        $mysubmissions = null;
        $unmarkedsubmissions = null;

        // UofL - data array that'll be returned
        $class_list = array();

        foreach ($assignments as $assignment) {
            // Do not show assignments that are not open.
            $class_chunk = new stdClass();
            if (!in_array($assignment->id, $assignmentids)) {
                continue;
            }

            $context = context_module::instance($assignment->coursemodule);

            // Does the submission status of the assignment require notification?
            if (has_capability('mod/assign:submit', $context, null, false)) {
                // Does the submission status of the assignment require notification?
                $submitdetails = $this->assign_get_mysubmission_details_for_print_overview(
                    $mysubmissions,
                    $sqlassignmentids,
                    $assignmentidparams,
                    $assignment
                );
            } else {
                $submitdetails = false;
            }

            if (has_capability('mod/assign:grade', $context, null, false)) {
                // Does the grading status of the assignment require notification ?
                $gradedetails = assign_get_grade_details_for_print_overview(
                    $unmarkedsubmissions,
                    $sqlassignmentids,
                    $assignmentidparams,
                    $assignment,
                    $context
                );
                // if (debugging()) {
                //     error_log("\n\nLandingBlock -> assign_print_overview() -> The grade details have been generated: \n\n". print_r($gradedetails, 1). "\n\n");
                // }
            } else {
                $gradedetails = false;
            }

            //if (empty($submitdetails) && empty($gradedetails)) {
                // There is no need to display this assignment as there is nothing to notify.
              //  continue;
            //}

            $dimmedclass = '';
            if (!$assignment->visible) {
                $dimmedclass = ' class="dimmed"';
            }
            // UofL - DALO
            $class_chunk->dimmedclass = $dimmedclass;
            $class_chunk->coursemodule = $assignment->coursemodule;
            $class_chunk->strassignment = $strassignment;
            $class_chunk->assign_name = format_string($assignment->name);
            $class_chunk->wwwroot = $CFG->wwwroot;
            $class_chunk->duedate = "";
            $class_chunk->cutoffdate = "";
            $class_chunk->submitdetails = false;
            $class_chunk->gradedetails = false;

            $href = $CFG->wwwroot . '/mod/assign/view.php?id=' . $assignment->coursemodule;
            $basestr = '<div class="assign overview">' .
                   '<div class="name">' .
                   $strassignment . ': '.
                   '<a ' . $dimmedclass .
                       'title="' . $strassignment . '" ' .
                       'href="' . $href . '">' .
                   format_string($assignment->name) .
                   '</a></div>';
            if ($assignment->duedate) {
                $class_chunk->duedate = $assignment->duedate;
                $userdate = userdate($assignment->duedate);
                $class_chunk->userdate = $userdate;
                $class_chunk->strduedate = $strduedate;
                $basestr .= '<div class="info">' . $strduedate . ': ' . $userdate . '</div>';
            } else {
                $basestr .= '<div class="info">' . $strduedateno . '</div>';
                $class_chunk->strduedateno = $strduedateno;
            }
            if ($assignment->cutoffdate) {
                $class_chunk->cutoffdate = $assignment->cutoffdate;

                if ($assignment->cutoffdate == $assignment->duedate) {
                    $class_chunk->strnolatesubmissions = $strnolatesubmissions;
                    $basestr .= '<div class="info">' . $strnolatesubmissions . '</div>';
                } else {
                    $userdate = userdate($assignment->cutoffdate);
                    $class_chunk->userdate = $userdate;
                    $class_chunk->strcutoffdate = $strcutoffdate;
                    $basestr .= '<div class="info">' . $strcutoffdate . ': ' . $userdate . '</div>';
                }
            }

            // Show only relevant information.
            if (!empty($submitdetails)) {
                $basestr .= $submitdetails;
                $class_chunk->submitdetails = $submitdetails;
            }

            if (!empty($gradedetails)) {
                $basestr .= $gradedetails;
                $class_chunk->gradedetails = $gradedetails;
            }
            $basestr .= '</div>';

            if (empty($htmlarray[$assignment->course]['assign'])) {
                $htmlarray[$assignment->course]['assign'] = $basestr;
            } else {
                $htmlarray[$assignment->course]['assign'] .= $basestr;
            }

            $class_list[] = $class_chunk;
        }
        // UofL - DALO return array of info instead
        if ($rawdata == "true") {
            return $class_list;
        }

        return true;
    }

    function assignment_display_lateness($timesubmitted, $timedue)
    {
        if (!$timedue) {
            return '';
        }
        $time = $timedue - $timesubmitted;
        if ($time < 0) {
            $timetext = get_string('late', 'block_landing_block', format_time($time));
            return ' (<span class="late">'.$timetext.'</span>)';
        } else {
            $timetext = get_string('early', 'block_landing_block', format_time($time));
            return ' (<span class="early">'.$timetext.'</span>)';
        }
    }


    public function isUserProf($courseid = 0) {

        global $CFG, $USER;

        if ($courseid == 0) {
            return false;
        }
        // UofL - is this user a prof or ta?
        $context = context_course::instance($courseid);
        $roles = get_user_roles($context, $USER->id, false);
        $is_ta = false; // is the user a teaching assistant?

        foreach ($roles as $role) {
            if ($role->shortname == 'advancedta' ||
                $role->shortname == 'ta' ||
                $role->shortname == 'editingteacher' ||
                $role->shortname == 'teacher' ||
                $role->shortname == 'manager'
            ) {
                $is_ta = true;
            }
        }
        return $is_ta;
    }

    /* Print a custom course overview as per the CRDC expectations. */
    public function lb_print_overview($courses, $rawdata = "false")
    {
        global $OUTPUT, $CFG, $PAGE;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        /* gather all the assignment information on courses */
        $assignment_status = array();

        // /* gather all the assignment information on quizes */
        // $raw_assign = $this->assign_print_overview($courses, $assignment_status, $rawdata);
        $quiz_status = array();
        $this->quiz_print_overview($courses, $quiz_status);

        /*
            for each course, print the title, assignment status and the
            instructors
        */

        $data = '<div class="container-fluid">';
        $rawdata_list = array();
        foreach ($courses as $course) {
            $course_context = context_course::instance($course->id);

            if ($rawdata == "true") {
                $course_raw_data = new stdClass();

                $course_raw_data->teachers = $this->lb_print_local_teachers($course_context, $course, $rawdata);
                // $course_raw_data->assign = $raw_assign;
                $course_raw_data->quiz = $quiz_status;
                $course_raw_data->is_ta = $this->isUserProf($course->id);

            } else {
                $data .= $this->lb_print_local_teachers($course_context, $course);
                // $print_this_assign_data =  $this->lb_print_overview_array($course, $assignment_status);
                // $print_this_quiz_data = $this->lb_print_overview_array($course, $quiz_status);

                if (isset($CFG->block_landing_block_simple_view) && $CFG->block_landing_block_simple_view == 1) {
                
                    // remove all Assign and Quiz data
                    $print_this_assign_data = '<a href="'. new moodle_url($CFG->wwwroot . "/mod/assign/index.php?id=". $course->id) .'">'. $OUTPUT->image_icon("icon", "UserAssignments", "assign", array('class' => 'lb_assn_quiz_icons')). ' View all assignments </a>';
                    $print_this_quiz_data = '<a href="'. new moodle_url($CFG->wwwroot . "/mod/quiz/index.php?id=". $course->id) .'">'. $OUTPUT->image_icon("icon", "UserAssignments", "quiz", array('class' => 'lb_assn_quiz_icons')). ' View all quizzes </a>';

                    $data .= '<div class="lb_assignments_container">'.
                        '<div class="row lb_assign_quiz_header" id="uofl_lb_for_antique">'.
                            '<div class="col-sm-12 col-md-12 col-lg-6 lb_assign_quiz_header_title">'.
                                '<h4 style="display: inline;">' . $print_this_assign_data . '</h4>'.
                            '</div>'.
                            '<div class="col-sm-12 col-md-12 col-lg-6 lb_assign_quiz_header_title">'.
                                '<h4 style="display: inline;">' . $print_this_quiz_data . '</h4>'.
                            '</div>'.
                        '</div>'.
                    '</div>';
                        // -----------------------------------------------------

                    // $data .= '<div class="lb_quiz_container">'.
                        // '<div class="row lb_assign_quiz_header">'.

                } else {
                    
                    $print_this_assign_data = $this->getAllAssignments($course->id, $course);
                    $print_this_quiz_data = $this->getAllQuizes($course->id, $course);
                    
                    if (strlen($print_this_assign_data) == 0) {
                        $print_this_assign_data = '<div class="lb_no_data_to_show">Currently there are no assignments with a due date</div>';
                    }

                    if (strlen($print_this_quiz_data) == 0) {
                        $print_this_quiz_data = '<div class="lb_no_data_to_show">Currently there are no quizes with a due date.</div>';
                    }
                
                    $data .= '<div class="lb_assignments_container">'.
                        '<div class="row lb_assign_quiz_header">'.
                            '<div class="col-sm-6 lb_assign_quiz_header_title">'.
                                '<h4 style="display: inline;">Course Assignments</h4>'.
                            '</div>'.
                            '<div class="col-sm-6 lb_assign_quiz_header_title_sh">'.
                                '<span class="lb_assignments_toggle pull-right">Show/Hide</span>'.
                            '</div>'.
                        '</div>';
                    $data .= '<div class="lb_assignment_panel">' . $print_this_assign_data . '</div>';
                    $data .= '</div>';

                    // -----------------------------------------------------
                    $data .= '<div class="lb_quiz_container">'.
                        '<div class="row lb_assign_quiz_header">'.
                            '<div class="col-sm-6 lb_assign_quiz_header_title">'.
                                '<h4 style="display: inline;">Course Exams</h4>'.
                            '</div>'.
                            '<div class="col-sm-6 lb_assign_quiz_header_title_sh">'.
                                '<span class="lb_quiz_toggle pull-right">Show/Hide</span>'.
                            '</div>'.
                        '</div>';
                    $data .= '<div class="lb_quiz_panel">' . $print_this_quiz_data . '</div>';
                    $data .= '</div>';

                }
            }
        }

        $data .= '</div>';

        if ($rawdata == "true") {
            return $course_raw_data;
        } else {

            if (strlen($data) <= 2) {
                $data = '<div class="no_course_data_yet"><h5>No new content has been added.</h5></div>';
            }
            return $data;
        }
    }



    /**
    * get_landing_content
    * requests a url and id, commplets a curl request
    * in order to get the classes on a remote server
    * @param  string $url the url of the server
    * @param  int $id  id of the student requesting it
    * @return string
    */
    public function get_landing_content($data, $url)
    {
        global $CFG;
        // if (debugging()) {
        //     error_log("\n");
        //     error_log("\nget_landing_content -> going to get data for: $url");
        // }

        $key    = $CFG->block_landing_block_secret;
        $secret = sha1($key);
        // list($name, $coll, $this_loop_site_url, $url_title, $uid, $username) = $data;
        $serial_data = serialize($data);
        /* query the remote Moodle instance for the data we want */
        // $instance_data = array($name, $coll, $this_loop_site_url, $url_title, $uid, $USER->username);

        // set the timeout for the curl call in seconds in case one of the instances is down
        if (isset($CFG->block_landing_block_postimeout)) {
            $max_time = $CFG->block_landing_block_postimeout;
        } else {
            $max_time = 2;
        }
        // =====================

        $curl = curl_init($url."/blocks/landing_block/lib/LBReturn.php");

        //don't fetch the actual page, you only want to check the connection is ok
        curl_setopt($curl, CURLOPT_NOBODY, true);

        //do request
        $result = curl_exec($curl);

        $ret = false;

        //if request did not fail
        if ($result !== false) {
            //if request was ok, check response code
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($statusCode != 200) {
                
                // if (debugging()) {
                //     error_log("\n");
                //     error_log("\nget_landing_content -> just recieved status code that's NOT 200, return false.");
                // }
                return false;
            }
        }

        curl_close($curl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url."/blocks/landing_block/lib/LBReturn.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $max_time);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'cereal_data=' . $serial_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); /* should be default */

        if (!($html = curl_exec($ch))) {
            /* don't push the error to the user, it'll just confuse them */
        }
        
        curl_close($ch);

        return $html;
    }


    /**
     * This api generates html to be displayed to students in print overview section, related to their submission status of the given
     * assignment. Going to use it here instead of lib.php to keep it alive.
     *
     * @deprecated since 3.3
     * @todo The final deprecation of this function will take place in Moodle 3.7 - see MDL-57487.
     * @param array $mysubmissions list of submissions of current user indexed by assignment id.
     * @param string $sqlassignmentids sql clause used to filter open assignments.
     * @param array $assignmentidparams sql params used to filter open assignments.
     * @param stdClass $assignment current assignment
     *
     * @return bool|string html to display , false if nothing needs to be displayed.
     * @throws coding_exception
     */
    function assign_get_mysubmission_details_for_print_overview(&$mysubmissions, $sqlassignmentids, $assignmentidparams,
                                                            $assignment) {
        global $USER, $DB;

        // debugging('The function assign_get_mysubmission_details_for_print_overview() is now deprecated.', DEBUG_DEVELOPER);

        if ($assignment->nosubmissions) {
            // Offline assignment. No need to display alerts for offline assignments.
            return false;
        }

        $strnotsubmittedyet = get_string('notsubmittedyet', 'assign');

        if (!isset($mysubmissions)) {

            // Get all user submissions, indexed by assignment id.
            $dbparams = array_merge(array($USER->id), $assignmentidparams, array($USER->id));
            $mysubmissions = $DB->get_records_sql('SELECT a.id AS assignment,
                                                          a.nosubmissions AS nosubmissions,
                                                          g.timemodified AS timemarked,
                                                          g.grader AS grader,
                                                          g.grade AS grade,
                                                          s.status AS status
                                                     FROM {assign} a, {assign_submission} s
                                                LEFT JOIN {assign_grades} g ON
                                                          g.assignment = s.assignment AND
                                                          g.userid = ? AND
                                                          g.attemptnumber = s.attemptnumber
                                                    WHERE a.id ' . $sqlassignmentids . ' AND
                                                          s.latest = 1 AND
                                                          s.assignment = a.id AND
                                                          s.userid = ?', $dbparams);
        }

        $submitdetails = '';
        $submitdetails .= '<div class="details">';
        $submitdetails .= get_string('mysubmission', 'assign');
        $submission = false;

        if (isset($mysubmissions[$assignment->id])) {
            $submission = $mysubmissions[$assignment->id];
        }

        if ($submission && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            // A valid submission already exists, no need to notify students about this.
            return false;
        }

        // We need to show details only if a valid submission doesn't exist.
        if (!$submission ||
            !$submission->status ||
            $submission->status == ASSIGN_SUBMISSION_STATUS_DRAFT ||
            $submission->status == ASSIGN_SUBMISSION_STATUS_NEW
        ) {
            $submitdetails .= $strnotsubmittedyet;
        } else {
            $submitdetails .= get_string('submissionstatus_' . $submission->status, 'assign');
        }
        if ($assignment->markingworkflow) {
            $workflowstate = $DB->get_field('assign_user_flags', 'workflowstate', array('assignment' =>
                    $assignment->id, 'userid' => $USER->id));
            if ($workflowstate) {
                $gradingstatus = 'markingworkflowstate' . $workflowstate;
            } else {
                $gradingstatus = 'markingworkflowstate' . ASSIGN_MARKING_WORKFLOW_STATE_NOTMARKED;
            }
        } else if (!empty($submission->grade) && $submission->grade !== null && $submission->grade >= 0) {
            $gradingstatus = ASSIGN_GRADING_STATUS_GRADED;
        } else {
            $gradingstatus = ASSIGN_GRADING_STATUS_NOT_GRADED;
        }
        $submitdetails .= ', ' . get_string($gradingstatus, 'assign');
        $submitdetails .= '</div>';
        return $submitdetails;
    }

    /**
     * Prints quiz summaries on MyMoodle Page
     *
     * @deprecated since 3.3
     * @todo The final deprecation of this function will take place in Moodle 3.7 - see MDL-57487.
     * @param array $courses
     * @param array $htmlarray
     */
    function quiz_print_overview($courses, &$htmlarray) {
        global $USER, $CFG;

        // debugging('The function quiz_print_overview() is now deprecated.', DEBUG_DEVELOPER);

        // These next 6 Lines are constant in all modules (just change module name).
        if (empty($courses) || !is_array($courses) || count($courses) == 0) {
            return array();
        }

        if (!$quizzes = get_all_instances_in_courses('quiz', $courses)) {
            return;
        }

        // Get the quizzes attempts.
        $attemptsinfo = [];
        $quizids = [];
        foreach ($quizzes as $quiz) {
            $quizids[] = $quiz->id;
            $attemptsinfo[$quiz->id] = ['count' => 0, 'hasfinished' => false];
        }
        $attempts = quiz_get_user_attempts($quizids, $USER->id);
        foreach ($attempts as $attempt) {
            $attemptsinfo[$attempt->quiz]['count']++;
            $attemptsinfo[$attempt->quiz]['hasfinished'] = true;
        }
        unset($attempts);

        // Fetch some language strings outside the main loop.
        $strquiz = get_string('modulename', 'quiz');
        $strnoattempts = get_string('noattempts', 'quiz');

        // We want to list quizzes that are currently available, and which have a close date.
        // This is the same as what the lesson does, and the dabate is in MDL-10568.
        $now = time();
        foreach ($quizzes as $quiz) {
            if ($quiz->timeclose >= $now && $quiz->timeopen < $now) {
                $str = '';

                // Now provide more information depending on the uers's role.
                $context = context_module::instance($quiz->coursemodule);
                if (has_capability('mod/quiz:viewreports', $context)) {
                    // For teacher-like people, show a summary of the number of student attempts.
                    // The $quiz objects returned by get_all_instances_in_course have the necessary $cm
                    // fields set to make the following call work.
                    $str .= '<div class="info">' . quiz_num_attempt_summary($quiz, $quiz, true) . '</div>';

                } else if (has_any_capability(array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), $context)) { // Student
                    // For student-like people, tell them how many attempts they have made.

                    if (isset($USER->id)) {
                        if ($attemptsinfo[$quiz->id]['hasfinished']) {
                            // The student's last attempt is finished.
                            continue;
                        }

                        if ($attemptsinfo[$quiz->id]['count'] > 0) {
                            $str .= '<div class="info">' .
                                get_string('numattemptsmade', 'quiz', $attemptsinfo[$quiz->id]['count']) . '</div>';
                        } else {
                            $str .= '<div class="info">' . $strnoattempts . '</div>';
                        }

                    } else {
                        $str .= '<div class="info">' . $strnoattempts . '</div>';
                    }

                } else {
                    // For ayone else, there is no point listing this quiz, so stop processing.
                    continue;
                }

                // Give a link to the quiz, and the deadline.
                $html = '<div class="quiz overview">' .
                        '<div class="name">' . $strquiz . ': <a ' .
                        ($quiz->visible ? '' : ' class="dimmed"') .
                        ' href="' . $CFG->wwwroot . '/mod/quiz/view.php?id=' .
                        $quiz->coursemodule . '">' .
                        $quiz->name . '</a></div>';
                $html .= '<div class="info">' . get_string('quizcloseson', 'quiz',
                        userdate($quiz->timeclose)) . '</div>';
                $html .= $str;
                $html .= '</div>';
                if (empty($htmlarray[$quiz->course]['quiz'])) {
                    $htmlarray[$quiz->course]['quiz'] = $html;
                } else {
                    $htmlarray[$quiz->course]['quiz'] .= $html;
                }
            }
        }
    }

    function getAllQuizes($id = null, $course = null) {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot.'/mod/quiz/locallib.php');
        $coursecontext = context_course::instance($id);
        // require_login($course);
        // $PAGE->set_pagelayout('incourse');

        if (!$course = $DB->get_record('course', array('id' => $id))) {
            print_error('invalidcourseid');
        }
        $params = array(
            'context' => $coursecontext
        );
        $event = \mod_quiz\event\course_module_instance_list_viewed::create($params);
        $event->trigger();

        // Print the header.
        $strquizzes = get_string("modulenameplural", "quiz");
        $streditquestions = '';
        
        $editqcontexts = new question_edit_contexts($coursecontext);
        
        if ($editqcontexts->have_one_edit_tab_cap('questions')) {
            $streditquestions =
                    "<form target=\"_parent\" method=\"get\" action=\"$CFG->wwwroot/question/edit.php\">
                       <div>
                       <input type=\"hidden\" name=\"courseid\" value=\"$course->id\" />
                       <input type=\"submit\" value=\"".get_string("editquestions", "quiz")."\" />
                       </div>
                     </form>";
        }
        // $PAGE->navbar->add($strquizzes);
        // $PAGE->set_title($strquizzes);
        // $PAGE->set_button($streditquestions);
        // $PAGE->set_heading($course->fullname);
        // echo $OUTPUT->header();
        // echo $OUTPUT->heading($strquizzes, 2);

        // Get all the appropriate data.
        $quizzes = get_all_instances_in_course("quiz", $course);

            // notice(get_string('thereareno', 'moodle', $strquizzes), "../../course/view.php?id=$course->id");
            // die;
        // }

        // Check if we need the closing date header.
        $showclosingheader = false;
        $showfeedback = false;
        foreach ($quizzes as $quiz) {
            if ($quiz->timeclose!=0) {
                $showclosingheader=true;
            }
            if (quiz_has_feedback($quiz)) {
                $showfeedback=true;
            }
            if ($showclosingheader && $showfeedback) {
                break;
            }
        }

        // Configure table for displaying the list of instances.
        $headings = array(get_string('name'));
        $align = array('left');

        if ($showclosingheader) {
            array_push($headings, get_string('quizcloses', 'quiz'));
            array_push($align, 'left');
        }

        if (course_format_uses_sections($course->format)) {
            array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
        } else {
            array_unshift($headings, '');
        }
        array_unshift($align, 'center');

        $showing = '';

        if (has_capability('mod/quiz:viewreports', $coursecontext)) {
            array_push($headings, get_string('attempts', 'quiz'));
            array_push($align, 'left');
            $showing = 'stats';

        } else if (has_any_capability(array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), $coursecontext)) {
            array_push($headings, get_string('grade', 'quiz'));
            array_push($align, 'left');
            if ($showfeedback) {
                array_push($headings, get_string('feedback', 'quiz'));
                array_push($align, 'left');
            }
            $showing = 'grades';

            $grades = $DB->get_records_sql_menu('
                    SELECT qg.quiz, qg.grade
                    FROM {quiz_grades} qg
                    JOIN {quiz} q ON q.id = qg.quiz
                    WHERE q.course = ? AND qg.userid = ?',
                    array($course->id, $USER->id));
        }

        $table = new html_table();
        $table->head = $headings;
        $table->align = $align;

        // Populate the table with the list of instances.
        $currentsection = '';
        foreach ($quizzes as $quiz) {
            $cm = get_coursemodule_from_instance('quiz', $quiz->id);
            $context = context_module::instance($cm->id);
            $data = array();

            // Section number if necessary.
            $strsection = '';
            if ($quiz->section != $currentsection) {
                if ($quiz->section) {
                    $strsection = $quiz->section;
                    $strsection = get_section_name($course, $quiz->section);
                }
                if ($currentsection) {
                    $learningtable->data[] = 'hr';
                }
                $currentsection = $quiz->section;
            }
            $data[] = $strsection;

            // Link to the instance.
            $class = '';
            if (!$quiz->visible) {
                $class = ' class="dimmed"';
            }
            $data[] = "<a$class href=\"view.php?id=$quiz->coursemodule\">" .
                    format_string($quiz->name, true) . '</a>';

            // Close date.
            if ($quiz->timeclose) {
                $data[] = userdate($quiz->timeclose);
            } else if ($showclosingheader) {
                $data[] = '';
            }

            if ($showing == 'stats') {
                // The $quiz objects returned by get_all_instances_in_course have the necessary $cm
                // fields set to make the following call work.
                $data[] = quiz_attempt_summary_link_to_reports($quiz, $cm, $context);

            } else if ($showing == 'grades') {
                // Grade and feedback.
                $attempts = quiz_get_user_attempts($quiz->id, $USER->id, 'all');
                list($someoptions, $alloptions) = quiz_get_combined_reviewoptions(
                    $quiz,
                    $attempts
                );

                $grade = '';
                $feedback = '';
                if ($quiz->grade && array_key_exists($quiz->id, $grades)) {
                    if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                        $a = new stdClass();
                        $a->grade = quiz_format_grade($quiz, $grades[$quiz->id]);
                        $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                        $grade = get_string('outofshort', 'quiz', $a);
                    }
                    if ($alloptions->overallfeedback) {
                        $feedback = quiz_feedback_for_grade($grades[$quiz->id], $quiz, $context);
                    }
                }
                $data[] = $grade;
                if ($showfeedback) {
                    $data[] = $feedback;
                }
            }

            $table->data[] = $data;
        } // End of loop over quiz instances.

        // Display the table.
        return html_writer::table($table);
    }

    function getAllAssignments($id = null, $course = null)
    {
        // mod/assign/index.php?id=66
        global $CFG;
        require_once($CFG->dirroot.'/mod/assign/locallib.php');
        // For this type of page this is the course id.
        // $id = required_param('id', PARAM_INT);

        // $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
        // require_login($course);
        // $PAGE->set_url('/mod/assign/index.php', array('id' => $id));
        // $PAGE->set_pagelayout('incourse');

        \mod_assign\event\course_module_instance_list_viewed::create_from_course($course)->trigger();

        // Print the header.
        $strplural = get_string("modulenameplural", "assign");
        // $PAGE->navbar->add($strplural);
        // $PAGE->set_title($strplural);
        // $PAGE->set_heading($course->fullname);
        // echo $OUTPUT->header();
        // echo $OUTPUT->heading(format_string($strplural));

        $context = context_course::instance($course->id);

        require_capability('mod/assign:view', $context);

        $assign = new assign($context, null, $course);

        // Get the assign to render the page.
        $temp = $assign->view('view_course_index_plain');
        $temp_array = json_decode($temp);
        $align = array('left');
        $headings = array("Assignment", "Due Date", "Submission");

        $table = new html_table();
        $table->head = $headings;
        $table->align = $align;

        if ($temp_array && count($temp_array) > 0) {
            foreach ($temp_array as $assi) {
                $data = array();
                
                // Name and link to assignment
                $linky = new moodle_url($CFG->wwwroot . "/mod/assign/view.php?id=". $assi->cmid);
                $data[] = '<a href="'. $linky. '">' . $assi->cmname;
                        
                // Due Date of Assignment
                $data[] = userdate($assi->timedue);
                
                // Submissions
                $data[] = $assi->submissioninfo;

                $table->data[] = $data;
            }
        }
        return html_writer::table($table);
    }
}
