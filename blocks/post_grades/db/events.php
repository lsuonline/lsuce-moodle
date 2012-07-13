<?php

$mapper = function($event) {
    return array(
        'handlerfile' => '/blocks/post_grades/events.php',
        'handlerfunction' => array('post_grades_handler', $event),
        'schedule' => 'instant'
    );
};

$events = array(
    'ues_section_drop', 'ues_semester_drop', 'user_deleted',
    'ues_people_outputs', 'quick_edit_anonymous_edited',
    'quick_edit_grade_edited', 'quick_edit_anonymous_instantiated',
    'quick_edit_grade_instantiated'
);

$handlers = array_combine($events, array_map($mapper, $events));
