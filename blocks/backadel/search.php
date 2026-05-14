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
$coursetype = optional_param('coursetype', '', PARAM_ALPHA);
$semester = optional_param('semester', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);

$coursetypevalid = ['', 'teaching', 'blueprint', 'other', 'undetermined'];
if (!in_array($coursetype, $coursetypevalid, true)) {
    $coursetype = '';
}

admin_externalpage_setup('block_backadel_search');
$context = context_system::instance();
require_capability('block/backadel:managebackups', $context);

$semrows = $DB->get_fieldset_sql(
    "SELECT DISTINCT semester FROM {block_backadel_courses}
      WHERE semester IS NOT NULL AND semester <> :empty
   ORDER BY semester DESC",
    ['empty' => '']
);
$semopts = ['' => get_string('any')];
foreach ($semrows as $sem) {
    if ($sem === null || $sem === '') {
        continue;
    }
    $slug = (string) $sem;
    $semopts[$slug] = $slug;
}

if ($semester !== '' && !array_key_exists($semester, $semopts)) {
    $semester = '';
}

$hasfilters = ($q !== '' || $category > 0 || $status !== '' || $coursetype !== '' || $semester !== '');

$urlargs = [];
if ($q !== '') {
    $urlargs['q'] = $q;
}
if ($category > 0) {
    $urlargs['category'] = $category;
}
if ($status !== '') {
    $urlargs['status'] = $status;
}
if ($coursetype !== '') {
    $urlargs['coursetype'] = $coursetype;
}
if ($semester !== '') {
    $urlargs['semester'] = $semester;
}

$PAGE->set_url(new moodle_url('/blocks/backadel/search.php', $urlargs));

$filters = [
    'q' => $q,
    'category' => $category,
    'status' => $status,
    'coursetype' => $coursetype,
    'semester' => $semester,
];

$form = new \block_backadel\form\search_filter_form(new moodle_url('/blocks/backadel/search.php'), [
    'semesters' => $semopts,
    'filtersactive' => $hasfilters,
]);
$form->set_data($filters);

$activefiltercount = 0;
if ($q !== '') {
    $activefiltercount++;
}
if ($category > 0) {
    $activefiltercount++;
}
if ($status !== '') {
    $activefiltercount++;
}
if ($coursetype !== '') {
    $activefiltercount++;
}
if ($semester !== '') {
    $activefiltercount++;
}

$table = new \block_backadel\local\table\results_table('backadel-search', $filters);
$table->define_baseurl(new moodle_url('/blocks/backadel/search.php', $urlargs));
$table->is_downloading($download, 'backadel-search');

$PAGE->requires->js_call_amd('block_backadel/help', 'init');
$PAGE->requires->js_call_amd('block_backadel/filter_panel', 'init');
$PAGE->requires->js_call_amd('block_backadel/crud_actions', 'init');

$renderer = $PAGE->get_renderer('block_backadel');

echo $OUTPUT->header();

$helpbtn = html_writer::tag('button', '?', [
    'type' => 'button',
    'class' => 'btn btn-outline-secondary px-2 ms-2',
    'data-action' => 'show-help',
    'data-help-topic' => 'search',
    'data-help-title' => get_string('nav_search', 'block_backadel'),
    'aria-label' => get_string('help_button_label', 'block_backadel'),
]);

ob_start();
$form->display();
$formhtml = ob_get_clean();
echo $renderer->render_filter_panel(new \block_backadel\output\filter_panel(
    'backadel-search-filters',
    get_string('filter_panel_toggle', 'block_backadel'),
    $activefiltercount,
    $formhtml,
    $helpbtn,
));
echo $OUTPUT->heading(get_string('search_results', 'block_backadel'));
if (!$hasfilters) {
    echo $OUTPUT->notification(get_string('search_instructions', 'block_backadel'), 'info');
} else {
    $table->out(50, true);
}
echo $OUTPUT->footer();
