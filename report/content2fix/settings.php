<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Report content2fix admin settings.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new \admin_externalpage(
    'reportcontent2fix',
    get_string('pluginname', 'report_content2fix'),
    "$CFG->wwwroot/report/content2fix/index.php"
));

$settings = new \admin_settingpage(
    'report_content2fix_config',
    get_string('settings', 'report_content2fix')
);

$settings->add(new \admin_setting_configtextarea(
    'report_content2fix/courseids',
    get_string('setting_courseids', 'report_content2fix'),
    get_string('setting_courseids_desc', 'report_content2fix'),
    '',
    PARAM_RAW,
    6,
    60
));

$settings->add(new \admin_setting_configcheckbox(
    'report_content2fix/mainpageonly',
    get_string('setting_mainpageonly', 'report_content2fix'),
    get_string('setting_mainpageonly_desc', 'report_content2fix'),
    0
));

$ADMIN->add('reports', $settings);
