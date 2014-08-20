<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

require_login();

$flash = optional_param('flash', null, PARAM_INT);
$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);

$system = $DB->get_record('course', array('id' => SITEID), '*', MUST_EXIST);

$context = context_system::instance();

require_capability('block/post_grades:canconfigure', $context);

$_s = ues::gen_str('block_post_grades');

$pluginname = $_s('pluginname');
$heading = $_s('posting_periods');

$create_url = new moodle_url('/blocks/post_grades/period.php');
$base_url = new moodle_url('/blocks/post_grades/posting_periods.php');
$admin_url = new moodle_url('/admin/settings.php', array(
    'section' => 'blocksettingpost_grades'
));

$title = "$system->shortname: $heading";

$PAGE->set_url($base_url);
$PAGE->set_context($context);
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->navbar->add($pluginname, $admin_url);
$PAGE->navbar->add($heading);

$periods = $DB->get_records('block_post_grades_periods', null, 'start_time ASC');
$semesters = ues_semester::get_all();

if ($action == 'confirm' and isset($periods[$id])) {
    // Cleanup
    $DB->delete_records('block_post_grades_postings', array('periodid' => $id));
    $DB->delete_records('block_post_grades_periods', array('id' => $id));

    redirect(new moodle_url($base_url, array('flash' => 1)));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($action == 'delete' and isset($periods[$id])) {
    $semester = $semesters[$periods[$id]->semesterid];
    $msg = $_s('are_you_sure', "$semester");

    $params = array('action' => 'confirm', 'id' => $id);
    $confirm_url = new moodle_url($base_url, $params);

    echo $OUTPUT->confirm($msg, $confirm_url, $base_url);
    echo $OUTPUT->footer();
    exit;
}

if ($flash) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

if (empty($periods)) {
    echo $OUTPUT->notification($_s('no_posting'));
    echo $OUTPUT->continue_button($create_url);
    echo $OUTPUT->footer();
    exit;
}

$create_link = html_writer::link($create_url, $_s('new_posting'));

echo html_writer::tag('div', $create_link, array('class' => 'centered controls'));

$table = new html_table();

$table->head = array(
    $_s('semester'),
    $_s('post_type'),
    $_s('start_time'),
    $_s('end_time'),
    get_string('active'),
    get_string('action')
);

$pattern = 'm/d/Y g:00:00 a';

$now = time();

$edit_icon = $OUTPUT->pix_icon('i/edit', get_string('edit'));
$delete_icon = $OUTPUT->pix_icon('i/cross_red_big', get_string('delete'));

foreach ($periods as $period) {
    $line = new html_table_row();

    $semester = $semesters[$period->semesterid];

    $active = ($now >= $period->start_time and $now <= $period->end_time) ? 'Y' : 'N';

    $params = array('id' => $period->id);
    $edit_url = new moodle_url($create_url, $params);
    $edit = html_writer::link($edit_url, $edit_icon);

    $params['action'] = 'delete';
    $delete_url = new moodle_url($base_url, $params);
    $delete = html_writer::link($delete_url, $delete_icon);

    $line->cells[] = "$semester";
    $line->cells[] = $_s($period->post_type);
    $line->cells[] = date($pattern, $period->start_time);
    $line->cells[] = date($pattern, $period->end_time);
    $line->cells[] = $active;
    $line->cells[] = $edit . ' - ' . $delete;

    $table->data[] = $line;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
