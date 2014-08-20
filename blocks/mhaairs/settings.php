<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

defined('MOODLE_INTERNAL') || die();

if (!$ADMIN->fulltree) {
    return;
}

global $CFG;

require_once($CFG->dirroot.'/blocks/mhaairs/settingslib.php');

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_sslonly',
        new lang_string('sslonlylabel', 'block_mhaairs'),
        '', 0
));

$settings->add(new admin_setting_configtext(
        'block_mhaairs_customer_number',
        new lang_string('customernumberlabel', 'block_mhaairs'),
        '',
        '',
        PARAM_ALPHANUMEXT
));

$settings->add(new admin_setting_configtext(
        'block_mhaairs_shared_secret',
        new lang_string('secretlabel', 'block_mhaairs'),
        '',
        '',
        PARAM_ALPHANUMEXT
));

if (!during_initial_install() && (CLI_SCRIPT === false)) {
    $adminurl = new moodle_url('/admin/settings.php');
    $currenturl = new moodle_url(qualified_me());
    if ($currenturl->compare($adminurl, URL_MATCH_BASE)) {
        $settings->add(new admin_setting_configmulticheckbox_mhaairs (
                'block_mhaairs_display_services',
                new lang_string('services_displaylabel', 'block_mhaairs'),
                new lang_string('services_desc', 'block_mhaairs')
        ));
    }
}

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_display_helplinks',
        new lang_string('mhaairs_displayhelp', 'block_mhaairs'),
        new lang_string('mhaairs_displayhelpdesc', 'block_mhaairs'),
        1
));

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_sync_gradebook',
        new lang_string('mhaairs_syncgradebook', 'block_mhaairs'),
        new lang_string('mhaairs_syncgradebookdesc', 'block_mhaairs'),
        1
));

$settings->add(new admin_setting_configselect(
        'block_mhaairs_locktype',
        new lang_string('mhaairs_locktype', 'block_mhaairs'),
        new lang_string('mhaairs_locktypedesc', 'block_mhaairs'),
        'nonelock',
        array('nonelock' => 'No locking', 'filelock' => 'File locking', 'redislock' => 'Redis locking')
));

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_gradelog',
        new lang_string('mhaairs_gradelog', 'block_mhaairs'),
        new lang_string('mhaairs_gradelogdesc', 'block_mhaairs'),
        '0'
));

$settings->add(new admin_setting_configcheckbox_mhaairs (
        'block_mhaairs_resetlog',
        new lang_string('mhaairs_resetlog', 'block_mhaairs'),
        new lang_string('mhaairs_resetlogdesc', 'block_mhaairs'),
        '0'
));
