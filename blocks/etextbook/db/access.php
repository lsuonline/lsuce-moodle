<?php

/**
 * Etextbook block capabilities.
 *
 * @package    block_etextbook
 * @copyright  David Elliott <delliott@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    $capabilities = array(
 
    'block/etextbook:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
 
    'block/etextbook:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);