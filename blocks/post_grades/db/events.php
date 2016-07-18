<?php

// $mapper = function($event) {
//     return array(
//         'handlerfile' => '/blocks/post_grades/events.php',
//         'handlerfunction' => array('post_grades_handler', $event),
//         'schedule' => 'instant'
//     );
// };

// $events = array(
//     'ues_semester_drop', 'user_deleted',
//     'ues_people_outputs', 'quick_edit_anonymous_edited',
//     'quick_edit_grade_edited', 'quick_edit_anonymous_instantiated',
//     'quick_edit_grade_instantiated'
// );

// $handlers = array_combine($events, array_map($mapper, $events));

$observers = array(

	// UES

    array(
        'eventname'   => '\enrol_ues\event\ues_section_dropped',
        'callback'    => 'block_post_grades_observer::ues_section_dropped',
    ),

    array(
        'eventname'   => '\enrol_ues\event\ues_semester_dropped',
        'callback'    => 'block_post_grades_observer::ues_semester_dropped',
    ),

);
