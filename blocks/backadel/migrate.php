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

global $OUTPUT, $PAGE, $DB;

admin_externalpage_setup('block_backadel_migrate');
$context = context_system::instance();
require_capability('block/backadel:managemigration', $context);

$PAGE->requires->js_call_amd('block_backadel/help', 'init');
$PAGE->requires->js_call_amd('block_backadel/migrate_status', 'init');

$pageurl = new moodle_url('/blocks/backadel/migrate.php');

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

// Spinner — shown by JS while task is queued; hidden otherwise.
echo html_writer::div(
    html_writer::div('', 'spinner-border spinner-border-sm text-primary me-2', ['role' => 'status']) .
    get_string('migrate_running', 'block_backadel'),
    'd-none d-flex align-items-center mb-3',
    ['data-region' => 'migrate-spinner']
);

// Success badge — shown by JS after task completes.
echo html_writer::div(
    get_string('migrate_completed', 'block_backadel'),
    'd-none alert alert-success mb-3',
    ['data-region' => 'migrate-success']
);

echo $OUTPUT->footer();
