<?php

require_once('../../config.php');
require_once('offices_form.php');
require_once('classes/office.php');
require_once('classes/voter.php');
require_once 'lib.php';

global $DB, $OUTPUT, $PAGE;

$id = optional_param('id', 0, PARAM_INT);
$election_id = optional_param('election_id', false, PARAM_INT);
$rtn = optional_param('rtn', '/', PARAM_ALPHAEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/sgelection/offices.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(sge::_str('office_page_header'));

require_login();
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);

$rtnurl = new moodle_url($rtn.".php", array('election_id'=>$election_id));
$ballothomeurl = new moodle_url('/blocks/sgelection/ballot.php', array('election_id'=>$election_id));
$selfurl       = new moodle_url('/blocks/sgelection/offices.php', array('election_id'=>$election_id));
$form = new office_form($selfurl, array('election_id'=>$election_id, 'id'=>$id, 'rtn'=>$rtn));

if($form->is_cancelled()) {
    redirect(sge::ballot_url($election_id));
} else if($fromform = $form->get_data()){
        $office = new office($fromform);
        $office->save();

        //logging
        $action = $id ? 'updated' : 'created';
        $office->logaction($action);

        redirect($rtnurl);
} else {
    echo $OUTPUT->header();

    if($id > 0){
        $office = office::get_by_id($id);
        $form->set_data($office);
    }
    $form->display();
    echo $OUTPUT->footer();
}
