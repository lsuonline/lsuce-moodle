<?php
// Every file should have GPL and copyright in the header - we skip it in tutorials but you should not skip it for real.

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// A description shown in the admin theme selector.
$string['choosereadme'] = 'Theme Uleth_v2 is a child theme of Boost. It adds the ability to upload background photos.';
// The name of our plugin.
$string['pluginname'] = 'Uleth v2';
// We need to include a lang string for each block region.
$string['region-side-pre'] = 'Right';
// The name of the second tab in the theme settings.
$string['advancedsettings'] = 'Advanced settings';
// The brand colour setting.
$string['brandcolor'] = 'Brand colour';
// The brand colour setting description.
$string['brandcolor_desc'] = 'The accent colour.';
// Name of the settings pages.
$string['configtitle'] = 'Uleth v2 settings';
// Name of the first settings tab.
$string['generalsettings'] = 'General settings';
// Preset files setting.
$string['presetfiles'] = 'Additional theme preset files';
// Preset files help text.
$string['presetfiles_desc'] = 'Preset files can be used to dramatically alter the appearance of the theme. See <a href=https://docs.moodle.org/dev/Boost_Presets>Boost presets</a> for information on creating and sharing your own preset files, and see the <a href=http://moodle.net/boost>Presets repository</a> for presets that others have shared.';
// Preset setting.
$string['preset'] = 'Theme preset';
// Preset help text.
$string['preset_desc'] = 'Pick a preset to broadly change the look of the theme.';
// Raw SCSS setting.
$string['rawscss'] = 'Raw SCSS';
// Raw SCSS setting help text.
$string['rawscss_desc'] = 'Use this field to provide SCSS or CSS code which will be injected at the end of the style sheet.';
// Raw initial SCSS setting.
$string['rawscsspre'] = 'Raw initial SCSS';
// Raw initial SCSS setting help text.
$string['rawscsspre_desc'] = 'In this field you can provide initialising SCSS code, it will be injected before everything else. Most of the time you will use this setting to define variables.';


// Settings Page for the Theme
$string['uleth_v2_custom_title'] = 'UofL Custom Settings';
$string['custom_header_title'] = 'Custom Header Links';
$string['custom_header_desc'] = 'This will allow you to dynamically add/remove links in the header bar. For multiple links create a comma seperated list follow this format: "Title,URL","Title,URL" ';

// Terms of Use on the theme settings page
$string['terms_of_use'] = 'Terms of Use Link';
$string['terms_of_use_desc'] = 'University of Lethbridge Moodle Terms of Use Link. <br>Ex: Current Server "../../terms_of_use.php" <br>or<br>Landing "https://moodle.uleth.ca/terms_of_use.php"';
