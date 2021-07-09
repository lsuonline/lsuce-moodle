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
 * @package   local_kalpanmaps
 * @copyright 2021 onwards LSUOnline & Continuing Education
 * @copyright 2021 onwards Robert Russo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = "Kaltura to Panopto";
$string['pluginname_desc'] = "Periodically converts remaining Kaltura items to their Panopto counterparts.";

// Task.
$string['import_kalvidmap'] = 'Import KalVidMap data';
$string['convert_kalvidres'] = 'Convert KalVidRes to URLs';
$string['convert_kalvidres_help'] = 'Converts Kaltura Video Resources to Moodle URL resources.';
$string['convert_kalembeds'] = 'Convert Kaltura embeds';
$string['convert_kalembeds_help'] = 'Converts Kaltura embeds to Panopto embeds.';

// Conversion Settings.
$string['hide_kaltura_items'] = 'Hide kalvidres';
$string['hide_kaltura_items_help'] = 'Hides kaltura Video Resource items on conversion.';
$string['hide_kaltura_items2'] = 'Hide converted';
$string['hide_kaltura_items2_help'] = 'Hides previously converted kaltura Video Resource items when found.';

// Import Settings.
$string['kalpanmapfile'] = 'File location';
$string['kalpanmapfile_help'] = 'Location of the csv file with kaltura, panopto ids provided by Panopto and edited to ONLY have those two fields in that order.';
$string['verbose'] = 'Verbose';
$string['verbose_help'] = 'Enabling verbose logging will give an output of EVERY imported line and converted kalvidres resource. This may prove too large for storing as a task log. Please use with caution.';
$string['purge'] = 'Purge data';
$string['purge_help'] = 'Truncate the local_kalpanmaps table prior to importing.';
