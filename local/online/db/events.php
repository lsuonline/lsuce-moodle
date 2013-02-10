<?php

$events = array('ues_list_provider', 'ues_load_online_provider');

$mapper = function($event) {
    return array(
        'handlerfile' => '/local/online/events.php',
        'handlerfunction' => array('online_enrollment_events', $event),
        'schedule' => 'instant'
    );
};

$handlers = array_combine($events, array_map($mapper, $events));
