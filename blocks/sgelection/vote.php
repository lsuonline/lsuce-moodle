<?php
// THIS CAN PROBABLY BE DELETED
// [TOBEDELETED?]
/*
// Written at Louisiana State University
require_once('../../config.php');
require_once('../../enrol/externallib.php');
require_once('../../lib/weblib.php');
require_once("{$CFG->libdir}/formslib.php");

require_login();
$blockname = get_string('sgelection', 'block_sgelection');
$header = get_string('vote', 'block_sgelection');
$context = context_system::instance();

$PAGE->set_context($context);
//$PAGE->set_course($course);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);

$PAGE->set_title($blockname . ': ' . $header);
$PAGE->set_heading($blockname . ': ' . $header);
$PAGE->set_url('/vote.php');
$PAGE->set_pagetype($blockname);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);
echo "VOTE HERE";
echo $OUTPUT->footer();
 * *
 */