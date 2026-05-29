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
 * @copyright  2016 Louisiana State University, Chad Mazilly, Robert Russo, Dave Elliott
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

admin_externalpage_setup('block_backadel_failed');
$context = context_system::instance();
require_capability('block/backadel:viewresults', $context);

$PAGE->requires->js_call_amd('block_backadel/help', 'init');
$PAGE->requires->js_call_amd('block_backadel/crud_actions', 'init');

$blockname = get_string('pluginname', 'block_backadel');
$header = get_string('failed_header', 'block_backadel');

$failedids = $DB->get_fieldset_select(
    'block_backadel_statuses',
    'coursesid',
    'status = :st',
    ['st' => 'FAIL']
);

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-sm btn-outline-secondary float-end mb-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'failed',
        'data-help-title' => get_string('failed_header', 'block_backadel'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]),
    'position-relative'
);
echo $OUTPUT->heading($header);

if (!$failedids) {
    echo html_writer::div(get_string('none_failed', 'block_backadel'));
    echo $OUTPUT->footer();
    exit;
}

$table = new \block_backadel\local\table\failed_table('backadel-failed');
$table->out(30, true);

echo $OUTPUT->footer();
