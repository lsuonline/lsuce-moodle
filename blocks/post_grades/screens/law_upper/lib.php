<?php

require_once dirname(dirname(__FILE__)) . '/final/lib.php';

class post_grades_law_upper extends post_grades_final
    implements post_filtered {

    function can_post($section) {
        $course = $section->course()->fill_meta();

        return empty($course->course_first_year) && parent::can_post($section);
    }
}
