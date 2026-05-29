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
 * Catalogue restore proxy: list backups by year with hand-off to native restore wizard (MD-2189 §3.4).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/backadel/lib.php');

$year = optional_param('year', (int) date('Y'), PARAM_INT);

admin_externalpage_setup('block_backadel_coursebackups', '', ['year' => $year]);
$context = context_system::instance();
require_capability('block/backadel:managebackups', $context);

$PAGE->requires->js_call_amd('block_backadel/help', 'init');

$action = optional_param('action', '', PARAM_ALPHA);
$fileid = optional_param('fileid', 0, PARAM_INT);

if ($action === 'stage' && $fileid > 0) {
    require_sesskey();
    \block_backadel\local\restore_handoff::stage_and_redirect($fileid, $context);
    // Stage_and_redirect() never returns — it calls redirect().
}

$dbmanager = $DB->get_manager();
if (!$dbmanager->table_exists('block_backadel_catalogue')) {
    echo $OUTPUT->header();
    echo html_writer::div(
        html_writer::tag('button', '?', [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary float-end mb-2',
            'data-action' => 'show-help',
            'data-help-topic' => 'restore',
            'data-help-title' => get_string('coursebackups_heading', 'block_backadel'),
            'aria-label' => get_string('help_button_label', 'block_backadel'),
        ]),
        'position-relative'
    );
    echo $OUTPUT->notification(get_string('catalogue_table_missing', 'block_backadel'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Distinct years available in catalogue.
$years = $DB->get_fieldset_sql(
    "SELECT DISTINCT year FROM {block_backadel_catalogue} WHERE year IS NOT NULL ORDER BY year DESC"
);
if (empty($years)) {
    $years = [];
}

// Fetch rows for the selected year, sorted teaching → blueprint → other.
$hascourses = $dbmanager->table_exists('block_backadel_courses');
if ($hascourses) {
    // Correlated subquery avoids duplicate rows when filename has multiple block_backadel_courses rows.
    $sql = "SELECT cat.id,
                   cat.filename,
                   cat.year,
                   (SELECT c.coursetype
                      FROM {block_backadel_courses} c
                     WHERE c.filename = cat.filename
                  ORDER BY c.id DESC
                     LIMIT 1) AS coursetype
              FROM {block_backadel_catalogue} cat
             WHERE cat.year = :year
          ORDER BY CASE COALESCE(
                            (SELECT c2.coursetype
                               FROM {block_backadel_courses} c2
                              WHERE c2.filename = cat.filename
                           ORDER BY c2.id DESC
                              LIMIT 1),
                            'other')
                        WHEN 'teaching'  THEN 1
                        WHEN 'blueprint' THEN 2
                        ELSE 3
                   END,
                   cat.filename ASC";
} else {
    $sql = "SELECT cat.id, cat.filename, cat.year, NULL AS coursetype
              FROM {block_backadel_catalogue} cat
             WHERE cat.year = :year
          ORDER BY cat.filename ASC";
}
$rows = $DB->get_records_sql($sql, ['year' => $year]);

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-sm btn-outline-secondary float-end mb-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'restore',
        'data-help-title' => get_string('coursebackups_heading', 'block_backadel'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]),
    'position-relative'
);
echo $OUTPUT->heading(get_string('coursebackups_heading', 'block_backadel'));

// Year selector form.
if (!empty($years)) {
    $yearnav  = html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
    $yearnav .= html_writer::label(
        get_string('coursebackups_year', 'block_backadel'),
        'backadel-year-select',
        true,
        ['class' => 'me-2']
    );
    $yearnav .= html_writer::select(
        array_combine($years, $years),
        'year',
        $year,
        false,
        ['id' => 'backadel-year-select', 'class' => 'form-select d-inline-block w-auto me-2']
    );
    $yearnav .= html_writer::empty_tag('input', [
        'type'  => 'submit',
        'class' => 'btn btn-secondary ms-2',
        'value' => get_string('go'),
    ]);
    $yearnav .= html_writer::end_tag('form');
    echo $yearnav;
}

// Results table.
$table = new html_table();
$table->attributes['class'] = 'table table-striped generaltable';
$table->head = [
    get_string('coursebackups_col_filename', 'block_backadel'),
    get_string('coursebackups_col_type', 'block_backadel'),
    get_string('coursebackups_col_year', 'block_backadel'),
    get_string('coursebackups_col_actions', 'block_backadel'),
];

foreach ($rows as $row) {
    $type = $row->coursetype ?? 'other';
    if (!in_array($type, ['teaching', 'blueprint', 'other'], true)) {
        $type = 'other';
    }

    if ($type === 'teaching') {
        $badge = html_writer::span(
            get_string('coursetype_teaching', 'block_backadel'), 'badge bg-primary'
        );
    } else if ($type === 'blueprint') {
        $badge = html_writer::span(
            get_string('coursetype_blueprint', 'block_backadel'), 'badge bg-warning text-dark'
        );
    } else {
        $badge = html_writer::span(
            get_string('coursetype_other', 'block_backadel'), 'badge bg-secondary text-dark'
        );
    }

    $restoreurl = new moodle_url('/blocks/backadel/restore.php', [
        'action'  => 'stage',
        'fileid'  => $row->id,
        'year'    => $year,
        'sesskey' => sesskey(),
    ]);
    $actions = html_writer::link(
        $restoreurl,
        get_string('coursebackups_restore', 'block_backadel'),
        ['class' => 'btn btn-sm btn-primary']
    );

    $table->data[] = [
        s($row->filename),
        $badge,
        s((string) ($row->year ?? '')),
        $actions,
    ];
}

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('coursebackups_empty', 'block_backadel'), 'info');
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
