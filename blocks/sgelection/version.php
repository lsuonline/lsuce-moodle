<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2014101013;
$plugin->requires = 2010112400;
$plugin->component = 'block_sgelection';
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = "v0";

$plugin->cron = 10;

$plugin->dependencies = array(
    'enrol_ues' => ANY_VERSION,
);