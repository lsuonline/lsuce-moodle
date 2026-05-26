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
     * Form definition: rich confirm content plus hidden submission fields.
     *
     * @return void
     */
    #[\Override]
    protected function definition(): void {
        global $CFG, $DB;
        $mform = $this->_form;

        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $filename = clean_param($this->optional_param('filename', '', PARAM_FILE), PARAM_FILE);
        $catalogueid = (int) $this->optional_param('catalogue_id', 0, PARAM_INT);
        $restoreto = (int) $this->optional_param('restore_to', 0, PARAM_INT);
        $confirmbodykey = ($restoreto === 1)
            ? 'restore_confirm_body_import'
            : 'restore_confirm_body_overwrite';
        $course = $courseid > 0 ? $DB->get_record('course', ['id' => $courseid]) : null;

        $html = \html_writer::start_div('mb-3');
        if ($course) {
            $html .= \html_writer::tag('p',
                \html_writer::tag('strong', get_string('restore_confirm_course_label', 'block_simple_restore') . ': ') .
                s($course->fullname) . ' (' . s($course->shortname) . ')'
            );
        }
        if ($filename !== '') {
            $html .= \html_writer::tag('p',
                \html_writer::tag('strong', get_string('restore_confirm_file_label', 'block_simple_restore') . ': ') .
                s($filename)
            );
        }
        $html .= \html_writer::div(
            get_string($confirmbodykey, 'block_simple_restore'),
            'alert alert-info mb-0'
        );
        $html .= \html_writer::end_div();
        $mform->addElement('html', $html);

        // File size display + sync/async explanation (Bug-079).
        $useasync = (bool) get_config('simple_restore', 'async_toggle');

        $sizebytes = 0;
        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');
        $sourcepath = \block_simple_restore\local\catalogue_staging_service::resolve_source_path($catalogueid, $filename);
        if ($sourcepath !== '') {
            $sizebytes = (int) (@filesize($sourcepath) ?: 0);
        }

        $infohtml = \html_writer::start_div('mt-3');
        if ($sizebytes > 0) {
            $infohtml .= \html_writer::tag('p',
                \html_writer::tag('strong', get_string('restore_filesize_label', 'block_simple_restore') . ': ') .
                display_size($sizebytes)
            );
        }

        $modekey = $useasync ? 'restore_mode_async' : 'restore_mode_sync';
        $infohtml .= \html_writer::div(
            get_string($modekey, 'block_simple_restore'),
            'alert ' . ($useasync ? 'alert-warning' : 'alert-info') . ' mb-0'
        );
        $infohtml .= \html_writer::end_div();
        $mform->addElement('html', $infohtml);

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
     * Threshold (bytes) above which sync mode releases the session lock and
     * raises the PHP time limit before executing. Bug-042A.
     */
    private const LARGE_BACKUP_BYTES = 100 * 1024 * 1024;

    /**
     * Prepare the backup file and run the same restore pipeline as {@see restore.php}.
     *
     * Bug-079: when Async Restore is enabled and {@code restore_to} is overwrite or merge,
     * the catalogue/legacy staging copy and restore run inside the
     * {@see \block_simple_restore\task\stage_catalogue_restore_task adhoc task} so the modal
     * returns immediately.
     *
     * Bug-042A: sync mode executes inline without ob_* wrapping; backups >= 100 MB raise PHP
     * time limits and release the session write-lock before running the restore_ui pipeline.
     * When Async Restore is on and archive mode ({@code restore_to} = 2) is requested, staging
     * still occurs here and redirect to {@see restore.php} behaves as before.
     *
     * @return array{success: bool, message: string, redirecturl: string}
     */
    #[\Override]
    public function process_dynamic_submission(): array {
        global $CFG, $USER;

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

        $course      = get_course($courseid);
        $useasync    = (bool) get_config('simple_restore', 'async_toggle');

        if ($useasync && $restoreto !== 2) {
            // Bug-079: catalogue / legacy restores — validate only, queue staging + restore.
            if ($catalogueid === 0) {
                $backadelpath = (string) get_config('block_backadel', 'path');
                if ($backadelpath === '') {
                    throw new \moodle_exception('error', 'webservice', '', get_string(
                        'empty_backups',
                        'block_simple_restore'
                    ));
                }
            }

            $safefile = basename(str_replace('\\', '/', clean_param((string) ($data->filename ?? ''), PARAM_FILE)));
            $sourcepath = \block_simple_restore\local\catalogue_staging_service::resolve_source_path(
                $catalogueid,
                $safefile
            );
            \block_simple_restore\local\catalogue_staging_service::validate_source($sourcepath);

            $filenameforqueue = ($catalogueid > 0) ? '' : $safefile;

            $task = new \block_simple_restore\task\stage_catalogue_restore_task();
            $task->set_userid((int) $USER->id);
            $task->set_custom_data([
                'courseid'    => $courseid,
                'catalogueid' => $catalogueid,
                'filename'    => $filenameforqueue,
                'restore_to'  => $restoreto,
                'userid'      => (int) $USER->id,
            ]);
            \core\task\manager::queue_adhoc_task($task);

            return [
                'success'     => true,
                'message'     => get_string('restore_queued_message', 'block_simple_restore'),
                'redirecturl' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            ];
        }

        if ($catalogueid > 0) {
            // Catalogue path: resolve absolute path via backadel_resolve_path().
            $fullpath = \backadel_resolve_path($catalogueid);
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
                throw new \moodle_exception(
                    'error',
                    'webservice',
                    '',
                    get_string('empty_backups', 'block_simple_restore')
                );
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

        $contextid = ($restoreto === 2)
            ? \context_system::instance()->id
            : \context_course::instance($courseid)->id;

        if ($useasync) {
            // Async mode (archive / edge cases): render progress on restore.php server-side.
            $progressurl = new moodle_url('/blocks/simple_restore/restore.php', [
                'contextid'  => $contextid,
                'filename'   => $tempfilename,
                'restore_to' => $restoreto,
                'confirm'    => 1,
            ]);
            return [
                'success'     => true,
                'message'     => get_string('restore_success', 'block_simple_restore'),
                'redirecturl' => $progressurl->out(false),
            ];
        }

        // Sync mode. Large files (>= 100 MB) need extra runway: raise the PHP
        // time limit and release the session lock so the user's other tabs
        // remain responsive while the restore runs.
        $size = (int) (@filesize($fullpath) ?: 0);
        if ($size >= self::LARGE_BACKUP_BYTES) {
            \core_php_time_limit::raise(60 * 30); // 30 minutes.
            // Release session lock without disabling sessions altogether.
            // session_write_close() is the canonical PHP way; Moodle's
            // \core\session\manager::write_close() wraps it.
            if (class_exists('\\core\\session\\manager')
                && method_exists('\\core\\session\\manager', 'write_close')) {
                \core\session\manager::write_close();
            } else if (function_exists('session_write_close')) {
                @session_write_close();
            }
        }

        // The restore_ui pipeline uses global optional_param() which reads from $_POST,
        // not from the form's $ajaxformdata. Inject the temp filename so the CONFIRM
        // stage can locate the extracted backup file without falling back to pathnamehash.
        $_POST['filename'] = $tempfilename;
        $restore = new \simple_restore($course, $tempfilename, $restoreto);
        try {
            // No ob_*() wrap: in sync mode the restore engine writes nothing
            // useful to stdout (no async template), so we don't need to swallow
            // output, and swallowing it hid real errors / fatal warnings.
            $restore->execute();
        } catch (\Throwable $e) {
            throw new \moodle_exception('no_restore', 'block_simple_restore', '', $e->getMessage());
        } finally {
            unset($_POST['filename']);
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
