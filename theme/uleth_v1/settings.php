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
 * @package   theme_uleth_v1
 * @copyright 2017 University of Lethbridge
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_uleth_v1_admin_settingspage_tabs('themesettinguleth_v1', get_string('configtitle', 'theme_uleth_v1'));
    $page = new admin_settingpage('theme_uleth_v1_general', get_string('generalsettings', 'theme_uleth_v1'));

    // Preset.
    $name = 'theme_uleth_v1/preset';
    $title = get_string('preset', 'theme_uleth_v1');
    $description = get_string('preset_desc', 'theme_uleth_v1');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_uleth_v1', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';
    $choices['uleth.scss'] = 'uleth.scss';

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_uleth_v1/presetfiles';
    $title = get_string('presetfiles','theme_uleth_v1');
    $description = get_string('presetfiles_desc', 'theme_uleth_v1');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Background image setting.
    $name = 'theme_uleth_v1/backgroundimage';
    $title = get_string('backgroundimage', 'theme_uleth_v1');
    $description = get_string('backgroundimage_desc', 'theme_uleth_v1');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $body-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_uleth_v1/brandcolor';
    $title = get_string('brandcolor', 'theme_uleth_v1');
    $description = get_string('brandcolor_desc', 'theme_uleth_v1');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    /* ********************************************************************** */
    /* ********************************************************************** */

    // Custom Links in header
    $setting = new admin_setting_heading('theme_uleth_v1_custom_settings_title', get_string('uleth_v1_custom_title', 'theme_uleth_v1'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');    
    $page->add($setting);

    // Text Box for links
    $name = 'theme_uleth_v1/custom_header_links';
    $title = get_string('custom_header_title', 'theme_uleth_v1');
    $description = get_string('custom_header_desc', 'theme_uleth_v1');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    $name = 'theme_uleth_v1/terms_of_use';
    $title = get_string('terms_of_use', 'theme_uleth_v1');
    $description = get_string('terms_of_use_desc', 'theme_uleth_v1');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // This is to show the app server
    $name = 'theme_uleth_v1/show_app_name';
    $title = get_string('show_app_name_title', 'theme_uleth_v1');
    // $description = get_string('terms_of_use_desc', 'theme_uleth_v1');
    $description = "";
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    /* ********************************************************************** */
    /* ********************************************************************** */
    
    // Must add the page after definiting all the settings!
    $settings->add($page);
    
    // Advanced settings.
    $page = new admin_settingpage('theme_uleth_v1_advanced', get_string('advancedsettings', 'theme_uleth_v1'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_uleth_v1/scsspre',
        get_string('rawscsspre', 'theme_uleth_v1'), get_string('rawscsspre_desc', 'theme_uleth_v1'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_uleth_v1/scss', get_string('rawscss', 'theme_uleth_v1'),
        get_string('rawscss_desc', 'theme_uleth_v1'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    /* ********************************************************************** */
    /* ********************************************************************** */
    $settings->add($page);
}
