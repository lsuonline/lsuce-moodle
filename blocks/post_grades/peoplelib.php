<?php

// Dynamic class definition... why not?
class post_grades_audit_people extends ues_people_element_output {
    function __construct() {
        $str = get_string('student_audit', 'block_post_grades');
        parent::__construct('student_audit', $str);
    }

    function format($user) {
        return empty($user->student_audit) ? 'N' : 'Y';
    }
}
