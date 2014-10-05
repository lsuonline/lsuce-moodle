<?php

require_once('../../config.php');
require_once('resolutions_form.php');
require_once('classes/resolution.php');
require_once('classes/election.php');
require_once 'lib.php';
require_once('renderer.php');

global $DB, $OUTPUT, $PAGE;
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

//next look for optional variables.
$resolutionTitle = optional_param('title_of_resolution', '', PARAM_TEXT);
$resolutionText = optional_param('resolution_text', '', PARAM_TEXT);

$id = optional_param('id', 0, PARAM_INT);
$election_id = required_param('election_id', PARAM_INT);
$election    = election::get_by_id($election_id);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/blocks/sgelection/resolutions.php', array('election_id' => $election_id));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('resolution_page_header', 'block_sgelection'));

require_login();

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);

$form = new resolution_form(new moodle_url('resolutions.php', array('election_id' => $election_id)), array('election' => $election, 'id'=>$id));

if($form->is_cancelled()) {
    redirect(sge::ballot_url($election_id));
} else if($fromform = $form->get_data()){
        if($election->readonly()){
            block_sgelection_renderer::print_readonly();
        }
        $resolution      = new resolution($fromform);
        $resolution->text = $fromform->text_editor['text'];
        $resolution->link = $fromform->link;
        if(isset($fromform->restrict_fulltime)){
            $resolution->restrict_fulltime = $fromform->restrict_fulltime;
        } else {
            $resolution->restrict_fulltime = 0;
        }

        $resolution->save();

        //logging
        $action = $id ? 'updated' : 'created';
        $resolution->logaction($action);

        $thisurl = new moodle_url('ballot.php', array('election_id' => $election_id));
        redirect($thisurl);
} else {
    // form didn't validate or this is the first display
    if($id){

        $editor_options = array(
            'trusttext' => true,
            'subdirs' => 1,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'accepted_types' => '*',
            'context' => $context
        );

        $resolution = resolution::get_by_id($id);
        $resolution = file_prepare_standard_editor($resolution, 'text', $editor_options);
        $form->set_data($resolution);
    }
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
