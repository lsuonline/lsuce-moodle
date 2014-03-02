<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2013120200;
$plugin->requires = 2010112400;
$plugin->cron      = 0;
$plugin->component = 'block_mhaairs';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '4.0 (2013120200)';

$plugin->dependencies = array(
        // Requires Moodlerooms Framework.
        'local_mr' => 2010090200
);