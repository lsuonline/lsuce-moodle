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
 * Action handler for local_checksummer (Scheduling and Deleting).
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();
$context = context_system::instance();

// Strictly limit access to site administrators.
require_admin();

// Determine which action the admin wishes to perform (e.g., 'schedule' or 'delete').
$action = required_param('action', PARAM_ALPHA);

// URL to return to after processing the action.
$returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_checksummer']);

if ($action === 'schedule') {
    // Ensure the request includes a valid session key to prevent CSRF.
    require_sesskey();

    // Schedule the checksummer adhoc task to run during the next available cron cycle.
    $task = new \local_checksummer\task\run_checksummer_task();
    $task->set_custom_data(['is_continuation' => false]);
    \core\task\manager::queue_adhoc_task($task);

    // Send the admin back to the settings page with a success message.
    redirect($returnurl, get_string('task_scheduled', 'local_checksummer'), null, \core\output\notification::NOTIFY_SUCCESS);
} elseif ($action === 'delete') {
    // Ensure the request includes a valid session key to prevent CSRF.
    require_sesskey();

    $filearea = required_param('filearea', PARAM_ALPHA);
    $filename = required_param('filename', PARAM_FILE);

    // Validate that the requested file area is one we manage.
    if ($filearea !== 'generated' && $filearea !== 'source') {
        throw new \moodle_exception('invalidfilearea');
    }

    // Locate the file within Moodle's file storage system and attempt to delete it.
    $fs = get_file_storage();
    if ($file = $fs->get_file($context->id, 'local_checksummer', $filearea, 0, '/', $filename)) {
        $file->delete();
        redirect($returnurl, get_string('file_deleted', 'local_checksummer'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // If the file could not be found, notify the user.
        redirect($returnurl, get_string('file_not_found', 'local_checksummer'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Fallback redirect if an unknown action was provided.
redirect($returnurl);
