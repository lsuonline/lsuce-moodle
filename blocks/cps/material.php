<?php

require_once '../../config.php';
require_once 'classes/lib.php';
require_once 'material_form.php';

require_login();

if (!cps_material::is_enabled()) {
    print_error('not_enabled', 'block_cps', '', cps_material::name());
}

if (!ues_user::is_teacher()) {
    print_error('not_teacher', 'block_cps');
}

$teacher = ues_teacher::get(array('userid' => $USER->id));

$non_primaries = (bool) get_config('block_cps', 'material_nonprimary');

$sections = $teacher->sections(!$non_primaries);

if (empty($sections)) {
    print_error('no_section', 'block_cps');
}

$_s = ues::gen_str('block_cps');

$blockname = $_s('pluginname');
$heading = cps_material::name();

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);
$PAGE->set_heading($blockname . ': '. $heading);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($heading);
$PAGE->set_title($heading);
$PAGE->set_url('/blocks/cps/material.php');

$form = new material_form(null, array('sections' => $sections));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/my'));
} else if ($data = $form->get_data()) {
    $fields = get_object_vars($data);

    $current_selections = cps_material::get_all(array('userid' => $USER->id));

    foreach ($fields as $name => $value) {
        if (!preg_match('/^material_(\d+)/', $name, $matches)) {
            continue;
        }

        $params = array('userid' => $USER->id, 'courseid' => $matches[1]);

        $material = cps_material::get($params);

        if (!$material) {
            $material = new cps_material();
            $material->fill_params($params);
        }

        $material->save();
        $material->apply();

        unset($current_selections[$material->id]);
    }

    // Remove deselected
    foreach ($current_selections as $material) {
        $material->unapply();
        $material->delete($material->id);
    }

    $success = true;
}

$all_materials = cps_material::get_all(array('userid' => $USER->id));
$data = array();
foreach ($all_materials as $material) {
    $data['material_' . $material->courseid] = 1;
}
$form->set_data($data);

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help($heading, 'material', 'block_cps');

if (isset($success) and $success) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$form->display();

echo $OUTPUT->footer();
