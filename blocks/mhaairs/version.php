<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2013081007;
$plugin->requires = 2010112400;
$plugin->cron      = 0;
$plugin->component = 'block_mhaairs';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '3.0 (2011091316)';

$plugin->dependencies = array(
        // Requires Moodlerooms Framework.
        'local_mr' => 2010090200
);
