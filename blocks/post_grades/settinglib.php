<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

abstract class post_grade_settings_callbacks {
    public static $exceptions;
    public static $legal_writing;
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
