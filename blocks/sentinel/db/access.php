<?php
    $capabilities = array(
 
    'block/sentinel:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        ),
 
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
 
    'block/sentinel:addinstance' => array(
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        ),
 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);