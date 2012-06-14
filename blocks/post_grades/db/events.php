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
    'quick_edit_anonymous_edited', 'quick_edit_grade_edited'
);

$handlers = array_combine($events, array_map($mapper, $events));
