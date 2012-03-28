<?php

require_once dirname(__FILE__) . '/processors.php';

class lsu_enrollment_provider extends enrollment_provider {
    var $url;
    var $wsdl;
    var $username;
    var $password;

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

        list($username, $password) = explode("\n", $resp);

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

        if ($init_on_create) {
            $this->init();
        }
    }

    public static function settings() {
        return array(
            'credential_location' => 'https://secure.web.lsu.edu/credentials.php',
            'wsdl_location' => 'webService.wsdl'
        );
    }

    public static function adv_settings() {
        $optional_pulls = array (
            'student_data' => 1,
            'anonymous_numbers' => 0,
            'degree_candidates' => 0,
            'sports_information' => 1
        );

        $admin_settings = array();

        foreach ($optional_pulls as $key => $default) {
            $k = self::get_name() . '_' . $key;
            $admin_settings[] = new admin_setting_configcheckbox('enrol_ues/' . $k,
                ues::_s('lsu_'. $key), ues::_s('lsu_' . $key . '_desc'), $default);
        }

        return $admin_settings;
    }

    function semester_source() {
        return new lsu_semesters($this->username, $this->password, $this->wsdl);
    }

    function course_source() {
        return new lsu_courses($this->username, $this->password, $this->wsdl);
    }

    function teacher_source() {
        return new lsu_teachers($this->username, $this->password, $this->wsdl);
    }

    function student_source() {
        return new lsu_students($this->username, $this->password, $this->wsdl);
    }

    function student_data_source() {
        return new lsu_student_data($this->username, $this->password, $this->wsdl);
    }

    function anonymous_source() {
        return new lsu_anonymous($this->username, $this->password, $this->wsdl);
    }

    function degree_source() {
        return new lsu_degree($this->username, $this->password, $this->wsdl);
    }

    function sports_source() {
        return new lsu_sports($this->username, $this->password, $this->wsdl);
    }

    function teacher_department_source() {
        return new lsu_teachers_by_department($this->username, $this->password, $this->wsdl);
    }

    function student_department_source() {
        return new lsu_students_by_department($this->username, $this->password, $this->wsdl);
    }

    function postprocess($enrol = null) {
        $semesters_in_session = ues_semester::in_session();

        $now = time();

        $by_closest_lsu = function ($in, $semester) use ($now) {
            if (empty($in)) {
                return $semester;
            } else if ($in->campus == 'LAW') {
                return $semester;
            } else if ($semester->campus == 'LAW') {
                return $in;
            } else {
                $start = $semester->classes_start;
                $closer = ($start <= $now and $start < $in->classes_start);
                return $closer ? $semester : $in;
            }
        };

        $lsu_semester = array_reduce($semesters_in_session, $by_closest_lsu);

        if (empty($lsu_semester)) {
            return true;
        }

        $law_semesters = ues_semester::get_all(array(
            'year' => $lsu_semester->year,
            'name' => $lsu_semester->name,
            'campus' => 'LAW',
        ));

        $processed_semesters = array($lsu_semester) + $law_semesters;

        $attempts = array(
            'student_data' => $this->student_data_source(),
            'anonymous_numbers' => $this->anonymous_source(),
            'degree_candidates' => $this->degree_source(),
            'sports_information' => $this->sports_source()
        );

        foreach ($processed_semesters as $semester) {

            foreach ($attempts as $key => $source) {
                if (!$this->get_setting($key)) {
                    continue;
                }

                If ($enrol) {
                    $enrol->log("Processing $key for $semester...");
                }

                // Clear out sports information on run
                if ($key == 'sports_information' and $semester->campus == 'LSU') {
                    foreach (range(1, 4) as $code) {
                        $params = array('name' => "user_sport$code");
                        ues_user::delete_meta($params);
                    }
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

        foreach ($datas as $data) {
            $params = array('idnumber' => $data->idnumber);

            $user = ues_user::upgrade_and_get($data, $params);

            if (empty($user->id)) {
                continue;
            }

            $user->save();
        }
    }
}
