<?php

$capabilities = array(
    'block/ues_people:viewmeta' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW
        ),
    ),
    
    'block/ues_people:addinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW
        ),
    'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    
    'block/ues_people:myaddinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'teacher' => CAP_PREVENT
        ),
    'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
);