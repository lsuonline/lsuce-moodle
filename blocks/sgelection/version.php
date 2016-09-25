<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2016091001;
$plugin->requires = 2010112400;
$plugin->component = 'block_sgelection';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = "v0";

$plugin->cron = 10;

$plugin->dependencies = array(
    'enrol_ues' => ANY_VERSION,
);
