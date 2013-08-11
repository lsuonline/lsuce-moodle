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
/* $Id: settings.php 1756 2012-06-18 22:51:37Z aaronz $ */

defined('MOODLE_INTERNAL') || die;

// control the config settings for this plugin
require_once ('bfwpub_service.php');
$block_name = bfwpub_service::BLOCK_NAME;
if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext($block_name.'/block_bfwpub_shared_key',
            get_string('config_shared_key', $block_name),
            get_string('config_shared_key_desc', $block_name),
            '', //50,200
            PARAM_TEXT,
            50
        )
    );
}
