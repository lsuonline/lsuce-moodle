<?php

abstract class post_grade_settings_callbacks {
    static $exceptions;
    static $legal_writing;
}

function post_grade_exceptions_callback() {
    $filters = ues::where()->department->equal('LAW');

    $courses = ues_course::get_all($filters);

    $ids = post_grade_settings_callbacks::$exceptions->get_setting();
    foreach ($ids as $id) {
        $course = $courses[$id];
        $course->fill_meta()->course_exception = 1;
        $course->save();

        unset($courses[$id]);
    }

    foreach ($courses as $course) {
        $course->course_exception = 0;
        $course->save();
    }
}

function post_grade_legal_writing_callback() {
    $filters = ues::where()
        ->department->equal('LAW')
        ->cou_number->less_equal(5300);

    $courses = ues_course::get_all($filters);

    $ids = post_grade_settings_callbacks::$legal_writing->get_setting();
    foreach ($ids as $id) {
        $course = $courses[$id];
        $course->fill_meta()->course_legal_writing = 1;
        $course->save();

        unset($courses[$id]);
    }

    foreach ($courses as $course) {
        $course->course_legal_writing = 0;
        $course->save();
    }
}
