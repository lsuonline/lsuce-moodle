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

defined('MOODLE_INTERNAL') || die;

function dd($thething) {
    var_dump($thething);
    die;
}

define('CLI_SCRIPT', true);

require_once('../../../config.php');
global $CFG;
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'links'       => false,
        'range'       => '', // Ex: 2017-1,2017-12,...
        'help'        => false
    ),
    array(
        'h' => 'help',
        'r' => 'range',
    )
);

if ($options['help']) {
    $help = "\nOptions:\n-h, --help            Print out this help\n-r, --range           The month range for activity generation (default: '2017-1,2017-12')\n\nExample:\n\$sudo -u www-data /usr/bin/php local/cas_help_links/seed_db.php --range=2016-1,2017-2\n\n";

    echo $help;
    die;
}

// Create a new seeder for this date range.
$seeder = new \local_cas_help_links_db_seeder();

// Create link records if necessary.
if ( ! empty($options['links'])) {
    // Clear all link records.
    $seeder->clearLinks();

    // First, seed category links.
    if ($amountAdded = $seeder->seedCategoryLinks()) {
        echo $amountAdded . " category links added!\n";
    } else {
        cli_error("Could not create category links.\n");
        die;
    }

    // Second, seed course links.
    if ($amountAdded = $seeder->seedCourseLinks()) {
        echo $amountAdded . " course links added!\n";
    } else {
        cli_error("Could not create course links.\n");
        die;
    }

    // Third, seed user (instructor) links.
    if ($amountAdded = $seeder->seedUserLinks()) {
        echo $amountAdded . " user links added!\n";
    } else {
        cli_error("Could not create user links.\n");
        die;
    }
}

// If we have valid range input.
if ( ! empty($options['range'])) {
    // Clear all log records.
    $seeder->clearLogs();

    // Attempt to generate log activity for the given range.
    if ($seeder->seedLog($options['range'])) {
        echo "Click activity added!\n";
    } else {
        cli_error("Could not create log activity.\n");
        die;
    }
}

die;