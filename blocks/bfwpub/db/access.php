<?php

// Written at Louisiana State University

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/bfwpub:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    'block/bfwpub:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'student' => CAP_PROHIBIT,
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
);
