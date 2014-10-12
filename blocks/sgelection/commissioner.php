<?php
require_once('../../config.php');
require_once('commissioner_form.php');
require_once('classes/election.php');
require_once('classes/voter.php');
require_once('classes/office.php');
require_once 'lib.php';
require_once('renderer.php');

global $DB, $OUTPUT, $PAGE, $USER;
require_login();
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

$id      = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$selfurl = '/blocks/sgelection/commissioner.php';

$PAGE->set_context($context);
$PAGE->set_url($selfurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('admin_page_header', 'block_sgelection'));

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);

list($minyear, $maxyear) = sge::commissioner_form_semester_year_range();
$data = array(
    'semesters' => sge::commissioner_form_available_semesters_menu(),
    'datedefaults' => array(
        'startyear'=> $minyear,
        'stopyear' => $maxyear,
        'optional' => false,
        'step'     => 1
        ),
    );

$form = new commissioner_form(null, $data);

if($form->is_cancelled()){
    $url = new moodle_url('/');
    redirect($url);
} else if($fromform = $form->get_data()){
    $election = new election($fromform);
    $election->thanksforvoting = $fromform->thanksforvoting_editor['text'];
    //@TODO update str_replace to handle newlines too
    $election->test_users = str_replace(' ', '', $fromform->test_users);
    $election->save();

    $collegemap = array();
    foreach(office::get_all() as $office){
        if(empty($collegemap[$office->college])){
            $collegemap[$office->college] = array();
        }
        $exists = in_array($office->name, $collegemap[$office->college]);
        if(!$exists){
            $collegemap[$office->college][] = $office->name;
        }else{
            throw new Exception(sprintf("Multiple offices found: '%s' for college '%s', id: %d", $office, $college, $id));
        }
    }

    // Update offices, if required
    $offices = $fromform->common_college_offices;

    if(!empty($offices)){
        global $DB;

        $colleges = sge::get_distinct_colleges(); // {enrol_ues_usermeta} row objects
        $newconfig = array();
        foreach(explode(',', $offices) as $office){ //comma-separated strings from user
            $office = trim($office);
            foreach($colleges as $college){
                $found = array_key_exists($college->value, $collegemap) && in_array($office, $collegemap[$college->value]);
                if(!$found){
                    $newoffice = new stdClass();
                    $newoffice->name = $office;
                    $newoffice->college = $college->value;
                    $newoffice->number = 1; //Arbitrary default.
                    $newoffice->weight = 3; //Arbitrary default.
                    $DB->insert_record('block_sgelection_office', $newoffice);
                }
            }
            $newconfig[] = $office;
        }
        sge::config('common_college_offices', implode(',', $newconfig));
    }

    //logging
    $action = $id ? 'updated' : 'created';
    $election->logaction($action);

    redirect(new moodle_url('ballot.php', array('election_id' => $election->id)));
} else {
    echo $OUTPUT->header();

    if($id > 0){
        $election = election::get_by_id($id);
        if($election->readonly()){
            block_sgelection_renderer::print_readonly();
        }
        $editor_options = array(
            'trusttext' => true,
            'subdirs' => 1,
            'maxfiles' => 0,
            'accepted_types' => '*',
            'context' => $context
        );
        $election = file_prepare_standard_editor($election, 'thanksforvoting', $editor_options);
        $form->set_data($election);

        $lookupvoter = new moodle_url('/blocks/sgelection/lookupvoter.php', array('election_id' => $id));
        echo html_writer::link($lookupvoter, html_writer::tag('h1', get_string('check_to_see', 'block_sgelection')));
    }
    if(empty($data['semesters'])){
        // In the extremely rare case that there are no available semesters, redirect to /my.
        // @TODO the definition of 'available' may need to be altered WRT semesters.
        // @TODO Make this a get_string() returned by the renderer.
        echo "No Active Semesters";
        echo $OUTPUT->continue_button(new moodle_url('/my'));
    }else{
        $form->display();
    }

    echo $OUTPUT->footer();
}
