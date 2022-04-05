<?php

defined('MOODLE_INTERNAL') || die();


$plugin->requires  = 2011120500;          // Moodle 2.2
$plugin->component = 'block_autoattend';  // Full name of the plugin (used for diagnostics)
$plugin->cron      = 0;
$plugin->maturity  = MATURITY_STABLE;

$plugin->release   = '2.6.0';

$plugin->version   = 2019082202;    // v2.6.0 for 3.7.1
//$plugin->version = 2019081802;    // v2.5.5 minor change
//$plugin->version = 2018101200;    // v2.5.4 centering of tabs.php
//$plugin->version = 2016071800;    // v2.5.3 db/access.php myaddinstance
//$plugin->version = 2016051900;    // v2.5.2 Bug Fix of e-mail for students
//$plugin->version = 2016031500;    // v2.5.1
