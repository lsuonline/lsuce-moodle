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
 * Background staging + headless restore for catalogue / legacy Simple Restore (Bug-079).
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_simple_restore\task;

use backup;
use backup_setting;
use block_simple_restore\local\catalogue_staging_service;
use core\task\adhoc_task;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task: copy catalogue backup, extract, restore into target course, notify user.
 */
class stage_catalogue_restore_task extends adhoc_task {

    #[\Override]
    public function execute(): void {
        global $CFG;

        \core_php_time_limit::raise(0);
        \core\session\manager::write_close();

        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $raw = $this->get_custom_data();
        $data = is_array($raw) ? (object) $raw : $raw;
        if (!isset($data->userid)) {
            throw new \moodle_exception('invalidarguments', 'error');
        }

        $userid = (int) $data->userid;

        $courseid    = isset($data->courseid) ? (int) $data->courseid : 0;
        $catalogueid = isset($data->catalogueid) ? (int) $data->catalogueid : 0;
        $filename    = (string) ($data->filename ?? '');
        $restoreto   = isset($data->restore_to) ? (int) $data->restore_to : 0;

        if ($courseid < 1) {
            throw new \moodle_exception('invalidcourseid', 'error');
        }

        if ($restoreto === 2) {
            throw new \moodle_exception('no_restore', 'block_simple_restore', '',
                'Archive mode restores are handled separately and cannot run in this queued task.');
        }

        try {
            $sourcepath = catalogue_staging_service::resolve_source_path($catalogueid, $filename);
            catalogue_staging_service::validate_source($sourcepath);

            $tempbasename = catalogue_staging_service::stage_file($sourcepath, $courseid, $userid);

            $backuptempdir = make_backup_temp_directory('');
            $stagedfile    = $backuptempdir . '/' . $tempbasename;
            $extractname   = restore_controller::get_tempdir_name($courseid, $userid) . '_extracted';
            $extractpath   = $backuptempdir . '/' . $extractname;

            $fb = get_file_packer('application/vnd.moodle.backup');
            if (!$fb->extract_to_pathname($stagedfile, $extractpath)) {
                throw new \moodle_exception('no_restore', 'block_simple_restore', '', 'Failed to extract backup archive.');
            }

            $target = ($restoreto === 1)
                ? backup::TARGET_CURRENT_ADDING
                : backup::TARGET_CURRENT_DELETING;

            $course = get_course($courseid);

            $rc = new restore_controller(
                $extractname,
                $courseid,
                backup::INTERACTIVE_NO,
                backup::MODE_GENERAL,
                $userid,
                $target
            );

            $configsettings = (array) get_config('simple_restore');
            foreach ($configsettings as $key => $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                if (!$rc->get_plan()->setting_exists($key)) {
                    continue;
                }
                $setting = $rc->get_plan()->get_setting($key);
                if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                    $setting->set_value($value);
                }
            }

            $rc->execute_precheck(true);
            $precheckresults = $rc->get_precheck_results();
            if (!empty($precheckresults['errors'] ?? [])) {
                throw new \moodle_exception('no_restore', 'block_simple_restore', '',
                    implode('; ', $precheckresults['errors']));
            }

            $rc->execute_plan();
            $rc->destroy();

            if ($restoreto === 0) {
                blocks_delete_all_for_context(\context_course::instance($courseid)->id);
                blocks_add_default_course_blocks($course);
            }

            @unlink($stagedfile);
            @fulldelete($extractpath);

            self::send_notification($userid, $courseid, true, '');

        } catch (\Throwable $e) {
            mtrace('block_simple_restore stage_catalogue_restore_task failed: ' . $e->getMessage());
            self::send_notification($userid, $courseid, false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a Moodle notification for restore success or failure.
     *
     * @param int $userid recipient
     * @param int $courseid target course
     * @param bool $success whether restore completed
     * @param string $errormessage failure detail (plain text)
     */
    private static function send_notification(int $userid, int $courseid, bool $success, string $errormessage): void {
        $message = new \core\message\message();
        $message->courseid       = $courseid;
        $message->component      = 'block_simple_restore';
        $message->name           = 'restore_notification';
        $message->userfrom       = \core_user::get_noreply_user();
        $message->userto         = \core_user::get_user($userid);
        $message->notification   = 1;
        $message->fullmessageformat = FORMAT_HTML;

        $course    = get_course($courseid);
        $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);

        if ($success) {
            $message->subject         = get_string('notify_restore_complete_subject', 'block_simple_restore', $course->fullname);
            $message->fullmessage     = get_string('notify_restore_complete_body', 'block_simple_restore', $course->fullname);
            $message->smallmessage    = get_string('notify_restore_complete_subject', 'block_simple_restore', $course->fullname);
            $message->fullmessagehtml = get_string('notify_restore_complete_body_html', 'block_simple_restore',
                (object) ['coursename' => $course->fullname, 'courseurl' => $courseurl->out(false)]);
        } else {
            $a = (object) ['coursename' => $course->fullname, 'error' => $errormessage];
            $safeerror = \htmlspecialchars($errormessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $ahtml     = (object) ['coursename' => $course->fullname, 'error' => $safeerror];
            $message->subject         = get_string('notify_restore_failed_subject', 'block_simple_restore', $course->fullname);
            $message->fullmessage     = get_string('notify_restore_failed_body', 'block_simple_restore', $a);
            $message->smallmessage    = get_string('notify_restore_failed_subject', 'block_simple_restore', $course->fullname);
            $message->fullmessagehtml = get_string('notify_restore_failed_body', 'block_simple_restore', $ahtml);
        }

        $message->contexturl     = $courseurl->out(false);
        $message->contexturlname = $course->fullname;
        message_send($message);
    }

    #[\Override]
    public function retry_until_success(): bool {
        return false;
    }
}
