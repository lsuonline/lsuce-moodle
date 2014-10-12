<?php

require_once('../../config.php');
require_once('admin_form.php');
require_once 'lib.php';

global $DB, $OUTPUT, $PAGE;
require_login();
sge::allow_only(sge::FACADVISOR);

$done    = optional_param('done', 0, PARAM_TEXT);
$selfurl = '/blocks/sgelection/admin.php';

$PAGE->requires->js('/blocks/sgelection/js/autouserlookup.js');

$PAGE->set_context(context_system::instance());
$PAGE->set_url($selfurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(sge::_str('admin_page_header'));

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);

$customdata['default_results_interval'] = 60;
$form = new sg_admin_form(null, $customdata);

if($form->is_cancelled()){
    redirect('/');
} else if($fromform = $form->get_data()){
    //We need to add code to appropriately act on and store the submitted data
    sge::config('commissioner', $fromform->commissioner);
    sge::config('fulltime', $fromform->fulltime);
    sge::config('parttime', $fromform->parttime);
    sge::config('results_recipients', str_replace(' ', '', $fromform->results_recipients));
    sge::config('results_interval', $fromform->results_interval);
    // @TODO if excl_curr_codes is not set, we have a problem.
    // Probably, supply a default value here.
    // Alternatively, provide a 'none' option in the form that will need to be checked here.
    if(!empty($fromform->excluded_curr_codes)){
        sge::config('excluded_curr_codes', implode(',', $fromform->excluded_curr_codes));
    }

    redirect(new moodle_url($selfurl, array('done'=>'true')));
} else {
    $form->set_data(sge::config());
    echo $OUTPUT->header();


    echo $done == true ? $OUTPUT->notification(sge::_str('savesuccess'), 'notifysuccess') : '';
    $form->display();
    $listofusers = sge::get_list_of_usernames();
    $PAGE->requires->js_init_call('autouserlookup', array($listofusers, '#id_commissioner'));

    echo $OUTPUT->footer();
}
