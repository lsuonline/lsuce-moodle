<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_pu
 * @copyright  2021 onwards LSU Online & Continuing Education
 * @copyright  2021 onwards Tim Hunt, Robert Russo, David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(dirname(dirname(__FILE__))) . '/config.php');
// require (dirname(__FILE__) . '/lib.php');
require (dirname(__FILE__) . '/classes/forms/upload_form.php');
require (dirname(__FILE__) . '/classes/models/upload_model.php');

$context = \context_system::instance();

// context_block::instance($instance->id);
require_login();

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_RAW);
$delete = optional_param('delete', '', PARAM_RAW);
$fileid = optional_param('idfile', 0, PARAM_INT);

$params = array();

$url = new moodle_url($CFG->wwwroot . '/blocks/pu/uploader.php');
$viewlink = new moodle_url($CFG->wwwroot . '/blocks/pu/view.php');
$uploadfile = null;





if ($id) {
    // $cm = get_coursemodule_from_id('pu', $id, 0, false, MUST_EXIST);
    // $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $uploadfile = $DB->get_record('block_pu_file', array('id' => $cm->instance), '*', MUST_EXIST);
    // $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
    // role_change_permission($managerrole->id, $context, 'moodle/
} else if ($n) {
    $uploadfile = $DB->get_record('block_pu_file', array('id' => $n), '*', MUST_EXIST);
    // $course = $DB->get_record('course', array('id' => $uploadfile->course), '*', MUST_EXIST);
    // $cm = get_coursemodule_from_instance('pu', $uploadfile->id, $course->id, false, MUST_EXIST);
} else {
    // error_log('You must specify a course_module ID or an instance ID');
    // global $COURSE;
    // $course = $COURSE;
    
    $uploadfile = new stdClass();
    $uploadfile->name = "Upload A File";
    $uploadfile->id = 0;

}    

$PAGE->set_context($context);
$PAGE->set_url($url, $params);
$PAGE->set_title(format_string($uploadfile->name));

$PAGE->navbar->add(get_string('dashboard', 'block_pu'), new moodle_url($CFG->wwwroot. "/my/"));
$PAGE->navbar->add(get_string('pu_settings', 'block_pu'), new moodle_url($CFG->wwwroot. "/admin/settings.php?section=blocksettingpu"));
// $PAGE->set_heading(format_string($course->fullname));
// TODO: Add to lang file
$PAGE->set_heading("Upload A File");
// $modcontext = context_module::instance($cm->id);


// echo $OUTPUT->header();
// $this->course_context = context_course::instance($this->course->id);



// require_login($course, true, $cm);

$context = context_system::instance();
// $event = \block_pu\event\course_module_viewed::create(array(
//             'objectid' => $PAGE->cm->instance,
//             'context' => $PAGE->context,
//         ));
// $event->add_record_snapshot('course', $PAGE->course);
// $event->add_record_snapshot($PAGE->cm->modname, $uploadfile);
// $event->trigger();
$model = new upload_model();

if ($uploadfile->id != 0) {
    $params = array(
        'id' => $uploadfile->id
    );
    
    $file = $model->get( $uploadfile->id );

    // Prepare the data to pass into the form with instance.
    $action = 'UPDATE';

    $mform = new upload_form('./uploader.php?id='.$id. "&action={$action}&idfile={$file->id}");

    // Copy all the files from the 'real' area, into the draft area.
    file_prepare_draft_area($file->block_pu_file, $context->id, 'block_pu', 'block_pu_file', $file->block_pu_files, null);

    // Set form data: This will load the file manager with your previous files
    $mform->set_data($file); 

} else {
    $action = 'ADD';
    $params = null;
    $mform = new upload_form();
}


$htmltidbits = html_writer::start_tag( 'a', array( 'href' => "./view.php" ) )
        .html_writer::start_tag( 'button', array( 'type' => 'button', 'class' => 'btn btn-primary', 'style' =>'margin:3%; width:20%' ) )
        .format_string( 'View Files' )
        .html_writer::end_tag('button')
        .html_writer::end_tag( 'a' );

// echo html_writer::start_tag( 'a', array( 'href' => "./uploader.php?id={$id}&action=DELETE" ) )
//         .html_writer::start_tag( 'button', array( 'type' => 'button', 'class' => 'btn btn-danger', 'style' =>'margin:3%; width:20%' ) )
//         .format_string( 'Delete File' )
//         .html_writer::end_tag('button')
//         .html_writer::end_tag( 'a' );



// if ($action == 'DELETE' ) {
    
//     if ($delete == 'ConfirmDelete') {
//         $model->delete($uploadfile->id, $file->block_pu_files);
//         redirect($viewlink);    
//     }
    
//     echo $OUTPUT->confirm(format_string( "Are you sure you want to delete this file"),
//             "upload.php?id={$id}&action=DELETE&delete=ConfirmDelete", $CFG->wwwroot . '/mod/uploadfile/view.php?id=' . $id );

//     echo $OUTPUT->footer();
//     die();
// }

// ---------
// CONFIGURE FILE MANAGER
// ---------


if ( $mform->is_cancelled() ) {
    
     redirect($viewlink);
    
} else if ( $formdata = $mform->get_data() ) {
    
    // Saves the form loaded file to the database in the files table.
    file_save_draft_area_files(
        // $formdata->attachments,
        $formdata->pu_file,
        $context->id,
        'block_pu',
        'pu_file',
        // $formdata->attachments,
        $formdata->pu_file,
        $mform->get_filemanager_options_array()
    );

    $content = $mform->get_file_content('pu_file');

    // To get the name of the uploaded file
    $name = $mform->get_new_filename('pu_file');

    // Get the path from the PU settings.
    $pupath = get_config('moodle', "block_pu_copy_file");

    // Make sure the folder is there.    
    if (!is_dir($pupath)) {
        mkdir($pupath, 0777, true);
    }

    $storedfile = $mform->save_stored_file(
        'pu_file',
        $context->id,
        'pu_file2',
        "",
        "",
        $pupath,
        null,
        false
    );

    // Save or update in local table uploadfile_files.
    // $formdata->instance = $cm->instance;
    error_log("What is action: ". $action);
    if ($action == 'ADD') {
        $model->save($formdata);
    } else {
        $formdata->id = $fileid;
        $model->update($formdata);
    }    

    error_log("uploader.php -> What is the redirect link: ". $viewlink);
    redirect ($viewlink);
}

echo $OUTPUT->header();

echo $htmltidbits;

$mform->display();
echo $OUTPUT->footer();

