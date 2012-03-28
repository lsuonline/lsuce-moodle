<?php

$capabilities = array(
    'block/ues_meta_viewer:access' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    )
);
