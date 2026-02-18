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
 * Content to fix report - lists content with malformed HTML from activity modules.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @author    David-Antonio Castro <dcastr10@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use report_content2fix\reportbuilder\local\systemreports\malformed_content_report;
use core_reportbuilder\system_report_factory;

admin_externalpage_setup('reportcontent2fix', '', null, '', ['pagelayout' => 'report']);

require_capability('report/content2fix:view', context_system::instance());

$PAGE->set_title(get_string('pluginname', 'report_content2fix'));
$PAGE->set_heading(get_string('pluginname', 'report_content2fix'));

echo $OUTPUT->header();

$report = system_report_factory::create(malformed_content_report::class, context_system::instance());
echo $report->output();

echo $OUTPUT->footer();
