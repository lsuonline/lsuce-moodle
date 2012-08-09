<?php

class post_grades_test extends post_grades_student_table
    implements post_filtered {

    function can_post($section) {
        $students = $section->students();

        if (empty($students)) {
            return false;
        }

        $userid = function($student) { return $student->userid; };

        $filters = ues::where()->id->in(array_map($userid, $students));

        $all_students = ues_user::count($filters);

        $filters->user_degree->equal('Y');

        $degree_students = ues_user::count($filters);

        return ($all_students - $degree_students) > 0;
    }

    function is_acceptable($student) {
        $user = ues_user::upgrade($student)->fill_meta();

        return empty($user->user_degree) || $user->user_degree == 'N';
    }
}
