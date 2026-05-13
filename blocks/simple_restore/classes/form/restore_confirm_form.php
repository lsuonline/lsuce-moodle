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

declare(strict_types=1);

namespace block_simple_restore\form;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_system;
use core_form\dynamic_form;
use moodle_url;

/**
 * Confirm and execute a course restore via a modal.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_confirm_form extends dynamic_form {

    /**
     * Form definition: hidden fields only.
     *
     * @return void
     */
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'filename');
        $mform->setType('filename', PARAM_FILE);
        $mform->addElement('hidden', 'restore_to');
        $mform->setType('restore_to', PARAM_INT);
        $mform->addElement('hidden', 'catalogue_id');
        $mform->setType('catalogue_id', PARAM_INT);
    }

    /**
     * Context for external validation: course, or system when restoring in site/archive mode.
     *
     * @return \context
     */
    #[\Override]
    protected function get_context_for_dynamic_submission(): \context {
        $courseid = (int) $this->optional_param('courseid', 0, PARAM_INT);
        $restoreto = (int) $this->optional_param('restore_to', 0, PARAM_INT);
        if ($restoreto === 2) {
            return context_system::instance();
        }
        return context_course::instance($courseid);
    }

    /**
     * Require core restore capability in the appropriate context.
     *
     * @return void
     */
    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        $courseid = (int) $this->optional_param('courseid', 0, PARAM_INT);
        $restoreto = (int) $this->optional_param('restore_to', 0, PARAM_INT);
        if ($restoreto === 2) {
            require_capability('moodle/restore:restorecourse', context_system::instance());
            return;
        }
        require_capability('moodle/restore:restorecourse', context_course::instance($courseid));
    }

    /**
     * Prepare the backup file and run the same restore pipeline as {@see restore.php}.
     *
     * @return array{success: bool, message: string, redirecturl: string}
     */
    #[\Override]
    public function process_dynamic_submission(): array {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

        $data = $this->get_data();
        if ($data === null) {
            throw new \moodle_exception('invaliddata', 'error');
        }

        $courseid    = (int) $data->courseid;
        $restoreto   = (int) $data->restore_to;
        $catalogueid = (int) ($data->catalogue_id ?? 0);

        if ($courseid < 1) {
            throw new \moodle_exception('invalidcourseid', 'error');
        }

        $course = get_course($courseid);

        if ($catalogueid > 0) {
            // Catalogue path: resolve absolute path via backadel_resolve_path().
            $fullpath = backadel_resolve_path($catalogueid);
            if ($fullpath === '' || !is_readable($fullpath)) {
                throw new \moodle_exception('filenotfound', 'error');
            }
            try {
                $tempfilename = \simple_restore_utils::prep_restore($catalogueid, 'catalogue', $courseid);
            } catch (\Throwable $e) {
                throw new \moodle_exception('no_restore', 'block_simple_restore', '', $e->getMessage());
            }
        } else {
            // Legacy path: filename in flat backadel directory.
            $filename = basename(str_replace('\\', '/', clean_param((string) $data->filename, PARAM_FILE)));
            $backadelpath = (string) get_config('block_backadel', 'path');
            if ($backadelpath === '') {
                throw new \moodle_exception('error', 'webservice', '', get_string('empty_backups', 'block_simple_restore'));
            }
            $fullpath = rtrim($CFG->dataroot, '/') . '/' . trim($backadelpath, '/') . '/' . $filename;
            if (!is_readable($fullpath)) {
                throw new \moodle_exception('filenotfound', 'error');
            }
            try {
                $tempfilename = \simple_restore_utils::prep_restore($filename, 'backadel', $courseid);
            } catch (\Throwable $e) {
                throw new \moodle_exception('no_restore', 'block_simple_restore', '', $e->getMessage());
            }
        }

        $restore = new \simple_restore($course, $tempfilename, $restoreto);
        try {
            ob_start();
            try {
                $restore->execute();
            } finally {
                ob_end_clean();
            }
        } catch (\Throwable $e) {
            throw new \moodle_exception('no_restore', 'block_simple_restore', '', $e->getMessage());
        }

        return [
            'success' => true,
            'message' => get_string('restore_success', 'block_simple_restore'),
            'redirecturl' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
        ];
    }

    /**
     * Defaults from modal / request arguments.
     *
     * @return void
     */
    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $this->set_data((object) [
            'courseid'    => $this->optional_param('courseid', 0, PARAM_INT),
            'filename'    => $this->optional_param('filename', '', PARAM_FILE),
            'restore_to'  => $this->optional_param('restore_to', 0, PARAM_INT),
            'catalogue_id' => $this->optional_param('catalogue_id', 0, PARAM_INT),
        ]);
    }

    /**
     * URL of the list page where the modal is launched.
     *
     * @return moodle_url
     */
    #[\Override]
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $courseid = (int) $this->optional_param('courseid', 0, PARAM_INT);
        return new moodle_url('/blocks/simple_restore/list.php', ['id' => $courseid]);
    }
}
