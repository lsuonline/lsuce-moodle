<?php

class post_grades_midterm extends post_grades_student_table {
    function is_acceptable($student) {
        return true;
    }
}
