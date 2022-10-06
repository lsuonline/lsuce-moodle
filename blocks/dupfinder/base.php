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
 *
 * @copyright 2021 onwards LSUOnline & Continuing Education
 * @copyright 2021 onwards Robert Russo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);
defined('MOODLE_INTERNAL') || die();

// Require config and CLIlib.
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/blocks/dupfinder/helpers.php');

// Make sure we are not in maintenance mode.
if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active, import execution suspended.\n";
    exit(1);
}

$df = new helpers();

$data = $df->getdata();
$xml = $df->objectify($data);
$dupes = $df->finddupes($xml, false);

$emailsuccess = $df->emailduplicates($dupes);

if ($emailsuccess) {
    mtrace(get_string('emailsent', 'block_dupfinder'));
}
