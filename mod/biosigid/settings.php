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
 * biosigid module admin settings and defaults
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');

if ($ADMIN->fulltree) {

    //--- sysid defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('biosigidsysiddefaults', get_string('sysiddefaults', BIOSIGID_MODULE_NAME), get_string('configsysiddefaults', BIOSIGID_MODULE_NAME)));
    $settings->add(new admin_setting_configtext('biosigid/url',
        get_string('url', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configurl', BIOSIGID_MODULE_NAME), '', PARAM_URL, 80));
    $settings->add(new admin_setting_configtext('biosigid/sharedcode',
        get_string('sharedcode', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configsharedcode', BIOSIGID_MODULE_NAME), '', PARAM_RAW, 40));
    $settings->add(new admin_setting_configtext('biosigid/systemid',
        get_string('systemid', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configsystemid', BIOSIGID_MODULE_NAME), '', PARAM_RAW, 40));
    $settings->add(new admin_setting_configtext('biosigid/customerid',
        get_string('customerid', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configcustomerid', BIOSIGID_MODULE_NAME), '', PARAM_RAW, 40));
    $settings->add(new admin_setting_configtext('biosigid/locale',
        get_string('locale', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configlocale', BIOSIGID_MODULE_NAME), 'en_US', PARAM_RAW, 40));

    $settings->add(new admin_setting_heading('biosigidcustom', get_string('settings_custom', BIOSIGID_MODULE_NAME), get_string('configsettings_custom', BIOSIGID_MODULE_NAME)));
    $settings->add(new admin_setting_configtext('biosigid/custom1',
        get_string('custom1', BIOSIGID_MODULE_NAME), "<strong>Optional:</strong> " . get_string('configcustom1', BIOSIGID_MODULE_NAME), '', PARAM_RAW, 40));
        
    //--- enc defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('biosigidencdefaults', get_string('encdefaults', BIOSIGID_MODULE_NAME), get_string('configencdefaults', BIOSIGID_MODULE_NAME)));
    $settings->add(new admin_setting_configtext('biosigid/passphrase',
        get_string('passphrase', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configpassphrase', BIOSIGID_MODULE_NAME), '', '/^[[:ascii:]]{8,36}$/', 50));
    $settings->add(new admin_setting_configtext('biosigid/salt',
        get_string('salt', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configsalt', BIOSIGID_MODULE_NAME), '', '/^[[:ascii:]]{8,36}$/', 50));
    $settings->add(new admin_setting_configtext('biosigid/vector',
        get_string('vector', BIOSIGID_MODULE_NAME), "<strong style='color: red'>Required:</strong> " . get_string('configvector', BIOSIGID_MODULE_NAME), '', '/^[[:ascii:]]{16,16}$/', 25));

}