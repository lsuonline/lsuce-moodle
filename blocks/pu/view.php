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
require (dirname(__FILE__) . '/classes/models/upload_model.php');
// require (dirname(__FILE__) . '/lib.php');

$context = \context_system::instance();

$model = new upload_model();

// This is the mdl_pu_files id NOT mdl_file id.
$id = optional_param('id', 0, PARAM_INT);

// Copy the file to destination or delete the file?
$action = optional_param('action', 0, PARAM_TEXT);

$mfileid = optional_param('mdl_file_id', 0, PARAM_INT);
$pfileid = optional_param('pu_file_id', 0, PARAM_INT);
$filetype = optional_param('pu_or_nonmood', '', PARAM_TEXT);
$nonmood_filename = optional_param('nonmood_filename', '', PARAM_TEXT);
$fpath = get_config('moodle', "block_pu_copy_file");


error_log("\n\n**************************************");
error_log("\nview.php -> What is the id: ". $id. "  |<----");
error_log("\nview.php -> What is the action: ". $action. "  |<----");
error_log("\nview.php -> What is mfileid: ". $mfileid. "  |<----");
error_log("\nview.php -> What is pfileid: ". $pfileid. "  |<----");
error_log("\nview.php -> What is file path: ". $fpath. "  |<----");
error_log("\nview.php -> What is filetype: ". $filetype. "  |<----");
error_log("\n**************************************\n\n");
if ($action === "copy") {

    // $fpath = get_config('moodle', "block_pu_copy_file");
    // error_log("\nview.php -> What is file path: ". $fpath);
    if (!isset($fpath)) {
        error_log("FAIL, no destination set for this file.");
    } else {
        error_log("What is the filepath: ". $fpath);
        // copy(string $from, string $to, ?resource $context = null): bool
        $fs = get_file_storage();
        // $fs->get_file(...);
        $file = $fs->get_file_by_id($mfileid);
        $fname = $file->get_filename();

        // if (file_exists($fpath . $fname)) {
        //     echo "The file $filename exists";
        // } else {
        //     echo "The file $filename does not exist";
        // }
        // $file->get_contextid()
        // $file->get_component()
        // $file->get_filearea()
        // $file->get_itemid()
        // $file->get_filepath()
        // $file->get_filename()
        // $file->copy_content_to($CFG->dataroot . '/peerassessments/' . $peerassessment->id . '/' . $student->id.'/'.$file->get_filename())
        // $file->copy_content_to($fpath. $fname);
    }
} else if ($action === "delete") {
    error_log("Do stuff to delete the file.......");
    // $pfileid
    if ($filetype === "pu") {

        $model->delete($pfileid, $mfileid);

    } else if ($filetype === "nonmood") {
        error_log("***********  WARNING WARNING WARNING ***********");
        error_log("TODO: Delete this file: ". $nonmood_filename);
        error_log("will be unlinking this file: ". $fpath.$nonmood_filename);

        unlink($fpath.$nonmood_filename);
        // error_log("TODO: Delete this file");
    }
}


// if file exists, ask to replace
// if path doesn't exists, ask to create


// $uploadedfiles = $DB->get_records('block_pu_file');

// if ($id) {
//     // $cm = get_coursemodule_from_id('pu', $id, 0, false, MUST_EXIST);
//     // $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//     // $uploadfile = $DB->get_record('block_pu_file', array('id' => $cm->instance), '*', MUST_EXIST);
// } else if ($n) {
//     $uploadfile = $DB->get_record('block_pu_file', array('id' => $n), '*', MUST_EXIST);
//     // $course = $DB->get_record('course', array('id' => $uploadfile->course), '*', MUST_EXIST);
//     // $cm = get_coursemodule_from_instance('pu', $uploadfile->id, $course->id, false, MUST_EXIST);
// } else {
//     // error_log('You must specify a course_module ID or an instance ID');
//     $uploadfile = new stdClass();
//     $uploadfile->name = "New File";
//     $uploadfile->id = 0;
// }

// require_login($course, true, $cm);
require_login();
$context = context_system::instance();
// $event = \block_pu\event\course_module_viewed::create(array(
//             'objectid' => $PAGE->cm->instance,
//             'context' => $PAGE->context,
//         ));
// $event->add_record_snapshot('course', $PAGE->course);
// $event->add_record_snapshot($PAGE->cm->modname, $uploadfile);
// $event->trigger();

$url = new moodle_url($CFG->wwwroot . '/blocks/pu/view.php');
// $model = new upload_model();
// $imageurl = null;

// if ($uploadfile->id != 0) {
//     $params = array(
//         'id' => $uploadfile->id
//     );
    
//     $file = $model->get($uploadfile->id);
//     $imageurl = print_image_uploadfile($file->attachments, $context->id);
// } else {
//     $params = null;
// }

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title("Uploaded Files");
$PAGE->navbar->add(get_string('dashboard', 'block_pu'), new moodle_url($CFG->wwwroot. "/my/"));
$PAGE->navbar->add(get_string('pu_settings', 'block_pu'), new moodle_url($CFG->wwwroot. "/admin/settings.php?section=blocksettingpu"));

// // TODO: Add to lang file
$PAGE->set_heading("Uploaded Files");
// $modcontext = context_module::instance($cm->id);


// --------------------------
// View the Mappings.
echo $OUTPUT->header();

echo html_writer::start_tag( 'a', array( 'href' => "./uploader.php" ) )
        .html_writer::start_tag( 'button', array( 'type' => 'button', 'class' => 'btn btn-primary', 'style' =>'margin:3%; width:20%' ) )
        .format_string( 'Upload a File' )
        .html_writer::end_tag('button')
        .html_writer::end_tag( 'a' );
// echo $xtras;
$renderable = new \block_pu\output\files_view();
echo $OUTPUT->render($renderable);
// --------------------------


// foreach ($uploadedfiles as $thisfile) {

//     echo html_writer::start_tag( 'a', array( 'href' => "./uploader.php?id={$id}&action=ADD" ) )
//         .html_writer::start_tag( 'button', array( 'type' => 'button', 'class' => 'btn btn-primary', 'style' =>'margin:3%; width:20%' ) )
//         .format_string( 'Manage Files' )
//         .html_writer::end_tag('button')
//         .html_writer::end_tag( 'a' );
// }

// if ($imageurl) {
    
    // Display an image, if you have imported another type of file,
    // treat it the way you want.
    // echo html_writer::empty_tag('img', array('width' => '100%', 'height' => '100%', 'src' => $imageurl));    

// } else {
    // echo $OUTPUT->notification(format_string('Please upload an image first'));
// }

echo $OUTPUT->footer();
