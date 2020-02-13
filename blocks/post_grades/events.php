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

defined('MOODLE_INTERNAL') || die();

abstract class post_grades_handler {
    public static function ues_semester_drop($semester) {
        global $DB;

        // At this point, I can be sure that only the posting periods remain.
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

        // If one of them contains LAW, then display student_audit.
        $islaw = false;
        foreach ($sections as $section) {
            if ($islaw) {
                break;
            }

            $islaw = $section->course()->department == 'LAW';
        }

        // No need to interject.
        if (empty($islaw)) {
            return $data;
        }

        require_once(dirname(__FILE__) . '/peoplelib.php');

        $data->outputs['student_audit'] = new post_grades_audit_people();

        return $data;
    }

    private static function injection_requirements() {
        global $CFG;

        if (!class_exists('post_grades')) {
            require_once($CFG->dirroot . '/blocks/post_grades/lib.php');
        }

        if (!class_exists('post_grades_compliance')) {
            require_once($CFG->dirroot . '/blocks/post_grades/screens/returnlib.php');
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

        $originalheaders = $data->headers();
        $originalheaders[] = $str;
        $data->set_headers($originalheaders);

        $originaldefinition = $data->definition();
        $originaldefinition[] = 'incomplete';
        $data->set_definition($originaldefinition);

        return true;
    }

    public static function quick_edit_anonymous_instantiated($data) {
        require_once(dirname(__FILE__) . '/quick_edit_lib.php');
        return self::apply_incomplete($data);
    }

    public static function quick_edit_grade_instantiated($data) {
        require_once(dirname(__FILE__) . '/quick_edit_lib.php');
        return self::apply_incomplete($data);
    }

    /** This is a stub handler for use with something other than a grade set
     *  is instantiated.  If there is ever an actual need for this handler
     *  (its event was being triggered indiscriminately by an earlier version
     *  of quick_edit) its behaviour will be defined here.
     * 
     *  @param $data quick_edit tablelike or quick_edit_select object being
     *               processed for this event.
     */
    public static function quick_edit_other_instantiated($data) {
        return true;
    }

    //TODO: This needs to be refactored to pass a single $data array.
    public static function quick_edit_grade_edited(&$instance, &$warnings) {
        global $PAGE;

        $allowed = (bool) get_config('block_post_grades', 'law_quick_edit_compliance');

        if (empty($allowed)) {
            return true;
        }

        self::injection_requirements();

        $sections = ues_section::from_course($instance->course);

        // Really only necessary for posting periods.
        $periods = post_grades::active_periods_for_sections($sections);

        if (empty($periods)) {
            return true;
        }

        $merged = ues_course::merge_sections($sections);
        $uescourse = reset($merged)->fill_meta();

        // No interested.
        if ($uescourse->department != 'LAW') {
            return true;
        }

        // Course compliance is ONLY valid for first year legal writing.
        $valid = (
            $uescourse->course_first_year and
            $uescourse->course_legal_writing
        );

        if (empty($instance->groupid) and !$valid) {
            return true;
        }

        $compliancereturn = new post_grades_compliance_return(
            $instance, $instance->items, $uescourse
        );

        $compliance = $compliancereturn->compliance;

        // No further work necessary.
        if ($compliance->is_compliant()) {
            return true;
        }

        $output = $PAGE->get_renderer('block_post_grades');

        $warnings[] = $compliance->get_explanation();
        $warnings[] = $output->display_graph($compliance, false);

        return true;
    }

    //TODO: This needs to be refactored to pass a single $data array.
    public static function quick_edit_anonymous_edited(&$instance, &$warnings) {
            global $DB, $PAGE;

        self::injection_requirements();
        
        $sections = ues_section::from_course($instance->course);

        if (empty($sections)) {
            return true;
        }

        $uescourse = ues_course::by_id(reset($sections)->courseid)->fill_meta();

        $passthrough = new post_grades_passthrough($uescourse);

        $breakearly = (
            $passthrough->is_compliant() or
            $uescourse->course_type == 'SEM'
        );

        if ($breakearly) {
            return true;
        }

        $compliance = new post_grades_class_size($instance->students
                                               , $uescourse
                                               , $instance->course
                                               , $instance->itemid
        );

        if ($compliance->is_compliant()) {
            return true;
        }

        $output = $PAGE->get_renderer('block_post_grades');

        $warnings[] = $compliance->get_explanation();
        $warnings[] = $output->display_graph($compliance, false);

        return true;
    }

    //TODO: This needs to be refactored to pass a single $data array.
    /** This is a stub handler for use when something other than a grade set
     *  is edited.  If there is ever an actual need for this handler
     *  (its event was being triggered indiscriminately by an earlier version
     *  of quick_edit) its behaviour will be defined here.
     * 
     *  @param    $instance Array of data being edited (passed by reference).
     *  @param    $warnings Array of warnings generated by the editing attempt
     *                      (passed by reference)
     *  @return             true if successful, false otherwise.
     */
    public static function quick_edit_other_edited(&$instance, &$warnings) {
        return true;
    }

    /**
     *  Stub for event that was triggered in quick_edit but never provided a handler.
     *  @param  $data Data array passed by original trigger.
     *  @return True on success, or false on error.
     */
    public static function quick_edit_anonymous_table_built($data) {
        return true;
    }

    /**
     *  Stub for event that was triggered in quick_edit but never provided a handler.
     *  @param  $data Data array passed by original trigger.
     *  @return True on success, or false on error.
     */
    public static function quick_edit_grade_table_built($data) {
        return true;
    }

    /** This is a stub handler for use when something other than a grade set
     *  table is built.  If there is ever an actual need for this handler
     *  (its event was being triggered indiscriminately by an earlier version
     *  of quick_edit) its behaviour will be defined here.
     * 
     *  @param $data Array of data for similar table-building events.
     */
    public static function quick_edit_other_table_built($data) {
        return true;
    }

}