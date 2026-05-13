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
 * @package    block_backadel
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $PAGE;

/** @var admin_root $ADMIN */
/** @var bool $hassiteconfig */

if (!$hassiteconfig) {
    return;
}

$pluginname = 'block_backadel';

// Plugin-level category under "Block settings".
$ADMIN->add('blocksettings', new admin_category(
    'block_backadel_category',
    new lang_string('pluginname', $pluginname)
));

// Place the pre-created $settings page under our category instead of the default
// blocksettings root. Override the display name (block loader sets it to the
// plugin display name "Backup And Delete") with a clearer label.
$settings->visiblename = new lang_string('nav_settings', $pluginname);
$ADMIN->add('block_backadel_category', $settings);

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/blocks/backadel/settingslib.php');

    $suffixchoices = [
        'username' => 'username',
        'idnumber' => 'idnumber',
        'fullname'  => 'fullname',
    ];

    $schedurl = new moodle_url('/admin/settings.php?section=automated');
    $schedulelink = html_writer::link($schedurl,
        get_string('sched_config', $pluginname));

    $settings->add(new backadel_path_setting('block_backadel/path',
        get_string('config_path', $pluginname),
        get_string('config_path_desc', $pluginname, $CFG->dataroot), ''));
    $settings->add(new admin_setting_configselect('block_backadel/suffix',
        get_string('config_pattern', $pluginname),
        get_string('config_pattern_desc', $pluginname), 0, $suffixchoices));
    $settings->add(new admin_setting_configtext('block_backadel/size_limit',
        get_string('config_size_limit', $pluginname),
        get_string('config_size_limit_desc', $pluginname), ''));
    $settings->add(new admin_setting_pickroles('block_backadel/roles',
        get_string('config_roles', $pluginname),
        get_string('config_roles_desc', $pluginname), []));

    // Catalogue & Migration settings (MD-2189 §2.13).
    $settings->add(new admin_setting_heading(
        'block_backadel/catalogue_migration_heading',
        get_string('catalogue_migration_heading', $pluginname),
        ''
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_backadel/blueprint_keywords',
        get_string('blueprint_keywords', $pluginname),
        get_string('blueprint_keywords_desc', $pluginname),
        \block_backadel\local\course_type_resolver::get_default_blueprint_keywords_text(),
        PARAM_RAW_TRIMMED
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_backadel/excluded_categories',
        get_string('excluded_categories', $pluginname),
        get_string('excluded_categories_desc', $pluginname),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_backadel/migration_extra_paths',
        get_string('migration_extra_paths', $pluginname),
        get_string('migration_extra_paths_desc', $pluginname),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'block_backadel/catalogue_path_prefix',
        get_string('catalogue_path_prefix', $pluginname),
        get_string('catalogue_path_prefix_desc', $pluginname),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_heading('block_backadel/sched_options', '', $schedulelink));
}

// External admin pages — accessible from Site administration → Blocks → Backup And Delete.
$ADMIN->add('block_backadel_category', new admin_externalpage(
    'block_backadel_search',
    get_string('nav_search', $pluginname),
    new moodle_url('/blocks/backadel/search.php')
));

$ADMIN->add('block_backadel_category', new admin_externalpage(
    'block_backadel_migrate',
    get_string('nav_migrate', $pluginname),
    new moodle_url('/blocks/backadel/migrate.php')
));

$ADMIN->add('block_backadel_category', new admin_externalpage(
    'block_backadel_coursebackups',
    get_string('nav_coursebackups', $pluginname),
    new moodle_url('/blocks/backadel/restore.php')
));

$ADMIN->add('block_backadel_category', new admin_externalpage(
    'block_backadel_catalogue',
    get_string('nav_catalogue', $pluginname),
    new moodle_url('/blocks/backadel/catalogue.php')
));

$ADMIN->add('block_backadel_category', new admin_externalpage(
    'block_backadel_failed',
    get_string('nav_failed', $pluginname),
    new moodle_url('/blocks/backadel/failed.php')
));

$ADMIN->add('block_backadel_category', new admin_externalpage(
    'block_backadel_delete',
    get_string('nav_delete', $pluginname),
    new moodle_url('/blocks/backadel/delete.php')
));

// Cross-page tab strip — must run outside fulltree so it fires on
// admin_externalpage requests (migrate.php, restore.php, etc.) too.
if (!CLI_SCRIPT) {
    $links = [
        (new moodle_url('/admin/settings.php', ['section' => 'blocksettingbackadel']))->out(false),
        (new moodle_url('/blocks/backadel/search.php'))->out(false),
        (new moodle_url('/blocks/backadel/migrate.php'))->out(false),
        (new moodle_url('/blocks/backadel/restore.php'))->out(false),
        (new moodle_url('/blocks/backadel/catalogue.php'))->out(false),
        (new moodle_url('/blocks/backadel/failed.php'))->out(false),
        (new moodle_url('/blocks/backadel/delete.php'))->out(false),
    ];

    $strings = [
        get_string('tab_settings', $pluginname),
        get_string('tab_search', $pluginname),
        get_string('tab_migrate', $pluginname),
        get_string('tab_coursebackups', $pluginname),
        get_string('tab_catalogue', $pluginname),
        get_string('tab_failed', $pluginname),
        get_string('tab_delete', $pluginname),
    ];

    $validplaces = [
        '#page-admin-setting-blocksettingbackadel',
        '#page-admin-blocks-backadel-search',
        '#page-admin-blocks-backadel-migrate',
        '#page-admin-blocks-backadel-restore',
        '#page-admin-blocks-backadel-catalogue',
        '#page-admin-blocks-backadel-failed',
        '#page-admin-blocks-backadel-delete',
    ];

    $PAGE->requires->js_call_amd("{$pluginname}/admin-tabs-lazy", 'init', [
        $links,
        $strings,
        $validplaces,
    ]);
}

// Prevent the block manager from double-registering $settings under 'blocksettings'.
$settings = null;
