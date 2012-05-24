<?php

abstract class post_grades_handler {
    function ues_semester_drop($semester) {
        global $DB;

        // At this point, I can be sure that only the posting periods remain
        $params = array('semesterid' => $semester->id);
        return $DB->delete_records('block_post_grades_periods', $params);
    }

    function ues_section_drop($section) {
        global $DB;
        $params = array('sectionid' => $section->id);
        return $DB->delete_records('block_post_grades_postings', $params);
    }

    function user_deleted($user) {
        global $DB;
        $params = array('userid' => $user->id);
        return $DB->delete_records('block_post_grades_postings', $params);
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

    function quick_edit_grade_edited($data) {
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

        $ues_course = reset(ues_course::merge_sections($sections))->fill_meta();

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

    function quick_edit_anonymous_edited($data) {
        global $DB, $PAGE;

        self::injection_requirements();

        $sections = ues_section::from_course($data->instance->course);

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
