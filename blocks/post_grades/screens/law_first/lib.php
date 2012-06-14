<?php

class post_grades_law_first extends post_grades_student_table
    implements post_filtered {

    function can_post($section) {
        $course = $section->course()->fill_meta();

        return isset($course->course_first_year) && $course->course_first_year == 1;
    }

    // All first year
    function is_acceptable($student) {
        return true;
    }
}
