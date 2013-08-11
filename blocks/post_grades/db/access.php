<?php

$capabilities = array(
    'block/post_grades:canpost' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    ),
    'block/post_grades:canconfigure' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'block/post_grades:addinstance' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'manager'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'teacher'           => CAP_ALLOW,
        )
    ),
    'block/post_grades:myaddinstance' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'manager'           => CAP_PROHIBIT,
            'editingteacher'    => CAP_PROHIBIT,
            'teacher'           => CAP_PROHIBIT,
        )
    ),
);
