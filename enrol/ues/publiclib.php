<?php

defined('MOODLE_INTERNAL') or die();

abstract class ues {
    const PENDING = 'pending';
    const PROCESSED = 'processed';

    // Section is created
    const MANIFESTED = 'manifested';
    const SKIPPED = 'skipped';

    // Teacher / Student manifestation
    const ENROLLED = 'enrolled';
    const UNENROLLED = 'unenrolled';

    public static function require_libs() {
        self::require_daos();
        self::require_extensions();
    }

    public static function require_daos() {
        $dao = self::base('classes/dao');

        require_once $dao . '/base.php';
        require_once $dao . '/extern.php';
        require_once $dao . '/lib.php';
        require_once $dao . '/daos.php';
        require_once $dao . '/error.php';
        require_once $dao . '/filter.php';
    }

    public static function require_extensions() {
        $classes = self::base('classes');

        require_once $classes . '/processors.php';
        require_once $classes . '/provider.php';
    }

    public static function format_time($time) {
        return strftime('%Y-%m-%d', $time);
    }

    public static function where($field = null) {
        return new ues_dao_filter($field);
    }

    public static function inject_manifest(array $sections, $inject = null, $silent = true) {
        self::unenroll_users($sections, $silent);

        if ($inject) {
            foreach ($sections as $section) {
                $inject($section);
            }
        }

        self::enroll_users($sections, $silent);
    }

    // Note: this will erase the idnumber of the sections
    public static function unenroll_users(array $sections, $silent = true) {
        $enrol = enrol_get_plugin('ues');

        $enrol->is_silent = $silent;

        foreach ($sections as $section) {
            $section->status = self::PENDING;
            $section->save();
        }

        $enrol->handle_pending_sections($sections);

        return $enrol->errors;
    }

    // Note: this will cause manifestation (course creation if need be)
    public static function enroll_users(array $sections, $silent = true) {
        $enrol = enrol_get_plugin('ues');

        $enrol->is_silent = $silent;

        foreach ($sections as $section) {
            foreach (array('teacher', 'student') as $type) {
                $class = 'ues_' . $type;

                $class::reset_status($section, self::PROCESSED);
            }

            $section->status = self::PROCESSED;

            // Appropriate events needs to be adhered to
            events_trigger('ues_section_process', $section);

            $section->save();
        }

        $enrol->handle_processed_sections($sections);

        return $enrol->errors;
    }

    public static function reprocess_department($semester, $department, $silent = true) {
        $enrol = enrol_get_plugin('ues');

        if (!$enrol or $enrol->errors) {
            return false;
        }

        if (!$enrol->provider()->supports_department_lookups()) {
            return false;
        }

        $enrol->is_silent = $silent;

        // Work on making department reprocessing code separate
        ues_error::department($semester, $department)->handle($enrol);

        $ids = ues_section::ids_by_course_department($semester, $department);

        $pending = ues_section::get_all(ues::where('id')->in($ids)->status->equal(ues::PENDING));
        $processed = ues_section::get_all(ues::where('id')->in($ids)->status->equal(ues::PROCESSED));

        $enrol->handle_pending_sections($pending);
        $enrol->handle_processed_sections($processed);

        return true;
    }

    public static function reprocess_course($course, $silent = true) {
        $sections = ues_section::from_course($course, true);

        return self::reprocess_sections($sections, $silent);
    }

    public static function reprocess_sections($sections, $silent = true) {
        $enrol = enrol_get_plugin('ues');

        if (!$enrol or $enrol->errors) {
            return false;
        }

        if (!$enrol->provider()->supports_section_lookups()) {
            return false;
        }

        $enrol->is_silent = $silent;

        foreach ($sections as $section) {
            $enrol->process_enrollment(
                $section->semester(), $section->course(), $section
            );
        }

        $ids = array_keys($sections);

        $pending = ues_section::get_all(ues::where('id')->in($ids)->status->equal(ues::PENDING));
        $processed = ues_section::get_all(ues::where('id')->in($ids)->status->equal(ues::PROCESSED));

        $enrol->handle_pending_sections($pending);
        $enrol->handle_processed_sections($processed);

        return true;
    }

    public static function reprocess_for($teacher, $silent = true) {
        $ues_user = $teacher->user();

        $provider = self::create_provider();

        if ($provider and $provider->supports_reverse_lookups()) {
            $enrol = enrol_get_plugin('ues');

            $info = $provider->teacher_info_source();

            $semesters = ues_semester::in_session();

            foreach ($semesters as $semester) {
                $courses = $info->teacher_info($semester, $ues_user);

                $processed = $enrol->process_courses($semester, $courses);

                foreach ($processed as $course) {

                    foreach ($course->sections as $section) {
                        $enrol->process_enrollment(
                            $semester, $course, $section
                        );
                    }
                }
            }

            $enrol->handle_enrollments();
            return true;
        }

        return self::reprocess_sections($teacher->sections(), $silent);
    }

    public static function reprocess_errors($errors, $report = false) {

        $enrol = enrol_get_plugin('ues');

        $amount = count($errors);

        if ($amount) {
            $e_txt = $amount === 1 ? 'error' : 'errors';

            $enrol->log('-------------------------------------');
            $enrol->log('Attempting to reprocess ' . $amount . ' ' . $e_txt . ':');
            $enrol->log('-------------------------------------');
        }

        foreach ($errors as $error) {
            $enrol->log('Executing error code: ' . $error->name);

            if ($error->handle($enrol)) {
                $enrol->handle_enrollments();
                ues_error::delete($error->id);
            }
        }

        if ($report) {
            $enrol->email_reports();
        }
    }

    public static function drop_semester($semester, $report = false) {
        $log = function ($msg) use ($report) {
            if ($report) mtrace($msg);
        };

        $log('Commencing ' . $semester . " drop...\n");

        $count = 0;
        // Remove data from local tables
        foreach ($semester->sections() as $section) {
            $section_param = array('sectionid' => $section->id);

            $types = array('ues_student', 'ues_teacher');

            // Triggered before db removal and enrollment drop
            events_trigger('ues_section_drop', $section);

            // Optimize enrollment deletion
            foreach ($types as $class) {
                $class::delete_all(array('sectionid' => $section->id));
            }
            ues_section::delete($section->id);

            $count ++;

            $should_report = ($count <= 100 and $count % 10 == 0);
            if ($should_report or $count % 100 == 0) {
                $log('Dropped ' . $count . " sections...\n");
            }

            if ($count == 100) {
                $log("Reporting 100 sections at a time...\n");
            }
        }

        $log('Dropped all ' . $count . " sections...\n");

        events_trigger('ues_semester_drop', $semester);
        ues_semester::delete($semester->id);

        $log('Done');
    }

    public static function gen_str($plugin = 'enrol_ues') {
        return function ($key, $a = null) use ($plugin) {
            return get_string($key, $plugin, $a);
        };
    }

    public static function _s($key, $a=null) {
        return get_string($key, 'enrol_ues', $a);
    }

    public static function format_string($pattern, $obj) {
        foreach (get_object_vars($obj) as $key => $value) {
            $pattern = preg_replace('/\{' . $key . '\}/', $value, $pattern);
        }

        return $pattern;
    }

    public static function plugin_base() {
        return self::base('plugins');
    }

    public static function base($dir='') {
        return dirname(__FILE__) . (empty($dir) ? '' : '/'.$dir);
    }

    public static function list_plugins() {

        $base = self::plugin_base();

        $all_files_folders = scandir($base);

        $plugins = array_filter($all_files_folders, function ($file) use ($base) {
            return is_dir($base . '/' . $file) and !preg_match('/^\./', $file);
        });

        if (empty($plugins)) {
            return array();
        }

        $provide_append = function ($name) {
            return ues::_s("{$name}_name");
        };

        return array_combine($plugins, array_map($provide_append, $plugins));
    }

    public static function provider_class() {
        $provider_name = get_config('enrol_ues', 'enrollment_provider');

        if (!$provider_name) {
            return false;
        }

        $class_file = self::plugin_base() . '/' . $provider_name . '/provider.php';

        if (!file_exists($class_file)) {
            return false;
        }

        // Require library code
        self::require_libs();

        // Require client code
        require_once $class_file;

        $provider_class = "{$provider_name}_enrollment_provider";

        return $provider_class;
    }

    public static function create_provider() {
        $provider_class = self::provider_class();

        return $provider_class ? new $provider_class() : false;
    }

    public static function translate_error($e) {
        $provider_class = self::provider_class();
        $provider_name = $provider_class::get_name();

        $code = $e->getMessage();

        if ($code == "enrollment_unsupported") {
            $problem = self::_s($code);
        } else {
            $problem = self::_s($provider_name . '_' . $code);
        }

        $a = new stdClass;
        $a->pluginname = self::_s($provider_name.'_name');
        $a->problem = $problem;

        return $a;
    }
}
