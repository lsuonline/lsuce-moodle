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
}
