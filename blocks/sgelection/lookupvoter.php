<?php
require_once('../../config.php');
require_once('lookupvoter_form.php');

require_once('classes/election.php');
require_once 'lib.php';

global $DB, $OUTPUT, $PAGE;
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

$election_id = required_param('election_id', PARAM_INT);
$election    = election::get_by_id($election_id);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/blocks/sgelection/lookupvoter.php');
$PAGE->set_pagelayout('standard');
$semester = $election->fullname();
$PAGE->set_heading(get_string('ballot_page_header', 'block_sgelection', $semester));

require_login();

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);


$form = new lookupvoter_form(new moodle_url('lookupvoter.php', array('election_id' => $election_id)));
$stringforresults = '';
if($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/sgelection/commissioner.php', array('id'=>$election_id)));
} else if($voter = $form->get_data()){
    $userid = $DB->get_field('user', 'id', array('username' => $voter->username));
    $voter->userid = $userid;
    $didvote = $DB->get_records('block_sgelection_voted',array('userid'=>$userid, 'election_id'=>$election_id));
    if(empty($didvote)){
        $stringforresults .= $voter->username . ' ' . get_string('didntvote', 'block_sgelection');
    }
    else{
        $stringforresults .= $voter->username . ' ' . get_string('didvote', 'block_sgelection');
    }
}

    // form didn't validate or this is the first display
    echo $OUTPUT->header();
    echo html_writer::tag('h1', get_string('check_vote_status', 'block_sgelection', $election->fullname()));
    $form->display();
    echo $stringforresults;
    echo $OUTPUT->footer();
