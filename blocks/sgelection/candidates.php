<?php

require_once('../../config.php');
require_once('candidates_form.php');
require_once('classes/candidate.php');
require_once('classes/election.php');
require_once 'lib.php';
require_once('renderer.php');

global $DB, $OUTPUT, $PAGE;
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

$election_id = required_param('election_id', PARAM_INT);
$election    = election::get_by_id($election_id);
$id          = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/blocks/sgelection/candidates.php');
$PAGE->set_pagelayout('standard');
$semester = $election->fullname();
$PAGE->set_heading(get_string('ballot_page_header', 'block_sgelection', $semester));

require_login();

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);

$form = new candidate_form(new moodle_url('candidates.php', array('election_id' => $election_id)), array('election' => $election, 'id'=>$id));

if($form->is_cancelled()) {
    redirect(sge::ballot_url($election_id));
} else if($fromform = $form->get_data()){
    if($election->readonly()){
        block_sgelection_renderer::print_readonly();
    }
    $userid = $DB->get_field('user', 'id', array('username' => $fromform->username));
    $fromform->userid = $userid;
    $candidate      = new candidate($fromform);
    $candidate->save();

    //logging
    $action = $id ? 'updated' : 'created';
    $candidate->logaction($action, null, array('relateduserid' => $candidate->userid));

    unset($username);
    $thisurl = new moodle_url('ballot.php', array('election_id' => $election_id));
    redirect($thisurl);
} else {
    // form didn't validate or this is the first display
    echo $OUTPUT->header();
    if($id){
        $candidate = candidate::get_by_id($id);
        $candidate->username = $DB->get_field('user', 'username', array('id'=>$candidate->userid));
        $form->set_data($candidate);
    }

    $form->display();
    echo $OUTPUT->footer();
}
