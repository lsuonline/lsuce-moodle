<?php

require_once '../../config.php';
require_once 'posting_period_form.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

require_login();

$id = optional_param('id', null, PARAM_INT);

$system = $DB->get_record('course', array('id' => SITEID), '*', MUST_EXIST);

$context = context_system::instance();

require_capability('block/post_grades:canconfigure', $context);

$_s = ues::gen_str('block_post_grades');

$pluginname = $_s('pluginname');
$heading = $_s('posting_period');

$postings_url = new moodle_url('/blocks/post_grades/posting_periods.php');
$base_url = new moodle_url('/blocks/post_grades/period.php');
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

$semesters = ues_semester::get_all(array(), false, 'classes_start DESC');

$form = new posting_period_form(null, array('semesters' => $semesters));

if ($form->is_cancelled()) {
    redirect($postings_url);
} else if ($data = $form->get_data()) {
    if (!isset($data->export_number)) {
        $data->export_number = 0;
    }

    if (empty($data->id)) {
        $id = $DB->insert_record('block_post_grades_periods', $data);
    } else {
        $DB->update_record('block_post_grades_periods', $data);
    }

    redirect(new moodle_url($postings_url, array('flash' => 1)));
}

if ($id) {
    $table = 'block_post_grades_periods';
    $params = array('id' => $id);

    $period = $DB->get_record($table, $params, '*', MUST_EXIST);

    $form->set_data($period);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$form->display();

echo $OUTPUT->footer();
