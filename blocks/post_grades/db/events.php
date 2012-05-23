<?php

$mapper = function($event) {
    return array(
        'handlerfile' => '/blocks/post_grades/events.php',
        'handlerfunction' => array('post_grades_handler', $event),
        'schedule' => 'instant'
    );
};

$events = array(
    'ues_section_drop', 'ues_semester_drop', 'user_deleted'
);

$handlers = array_combine($events, array_map($mapper, $events));
