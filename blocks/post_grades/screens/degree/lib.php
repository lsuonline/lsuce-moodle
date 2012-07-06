<?php

class post_grades_degree extends post_grades_student_table implements post_filtered {
    function can_post($section) {
        $students = $section->students();

        if (empty($students)) {
            return false;
        }

        $userid = function($student) { return $student->userid; };

        $filters = ues::where()
            ->id->in(array_map($userid, $students))
            ->user_degree->equal('Y');

        // Explicit boolean return
        return ues_user::count($filters) ? true : false;
    }

    function is_acceptable($student) {
        $user = ues_user::upgrade($student)->fill_meta();

        return isset($user->user_degree) && $user->user_degree == 'Y';
    }
}
