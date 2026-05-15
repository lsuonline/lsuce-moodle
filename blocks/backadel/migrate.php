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
 * Admin trigger page: queue Backadel filesystem catalogue migration scan.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use block_backadel\local\catalogue_allowlists;
use block_backadel\local\reclassifier;

global $OUTPUT, $PAGE, $DB, $CFG;

admin_externalpage_setup('block_backadel_migrate');
$context = context_system::instance();
require_capability('block/backadel:managemigration', $context);

$PAGE->requires->js_call_amd('block_backadel/help', 'init');
$PAGE->requires->js_call_amd('block_backadel/migrate_status', 'init');

$pageurl = new moodle_url('/blocks/backadel/migrate.php');

$backadelqueuedtasks = $DB->count_records_select(
    'task_adhoc',
    'classname LIKE :pat',
    ['pat' => '%block_backadel%']
);

$catalogcount = $DB->count_records('block_backadel_catalogue');
$coursescount = $DB->count_records('block_backadel_courses');

// Check whether the scan directory has been configured.
$scanrelpath = get_config('block_backadel', 'path');
$scanpathconfigured = ($scanrelpath !== false && trim((string) $scanrelpath) !== '');

if (data_submitted() && optional_param('runmigrate', 0, PARAM_INT) === 1) {
    require_sesskey();

    \core\task\manager::queue_adhoc_task(new \block_backadel\task\migrate_filesystem_adhoc(), true);

    redirect(
        $pageurl,
        get_string('migrate_queued', 'block_backadel'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if (data_submitted() && optional_param('reclassify', 0, PARAM_INT) === 1) {
    require_sesskey();

    if ($backadelqueuedtasks > 0) {
        redirect(
            $pageurl,
            get_string('reclassify_disabled_queued', 'block_backadel'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $rawpatterns = optional_param_array('reclassify_patterns', [], PARAM_RAW);
    $selpatterns = [];
    foreach ($rawpatterns as $p) {
        $p = core_text::strtolower(trim((string) $p));
        if (in_array($p, catalogue_allowlists::VALID_PATTERNS, true)) {
            $selpatterns[] = $p;
        }
    }
    $selpatterns = array_values(array_unique($selpatterns));

    $nullsem = optional_param('reclassify_null_semester', 0, PARAM_INT) === 1;
    $dry = optional_param('reclassify_dry_run', 0, PARAM_INT) === 1;

    if ($selpatterns === [] && !$nullsem) {
        redirect(
            $pageurl,
            get_string('reclassify_none_selected', 'block_backadel'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if ($dry) {
        $count = (new reclassifier())->count_matching($selpatterns, $nullsem);
        redirect(
            $pageurl,
            get_string('reclassify_dryrun_result', 'block_backadel', $count),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }

    $task = new \block_backadel\task\reclassify_catalogue_adhoc();
    $task->set_custom_data((object) [
        'patterns' => $selpatterns,
        'null_semester' => $nullsem,
        'cursor_id' => 0,
        'chain_id' => '',
        'chain_started_ts' => 0,
        'total_processed' => 0,
        'total_reclassified' => 0,
        'total_missing' => 0,
        'total_unparseable' => 0,
    ]);
    \core\task\manager::queue_adhoc_task($task, true);

    redirect(
        $pageurl,
        get_string('reclassify_queued', 'block_backadel'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-sm btn-outline-secondary float-end mb-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'migrate',
        'data-help-title' => get_string('migrate_heading', 'block_backadel'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]),
    'position-relative'
);
echo $OUTPUT->heading(get_string('migrate_heading', 'block_backadel'));

// Warning: scan directory not configured — migration will scan 0 files.
if (!$scanpathconfigured) {
    $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'blocksettingbackadel']);
    echo $OUTPUT->notification(
        get_string('migrate_warn_no_path', 'block_backadel', $settingsurl->out(false)),
        \core\output\notification::NOTIFY_WARNING
    );
} else {
    // Show the resolved absolute scan path so admins can confirm what will be scanned.
    echo html_writer::div(
        get_string('migrate_scan_path', 'block_backadel') . ' ' .
        html_writer::tag('code', rtrim($CFG->dataroot, '/') . '/' . ltrim(trim((string)$scanrelpath), '/')),
        'text-muted small mb-3'
    );
}

// Counts — updated live by migrate_status.js via AJAX.
echo html_writer::div(
    get_string('migrate_catalogue_count', 'block_backadel', '') .
    html_writer::tag('strong', $catalogcount, ['data-region' => 'migrate-count-catalogue']),
    'mb-1'
);
echo html_writer::div(
    get_string('migrate_courses_count', 'block_backadel', '') .
    html_writer::tag('strong', $coursescount, ['data-region' => 'migrate-count-courses']),
    'mb-3'
);

echo html_writer::div(get_string('migrate_what_happens', 'block_backadel'), 'text-muted small mb-3');

$form = html_writer::start_tag(
    'form',
    ['method' => 'post', 'action' => $pageurl->out(false), 'class' => 'mb-3']
);
$form .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'runmigrate', 'value' => '1']);
$form .= html_writer::empty_tag('input', [
    'type'  => 'submit',
    'name'  => 'submitmigration',
    'value' => get_string('migrate_run_button', 'block_backadel'),
    'class' => 'btn btn-primary',
    'data-action' => 'run-migrate',
]);
$form .= html_writer::end_tag('form');
echo $form;

// Success badge — shown by JS after task completes.
echo html_writer::div(
    get_string('migrate_completed', 'block_backadel'),
    'd-none alert alert-success mb-3',
    ['data-region' => 'migrate-success']
);

if ($backadelqueuedtasks > 0) {
    echo $OUTPUT->notification(
        get_string('reclassify_disabled_queued', 'block_backadel'),
        \core\output\notification::NOTIFY_WARNING
    );
}

$reclasspatterns = catalogue_allowlists::VALID_PATTERNS;
sort($reclasspatterns, SORT_STRING);
$patternlabels = [
    'semester_legacy' => 'Semester Legacy',
    'semester_legacy_lc' => 'Semester Legacy (lowercase)',
    'semester_legacy_intl' => 'Semester Legacy (international)',
    'semester_legacy_clone' => 'Semester Legacy (clone)',
    'storage_course' => 'Storage course',
    'storagecourse_dept' => 'Storage course (department)',
    'storage_legacy' => 'Storage legacy',
    'backadel_modern' => 'Backadel modern',
    'backadel_instructor' => 'Backadel instructor',
    'moodle_native' => 'Moodle native',
    'unknown' => 'Unknown / unclassified',
];

echo html_writer::tag('hr', '', ['class' => 'my-4']);
echo html_writer::div(
    html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-sm btn-outline-secondary float-end mb-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'reclassify',
        'data-help-title' => get_string('reclassify_heading', 'block_backadel'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]),
    'position-relative'
);
echo $OUTPUT->heading(get_string('reclassify_heading', 'block_backadel'));
echo html_writer::div(get_string('reclassify_what_happens', 'block_backadel'), 'text-muted small mb-3');

$reclassform = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'mb-3',
]);
$reclassform .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
$reclassform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'reclassify', 'value' => '1']);
$reclassform .= html_writer::div(get_string('reclassify_label_patterns', 'block_backadel'), 'mb-1');
$reclassform .= html_writer::start_tag('div', ['class' => 'ms-1 mb-2']);
foreach ($reclasspatterns as $pat) {
    $label = $patternlabels[$pat] ?? ucwords(str_replace('_', ' ', $pat));
    $reclassform .= html_writer::tag(
        'div',
        html_writer::tag('label', html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'reclassify_patterns[]',
            'value' => $pat,
            'class' => 'form-check-input me-1',
        ]) . $label, ['class' => 'form-check small']),
        ['class' => 'mb-1']
    );
}
$reclassform .= html_writer::end_tag('div');

$nullsemesterhelp = html_writer::tag('button', '?', [
    'type' => 'button',
    'class' => 'btn btn-sm btn-outline-secondary align-baseline ms-1',
    'data-action' => 'show-help',
    'data-help-topic' => 'reclassify_null_semester',
    'data-help-title' => get_string('reclassify_null_semester_help_title', 'block_backadel'),
    'aria-label' => get_string('help_button_label', 'block_backadel'),
]);
$reclassform .= html_writer::div(
    html_writer::tag('label', html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'reclassify_null_semester',
        'value' => '1',
        'class' => 'form-check-input me-1',
    ]) . get_string('reclassify_label_null_semester', 'block_backadel') . $nullsemesterhelp, [
        'class' => 'form-check small',
    ]),
    'mb-2'
);

$reclassform .= html_writer::div(
    html_writer::tag('label', html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'reclassify_dry_run',
        'value' => '1',
        'class' => 'form-check-input me-1',
    ]) . get_string('reclassify_label_dry_run', 'block_backadel'), ['class' => 'form-check small']),
    'mb-2'
);

$reclasssubmitattrs = [
    'type' => 'submit',
    'name' => 'reclassifysubmit',
    'value' => get_string('reclassify_run_button', 'block_backadel'),
];
if ($backadelqueuedtasks > 0) {
    $reclasssubmitattrs['disabled'] = 'disabled';
    $reclasssubmitattrs['class'] = 'btn btn-secondary';
} else {
    $reclasssubmitattrs['class'] = 'btn btn-primary';
}

$reclassform .= html_writer::empty_tag('input', $reclasssubmitattrs);
$reclassform .= html_writer::end_tag('form');
echo $reclassform;

echo $OUTPUT->footer();
