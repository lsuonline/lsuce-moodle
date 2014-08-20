<?php
require_once '../../config.php';
global $CFG, $PAGE;
//$courseid = required_param('id', PARAM_INT);
$blockname = get_string('pluginname', 'block_sentinel');
$PAGE->set_url('/blocks/sentinel/index.php');
$PAGE->set_context(context_system::instance());
//$PAGE->set_course($course);
$PAGE->set_heading($blockname);
$PAGE->set_title($blockname);
$PAGE->navbar->add($blockname);
$PAGE->set_pagetype('block_ues_logs');

echo $OUTPUT->header();
echo $OUTPUT->heading($blockname);

$puOutput       = $PAGE->theme->get_renderer($PAGE,'block_sentinel');
$message        = $puOutput->getUnregisteredMessage();
$landingCourse  = get_config('block_sentinel', 'landing_course');
$redirect       = new moodle_url('/course/view.php',array('id'=>$landingCourse));

notice($message, $redirect);
?>
