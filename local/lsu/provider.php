<?php
global $CFG;
require_once dirname(__FILE__) . '/processors.php';
require_once $CFG->dirroot.'/enrol/ues/lib.php';
require_once $CFG->dirroot.'/lib/enrollib.php';

class lsu_enrollment_provider extends enrollment_provider {
    var $url;
    var $wsdl;
    var $username;
    var $password;

    var $testing;

    var $settings = array(
        'credential_location' => 'https://secure.web.lsu.edu/credentials.php',
        'wsdl_location' => 'webService.wsdl',
        'semester_source' => 'MOODLE_SEMESTERS',
        'course_source' => 'MOODLE_COURSES',
        'teacher_by_department' => 'MOODLE_INSTRUCTORS_BY_DEPT',
        'student_by_department' => 'MOODLE_STUDENTS_BY_DEPT',
        'teacher_source' => 'MOODLE_INSTRUCTORS',
        'student_source' => 'MOODLE_STUDENTS',
        'student_data_source' => 'MOODLE_STUDENT_DATA',
        'student_degree_source' => 'MOODLE_DEGREE_CANDIDATE',
        'student_anonymous_source' => 'MOODLE_LAW_ANON_NBR',
        'student_ath_source' => 'MOODLE_STUDENTS_ATH'
    );

    // User data caches to speed things up
    private $lsu_degree_cache = array();
    private $lsu_student_data_cache = array();
    private $lsu_sports_cache = array();
    private $lsu_anonymous_cache = array();

    function init() {
        global $CFG;

        $path = pathinfo($this->wsdl);

        // Path checks
        if (!file_exists($this->wsdl)) {
            throw new Exception('no_file');
        }

        if ($path['extension'] != 'wsdl') {
            throw new Exception('bad_file');
        }

        if (!preg_match('/^[http|https]/', $this->url)) {
            throw new Exception('bad_url');
        }

        require_once $CFG->libdir . '/filelib.php';

        $curl = new curl(array('cache' => true));
        $resp = $curl->post($this->url, array('credentials' => 'get'));

        list($username, $password) = $this->testing ? array('hello','world1') : explode("\n", $resp);

        if (empty($username) or empty($password)) {
            throw new Exception('bad_resp');
        }

        $this->username = trim($username);
        $this->password = trim($password);
    }

    function __construct($init_on_create = true) {
        global $CFG;

        $this->url = $this->get_setting('credential_location');

        $this->wsdl = $CFG->dataroot . '/'. $this->get_setting('wsdl_location');

        $this->testing = get_config('local_lsu', 'testing');

        if ($init_on_create) {
            $this->init();
        }
    }

    public function settings($settings) {
        parent::settings($settings);

        $key = $this->plugin_key();
        $_s = ues::gen_str($key);

        $optional_pulls = array (
            'student_data' => 1,
            'anonymous_numbers' => 0,
            'degree_candidates' => 0,
            'sports_information' => 1
        );

        foreach ($optional_pulls as $name => $default) {
            $settings->add(new admin_setting_configcheckbox($key . '/' . $name,
                $_s($name), $_s($name. '_desc'), $default)
            );
        }
    }

    public static function plugin_key() {
        return 'local_lsu';
    }

    function semester_source() {
        return new lsu_semesters(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('semester_source')
        );
    }

    function course_source() {
        return new lsu_courses(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('course_source')
        );
    }

    function teacher_source() {
        return new lsu_teachers(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('teacher_source')
        );
    }

    function student_source() {
        return new lsu_students(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_source')
        );
    }

    function student_data_source() {
        return new lsu_student_data(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_data_source')
        );
    }

    function anonymous_source() {
        return new lsu_anonymous(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_anonymous_source')
        );
    }

    function degree_source() {
        return new lsu_degree(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_degree_source')
        );
    }

    function sports_source() {
        return new lsu_sports(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_ath_source')
        );
    }

    function teacher_department_source() {
        return new lsu_teachers_by_department(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('teacher_by_department')
        );
    }

    function student_department_source() {
        return new lsu_students_by_department(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_by_department')
        );
    }

    function preprocess($enrol = null) {

        // cleanup orphaned groups- https://trello.com/c/lQqVUrpQ
        $orphanedGroupMemebers = $this-> findOrphanedGroups();
        $this->unenrollGroupsUsers($orphanedGroupMemebers);
        
        // find and remove any duplicate group membership records
        $duplicateGroupMemberships = $this->findDuplicateGroupMembers();
        $this->removeGroupDupes($duplicateGroupMemberships);

        // Clear student auditing flag on each run; It'll be set in processor
        return (
            ues_student::update_meta(array('student_audit' => 0)) and
            ues_user::update_meta(array('user_degree' => 0)) and
            // Safe to clear sports on preprocess now that end date is 21 days
            ues_user::update_meta(array('user_sport1' => '')) and
            ues_user::update_meta(array('user_sport2' => '')) and
            ues_user::update_meta(array('user_sport3' => '')) and
            ues_user::update_meta(array('user_sport4' => ''))
        );
    }

    function postprocess($enrol = null) {
        $semesters_in_session = ues_semester::in_session();

        $now = time();

        $attempts = array(
            'student_data' => $this->student_data_source(),
            'anonymous_numbers' => $this->anonymous_source(),
            'degree_candidates' => $this->degree_source(),
            'sports_information' => $this->sports_source()
        );

        foreach ($semesters_in_session as $semester) {

            foreach ($attempts as $key => $source) {
                if (!$this->get_setting($key)) {
                    continue;
                }

                if ($enrol) {
                    $enrol->log("Processing $key for $semester...");
                }

                try {
                    $this->process_data_source($source, $semester);
                } catch (Exception $e) {
                    $handler = new stdClass;

                    $handler->file = '/enrol/ues/plugins/lsu/errors.php';
                    $handler->function = array(
                        'lsu_provider_error_handlers',
                        'reprocess_' . $key
                    );

                    $params = array('semesterid' => $semester->id);

                    ues_error::custom($handler, $params)->save();
                }
            }
        }

        return true;
    }

    function process_data_source($source, $semester) {
        $datas = $source->student_data($semester);

        $name = get_class($source);

        $cache =& $this->{$name . '_cache'};
        foreach ($datas as $data) {
            $params = array('idnumber' => $data->idnumber);

            if (isset($cache[$data->idnumber])) {
                continue;
            }

            $user = ues_user::upgrade_and_get($data, $params);

            if(isset($data->user_college)) {
            $user->department = $data->user_college;
            }

            if (empty($user->id)) {
                continue;
            }

            $cache[$data->idnumber] = $data;

            $user->save();

            events_trigger('ues_' . $name . '_updated', $user);
        }
    }
    
    public function findOrphanedGroups() {
                global $DB;
        
        $sql = "SELECT
            CONCAT(u.id, '-', gg.id, '-', cc.id, '-', gg.name) as uid,
            u.id AS userId,
            cc.id AS courseId,
            gg.id as groupId,
            u.username,
            cc.fullname,
            gg.name
        FROM (
            SELECT
                grp.id,
                grp.courseid,
                grp.name,
                c.fullname
            FROM (
                SELECT
                    g.name,
                    count(g.name) as gcount
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE c.fullname like '2014 Spring %'
                GROUP BY g.name
                HAVING gcount > 1
            ) AS dupes
            LEFT JOIN {groups} grp ON grp.name = dupes.name
            INNER JOIN {course} c ON c.id = grp.courseid
            WHERE c.fullname like '2014 Spring %'
                AND (
                        SELECT count(id) AS memcount
                        FROM {groups_members} 
                        WHERE groupid = grp.id
                    ) > 0
            ORDER BY c.fullname
            ) AS gg
            INNER JOIN {course} cc ON cc.id = gg.courseid
            INNER JOIN {groups_members} ggm ON ggm.groupid = gg.id
            INNER JOIN {user} u ON ggm.userid = u.id
            INNER JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = 50
            INNER JOIN {role_assignments} ra ON ctx.id = ra.contextid AND u.id = ra.userid
            INNER JOIN {role} r ON ra.roleid = r.id AND r.archetype = 'student'
        WHERE CONCAT(gg.courseid,gg.name) NOT IN (
            SELECT DISTINCT(CONCAT(mc.id,g.name))
            FROM {enrol_ues_sections} s
                INNER JOIN {enrol_ues_courses} c ON s.courseid = c.id
                INNER JOIN {enrol_ues_semesters} sem ON s.semesterid = sem.id
                INNER JOIN {course} mc ON mc.idnumber = s.idnumber
                INNER JOIN 
                (
            SELECT
                grp.id,
                grp.courseid,
                grp.name,
                c.fullname
            FROM (
                SELECT
                    g.name,
                    count(g.name) as gcount
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE c.fullname like '2014 Spring %'
                GROUP BY g.name
                HAVING gcount > 1
            ) AS dupes
            LEFT JOIN {groups} grp ON grp.name = dupes.name
            INNER JOIN {course} c ON c.id = grp.courseid
            WHERE c.fullname like '2014 Spring %'
                AND (
                        SELECT count(id) AS memcount
                        FROM {groups_members} 
                        WHERE groupid = grp.id
                    ) > 0
            ORDER BY c.fullname
            ) g ON mc.id = g.courseid AND g.name = CONCAT(c.department, ' ', c.cou_number, ' ', s.sec_number)
            WHERE sem.name = 'Spring'
            AND sem.year = 2014)
        AND gg.name IN (
            SELECT DISTINCT(g.name)
            FROM {enrol_ues_sections} s
                INNER JOIN {enrol_ues_courses} c ON s.courseid = c.id
                INNER JOIN {enrol_ues_semesters} sem ON s.semesterid = sem.id
                INNER JOIN {course} mc ON mc.idnumber = s.idnumber
                INNER JOIN 
                (
            SELECT
                grp.id,
                grp.courseid,
                grp.name,
                c.fullname
            FROM (
                SELECT
                    g.name,
                    count(g.name) as gcount
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE c.fullname like '2014 Spring %'
                GROUP BY g.name
                HAVING gcount > 1
            ) AS dupes
            LEFT JOIN {groups} grp ON grp.name = dupes.name
            INNER JOIN {course} c ON c.id = grp.courseid
            WHERE c.fullname like '2014 Spring %'
                AND (
                        SELECT count(id) AS memcount
                        FROM {groups_members} 
                        WHERE groupid = grp.id
                    ) > 0
            ORDER BY c.fullname
            ) g ON mc.id = g.courseid AND g.name = CONCAT(c.department, ' ', c.cou_number, ' ', s.sec_number)
            WHERE sem.name = 'Spring'
            AND sem.year = 2014)
        AND cc.visible = 1
        AND cc.shortname LIKE '2014 Spring %';";
        
        return $DB->get_records_sql($sql);
    }

    /**
     * Specialized cleanup fn to unenroll users from groups
     * 
     * Use cases: unenroll members of orphaned groups 
     * Takes the output of @see lsu_enrollment_provider::findOrphanedGroups 
     * and prepares it for unenrollment.
     * 
     * @global object $DB
     * @param object[] $groupMembers rows from 
     * @see lsu_enrollment_provider::findOrphanedGroups
     */
    public function unenrollGroupsUsers($groupMembers) {
        $ues        = new enrol_ues_plugin();
        foreach($groupMembers as $user){
            $instance   = $ues->get_instance($user->courseid);
            $ues->unenrol_user($instance, $user->userid);
        }
    }

    public function findDuplicateGroupMembers() {
        global $DB;
        $sql = "SELECT CONCAT (u.firstname, ' ', u.lastname) AS UserFullname, u.username, g.name, u.id userid, c.id courseid, g.id, c.fullname, COUNT(g.name) AS groupcount
                FROM {groups_members} gm
                    INNER JOIN {groups} g ON g.id = gm.groupid
                    INNER JOIN {course} c ON g.courseid = c.id
                    INNER JOIN {user} u ON gm.userid =u.id
                WHERE c.fullname NOT LIKE CONCAT('%', u.firstname, ' ', u.lastname)
                    AND c.fullname LIKE '2014 Spring%'
                GROUP BY gm.groupid, u.username
                HAVING groupcount > 1;";
        return $DB->get_records_sql($sql);
    }

    public function removeGroupDupes($dupes) {
        global $DB;
        
        foreach($dupes as $dupe){
            // find all records for the current user/groupid
            $dupeRecs = $DB->get_records('groups_members', array('groupid'=>$dupe->id, 'userid'=>$dupe->userid));
            
            // delete from DB until only one remains
            while(count($dupeRecs) > 1){
                $toDelete = array_shift($dupeRecs);
                $DB->delete_records('groups_members',array('id'=>$toDelete->id));
            }
        }
    }

}