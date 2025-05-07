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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo, David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// require_once("$CFG->dirroot/enrol/workdaystudent/lib.php");
if (isset($CFG)) {
    require_once($CFG->dirroot . '/enrol/workdaystudent/lib.php');
} else {
    // require_once('../../config.php');
    require_once('../../enrol/workdaystudent/lib.php');
}

require_once(dirname(__FILE__) . '/publiclib.php');

class enrol_wds_plugin extends enrol_workdaystudent_plugin {

    /**
     * Typical error log
     *
     * @var array
     */
    private $errors = array();

    /**
     * Typical email log
     *
     * @var array
     */
    private $emaillog = array();

    /**
     * admin config setting
     *
     * @var bool
     */
    public $issilent = false;

    /**
     * an instance of the wds enrollment provider.
     *
     * Provider is configured in admin settings.
     *
     * @var enrollment_provider $_provider
     */
    private $_provider;

    /**
     * Provider initialization status.
     *
     * @var bool
     */
    private $_loaded = false;

    /**
     * Require internal and external libs.
     *
     * @global object $CFG
     */
    public function __construct() {
        global $CFG;

        wds::require_dalos();
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');
    }

    /**
     * Returns name of the enrol plugin used.
     * @return string
     */
    public function get_name() {
        return 'workdaystudent';
    }

    /**
     * Master method for kicking off WDS enrollment
     * First checks a few top-level requirements to run, and then passes on to a secondary method for handling the process
     *
     * @param  boolean $run_as_adhoc  whether or not the task has been run "ad-hoc" or "scheduled" (default)
     * @return boolean
     */
    public function run_enrollment_process($runasadhoc = false) {
        global $CFG;

        // Capture start time for later use.
        $starttime = microtime(true);

        // First, run a few top-level checks before processing enrollment.
        try {
            // Make sure task is NOT disabled (if not run adhoc).
            if (!$runasadhoc and ! $this->task_is_enabled()) {
                // TODO: Make a real lang string for this.
                throw new WdsInitException('This scheduled task has been disabled.');
            }

            // Make sure WDS is NOT running.
            if ($this->is_running()) {
                throw new WdsInitException(
                    wds::_s('already_running', $CFG->wwwroot . '/admin/settings.php?section=enrolsettingswds')
                );
            }

            // Make sure WDS is not within grace period threshold.
            if ($this->is_within_graceperiod()) {
                throw new WdsInitException(
                    wds::_s('within_grace_period', $CFG->wwwroot . '/admin/settings.php?section=enrolsettingswds')
                );
            }

            // Attempt to fetch the configured enrollment provider.
            $provider = $this->provider();

            // Make sure we have a provider loaded before we proceed any further.
            if (!$provider) {
                // TODO: Make a real lang string for this.
                throw new WdsInitException('Could not load the enrollment provider.');
            }

            // Catch any initial errors here before attempting to run.
        } catch (WdsInitException $e) {
            // Add the error to the stack.
            $this->add_error($e->getMessage());

            // Email the error report, reporting errors only.
            $this->email_reports(true);

            // Leave the process.
            return false;
        }

         // Now, begin using the provider to pull data and manifest enrollment.
         // Note start time for reporting.
        return $this->run_provider_enrollment($provider, $starttime);
    }

    /**
     * Runs enrollment for a given wds provider
     *
     * @param  enrollment_provider  $provider
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return boolean  success
     */
    public function run_provider_enrollment($provider, $starttime) {
        // First, flag the process as running.
        $this->setting('running', true);

        // Send startup email.
        $this->email_startup_report($starttime);

        // Begin log messages.
        $this->log('------------------------------------------------');
        $this->log(wds::_s('pluginname'));
        $this->log('------------------------------------------------');

        // Handle any provider preprocesses.
        if (!$provider->preprocess($this)) {
            $this->add_error('Error during preprocess.');
        }

        // Pull provider data.
        $this->log('Pulling information from ' . $provider->get_name());
        $this->process_all();
        $this->log('------------------------------------------------');

        // Manifest provider data.
        $this->log('Begin manifestation ...');
        $this->handle_enrollments();

        // Handle any provider postprocesses.
        if (!$provider->postprocess($this)) {
            $this->add_error('Error during postprocess.');
        }

        // End log messages.
        $this->log('------------------------------------------------');
        $this->log('wds enrollment took: ' . $this->get_time_elapsed_during_enrollment($starttime));
        $this->log('------------------------------------------------');

        // Flag the process as no longer running.
        $this->setting('running', false);

        // Email final report.
        $this->email_reports(false, $starttime);

        // Handle any errors automatically per threshold settings.
        // TODO: This causes a blank email to be sent even if everything ran OK.
        $this->handle_automatic_errors();

        $this->email_reports(true);
        return true;
    }

    /**
     * Emails a wds "startup" report to moodle administrators
     *
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return void
     */
    private function email_startup_report($starttime) {
        // Get all moodle admin users.
        $users = get_admins();
        // Email these users the job has begun.
        $this->email_wds_startup_report_to_users($users, $starttime);
    }

    /**
     * Emails a wds startup report (notification of start time) to given users
     *
     * @param  array  $users  moodle users
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return void
     */
    private function email_wds_startup_report_to_users($users, $starttime) {
        global $CFG;

        $starttimedisplay = $this->format_time_display($starttime);

        // Get email content from email log.
        $emailcontent = 'This email is to let you know that WDS Enrollment has begun at:' . $starttimedisplay;

        // Send to each admin.
        foreach ($users as $user) {
            email_to_user($user, wds::_s('pluginname'), sprintf('WDS Enrollment Begun [%s]', $CFG->wwwroot), $emailcontent);
        }
    }

    /**
     * Finds and emails moodle administrators enrollment reports
     *
     * Optionally, skips the default log report and send errors only
     *
     * @param  boolean  $report_errors_only
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return void
     */
    public function email_reports($reporterrorsonly = false, $starttime = '') {
        // Get all moodle admin users.
        $users = get_admins();

        // Determine whether or not we're sending an email log report to admins.
        if (!$reporterrorsonly and $this->setting('email_report')) {
            $this->email_wds_log_report_to_users($users, $starttime);
        }

        // Determine whether or not there are errors to report.
        if ($this->errors_exist()) {
            $this->email_wds_error_report_to_users($users, $starttime);
        }
    }

    /**
     * Emails a wds log report (from emaillog) to given users
     *
     * @param  array  $users  moodle users
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return void
     */
    private function email_wds_log_report_to_users($users, $starttime) {
        global $CFG;

        // Get email content from email log.
        $emailcontent = implode("\n", $this->emaillog);

        if ($starttime) {
            $starttimedisplay = $this->format_time_display($starttime);

            $emailcontent .= "\n\nThis process began at: " . $starttimedisplay;
        }

        // Send to each admin.
        foreach ($users as $user) {
            email_to_user($user, wds::_s('pluginname'), sprintf('WDS Log [%s]', $CFG->wwwroot), $emailcontent);
        }
    }

    /**
     * Emails a wds error report (from errors stack) to given users
     *
     * @param  array  $users  moodle users
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return void
     */
    private function email_wds_error_report_to_users($users, $starttime) {
        global $CFG;

        // Get email content from error log.
        $emailerrorcontent = implode("\n", $this->get_errors());

        if ($starttime) {
            $starttimedisplay = $this->format_time_display($starttime);

            $emailerrorcontent .= "\n\nThis process begun at: " . $starttimedisplay;
        }

        // Send to each admin.
        foreach ($users as $user) {
            email_to_user($user, wds::_s('pluginname'), sprintf('[SEVERE] WDS Errors [%s]', $CFG->wwwroot), $emailerrorcontent);
        }
    }

    /**
     * Determines whether or not there are any saved errors at this point
     *
     * @return bool
     */
    private function errors_exist() {
        return (empty($this->get_errors())) ? false : true;
    }

    /**
     * Formats a Unix time for display
     *
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return string
     */
    private function format_time_display($time) {
        $dformat = "l jS F, Y - H:i:s";
        $msecs = $time - floor($time);
        $msecs = substr($msecs, 1);

        $formatted = sprintf('%s%s', date($dformat), $msecs);

        return $formatted;
    }

    /**
     * Calculates amount of time (in seconds) that has elapsed since a given start time
     *
     * @param  float  $start_time  the current time in seconds since the Unix epoch
     * @return string  time difference in seconds
     */
    private function get_time_elapsed_during_enrollment($starttime) {
        // Get the difference between start and end in microseconds, as a float value.
        $diff = microtime(true) - $starttime;

        // Break the difference into seconds and microseconds.
        $sec = intval($diff);
        $micro = $diff - $sec;

        // Format the result as you want it - will contain something like "00:00:02.452".
        $timeelapsed = core_date::strftime('%T', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro));

        return $timeelapsed;
    }

    /**
     * Getter for self::$_provider.
     *
     * If self::$provider is not set already, this method
     * will attempt to initialize it by calling self::init()
     * before returning the value of self::$_provider
     * @return enrollment_provider
     */
    public function provider() {
        if (empty($this->_provider) and !$this->_loaded) {
            $this->init();
        }

        return $this->_provider;
    }

    /**
     * Try to initialize the provider.
     *
     * Tries to create and initialize the provider.
     * Tests whether provider supports departmental or section lookups.
     * @throws Exception if provider cannot be created of if provider supports
     * neither section nor department lookups.
     */
    public function init() {
        try {
            $this->_provider = wds::create_provider();

            if (empty($this->_provider)) {
                throw new Exception('enrollment_unsupported');
            }

            $works = (
                $this->_provider->supports_section_lookups() or
                $this->_provider->supports_department_lookups()
            );

            if ($works === false) {
                throw new Exception('enrollment_unsupported');
            }
        } catch (Exception $e) {
            $a = wds::translate_error($e);

            $this->add_error(wds::_s('provider_cron_problem', $a));
        }

        $this->_loaded = true;
    }

    public function course_updated($inserted, $course, $data) {
        // WDS is the one to create the course.
        if ($inserted) {
            return;
        }
    }

    private function handle_automatic_errors() {
        $errors = wds_error::get_all();

        $errorthreshold = $this->setting('error_threshold');

        $running = (bool)$this->setting('running');

        // Don't reprocess if the module is running.
        if ($running) {
            return;
        }

        if (count($errors) > $errorthreshold) {
            $this->add_error(wds::_s('error_threshold_log'));
            return;
        }

        wds::reprocess_errors($errors, true);
    }

    public function handle_enrollments() {
        // Users will be unenrolled.
        $pending = wds_section::get_all(array('status' => wds::PENDING));
        $this->handle_pending_sections($pending);

        // Users will be enrolled.
        $processed = wds_section::get_all(array('status' => wds::PROCESSED));
        $this->handle_processed_sections($processed);
    }

    /**
     * Get (fetch, instantiate, save) semesters
     * considered valid at the current time, and
     * process enrollment for each.
     */
    public function process_all() {
        $time = time();
        $processedsemesters = $this->get_semesters($time);

        foreach ($processedsemesters as $semester) {
            $this->process_semester($semester);
        }
    }

    /**
     * @param wds_semester[] $semester
     */
    public function process_semester($semester) {
        $processcourses = $this->get_courses($semester);

        if (empty($processcourses)) {
            return;
        }

        $setbydepartment   = (bool) $this->setting('process_by_department');

        $supportsdepartment = $this->provider()->supports_department_lookups();

        $supportssection    = $this->provider()->supports_section_lookups();

        if ($setbydepartment and $supportsdepartment) {
            $this->process_semester_by_department($semester, $processcourses);
        } else if (!$setbydepartment and $supportssection) {
            $this->process_semester_by_section($semester, $processcourses);
        } else {
            $message = wds::_s('could_not_enroll', $semester);

            $this->log($message);
            $this->add_error($message);
        }
    }

    /**
     * @param wds_semester $semester
     * @param wds_course[] $courses NB: must have department attribute set
     */
    private function process_semester_by_department($semester, $courses) {
        $departments = wds_course::flatten_departments($courses);

        foreach ($departments as $department => $courseids) {
            $filters = wds::where()->semesterid->equal($semester->id)->courseid->in($courseids);

            // Current means they already exist in the DB.
            $currentsections = wds_section::get_all($filters);

            $this->process_enrollment_by_department(
                $semester, $department, $currentsections
            );
        }
    }

    private function process_semester_by_section($semester, $courses) {
        foreach ($courses as $course) {
            foreach ($course->sections as $section) {
                $wdssection = wds_section::by_id($section->id);
                $this->process_enrollment(
                    $semester, $course, $wdssection
                );
            }
        }
    }

    /**
     * From enrollment provider, get, instantiate,
     * save (to {enrol_wds_semesters}) and return all valid semesters.
     * @param int time
     * @return wds_semester[] these objects will be later upgraded to wds_semesters
     *
     */
    public function get_semesters($time) {
        $setdays = (int) $this->setting('sub_days');
        $subdays = 24 * $setdays * 60 * 60;

        $now = wds::format_time($time - $subdays);

        $this->log('Pulling Semesters for ' . $now . '...');
        $onlinesemesters = array();

        try {
            $semestersource = $this->provider()->semester_source();
            $semestersource2 = $this->provider()->semester_source2();

            $semesters1 = $semestersource->semesters($now);
            $semesters2 = $semestersource2->semesters($now);
            foreach ($semesters2 as $onlinesemester) {
                $onlinesemester->campus = "ONLINE";
                $onlinesemesters[] = $onlinesemester;
            }

            $semesters = array_merge($onlinesemesters, $semesters1);

            $this->log('Processing ' . count($semesters) . " Semesters...\n");
            $psemesters = $this->process_semesters($semesters);

            $v = function($s) {
                return !empty($s->grades_due);
            };

            $i = function($s) {
                return !empty($s->semester_ignore);
            };

            list($other, $failures) = $this->partition($psemesters, $v);

            // Notify improper semester.
            foreach ($failures as $failedsem) {
                $this->add_error(wds::_s('failed_sem', $failedsem));
            }

            list($ignored, $valids) = $this->partition($other, $i);

            // Ignored sections with semesters will be unenrolled.
            foreach ($ignored as $ignoredsem) {
                $wheremanifested = wds::where()->semesterid->equal($ignoredsem->id)->status->equal(wds::MANIFESTED);

                $todrop = array('status' => wds::PENDING);

                // This will be caught in regular process.
                wds_section::update($todrop, $wheremanifested);
            }

            $semsin = function ($sem) use ($time, $subdays) {
                $endcheck = $time < $sem->grades_due;

                return ($sem->classes_start - $subdays) < $time && $endcheck;
            };

            return array_filter($valids, $semsin);
        } catch (Exception $e) {

            $this->add_error($e->getMessage());
            return array();
        }
    }

    public function partition($collection, $func) {
        $pass = array();
        $fail = array();

        foreach ($collection as $key => $single) {
            if ($func($single)) {
                $pass[$key] = $single;
            } else {
                $fail[$key] = $single;
            }
        }

        return array($pass, $fail);
    }

    /**
     * Fetch courses from the enrollment provider, and pass them to
     * process_courses() for instantiations as wds_course objects and for
     * persisting to {enrol_wds(_courses|_sections)}.
     *
     * @param wds_semester $semester
     * @return wds_course[]
     */
    public function get_courses($semester) {
        $this->log('Pulling Courses / Sections for ' . $semester);
        try {
            $courses = $this->provider()->course_source()->courses($semester);

            $this->log('Processing ' . count($courses) . " Courses...\n");
            $processcourses = $this->process_courses($semester, $courses);

            return $processcourses;
        } catch (Exception $e) {
            $this->add_error(sprintf(
                    'Unable to process courses for %s; Message was: %s',
                    $semester,
                    $e->getMessage()
                    ));

            // Queue up errors.
            wds_error::courses($semester)->save();

            return array();
        }
    }

    /**
     * Workhorse method that brings enrollment data from the provider together with existing records
     * and then dispatches sub processes that operate on the differences between the two.
     *
     * @param wds_semester $semester semester to process
     * @param string $department department to process
     * @param wds_section[] $current_sections current wds records for the department/semester combination
     */
    public function process_enrollment_by_department($semester, $department, $currentsections) {
        try {

            $teachersource = $this->provider()->teacher_department_source();
            $studentsource = $this->provider()->student_department_source();

            $teachers = $teachersource->teachers($semester, $department);
            $students = $studentsource->students($semester, $department);

            $sectionids = wds_section::ids_by_course_department($semester, $department);

            $filter = wds::where('sectionid')->in($sectionids);
            $currentteachers = wds_teacher::get_all($filter);
            $currentstudents = wds_student::get_all($filter);

            $idsparam    = wds::where('id')->in($sectionids);
            $allsections = wds_section::get_all($idsparam);

            $this->process_teachers_by_department($semester, $department, $teachers, $currentteachers);
            $this->process_students_by_department($semester, $department, $students, $currentstudents);

            unset($currentteachers);
            unset($currentstudents);

            foreach ($currentsections as $section) {
                $course = $section->course();
                // Set status to wds::PROCESSED.
                $this->post_section_process($semester, $course, $section);

                unset($allsections[$section->id]);
            }

            // Drop remaining sections.
            if (!empty($allsections)) {
                wds_section::update(
                    array('status' => wds::PENDING),
                    wds::where('id')->in(array_keys($allsections))
                );
            }

        } catch (Exception $e) {

            $info = "$semester $department";

            $message = sprintf(
                    "Message: %s\nFile: %s\nLine: %s\nTRACE:\n%s\n",
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                    );
            $this->add_error(sprintf('Failed to process %s:\n%s', $info, $message));

            wds_error::department($semester, $department)->save();
        }
    }

    /**
     *
     * @param wds_semester $semester
     * @param string $department
     * @param object[] $teachers
     * @param wds_teacher[] $current_teachers
     */
    public function process_teachers_by_department($semester, $department, $teachers, $currentteachers) {
        $this->fill_roles_by_department('teacher', $semester, $department, $teachers, $currentteachers);
    }

    /**
     *
     * @param wds_semester $semester
     * @param string $department
     * @param object[] $students
     * @param wds_student[] $current_students
     */
    public function process_students_by_department($semester, $department, $students, $currentstudents) {
        $this->fill_roles_by_department('student', $semester, $department, $students, $currentstudents);
    }

    /**
     *
     * @param string $type @see process_teachers_by_department
     * and @see process_students_by_department for possible values 'student'
     * or 'teacher'
     * @param wds_section $semester
     * @param string $department
     * @param object[] $pulled_users incoming users from the provider
     * @param wds_teacher[] | wds_student[] $current_users all wds users for this semester
     */
    private function fill_roles_by_department($type, $semester, $department, $pulledusers, $currentusers) {
        foreach ($pulledusers as $user) {
            $courseparams = array(
                'department' => $department,
                'course_number' => $user->course_number
            );

            $course = wds_course::get($courseparams);

            if (empty($course)) {
                continue;
            }

            $sectionparams = array(
                'semesterid' => $semester->id,
                'courseid'   => $course->id,
                'section_number' => $user->section_number
            );

            $section = wds_section::get($sectionparams);

            if (empty($section)) {
                continue;
            }
            $this->{'process_'.$type.'s'}($section, array($user), $currentusers);

        }

        $this->release($type, $currentusers);
    }

    /**
     *
     * @param stdClass[] $semesters
     * @return wds_semester[]
     */
    public function process_semesters($semesters) {
        $processed = array();

        foreach ($semesters as $semester) {
            try {
                $params = array(
                    'year'        => $semester->period_year,
                    'name'        => $semester->period_type,
                    'campus'      => $semester->campus,
                    'session_key' => $semester->session_key
                );

                // Convert the obj to full-fledged wds semester.
                $wds = wds_semester::upgrade_and_get($semester, $params);

                if (empty($wds->classes_start)) {
                    continue;
                }

                // Persist to Database table wds_semesters.
                $wds->save();
                // TODO: semestermeta?????
                // Fill in metadata from the table enrol_wds_semestermeta.
                $wds->fill_meta();

                $processed[] = $wds;
            } catch (Exception $e) {
                $this->add_error($e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * For each of the courses provided, instantiate as a wds_course
     * object; persist to the {enrol_wds_courses} table; then iterate
     * through each of its sections, instantiating and persisting each.
     * Then, assign the sections to the <code>course->sections</code> attirbute,
     * and add the course to the return array.
     *
     * @param wds_semester $semester
     * @param object[] $courses
     * @return wds_course[]
     */
    public function process_courses($semester, $courses) {
        $processed = array();

        foreach ($courses as $course) {
            try {
                $params = array(
                    'department' => $course->department,
                    'course_number' => $course->course_number
                );

                $wdscourse = wds_course::upgrade_and_get($course, $params);

                $wdscourse->save();

                $processedsections = array();
                foreach ($wdscourse->sections as $section) {
                    $params = array(
                        'courseid'   => $wdscourse->id,
                        'semesterid' => $semester->id,
                        'section_number' => $section->section_number
                    );

                    $wdssection = wds_section::upgrade_and_get($section, $params);

                    /*
                     * If the section does not already exist
                     * in {enrol_wds_sections}, insert it,
                     * marking its status as PENDING.
                     */
                    if (empty($wdssection->id)) {
                        $wdssection->courseid   = $wdscourse->id;
                        $wdssection->semesterid = $semester->id;
                        $wdssection->status     = wds::PENDING;

                        $wdssection->save();
                    }

                    $processedsections[] = $wdssection;
                }

                /*
                 * Replace the sections attribute of the course with
                 * the fully instantiated, and now persisted,
                 * wds_section objects.
                 */
                $wdscourse->sections = $processedsections;

                $processed[] = $wdscourse;
            } catch (Exception $e) {
                $this->add_error($e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Could be used to process a single course upon request
     */
    public function process_enrollment($semester, $course, $section) {
        $teachersource = $this->provider()->teacher_source();

        $studentsource = $this->provider()->student_source();

        try {
            $teachers = $teachersource->teachers($semester, $course, $section);
            $students = $studentsource->students($semester, $course, $section);

            $filter = array('sectionid' => $section->id);
            $currentteachers = wds_teacher::get_all($filter);
            $currentstudents = wds_student::get_all($filter);

            $this->process_teachers($section, $teachers, $currentteachers);
            $this->process_students($section, $students, $currentstudents);

            $this->release('teacher', $currentteachers);
            $this->release('student', $currentstudents);

            $this->post_section_process($semester, $course, $section);
        } catch (Exception $e) {
            $this->add_error($e->getMessage());

            wds_error::section($section)->save();
        }
    }

    private function release($type, $users) {

        foreach ($users as $user) {
            // No reason to release a second time.
            if ($user->status == wds::UNENROLLED) {
                continue;
            }

            // Maybe the course hasn't been created... clear the pending flag.
            $status = $user->status == wds::PENDING ? wds::UNENROLLED : wds::PENDING;

            $user->status = $status;
            $user->save();

            global $CFG;
            if ($type === 'teacher') {
                if (file_exists($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php')) {
                    require_once($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php');

                    // Specific release for instructor.
                    $user = blocks_wdsprefs_wds_handler::wds_teacher_release($user);
                }
            } else if ($type === 'student') {
                if (file_exists($CFG->dirroot.'/blocks/ues_logs/eventslib.php')) {
                    require_once($CFG->dirroot.'/blocks/ues_logs/eventslib.php');
                    // TODO: FIX THIS SHIT!!!!!
                    ues_logs_event_handler::ues_student_release($user);
                }
            }

            // Drop manifested sections for teacher POTENTIAL drops.
            if ($user->status == wds::PENDING and $type == 'teacher') {
                $existing = wds_teacher::get_all(wds::where()->status->in(wds::PROCESSED, wds::ENROLLED));

                // No other primary, so we can safely flip the switch.
                if (empty($existing)) {
                   wds_section::update(
                        array('status' => wds::PENDING),
                        array(
                            'status' => wds::MANIFESTED,
                            'id' => $user->sectionid
                        )
                    );
                }
            }
        }
    }

    private function post_section_process($semester, $course, $section) {
        // Process section only if teachers can be processed.
        // Take into consideration outside forces manipulating.
        // Processed numbers through event handlers.
        $byprocessed = wds::where()->status->in(wds::PROCESSED, wds::ENROLLED)->sectionid->equal($section->id);

        $processedteachers = wds_teacher::count($byprocessed);

        // A section _can_ be processed only if they have a teacher.
        // Further, this has to happen for a section to be queued for enrollment.
        if (!empty($processedteachers)) {
            // Full section.
            $section->semester = $semester;
            $section->course = $course;

            $previousstatus = $section->wd_status;

            $count = function ($type) use ($section) {
                $enrollment = wds::where()->sectionid->equal($section->id)->status->in(wds::PROCESSED, wds::PENDING);

                $class = 'wds_'.$type;

                return $class::count($enrollment);
            };

            $willenroll = ($count('teacher') or $count('student'));

            if ($willenroll) {
                // Make sure the teacher will be enrolled.
                wds_teacher::reset_status($section, wds::PROCESSED, wds::ENROLLED);
                $section->wd_status = wds::PROCESSED;
            }

            // Allow outside interaction.
            global $CFG;
            if (file_exists($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php')) {
                require_once($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php');
                $section = blocks_wdsprefs_wds_handler::wds_section_process($section);
            }

            if ($previousstatus != $section->wd_status) {
                $section->save();
            }
        }
    }

    public function process_teachers($section, $users, &$currentusers) {
        return $this->fill_role('teacher', $section, $users, $currentusers, function($user) {
            return array('primary_flag' => $user->primary_flag);
        });
    }

    /**
     * Process students.
     *
     * This function passes params on to enrol_wds_plugin::fill_role()
     * which does not return any value.
     *
     * @see enrol_wds_plugin::fill_role()
     * @param wds_section $section
     * @param object[] $users
     * @param (wds_student | wds_teacher)[] $current_users
     * @return void
     */
    public function process_students($section, $users, &$currentusers) {
        return $this->fill_role('student', $section, $users, $currentusers);
    }

    // Allow public API to reset unenrollments.
    public function reset_unenrollments($section) {
        $course = $section->moodle();

        // Nothing to do.
        if (empty($course)) {
            return;
        }

        $wdscourse = $section->course();

        foreach (array('student', 'teacher') as $type) {
            $group = $this->manifest_group($course, $wdscourse, $section);

            $class = 'wds_' . $type;

            $params = array(
                'sectionid' => $section->id,
                'status' => wds::UNENROLLED
            );

            $users = $class::get_all($params);
            $this->unenroll_users($group, $users);
        }
    }

    /**
     * Unenroll courses/sections.
     *
     * Given an input array of wds_sections, remove them and their enrollments
     * from active status.
     * If the section is not manifested, set its status to wds::SKIPPED.
     * If it has been manifested, get a reference to the Moodle course.
     * Get the students and teachers enrolled in the course and unenroll them.
     * Finally, set the idnumber to the empty string ''.
     *
     * In addition, we will @see events_trigger TRIGGER EVENT 'wds_course_severed'.
     *
     * @global object $DB
     * @param wds_section[] $sections
     */
    public function handle_pending_sections($sections) {
        global $DB, $USER;

        if ($sections) {
            
            $zcount = count($sections);
            error_log('Found ' . $zcount. ' Sections that will not be manifested.');
            
            // try {
            //     $this->log('Found ' . count($sections) . ' Sections that will not be manifested.');
            // } catch (Exception $e) {
            //     error_log("\n\nERROR: can't seem to output to the log");
            // }
        }

        foreach ($sections as $section) {
            if ($section->is_manifested()) {

                $params = array('idnumber' => $section->idnumber);
                // Get the moodle course
                $course = $section->moodle();

                $wdscourse = $section->course();

                foreach (array('student', 'teacher') as $type) {

                    $group = $this->manifest_group($course, $wdscourse, $section);

                    $class = 'wds_' . $type;
                    $params = wds::where()->section_listing_id
                        ->equal($section->section_listing_id)
                        ->status
                        ->in(
                            wds::ENROLLED,
                            wds::PROCESSED,
                            wds::PENDING,
                            // wds::UNENROLLED,
                            // wds::COMPLETED,
                            wds::COMPLETEDED,
                            wds::ENROLL,
                            wds::UNENROLL
                        );

                    // $params = $class::get_all(wds::where()
                    //     ->join('{enrol_'.$class.'_enroll}', 'rolly')->on('universal_id', 'universal_id')
                    //     ->rolly->section_listing_id->equal($section->section_listing_id)
                    //     ->status->status
                    //     ->in(
                    //         wds::ENROLLED,
                    //         wds::PROCESSED,
                    //         wds::PENDING,
                    //         wds::UNENROLLED
                    //     )
                    // );

                    // DALO: hack fix for now
                    if ($class == 'wds_student') {
                        $params->tablex = 'enrol_wds_student_enroll';
                    } else if ($class == 'wds_teacher') {
                        $params->tablex = 'enrol_wds_teacher_enroll';
                    }
                    $users = $class::get_all($params);

                    foreach ($users as $user) {
                        $dis_user = $class::get_userid($user->universal_id);
                        // $class::get_all(wds::where()->universal_id
                        //     ->equal($user->universal_id
                        // ));
                        // $dis_user = $class::get_all($params);
                        $users[$user->id]->user = $dis_user;
                        $users[$user->id]->userid = $dis_user->userid;
                    }
                    $this->unenroll_users($group, $users);
                }
                // DALO: TODO - visible setting???????
                // Set course visibility according to user preferences (block_cps).
                /*
                $settingparams = wds::where()->userid->equal($USER->id)->name->starts_with('creation_');

                $settings        = wds_setting::get_to_name($settingparams);
                $setting         = !empty($settings['creation_visible']) ? $settings['creation_visible'] : false;

                $course->visible = isset($setting->value) ? $setting->value : get_config('moodlecourse', 'visible');

                $DB->update_record('course', $course);
                */
                error_log("\n\nFUK YOU LOG function, you have crashed here 987654 times \n\n");
                // $this->log('Unloading ' . $course->idnumber);
                // $this->log('Unloading ' . $course->idnumber);

                // Refactor events_trigger_legacy().
                global $CFG;
                if (file_exists($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php')) {
                    require_once($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php');
                    blocks_wdsprefs_wds_handler::wds_course_severed($course);
                }

                $section->idnumber = '';
            }
            $section->wd_status = wds::SKIPPED;
            $section->save();
        }
        // $this->log(' ');
    }

    /**
     * Handle courses to be manifested.
     *
     * For each incoming section, manifest the course and update its status to
     * wds::Manifested.
     *
     * Skip any incoming section whose status is wds::PENDING.
     *
     * @param wds_section[] $sections
     */
    public function handle_processed_sections($sections) {
        if ($sections) {
            error_log('STUPID POS LOG CRASHES LIKE A BIT**');
            // $this->log('Found ' . count($sections) . ' Sections ready to be manifested.');
        }

        $returndata = [];

        foreach ($sections as $section) {
            if ($section->wd_status == wds::PENDING) {
                continue;
            }

            $semester = $section->semester();

            $course = $section->course();

            $success = $this->manifestation($semester, $course, $section);

            $data = new stdClass();
            $data->id = $section->id;

            if ($success) {
                $section->wd_status = wds::MANIFESTED;
                $section->save();

                $data->value = $section->wd_status;
            } else {
                $data->value = 'failed';
            }
            $returndata[] = $data;
        }

        // $this->log(' ');
        // log keeps crashing

        return $returndata;
    }

    public function get_instance($courseid) {
        global $DB;

        $instances = enrol_get_instances($courseid, true);

        $attempt = array_filter($instances, function($in) {
            return $in->enrol == 'workdaystudent';
        });

        // Cannot enrol without an instance.
        if (empty($attempt)) {
            $courseparams = array('id' => $courseid);
            $course = $DB->get_record('course', $courseparams);

            $id = $this->add_instance($course);

            return $DB->get_record('enrol', array('id' => $id));
        } else {
            return current($attempt);
        }
    }

    public function manifest_category($course) {
        global $DB;

        // $catparams = array('name' => $course->department);
        // $categories = $DB->get_records('course_categories', $catparams);
    
        $sql = "SELECT child.*, parent.id as pid
            FROM mdl_course_categories child
            JOIN mdl_course_categories parent ON child.parent = parent.id
            WHERE parent.name = 'WorkdayStudent' 
            and child.name='".$course->course_subject_abbreviation."' ORDER BY child.sortorder";

        $category = $DB->get_records_sql($sql);

        if (!$category) {
            $category = new stdClass;

            $category->name = $course->department;
            $category->sortorder = 999;
            $category->parent = 0;
            $category->description = 'Courses under ' . $course->department;
            $category->id = $DB->insert_record('course_categories', $category);
        }
        
        if (count($category) > 1) {
            error_log("\n\nHEY dumbass, there's more than one cat\n\n");
        } else {
            $category = array_values($category);
            return $category[0];
        }
        return $category;
    }

    /**
     * Create all moodle objects for a given course.
     *
     * This method oeprates on a single section at a time.
     *
     * It's first action is to determine if a primary instructor change
     * has happened. This case is indicated by the existence, in {wds_teachers}
     * of two records for this section with primary_flag = 1. If one of those
     * records has status wds::PROCESSED (meaning: the new primary inst)
     * and the other has status wds::PENDING (meaning the old instructor,
     * marked for disenrollment), then we know a primary instructor swap is taking
     * place for the section, therefore, we trigger the
     * @link https://github.com/.......FIX THIS SHIT wds_primary_change event.
     *
     * Once the event fires, subscribers, such as CPS, have the opportunity to take
     * action on the players in the instructor swap.
     *
     * With respect to the notion of manifestation, the real work of this method
     * begins after handing instructor swaps, namely, manifesting the course and
     * its enrollments.
     *
     * @see wds_enrol_plugin::manifest_course
     * @see wds_enrol_plugin::manifest_course_enrollment
     * @event wds_primary_change
     * @param wds_semester $semester
     * @param wds_course $course
     * @param wds_section $section
     * @return boolean
     */
    private function manifestation($semester, $course, $section) {
        // Check for instructor changes.
        $teacherparams = array(
            'section_listing_id' => $section->section_listing_id,
            'role' => 'primary'
        );

        $newprimary = wds_teacher::get($teacherparams + array(
            'status' => wds::PROCESSED,
            'tablex' => 'enrol_wds_teacher_enroll'
        ));

        $oldprimary = wds_teacher::get($teacherparams + array(
            'status' => wds::PENDING,
            'tablex' => 'enrol_wds_teacher_enroll'
        ));

        // If there's no old primary, check to see if there's an old non-primary.
        if (!$oldprimary) {
            $oldprimary = wds_teacher::get(array(
                'section_listing_id'    => $section->section_listing_id,
                'status'       => wds::PENDING,
                'role' => '',
                'tablex' => 'enrol_wds_teacher_enroll'
            ));

            // Ff this is the same user getting a promotion, no need to unenroll the course.
            if ($oldprimary) {
                $oldprimary = $oldprimary->userid == $newprimary->userid ? false : $oldprimary;
            }
        }

        // Campuses may want to handle primary instructor changes differently.
        if ($newprimary and $oldprimary) {

            global $DB;
            $new = $DB->get_record('user', array('id' => $newprimary->userid));
            $old = $DB->get_record('user', array('id' => $oldprimary->userid));
            $this->log(sprintf("instructor change from %s to %s\n", $old->username, $new->username));

            $data = new stdClass;
            $data->section = $section;
            $data->old_primary = $oldprimary;
            $data->new_primary = $newprimary;

            // Refactor events_trigger().
            global $CFG;
            if (file_exists($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php')) {
                require_once($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php');
                $data = blocks_wdsprefs_wds_handler::wds_primary_change($data);
            }

            $section = $data->section;
        }

        // For certain we are working with a real course.
        try {
            // Quick fix 1/13/17 - if this fails, let's throw an exception, log, and then continue.
            $moodlecourse = $this->manifest_course($semester, $course, $section);
        } catch (Exception $e) {
            $this->log($e->getMessage());

            // Was not successful, so return false (?).
            return false;
        }
        $this->manifest_course_enrollment($moodlecourse, $course, $section);

        return true;
    }

    /**
     * Manifest enrollment for a given course section
     * Fetches a group using @see enrol_wds_plugin::manifest_group(),
     * fetches all teachers, students that belong to the group/section
     * and enrolls/unenrolls via @see enrol_wds_plugin::enroll_users() or @see unenroll_users()
     *
     * @param object $moodle_course object from {course}
     * @paramwds_course $course object from {enrol_wds_courses}
     * @paramwds_section $section object from {enrol_wds_sections}
     */
    private function manifest_course_enrollment($moodlecourse, $course, $section) {
        $group = $this->manifest_group($moodlecourse, $course, $section);

        $generalparams = array('section_listing_id' => $section->section_listing_id);

        $actions = array(
            wds::PROCESSED => 'enroll',
            wds::PENDING => 'unenroll'
        );

        $unenrollcount = $enrollcount = 0;
        foreach (array('teacher', 'student') as $type) {
            $class = 'wds_' . $type;
            $tablex = 'enrol_wds_' . $type. '_enroll';
            foreach ($actions as $status => $action) {
                $actionparams = $generalparams + array(
                    'status' => $status,
                    'tablex' => $tablex
                );
                ${$action . 'count'} = $class::count($actionparams);
                
                if (${$action . 'count'}) {
                    // This will only happen if there are no more
                    // teachers and students are set to be enrolled
                    // We should log it as a potential error and continue.
                    try {
                        
                        $toaction = $class::get_all($actionparams);
                        $this->{$action . '_users'}($group, $toaction);
                        // foreach ($toaction as $tion) {
                        //     $user = $tion::get_userid($tion->universal_id);
                        //     $this->{$action . '_users'}($group, array($user));
                        // }
                    } catch (Exception $e) {
                        $this->add_error(wds::_s('error_no_group', $group));
                    }
                }
            }
        }

        if ($unenrollcount or $enrollcount) {
            $this->log('Manifesting enrollment for: ' . $moodlecourse->idnumber .
            ' ' . $section->section_number);

            $out = '';
            if ($unenrollcount) {
                $out .= 'Unenrolled ' . $unenrollcount . ' users; ';
            }

            if ($enrollcount) {
                $out .= 'Enrolled ' . $enrollcount . ' users';
            }

            $this->log($out);
        }
    }

    private function enroll_users($group, $users) {
        $instance = $this->get_instance($group->courseid);

        // Pull this setting once.
        $recover = $this->setting('recover_grades');

        // Require check once.
        if ($recover and !function_exists('grade_recover_history_grades')) {
            global $CFG;
            require_once($CFG->libdir . '/gradelib.php');
        }

        $recovergradesfor = function($user) use ($recover, $instance) {
            if ($recover) {
                grade_recover_history_grades($user->userid, $instance->courseid);
            }
        };
        $ecount = 0;
        foreach ($users as $user) {
            $roley = $this->determine_role($user);
            
            $roleid = $this->setting($roley->role . '_role');

            // DALO FIX: there is no setting for WDSPREFS YET!
            // if ($roleid == false) {
            //     if ($shortname == "student") {
            //         $roleid = 5;
            //     } else if ($shortname == "teacher") {
            //         $roleid = 10;
            //     }
            // }
            $user2 = $user::get_userid($user->universal_id);
            // $user3 = $userr::get(array('universal_id' => $userr->universal_id));

            $user->userid = $user2->userid;
            $this->enrol_user($instance, $user->userid, $roley->id);
            groups_add_member($group->id, $user->userid);
            
            $recovergradesfor($user);
            
            $class = get_class($user);
            $user->status = wds::ENROLLED;
            $saveparams = array(
                'tablex' => 'enrol_'.$class.'_enroll',
            );
            
            $user->save($saveparams);
            $ecount++;
        }
    }

    private function unenroll_users($group, $users) {
        global $DB;

        $instance = $this->get_instance($group->courseid);

        $course = $DB->get_record('course', array('id' => $group->courseid));

        foreach ($users as $user) {
            $roley = $this->determine_role($user);

            $class = 'wds_' . $roley->role;
            $roleid = $this->setting($roley->role . '_role');

            // DALO FIX: there is no setting for WDSPREFS YET!
            // if ($roleid == false) {
            //     if ($shortname == "student") {
            //         $roleid = 5;
            //     } else if ($shortname == "teacher") {
            //         $roleid = 10;
            //     }
            // }
            // Ignore pending statuses for users who have no role assignment.
            $context = context_course::instance($course->id);
            if (!is_enrolled($context, $user->userid)) {
                continue;
            }

            groups_remove_member($group->id, $user->user->userid);

            // Don't mark those meat to be unenrolled to processed.
            $prevstatus = $user->status;

            $tostatus = (
                $user->status == wds::PENDING or
                $user->status == wds::UNENROLLED
            ) ?
                wds::UNENROLLED :
                wds::PROCESSED;

            $user->status = $tostatus;
            
            // DALO: Can't use wsd_teacher obj as enroll has status.
            // $user->save();
            $this_class = 'enrol_'.$class.'_enroll';
            $userenrol = array(
                'section_listing_id' => $user->section_listing_id,
                'universal_id' => $user->universal_id,
            );
            $userenrolrec = $DB->get_record($this_class, $userenrol);
            $userenrolrec->status = $tostatus;
            if (!$DB->update_record($this_class, $userenrolrec)) {
                error_log("\n\nUSER ".$user->universal_id. " NOT FOUND!!!!!\n\n");
            }

            $sections = $user->sections_by_status(wds::ENROLLED);

            $isenrolled = false;
            $samesection = false;
            $suspendenrollment = get_config('enrol_wds', 'suspend_enrollment');

            foreach ($sections as $section) {
                if ($section->idnumber == $course->idnumber) {
                    $isenrolled = true;
                }

                // This user is enrolled as another role in the same section.
                if ($section->id == $user->section_listing_id) {
                    $samesection = true;
                }
            }

            // This user is enrolled as another role (teacher) in the same section so keep groups alive.
            if (!$isenrolled) {
                if ($suspendenrollment == 0) {
                    $this->unenrol_user($instance, $user->userid, $roley->id);
                } else {
                    $this->update_user_enrol($instance, $user->userid, ENROL_USER_SUSPENDED);
                }
            } else if ($samesection) {
                groups_add_member($group->id, $user->userid);
            }

            if ($tostatus != $prevstatus and $tostatus == wds::UNENROLLED) {
                $eventparams = array(
                    'group' => $group,
                    'wds_user' => $user
                );
            }
        }

        $countparams = array('groupid' => $group->id);
        if (!$DB->count_records('groups_members', $countparams)) {
            // Going ahead and delete.
            groups_delete_group($group->id);
        }
    }

    /**
     * Fetches existing or creates new group based on given params
     * @global type $DB
     * @param stdClass $moodle_course object from {course}
     * @param wds_course $course object from {enrol_wds_courses}
     * @param wds_section $section object from {enrol_wds_sections}
     * @return stdClass object from {groups}
     */
    private function manifest_group($moodlecourse, $course, $section) {
        global $DB;

        $dept = $course->get_department();

        $groupparams = array(
            'courseid' => $moodlecourse->id,
            'name' => "{$course->department} {$course->course_number} {$section->section_number}"
        );

        if (!$group = $DB->get_record('groups', $groupparams)) {
            $group = (object) $groupparams;
            $group->id = groups_create_group($group);
        }

        return $group;
    }

    private function manifest_course($semester, $course, $section) {
        global $DB;
        $primaryteacher = $section->primary();

        if (!$primaryteacher) {

            $primaryteacher = current($section->teachers());

            // Quick fix 1/13/17 - if there is still no primary teacher at this point
            // let's throw an exception to the parent manifestation method (which will log).
            if (!$primaryteacher) {
                throw new Exception('Cannot find primary teacher for section id: ' . $section->id);

                return;
            }
        }

        $assumedidnumber = $semester->period_year . $semester->period_type .
            // $course->course_subject_abbreviation . $semester->session_key . $course->course_number .
            $course->course_subject_abbreviation . $course->course_number .
            '-'.$primaryteacher->universal_id;

        // Take into consideration of outside forces manipulating idnumbers.
        // Therefore we must check the section's idnumber before creating one.
        // Possibility the course was deleted externally.

        $idnumber = !empty($section->idnumber) ? $section->idnumber : $assumedidnumber;

        $courseparams = array('idnumber' => $idnumber);

        $moodlecourse = $DB->get_record('course', $courseparams);

        // Handle system creation defaults.
        $settings = array(
            'visible', 'format', 'lang', 'groupmode', 'groupmodeforce', 'hiddensections',
            'newsitems', 'showgrades', 'showreports', 'maxbytes', 'enablecompletion',
            'completionstartonenrol', 'numsections', 'legacyfiles'
        );

        if (!$moodlecourse) {
            // DALO: this breaks, need mdl user, not wsd_user object
            // $user = $primaryteacher->user();
            $user = $DB->get_record('user', array('id' => $primaryteacher->userid));
            // $user = $primaryteacher->user();

            $session = empty($semester->session_key) ? '' :
                '(' . $semester->session_key . ') ';

            $category = $this->manifest_category($course);
            if (!isset($course->departments)) {
                $dept = $course->get_department();
            }
            $a = new stdClass;
            $a->year = $semester->period_year;
            $a->name = $semester->period_type;
            $a->session = $session;
            $a->department = $course->department;
            $a->course_number = $course->course_number;
            $a->fullname = fullname($user);
            $a->userid = $user->id;

            // $snpattern = $this->setting('course_shortname');
            // $fnpattern = $this->setting('course_fullname');

            // $shortname = wds::format_string($snpattern, $a);
            // $assumedfullname = wds::format_string($fnpattern, $a);

            $moodlecourse = new stdClass;
            $moodlecourse->idnumber = $idnumber;
            $moodlecourse->shortname = $section->moodle->shortname;
            // $moodlecourse->shortname = $shortname;
            // $moodlecourse->fullname = $assumedfullname;
            $moodlecourse->fullname = $section->moodle->fullname;
            $moodlecourse->category = $category->id;
            $moodlecourse->summary = $section->moodle->summary;
            // $moodlecourse->startdate = $semester->classes_start;
            $moodlecourse->startdate = $semester->start_date;

            // Set system defaults.
            foreach ($settings as $key) {
                $moodlecourse->$key = get_config('moodlecourse', $key);
            }

            // Refactor events_trigger_legacy call.
            global $CFG;
            if (file_exists($CFG->dirroot . '/blocks/wdsprefs/classes/wds_handler.php')) {
                require_once($CFG->dirroot . '/blocks/wdsprefs/classes/wds_handler.php');
                $moodlecourse = blocks_wdsprefs_wds_handler::wds_course_created($moodlecourse);
            }

            try {
                $moodlecourse = create_course($moodlecourse);

                $this->add_instance($moodlecourse);
            } catch (Exception $e) {
                $this->add_error(wds::_s('error_shortname', $moodlecourse));

                $courseparams = array('shortname' => $moodlecourse->shortname);
                $idnumber = $moodlecourse->idnumber;

                $moodlecourse = $DB->get_record('course', $courseparams);
                $moodlecourse->idnumber = $idnumber;

                if (!$DB->update_record('course', $moodlecourse)) {
                    $this->add_error('Could not update course: ' . $moodlecourse->idnumber);
                }
            }
        }

        if (!$section->idnumber) {
            $section->idnumber = $moodlecourse->idnumber;
            $section->save();
        }

        return $moodlecourse;
    }

    /**
     *
     * @global type $CFG
     * @param type $u
     *
     * @returnwds_user $user
     * @throws Exception
     */
    private function create_user($u) {
        $present = !empty($u->idnumber);

        $byidnumber = array('idnumber' => $u->idnumber);

        $byusername = array('username' => $u->username);

        $exactparams = $byidnumber + $byusername;

        $user =wds_user::upgrade($u);

        $unorem = $this->setting('username_email');

        if ($prev =wds_user::get($exactparams, true)) {
            $user->id = $prev->id;
        } else if ($present and $prev =wds_user::get($byidnumber, true)) {
            $user->id = $prev->id;
            // Update email or username.
            if ($unorem == 'un') {
                $user->email = $user->username . $this->setting('user_email');
            } else {
                $user->email = $user->username;
            }
        } else if ($prev =wds_user::get($byusername, true)) {
            $user->id = $prev->id;
        } else {
            global $CFG;
            if ($unorem == 'un') {
                $user->email = $user->username . $this->setting('user_email');
            } else {
                $user->email = $user->username;
            }
            $user->confirmed = $this->setting('user_confirm');
            $user->city = $this->setting('user_city');
            $user->country = $this->setting('user_country');
            $user->lang = $this->setting('user_lang');
            $user->firstaccess = time();
            $user->timecreated = $user->firstaccess;
            $user->auth = $this->setting('user_auth');
            $user->mnethostid = $CFG->mnet_localhost_id; // Always local user.

            $created = true;
        }

        if (!empty($created)) {
            $user->save();
        } else if ($prev and $this->user_changed($prev, $user)) {
            // Re-throw exception with more helpful information.
            try {
                $user->save();
            } catch (Exception $e) {
                $rea = $e->getMessage();

                $newerr = "%s | Current %s | Stored %s";
                $log = "(%s: '%s')";

                $curr = sprintf($log, $user->username, $user->idnumber);
                $prev = sprintf($log, $prev->username, $prev->idnumber);

                throw new Exception(sprintf($newerr, $rea, $curr, $prev));
            }
        }

        // If the provider supplies initial password information, set it now.
        if (isset($user->auth) and $user->auth === 'manual' and isset($user->init_password)) {
            $user->password = $user->init_password;
            update_internal_user_password($user, $user->init_password);

            // Let's not pass this any further.
            unset($user->init_password);

            // Need an instance of stdClass in the try stack.
            $userx = (array) $user;
            $usery = (object) $userx;

            // Force user to change password on next login.
            set_user_preference('auth_forcepasswordchange', 1, $usery);
        }
        return $user;
    }

    /**
     *
     * @global object $DB
     * @paramwds_user $prev these var names are misleading: $prev is the user
     * 'previously' stored in the DB- that is the current DB record for a user.
     * @paramwds_user $current Also a tad misleading, $current repressents the
     * incoming user currently being evaluated at this point in the wds process.
     * Depending on the outcome of this function, current's data may or may not ever
     * be used of stored.
     * @return boolean According to our comparissons, does current hold new information
     * for a previously stored user that we need to replace the DB record with [replacement
     * happens in the calling function]?
     */
    private function user_changed(wds_user $prev,wds_user $current) {
        global $DB;
        $namefields = \core_user\fields::for_userpic()->get_sql('', false, '', '', false)->selects;
        $sql          = "SELECT id, idnumber, $namefields FROM {user} WHERE id = :id";

        // Thewds_user method does not currently upgrade with the alt names.
        $previoususer = $DB->get_record_sql($sql, array('id' => $prev->id));

        // So we need to establish which users have preferred names.
        $haspreferredname            = !empty($previoususer->alternatename);

        // For users without preferred names, check that old and new firstnames match.
        // No need to take action, if true.
        $reguserfirstnameunchanged  = !$haspreferredname && $previoususer->firstname == $current->firstname;

        // For users with preferred names, check that old altname matches incoming firstname.
        // No need to take action, if true.
        $prefuserfirstnameunchanged = $haspreferredname && $previoususer->alternatename == $current->firstname;

        // Composition of the previous two variables. If either if false,
        // we need to take action and return 'true'.
        $firstnameunchanged          = $reguserfirstnameunchanged || $prefuserfirstnameunchanged;

        // We take action if last name has changed at all.
        $lastnameunchanged           = $previoususer->lastname == $current->lastname;

        // If there is change in either first or last, we are going to update the user DB record.
        if (!$firstnameunchanged || !$lastnameunchanged) {
            // When the first name of a user who has set a preferred
            // name changes, we reset the preference in CPS.
            if (!$prefuserfirstnameunchanged) {
                $DB->set_field('user', 'alternatename', null, array('id' => $previoususer->id));

                // Refactor events_trigger_legacy.
                global $CFG;
                if (file_exists($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php')) {
                    require_once($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php');
                    blocks_wdsprefs_wds_handler::preferred_name_legitimized($current);
                }
            } else {
                // Don't update.
                return false;
            }
            return true;
        }

        if ($prev->idnumber != $current->idnumber) {
            return true;
        }

        if ($prev->username != $current->username) {
            return true;
        }

        $currentmeta = $current->meta_fields(get_object_vars($current));

        foreach ($currentmeta as $field) {
            if (!isset($prev->{$field})) {
                return true;
            }

            if ($prev->{$field} != $current->{$field}) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $type 'student' or 'teacher'
     * @param wds_section $section
     * @param object[] $users
     * @param wds_student[] $current_users all users currently registered in the wds tables for this section
     * @param callback $extra_params function returning additional user parameters/fields
     * an associative array of additional params, given a user as input
     */
    private function fill_role($type, $section, $users, &$currentusers, $extraparams = null) {
        $class = 'wds_' . $type;
        $alreadyenrolled = array(wds::ENROLLED, wds::PROCESSED);

        foreach ($users as $user) {
            $wdsuser = $this->create_user($user);

            $params = array(
                'sectionid' => $section->id,
                'userid'    => $wdsuser->id
            );

            if ($extraparams) {
                // Teacher-specific; returns user's primary flag key => value.
                $params += $extraparams($wdsuser);
            }

            $wdstype = $class::upgrade($wdsuser);

            unset($wdstype->id);
            if ($prev = $class::get($params, true)) {
                $wdstype->id = $prev->id;
                unset($currentusers[$prev->id]);

                // Intentionally save meta fields before continuing.
                // Meta fields can change without enrollment changes.
                $fields = get_object_vars($wdstype);
                if ($wdstype->params_contains_meta($fields)) {
                    $wdstype->save();
                }

                if (in_array($prev->status, $alreadyenrolled)) {
                    continue;
                }
            }

            $wdstype->userid = $wdsuser->id;
            $wdstype->sectionid = $section->id;
            $wdstype->status = wds::PROCESSED;

            $wdstype->save();

            if (empty($prev) or $prev->status == wds::UNENROLLED) {
                // Refactor events_trigger_legacy.
                global $CFG;
                // TODO: FIX THIS SHIT
                if ($type === 'student' && file_exists($CFG->dirroot.'/blocks/ues_logs/eventslib.php')) {
                    require_once($CFG->dirroot.'/blocks/ues_logs/eventslib.php');
                    wds_logs_event_handler::wds_student_process($wdstype);
                } else if ($type === 'teacher' && file_exists($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php')) {
                    require_once($CFG->dirroot.'/blocks/wdsprefs/classes/wds_handler.php');
                    blocks_wdsprefs_wds_handler::wds_teacher_process($wdstype);
                }
            }
        }
    }

    /**
     * determine a user's role based on the presence and setting
     * of a a field primary_flag
     * @param type $user
     * @return string editingteacher | teacher | student
     */
    private function determine_role($user) {

        $roley = new stdClass;

        // It's either wds_teacher or wds_student. Students don't have a 'role' attribute
        // so.....get id and 
        if (isset($user->role)) {
            if ($user->role == 'primary') {
                // $role = 'teacher';
                $roley->role = 'teacher';
                $roley->id = 10;
            } else if ($user->role == 'editingteacher') {
                $roley->role = 'editingteacher';
                $roley->id = 10;
            }
        } else {
            $roley->role = 'student';
            $roley->id = 5;
        }
        return $roley;
    }

    public function log($what) {
        try {
            if (!$this->issilent) {
                mtrace($what);
            }
            
            $this->emaillog[] = $what;
        } catch (Exception $e) {
            error_log("\n\nLOG ERROR MOTHER F.....\n\n");
        }
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        return is_siteadmin();
    }

    public function setting($key, $value = null) {
        if ($value !== null) {
            return set_config($key, $value, 'block_wdsprefs');
        } else {
            return get_config('block_wdsprefs', $key);
        }
    }

    /**
     * Adds an error to the stack
     *
     * If an optional key is provided, the error will be added by that key
     *
     * @param string  $error
     * @param string  $key
     */
    public function add_error($error, $key = false) {
        if ( ! $key) {
            $this->errors[] = $error;
        } else {
            $this->errors[$key] = $error;
        }
    }

    /**
     * Gets the error stack
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Determines whether or not this enrollment plugin's scheduled task is enabled
     *
     * @return bool
     */
    private function task_is_enabled() {
        $task = $this->get_scheduled_task();

        return ! $task->get_disabled();
    }

    /**
     * Determines whether or not this enrollment plugin is currently running
     *
     * @return bool
     */
    private function is_running() {
        return (bool)$this->setting('running');
    }

    /**
     * Determines whether or not this enrollment plugin is within it's "grace period" threshold setting
     *
     * @return bool
     */
    private function is_within_graceperiod() {
        $task = $this->get_scheduled_task();

        // Get the "last run" timestamp.
        $lastrun = (int)$task->get_last_run_time();

        // Get the "grace period" setting.
        $graceperiod = (int)$this->setting('grace_period');

        // Calculate the time elapsed since last run.
        $timeelapsedsincerun = time() - $lastrun;

        // Determine whether or not we are in the grace period.
        return ($timeelapsedsincerun < $graceperiod) ? true : false;
    }

    /**
     * Fetches the moodle "scheduled task" object
     *
     * @return \core\task\scheduled_task
     */
    /*private function get_scheduled_task() {
        $task = \core\task\manager::get_scheduled_task('\enrol_wdsprefs\task\full_process');

        return $task;
    }
    */


    // Moodle enrol plugin stuff below.
    public function course_edit_validation($instance, array $data, $context) {
        $errors = array();
        if (is_null($instance)) {
            return $errors;
        }

        $system = context_system::instance();
        $canchange = has_capability('moodle/course:update', $system);

        $restricted = explode(',', $this->setting('course_restricted_fields'));

        foreach ($restricted as $field) {
            if ($canchange) {
                continue;
            }

            $default = get_config('moodlecourse', $field);
            if (isset($data[$field]) and $data[$field] != $default) {
                $this->add_error(wds::_s('bad_field'), $field);
            }
        }

        // Delegate extension validation to extensions.
        $event = new stdClass;
        $event->instance = $instance;
        $event->data = $data;
        $event->context = $context;
        $event->errors = $errors;

        return $event->errors;
    }

    /**
     *
     * @param type $instance
     * @param MoodleQuickForm $form
     * @param type $data
     * @param type $context
     * @return type
     */
    public function course_edit_form($instance, MoodleQuickForm $form, $data, $context) {
        if (is_null($instance)) {
            return;
        }

        // Allow extension interjection.
        $event = new stdClass;
        $event->instance = $instance;
        $event->form = $form;
        $event->data = $data;
        $event->context = $context;
    }

    public function add_course_navigation($nodes, stdClass $instance) {
        global $COURSE;
        // Only interfere with wds courses.
        if (is_null($instance)) {
            return;
        }

        $coursecontext = context_course::instance($COURSE->id);
        $canchange = has_capability('moodle/course:update', $coursecontext);
        if ($canchange) {
            if ($this->setting('course_form_replace')) {
                $url = new moodle_url(
                    '/enrol/wds/edit.php',
                    array('id' => $instance->courseid)
                );
                $nodes->parent->parent->get('editsettings')->action = $url;
            }
        }

        // Allow outside interjection.
        $params = array($nodes, $instance);

        // Refactor events_trigger_legacy().
        global $CFG;
        if (file_exists($CFG->dirroot.'/blocks/ues_reprocess/eventslib.php')) {
            require_once($CFG->dirroot.'/blocks/ues_reprocess/eventslib.php');
            wds_event_handler::wds_course_settings_navigation($params);
        }
    }

    /**
     * Master method for kicking off wds enrollment
     *
     * First checks a few top-level requirements to run, and then passes on to a secondary method for handling the process
     *
     * @return boolean
     */
    public function run_clear_reprocess() {
        global $DB;
        // TODO: FIX THIS SHIT
        $DB->delete_records('enrol_ues_sectionmeta', array('name' => 'section_reprocessed'));
    }
}

function enrol_wds_supports($feature) {
    switch ($feature) {
        case ENROL_RESTORE_TYPE:
            return ENROL_RESTORE_EXACT;

        default:
            return null;
    }
}

class WdsInitException extends Exception {
}
