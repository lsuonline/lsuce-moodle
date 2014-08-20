<?php

/**
 * Code fragment to define the version of the certificate module
 *
 * @package mod
 * @subpackage  certificate
 * @copyright   Mark Nelson <mark@moodle.com.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

$plugin->version  = 2014070800;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2010112400;  // Requires this Moodle version
$plugin->cron     = 0;           // Period for cron to check this module (secs)
$plugin->component = 'mod_certificate';

$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = "v1.7.0"; // User-friendly version number
