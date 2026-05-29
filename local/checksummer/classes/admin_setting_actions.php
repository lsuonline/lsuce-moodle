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
 * Custom admin setting class for local_checksummer actions and file management.
 *
 * @package    local_checksummer
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_checksummer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Custom admin setting to display action buttons and file lists.
 */
class admin_setting_actions extends \admin_setting {

    /**
     * Get the HTML for this custom setting.
     *
     * This method renders the action buttons for scheduling tasks and uploading files,
     * as well as the file lists for generated manifests and uploaded source manifests.
     *
     * @param string $data The value of the setting (unused for this custom setting).
     * @param string $query Search query for highlighting (unused).
     * @return string The generated HTML.
     */
    public function output_html($data, $query = '') {
        global $CFG, $OUTPUT;

        $html = '';

        // Action Buttons: Schedule Task & Upload Manifest
        $html .= '<div style="margin-bottom: 20px;">';
        $scheduleurl = new \moodle_url('/local/checksummer/actions.php', [
            'action' => 'schedule',
            'sesskey' => sesskey()
        ]);
        $html .= '<a href="' . $scheduleurl . '" class="btn btn-primary">' . get_string('schedule_task', 'local_checksummer') . '</a> ';
        $html .= '<a href="' . $CFG->wwwroot . '/local/checksummer/upload.php" class="btn btn-secondary">' . get_string('upload_manifest', 'local_checksummer') . '</a>';
        $html .= '</div>';

        // Lists of Files
        $fs = get_file_storage();
        $context = \context_system::instance();

        $html .= $this->render_file_list($fs, $context, 'generated', get_string('generated_files', 'local_checksummer'), get_string('generated_files_desc', 'local_checksummer'));
        $html .= $this->render_file_list($fs, $context, 'source', get_string('uploaded_files', 'local_checksummer'), get_string('uploaded_files_desc', 'local_checksummer'));

        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
    }

    /**
     * Renders a list of files for a specific file area.
     *
     * Retrieves all files stored under a specific file area and builds an HTML list
     * with options to download or delete each file.
     *
     * @param \file_storage $fs The Moodle file storage instance.
     * @param \context $context The system context instance.
     * @param string $filearea The file area to list (e.g., 'generated', 'source').
     * @param string $title The display title for the list.
     * @param string $desc The display description for the list.
     * @return string The generated HTML for the list.
     */
    private function render_file_list(\file_storage $fs, \context $context, string $filearea, string $title, string $desc): string {
        global $CFG, $OUTPUT;

        $files = $fs->get_area_files($context->id, 'local_checksummer', $filearea, 0, 'filename', false);

        $html = '<h4>' . $title . '</h4>';
        $html .= '<p>' . $desc . '</p>';

        if (empty($files)) {
            $html .= '<p>' . get_string('no_files', 'local_checksummer') . '</p>';
        } else {
            $html .= '<ul class="list-unstyled">';
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $filename = $file->get_filename();
                $url = \moodle_url::make_pluginfile_url($context->id, 'local_checksummer', $filearea, 0, '/', $filename);

                $html .= '<li style="margin-bottom: 10px;">';
                $html .= '<strong>' . s($filename) . '</strong> (' . display_size($file->get_filesize()) . ') - ';
                $html .= '<a href="' . $url . '">' . get_string('download', 'local_checksummer') . '</a> | ';

                $deleteurl = new \moodle_url('/local/checksummer/actions.php', [
                    'action' => 'delete',
                    'filearea' => $filearea,
                    'filename' => $filename,
                    'sesskey' => sesskey()
                ]);
                $html .= '<a href="' . $deleteurl . '" class="text-danger">' . get_string('delete', 'local_checksummer') . '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Retrieve the current setting value.
     *
     * Returns true as this custom setting doesn't store an actual value in the database.
     *
     * @return bool
     */
    public function get_setting() {
        return true;
    }

    /**
     * Save the setting value.
     *
     * Returns an empty string as there's no data to write to the config tables.
     *
     * @param mixed $data The user input data.
     * @return string Always empty.
     */
    public function write_setting($data) {
        return '';
    }
}
