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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
// require_once($CFG->libdir . '/blocklib.php');

class upload_form extends moodleform {

    function definition() {
        
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('hidden', 'idfile', true);
        $mform->setType('idfile', PARAM_TEXT);
        
        // FILE MANAGER        
        $mform->addElement('filemanager', 'pu_file', format_string('File Manager'), 
        // $mform->addElement('filemanager', 'attachments', format_string('File Manager'), 
                null, $this->get_filemanager_options_array());
        
        // Buttons
        $this->add_action_buttons();
    }

    /**Set here the options available for your file manager
     * https://docs.moodle.org/dev/Using_the_File_API_in_Moodle_forms
     * @return options for file manager
     */
    function get_filemanager_options_array () {
        return array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1,
                'accepted_types' => array('*'));
    }

}

/*
    function definition() {
        $mform =& $this->_form;

        // $fileoptions = array('accepted_types' => array('.csv'));
        $fileoptions = array(
            'subdirs' => 0,
            // 'maxbytes' => $maxbytes,
            'areamaxbytes' => 10485760,
            'maxfiles' => 10,
            'accepted_types' => ['.csv'],
            // 'accepted_types' => ['document'],
            // 'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        );

        // $mform->addElement('filepicker', 'fileupload', 'File Upload', null, $fileoptions);

        $mform->addElement(
            'filemanager',
            'puuploader',
            get_string('codeuploader', 'block_pu'),
            null,
            $fileoptions  
        );

        // ---------------------------------------------------
        /*
        // Fetch the entry being edited, or create a placeholder.
        if (empty($id)) {
            $entry = (object) [
                'id' => null,
            ];
        // } else {
        //     $entry = $DB->get_records('block_pu', ['id' => $id]);
        }

        // Get an unused draft itemid which will be used for this form.
        $draftitemid = file_get_submitted_draft_itemid('attachments');

        // Copy the existing files which were previously uploaded
        // into the draft area used by this form.
        file_prepare_draft_area(
            // The $draftitemid is the target location.
            $draftitemid,

            // The combination of contextid / component / filearea / itemid
            // form the virtual bucket that files are currently stored in
            // and will be copied from.
            $context->id,
            'block_pu',
            'puuploader',
            $entry->id,
            [
                'subdirs' => 0,
                // 'maxbytes' => $maxbytes,
                'maxfiles' => 50,
            ]
        );

        // Set the itemid of draft area that the files have been moved to.
        $entry->attachments = $draftitemid;
        // $form->set_data($entry);
        *
        // ---------------------------------------------------

        $mform->setType('puuploader', PARAM_FILE);

        $mform->addRule('puuploader', null, 'required');

        // $encodings = core_text::get_encodings();

        // $mform->addElement('select', 'encoding', "Encoding", $encodings);

        $this->add_action_buttons(true, "Submit");
    }
}
*/