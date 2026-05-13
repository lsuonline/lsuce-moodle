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
 * Course search and queue / delete actions (Backadel).
 *
 * @package    block_backadel
 * @copyright  2016 Louisiana State University, Chad Mazilly, Robert Russo, Dave Elliott
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

$q = optional_param('q', '', PARAM_TEXT);
$category = optional_param('category', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

admin_externalpage_setup('block_backadel_search');
$context = context_system::instance();
require_capability('block/backadel:managebackups', $context);

$PAGE->set_url(new moodle_url('/blocks/backadel/search.php', [
    'q' => $q,
    'category' => $category,
    'status' => $status,
]));

$filters = [
    'q' => $q,
    'category' => $category,
    'status' => $status,
];

$form = new \block_backadel\form\search_filter_form(new moodle_url('/blocks/backadel/search.php'));
$form->set_data($filters);

$hasfilters = ($q !== '' || $category > 0 || $status !== '');

$table = new \block_backadel\local\table\results_table('backadel-search', $filters);
$table->define_baseurl(new moodle_url('/blocks/backadel/search.php', [
    'q' => $q,
    'category' => $category,
    'status' => $status,
]));
$table->is_downloading($download, 'backadel-search');

$PAGE->requires->js_call_amd('block_backadel/help', 'init');
$PAGE->requires->js_call_amd('block_backadel/crud_actions', 'init');

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-sm btn-outline-secondary float-end mb-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'search',
        'data-help-title' => get_string('nav_search', 'block_backadel'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]),
    'position-relative'
);
$form->display();
echo $OUTPUT->heading(get_string('search_results', 'block_backadel'));
if (!$hasfilters) {
    echo $OUTPUT->notification(get_string('search_instructions', 'block_backadel'), 'info');
} else {
    $table->out(50, true);
}
echo $OUTPUT->footer();
