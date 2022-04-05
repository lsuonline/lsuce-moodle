<?php

// Every file should have GPL and copyright in the header - we skip it in tutorials but you should not skip it for real.

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {

    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettinguleth_v2', get_string('configtitle', 'theme_uleth_v2'));

    // Each page is a tab - the first is the "General" tab.
    $page = new admin_settingpage('theme_uleth_v2_general', get_string('generalsettings', 'theme_uleth_v2'));

    // Replicate the preset setting from boost.
    $name = 'theme_uleth_v2/preset';
    $title = get_string('preset', 'theme_uleth_v2');
    $description = get_string('preset_desc', 'theme_uleth_v2');
    $default = 'default.scss';

    // We list files in our own file area to add to the drop down. We will provide our own function to
    // load all the presets from the correct paths.
    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_uleth_v2', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // error_log("\nsettings.php -> what are the file choices: ". print_r($choices, 1). "\n");

    // These are the built in presets from Boost.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';
    // $choices['uleth.scss'] = 'uleth.scss';
    // error_log("\nsettings.php -> what are the file choices NOW: ". print_r($choices, 1). "\n");

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_uleth_v2/presetfiles';
    $title = get_string('presetfiles','theme_uleth_v2');
    $description = get_string('presetfiles_desc', 'theme_uleth_v2');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
                                                  array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Variable $brand-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_uleth_v2/brandcolor';
    $title = get_string('brandcolor', 'theme_uleth_v2');
    $description = get_string('brandcolor_desc', 'theme_uleth_v2');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    /* ********************************************************************** */
    /* ***********      Uleth *********************************************** */

    // Custom Links in header
    $setting = new admin_setting_heading('theme_uleth_v2_custom_settings_title', get_string('uleth_v2_custom_title', 'theme_uleth_v2'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');    
    $page->add($setting);

    // Text Box for links
    $name = 'theme_uleth_v2/custom_header_links';
    $title = get_string('custom_header_title', 'theme_uleth_v2');
    $description = get_string('custom_header_desc', 'theme_uleth_v2');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    $name = 'theme_uleth_v2/terms_of_use';
    $title = get_string('terms_of_use', 'theme_uleth_v2');
    $description = get_string('terms_of_use_desc', 'theme_uleth_v2');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    /* ********************************************************************** */
    /* ********************************************************************** */


    // Must add the page after definiting all the settings!
    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_uleth_v2_advanced', get_string('advancedsettings', 'theme_uleth_v2'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_configtextarea('theme_uleth_v2/scsspre',
                                                get_string('rawscsspre', 'theme_uleth_v2'), get_string('rawscsspre_desc', 'theme_uleth_v2'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_configtextarea('theme_uleth_v2/scss', get_string('rawscss', 'theme_uleth_v2'),
                                                get_string('rawscss_desc', 'theme_uleth_v2'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
