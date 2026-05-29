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
 * Core library functions for local_checksummer.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serves files from the local_checksummer file areas.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function local_checksummer_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    // Only site admins should have the privilege to access these generated and uploaded files.
    if (!is_siteadmin()) {
        return false;
    }

    // Restrict access to specific managed fileareas to prevent unintended directory traversal.
    if ($filearea !== 'generated' && $filearea !== 'source') {
        return false;
    }

    // Extract the item ID and file path components from the URL arguments.
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Locate the exact file in the database mapping to the given path and name.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_checksummer', $filearea, $itemid, $filepath, $filename);

    // If the file doesn't exist or we hit a directory instead of a file, deny access.
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Finally, stream the actual file contents back to the authorized user.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
