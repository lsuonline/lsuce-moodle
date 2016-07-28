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
/* $Id: block_bfwpub.php 1756 2012-06-18 22:51:37Z aaronz $ */

// AZ test and force UTF-8 - 吞下玻璃 - Iñtërnâtiônàlizætiøn

// i18n file
$string['pluginname']           = 'BFW LMS';
$string['app.bfwpub']           = 'bfwpub';
$string['app.title']            = 'BFW LMS integration';

// Config
$string['config_general'] = 'General';
$string['config_shared_key'] = 'BFW-LMS shared key';
$string['config_shared_key_desc'] = 'This is the same shared key used for BFW-LMS Basic LTI. It is also used for grade transfer from BFW-LMS. This must be set or the grade transfer will not work.';
