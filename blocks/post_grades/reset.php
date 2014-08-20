<?php

require_once '../../config.php';
require_once 'lib.php';
require_once 'query_form.php';

require_login();

$system = $DB->get_record('course', array('id' => SITEID), '*', MUST_EXIST);

$context = context_system::instance();

$shortname = optional_param('shortname', null, PARAM_TEXT);
$flash = optional_param('flash', null, PARAM_INT);

require_capability('block/post_grades:canconfigure', $context);

$_s = ues::gen_str('block_post_grades');

$pluginname = $_s('pluginname');
$heading = $_s('reset_posting');

$base_url = new moodle_url('/blocks/post_grades/reset.php');
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

$query_form = new query_form();

if ($query_form->is_cancelled()) {
    redirect($admin_url);
}

if ($shortname) {
    $data = new stdClass();
    $data->shortname = $shortname;
    $query_form->set_data($data);

    $entries = post_grades::find_postings_by_shortname($shortname);

    $reset_form = new reset_form(null, array(
        'shortname' => $shortname,
        'entries' => $entries
    ));

    if ($reset_form->is_cancelled()) {
        redirect($base_url);
    } else if ($data = $reset_form->get_data()) {
        $fields = (array)$data;
        unset($fields['submitbutton'], $fields['shortname']);

        $ids = implode(',', array_keys($fields));
        $DB->delete_records_select('block_post_grades_postings', "id IN ($ids)");

        redirect(new moodle_url($base_url, array('flash' => 1)));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($flash) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$query_form->display();

if (!empty($reset_form)) {
    $reset_form->display();
}

echo $OUTPUT->footer();
