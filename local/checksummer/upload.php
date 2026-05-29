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
 * Page for uploading a source manifest for local_checksummer.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/checksummer/classes/form/upload_manifest_form.php');

require_login();
$context = context_system::instance();

// Strictly limit access to site administrators.
require_admin();

// Configure page setup variables.
$url = new moodle_url('/local/checksummer/upload.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('upload_manifest', 'local_checksummer'));
$PAGE->set_heading(get_string('upload_manifest', 'local_checksummer'));

$returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_checksummer']);

// Instantiate the form to handle manifest file uploads.
$mform = new \local_checksummer\form\upload_manifest_form();

if ($mform->is_cancelled()) {
    // If the admin cancelled the upload, return them to the settings page.
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    // Process the submitted form data and save the uploaded file to the 'source' filearea.
    $draftitemid = $data->manifestfile;
    
    $fs = get_file_storage();
    $usercontext = context_user::instance($USER->id);
    
    // Retrieve the file from the user's draft area.
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);

    if (!empty($files)) {
        $file = reset($files);
        $filename = $file->get_filename();
        
        // Before saving, ensure any existing file with the same name in the 'source' area is deleted to avoid conflicts.
        if ($existing = $fs->get_file($context->id, 'local_checksummer', 'source', 0, '/', $filename)) {
            $existing->delete();
        }

        // Define the permanent storage record for the file.
        $record = new stdClass();
        $record->contextid = $context->id;
        $record->component = 'local_checksummer';
        $record->filearea  = 'source';
        $record->itemid    = 0;
        $record->filepath  = '/';
        $record->filename  = $filename;

        // Persist the file from the draft area into the plugin's actual storage area.
        $fs->create_file_from_storedfile($record, $file);
        
        redirect($returnurl, get_string('upload_success', 'local_checksummer'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($returnurl, get_string('error_upload', 'local_checksummer'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Render the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('upload_form', 'local_checksummer'));
$mform->display();
echo $OUTPUT->footer();
