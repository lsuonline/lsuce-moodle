<?php
/**
 * Copyright (c) 2011 bfwpub.com (R) <http://support.bfwpub.com/>
 *
 * This file is part of bfwpub Moodle LMS integration.
 *
 * bfwpub Sakai LMS integration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * bfwpub Sakai LMS integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with bfwpub Sakai LMS integration.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: version.php 1892 2012-08-25 15:26:33Z aaronz $ */

defined('MOODLE_INTERNAL') || die();

// http://docs.moodle.org/dev/version.php
$plugin->version    = 2012082500;        // The current plugin version (Date: YYYYMMDDXX) - must match bfwpub_service.php
$plugin->requires   = 2010112400;        // moodle 2.0 - Requires this Moodle version - Moodle 2.0 = 2010112400; Moodle 2.1 = 2011070100; Moodle 2.2 = 2011120100; Moodle 2.3 = 2012062500
$plugin->cron       = 0;
$plugin->component  = 'block_bfwpub';    // Full name of the plugin (used for diagnostics)
$plugin->maturity   = MATURITY_STABLE;
$plugin->release    = '2.3 (Build: 2012082500)'; // should match the plugin->version
