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
 * Backadel backup catalogue (filesystem index).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

$q = optional_param('q', '', PARAM_TEXT);
$yearraw = optional_param('year', null, PARAM_INT);
$semester = optional_param('semester', '', PARAM_TEXT);
$status = optional_param('status', '', PARAM_ALPHA);
$source = optional_param('source', '', PARAM_ALPHANUMEXT);
$pattern = optional_param('pattern', '', PARAM_ALPHAEXT);
$coursetype = optional_param('coursetype', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

$yeardistinct = $DB->get_fieldset_sql(
    "SELECT DISTINCT year
       FROM {block_backadel_catalogue}
      WHERE year IS NOT NULL
   ORDER BY year DESC"
);

$yearoptions = [0 => get_string('catalogue_filter_year_all', 'block_backadel')];
foreach ($yeardistinct as $y) {
    $yi = (int) $y;
    $yearoptions[$yi] = (string) $yi;
}

$currentcal = (int) date('Y');
if ($yearraw === null) {
    $year = in_array($currentcal, array_map('intval', $yeardistinct), true) ? $currentcal : 0;
} else {
    $year = $yearraw;
}

if (!array_key_exists($year, $yearoptions)) {
    $year = 0;
}

if ($year === 0) {
    $distinctsemesters = $DB->get_fieldset_sql(
        "SELECT DISTINCT semester
           FROM {block_backadel_catalogue}
          WHERE semester IS NOT NULL
            AND semester <> :empty
       ORDER BY semester ASC",
        ['empty' => '']
    );
} else {
    $distinctsemesters = $DB->get_fieldset_sql(
        "SELECT DISTINCT semester
           FROM {block_backadel_catalogue}
          WHERE year = :year
            AND semester IS NOT NULL
            AND semester <> :empty
       ORDER BY semester ASC",
        ['year' => $year, 'empty' => '']
    );
}

$semesteroptions = ['' => get_string('any')];
foreach ($distinctsemesters as $sem) {
    if ($sem === null || $sem === '') {
        continue;
    }
    $semesteroptions[(string) $sem] = (string) $sem;
}

if ($semester !== '' && !array_key_exists($semester, $semesteroptions)) {
    $semester = '';
}

$sourcesvalid = array_merge([''], \block_backadel\local\catalogue_allowlists::VALID_SOURCES);
if (!in_array($source, $sourcesvalid, true)) {
    $source = '';
}

$patternsvalid = array_merge([''], \block_backadel\local\catalogue_allowlists::VALID_PATTERNS);
if (!in_array($pattern, $patternsvalid, true)) {
    $pattern = '';
}

$coursetypevalid = array_merge([''], \block_backadel\local\catalogue_allowlists::VALID_COURSETYPES);
if (!in_array($coursetype, $coursetypevalid, true)) {
    $coursetype = '';
}

$statusvalid = array_merge([''], \block_backadel\local\catalogue_allowlists::VALID_STATUSES);
if (!in_array($status, $statusvalid, true)) {
    $status = '';
}

$urlargs = [];
if ($q !== '') {
    $urlargs['q'] = $q;
}
if ($yearraw !== null) {
    $urlargs['year'] = $year;
}
if ($semester !== '') {
    $urlargs['semester'] = $semester;
}
if ($status !== '') {
    $urlargs['status'] = $status;
}
if ($source !== '') {
    $urlargs['source'] = $source;
}
if ($pattern !== '') {
    $urlargs['pattern'] = $pattern;
}
if ($coursetype !== '') {
    $urlargs['coursetype'] = $coursetype;
}

admin_externalpage_setup('block_backadel_catalogue', '', $urlargs);

$context = context_system::instance();
require_capability('block/backadel:viewresults', $context);

$PAGE->set_url(new moodle_url('/blocks/backadel/catalogue.php', $urlargs));

$pagetitle = get_string('catalogue_title', 'block_backadel');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$filtersactive = (
    $q !== ''
    || $semester !== ''
    || $status !== ''
    || $source !== ''
    || $pattern !== ''
    || $coursetype !== ''
    || $yearraw !== null
);

$activefiltercount = 0;
if ($coursetype !== '') {
    $activefiltercount++;
}
if ($semester !== '') {
    $activefiltercount++;
}
if ($yearraw !== null) {
    $activefiltercount++;
}
if ($q !== '') {
    $activefiltercount++;
}
if ($pattern !== '') {
    $activefiltercount++;
}
if ($status !== '') {
    $activefiltercount++;
}

$form = new \block_backadel\form\catalogue_filter_form(new moodle_url('/blocks/backadel/catalogue.php'), [
    'years' => $yearoptions,
    'semesters' => $semesteroptions,
    'filtersactive' => $filtersactive,
]);
$form->set_data(compact('q', 'year', 'semester', 'status', 'source', 'pattern', 'coursetype'));

$tablefilters = compact('q', 'year', 'semester', 'status', 'source', 'pattern', 'coursetype');
$table = new \block_backadel\local\table\catalogue_table('backadel-catalogue', $tablefilters);
$table->define_baseurl(new moodle_url('/blocks/backadel/catalogue.php', $urlargs));
$table->is_downloading($download, 'backadel-catalogue');

if (!$table->is_downloading()) {
    $PAGE->requires->js_call_amd('block_backadel/help', 'init');
    $PAGE->requires->js_call_amd('block_backadel/filter_panel', 'init');
    $PAGE->requires->js_call_amd('block_backadel/instructors_modal', 'init', [[
        'modalTitle'  => get_string('catalogue_instructors_modal_title', 'block_backadel'),
        'colUsername' => get_string('catalogue_instructors_col_username', 'block_backadel'),
        'colFullname' => get_string('catalogue_instructors_col_fullname', 'block_backadel'),
        'none'        => get_string('catalogue_instructors_none', 'block_backadel'),
    ]]);
    if (has_capability('block/backadel:managebackups', $context)) {
        $PAGE->requires->js_call_amd('block_backadel/catalogue_coursetype_override', 'init', [[
            'modalTitle' => get_string('catalogue_override_modal_title', 'block_backadel'),
            'labelType' => get_string('catalogue_override_label_type', 'block_backadel'),
            'labelNote' => get_string('catalogue_override_label_note', 'block_backadel'),
            'save' => get_string('catalogue_override_save', 'block_backadel'),
            'clearLabel' => get_string('catalogue_override_clear', 'block_backadel'),
            'saved' => get_string('catalogue_override_saved', 'block_backadel'),
            'cleared' => get_string('catalogue_override_cleared', 'block_backadel'),
            'typeTeaching' => get_string('coursetype_teaching', 'block_backadel'),
            'typeBlueprint' => get_string('coursetype_blueprint', 'block_backadel'),
            'typeOther' => get_string('coursetype_other', 'block_backadel'),
        ]]);
    }
    echo $OUTPUT->header();

    $helpbtn = html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary px-2 ms-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'catalogue',
        'data-help-title' => get_string('catalogue_title', 'block_backadel'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]);

    $renderer = $PAGE->get_renderer('block_backadel');
    ob_start();
    $form->display();
    $formhtml = ob_get_clean();
    echo $renderer->render_filter_panel(new \block_backadel\output\filter_panel(
        'backadel-catalogue-filters',
        get_string('filter_panel_toggle', 'block_backadel'),
        $activefiltercount,
        $formhtml,
        $helpbtn,
    ));
}

$table->setup();
$table->query_db(30, false);

if (!$table->is_downloading()) {
    echo $OUTPUT->heading(
        get_string('catalogue_title', 'block_backadel') . ' — ' .
            get_string('catalogue_results_count', 'block_backadel', $table->totalrows),
        3
    );

    if ($table->totalrows === 0) {
        echo $OUTPUT->notification(get_string('catalogue_no_results', 'block_backadel'), 'info');
        $table->close_recordset();
    } else {
        $table->build_table();
        $table->close_recordset();
        $table->finish_output();
    }

    echo $OUTPUT->footer();
} else {
    $table->build_table();
    $table->close_recordset();
    $table->finish_output();
}
