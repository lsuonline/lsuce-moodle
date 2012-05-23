<?php

class post_grades_final extends post_grades_student_table {
    function is_acceptable($student) {
        $user = ues_user::upgrade($student)->fill_meta();

        return empty($user->user_degree) || $user->user_degree == 'N';
    }
}
