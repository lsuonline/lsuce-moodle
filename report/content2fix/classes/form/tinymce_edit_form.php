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
// along with Moodle.  If not, see <http://moodle.org/licenses/>.

namespace report_content2fix\form;

use core_form\dynamic_form;
use context;
use context_course;
use context_module;
use context_system;
use moodle_url;
use report_content2fix\local\format_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Dynamic form for editing content with TinyMCE.
 *
 * Displays a single editor field pre-filled with the content to fix.
 * On submit, persists the content to the mod_* or course entity and triggers events.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tinymce_edit_form extends dynamic_form {

    /**
     * Form definition.
     */
    protected function definition(): void {
        global $DB;

        $mform = $this->_form;
        $entryid = (int) $this->optional_param('entryid', 0, PARAM_INT);
        $entry = $entryid ? $DB->get_record('report_content2fix', ['id' => $entryid]) : null;
        if (!$entry) {
            return;
        }

        $context = $this->get_editor_context($entry);
        $editoroptions = [
            'subdirs' => 1,
            'maxbytes' => 0,
            'maxfiles' => -1,
            'changeformat' => 0,
            'context' => $context,
            'noclean' => 1,
            'trusttext' => 0,
        ];

        $mform->addElement('editor', 'content_editor', get_string('editcontent', 'report_content2fix'), null, $editoroptions);
        $mform->setType('content_editor', PARAM_RAW);

        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);
    }

    /**
     * Get context for the editor based on entry (course or module).
     *
     * @param object $entry report_content2fix entry
     * @return context
     */
    protected function get_editor_context(object $entry): context {
        if (!empty($entry->cmid)) {
            return context_module::instance($entry->cmid);
        }
        if (!empty($entry->courseid)) {
            return context_course::instance($entry->courseid);
        }
        return context_system::instance();
    }

    /**
     * Returns context where this form is used.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Checks if current user has access to this form.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('report/content2fix:fix', context_system::instance());
    }

    /**
     * Process the form submission.
     *
     * Saves content to the mod_* or course entity, clears cache, triggers events.
     *
     * @return array{success: bool, message?: string}
     */
    public function process_dynamic_submission(): array {
        $entryid = (int) $this->optional_param('entryid', 0, PARAM_INT);
        $data = $this->get_data();
        if (!$data || !$entryid) {
            return ['success' => false, 'message' => get_string('error', 'core')];
        }

        $result = format_helper::persist_tinymce_content($entryid, $data->content_editor);
        if ($result['success']) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => $result['message'] ?? get_string('error', 'core')];
    }

    /**
     * Load in existing data as form defaults.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $entryid = (int) $this->optional_param('entryid', 0, PARAM_INT);
        $entry = $DB->get_record('report_content2fix', ['id' => $entryid]);
        if (!$entry) {
            return;
        }

        $content = format_helper::get_content_for_entry($entry);
        $this->set_data([
            'entryid' => $entryid,
            'content_editor' => [
                'text' => $content,
                'format' => FORMAT_HTML,
                'itemid' => file_get_unused_draft_itemid(),
            ],
        ]);
    }

    /**
     * Returns URL for dynamic form page.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/report/content2fix/index.php');
    }
}
