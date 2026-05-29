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
 * Form for uploading source manifests.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_checksummer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Class upload_manifest_form
 *
 * Provides the user interface for administrators to upload a source manifest CSV file.
 * This manifest is later used in the 'compare' mode to verify the integrity of a directory.
 */
class upload_manifest_form extends \moodleform {

    /**
     * Defines the structure of the form.
     *
     * Adds a filepicker element restricted to .csv files and requires it to be filled
     * before submission. Finally, adds standard save/cancel action buttons.
     */
    public function definition() {
        $mform = $this->_form;

        // Add a filepicker to allow the user to select their local CSV file.
        $mform->addElement('filepicker', 'manifestfile', get_string('file', 'local_checksummer'), null, ['accepted_types' => ['.csv']]);

        // Ensure the form cannot be submitted without a file being provided.
        $mform->addRule('manifestfile', null, 'required', null, 'client');

        // Add standard submit and cancel buttons at the bottom of the form.
        $this->add_action_buttons(true, get_string('save', 'local_checksummer'));
    }
}
