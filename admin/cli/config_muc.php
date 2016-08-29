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
 * Configure MUC for the site.
 *
 * @package   local_mrooms
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once(__DIR__.'/../../cache/locallib.php');

list($options, $unrecognized) = cli_get_params(
    array('help' => false),
    array('h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo "Configure MUC

Options:
-h, --help    Print out this help

";
    die;
}

if (empty($CFG->local_mrooms_muc_application_name)) {
    cli_error('Must have $CFG->local_mrooms_muc_application_name defined in the site config file');
}
if (empty($CFG->local_mrooms_muc_application_plugin)) {
    cli_error('Must have $CFG->local_mrooms_muc_application_plugin defined in the site config file');
}
if (empty($CFG->local_mrooms_muc_application_config)) {
    cli_error('Must have $CFG->local_mrooms_muc_application_config defined in the site config file');
}
if (!is_array($CFG->local_mrooms_muc_application_config)) {
    cli_error('The $CFG->local_mrooms_muc_application_config is not an array and must be defined as one');
}

$name   = $CFG->local_mrooms_muc_application_name;
$plugin = $CFG->local_mrooms_muc_application_plugin;
$config = $CFG->local_mrooms_muc_application_config;

$writer = cache_config_writer::instance();
if (array_key_exists($name, $writer->get_all_stores())) {
    $writer->edit_store_instance($name, $plugin, $config);
} else {
    $writer->add_store_instance($name, $plugin, $config);
}

// Set the defaults for mappings.
$writer->set_mode_mappings([
    cache_store::MODE_APPLICATION => [$name],
    cache_store::MODE_SESSION     => ['default_session'],
    cache_store::MODE_REQUEST     => ['default_request'],
]);

purge_all_caches();

cli_writeln("MUC sucessfully configured to use $name for Application cache");
