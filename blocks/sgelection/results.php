<?php

// 1. GET CANDIDATES
// 1. a. LIST CANDIDATES
// 1. b. TALLY HOW MANY VOTES THEY"VE RECEIVED
// 1. c. ORDER BY NUMBER OF VOTES

// 2. GET RESOLTIONS a-b same 2.c. n/a

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
 * List and edit offices.
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_once $CFG->dirroot.'/blocks/sgelection/lib.php';
sge::require_db_classes();

// page security
require_login();
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

// Only required to return the user to the correct ballot page.
$election = Election::get_by_id(required_param('election_id', PARAM_INT));

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/sgelection/results.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(sge::_str('results_page_header'));
$renderer = $PAGE->get_renderer('block_sgelection');

$PAGE->requires->js('/blocks/sgelection/js/datatableforstudentdata.js');

$voter    = new voter($USER->id);

$renderer->set_nav(null, $voter);
echo $OUTPUT->header();
echo $renderer->get_office_results($election);
echo $renderer->get_resolution_results($election);

echo $renderer->print_analytics_tables($election);

echo $OUTPUT->footer();
