<?php

require_once dirname(dirname(__FILE__)) . '/final/lib.php';

class post_grades_law_degree extends post_grades_final
    implements post_filtered {

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
