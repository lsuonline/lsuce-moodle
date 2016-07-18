<?php

abstract class post_grades_handler {
    public static function ues_semester_drop($semester) {
        global $DB;

        // At this point, I can be sure that only the posting periods remain
        $params = array('semesterid' => $semester->id);
        return $DB->delete_records('block_post_grades_periods', $params);
    }

    public static function ues_section_drop($section) {
        global $DB;
        $params = array('sectionid' => $section->id);
        return $DB->delete_records('block_post_grades_postings', $params);
    }

    public static function user_deleted($user) {
        global $DB;
        $params = array('userid' => $user->id);
        return $DB->delete_records('block_post_grades_postings', $params);
    }

    public static function ues_people_outputs($data) {
        $sections = ues_section::from_course($data->course);

        // If one of them contains LAW, then display student_audit
        $is_law = false;
        foreach ($sections as $section) {
            if ($is_law) break;

            $is_law = $section->course()->department == 'LAW';
        }

        // No need to interject
        if (empty($is_law)) {
            return $data;
        }

        require_once dirname(__FILE__) . '/peoplelib.php';

        $data->outputs['student_audit'] = new post_grades_audit_people();

        return $data;
    }

    private static function injection_requirements() {
        global $CFG;

        if (!class_exists('post_grades')) {
            require_once $CFG->dirroot . '/blocks/post_grades/lib.php';
        }

        if (!class_exists('post_grades_compliance')) {
            require_once $CFG->dirroot . '/blocks/post_grades/screens/returnlib.php';
        }
    }

    private static function apply_incomplete($data) {
        self::injection_requirements();

        $sections = ues_section::from_course($data->course);

        if (empty($sections)) {
            return true;
        }

        $course = reset($sections)->course();

        if ($course->department != 'LAW') {
            return true;
        }

        $ci = grade_item::fetch_course_item($data->course->id);

        if (empty($ci)) {
            return true;
        }

        $str = get_string('student_incomplete', 'block_post_grades');

        $original_headers = $data->headers();
        $original_headers[] = $str;
        $data->set_headers($original_headers);

        $original_definition = $data->definition();
        $original_definition[] = 'incomplete';
        $data->set_definition($original_definition);

        return true;
    }

    public static function quick_edit_anonymous_instantiated($data) {
        require_once dirname(__FILE__) . '/quick_edit_lib.php';
        return self::apply_incomplete($data);
    }

    public static function quick_edit_grade_instantiated($data) {
        require_once dirname(__FILE__) . '/quick_edit_lib.php';
        return self::apply_incomplete($data);
    }

    public static function quick_edit_grade_edited($data) {
        global $PAGE;

        $allowed = (bool) get_config('block_post_grades', 'law_quick_edit_compliance');

        if (empty($allowed)) {
            return true;
        }

        self::injection_requirements();

        $sections = ues_section::from_course($data->instance->course);

        // Really only necessary for posting periods
        $periods = post_grades::active_periods_for_sections($sections);

        if (empty($periods)) {
            return true;
        }

        $merged = ues_course::merge_sections($sections);
        $ues_course = reset($merged)->fill_meta();

        // No interested
        if ($ues_course->department != 'LAW') {
            return true;
        }

        // Course compliance is ONLY valid for first year legal writing
        $valid = (
            $ues_course->course_first_year and
            $ues_course->course_legal_writing
        );

        if (empty($data->instance->groupid) and !$valid) {
            return true;
        }

        $compliance_return = new post_grades_compliance_return(
            $data->instance, $data->instance->items, $ues_course
        );

        $compliance = $compliance_return->compliance;

        // No further work necessary
        if ($compliance->is_compliant()) {
            return true;
        }

        $output = $PAGE->get_renderer('block_post_grades');

        $data->warnings[] = $compliance->get_explanation();
        $data->warnings[] = $output->display_graph($compliance, false);

        return true;
    }

    public static function quick_edit_anonymous_edited($data) {
        global $DB, $PAGE;

        self::injection_requirements();

        $sections = ues_section::from_course($data->instance->course);

        if (empty($sections)) {
            return true;
        }

        $ues_course = ues_course::by_id(reset($sections)->courseid)->fill_meta();

        $passthrough = new post_grades_passthrough($ues_course);

        $break_early = (
            $passthrough->is_compliant() or
            $ues_course->course_type == 'SEM'
        );

        if ($break_early) {
            return true;
        }

        $compliance = new post_grades_class_size(
            $data->instance->students,
            $ues_course, $data->instance->course,
            $data->instance->itemid
        );

        if ($compliance->is_compliant()) {
            return true;
        }

        $output = $PAGE->get_renderer('block_post_grades');

        $data->warnings[] = $compliance->get_explanation();
        $data->warnings[] = $output->display_graph($compliance, false);

        return true;
    }
}
