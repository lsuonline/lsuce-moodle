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
 * Catalogue / legacy Backadel path resolution and staging for Simple Restore (Bug-079).
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_simple_restore\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Path resolution, validation, and staging for catalogue and legacy semester backups.
 */
final class catalogue_staging_service {

    /**
     * Resolve the absolute source path for a restore row.
     *
     * @param int $catalogueid catalogue row PK (0 = legacy filesystem row)
     * @param string $filename basename for legacy rows (ignored when catalogue_id > 0)
     * @return string absolute path or ''
     */
    public static function resolve_source_path(int $catalogueid, string $filename = ''): string {
        global $CFG;

        if ($catalogueid > 0) {
            if (!function_exists('backadel_resolve_path')) {
                require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');
            }
            $resolved = backadel_resolve_path($catalogueid);
            return is_string($resolved) ? $resolved : '';
        }

        $basename = basename(str_replace('\\', '/', clean_param($filename, PARAM_FILE)));
        if ($basename === '') {
            return '';
        }

        $backadelpath = (string) get_config('block_backadel', 'path');
        if ($backadelpath === '') {
            return '';
        }

        return rtrim($CFG->dataroot, '/') . '/' . trim($backadelpath, '/') . '/' . $basename;
    }

    /**
     * Validate source: file exists, is readable, and is non-zero bytes.
     *
     * @param string $sourcepath absolute path from resolve_source_path()
     * @return bool true on success
     * @throws \moodle_exception on failure (error_backup_missing, error_backup_empty)
     */
    public static function validate_source(string $sourcepath): bool {
        $diag = basename($sourcepath) !== '' ? basename($sourcepath) : ($sourcepath !== '' ? $sourcepath : get_string('pluginname', 'block_simple_restore'));
        if ($sourcepath === '' || !file_exists($sourcepath)) {
            throw new \moodle_exception('error_backup_missing', 'block_simple_restore', '', $diag);
        }
        if (!is_readable($sourcepath)) {
            throw new \moodle_exception('error_backup_missing', 'block_simple_restore', '', $diag);
        }
        if (@filesize($sourcepath) === 0) {
            throw new \moodle_exception('error_backup_empty', 'block_simple_restore', '', $diag);
        }

        return true;
    }

    /**
     * Stage (copy) the source file into Moodle's backup temp directory.
     *
     * @param string $sourcepath absolute source path
     * @param int $courseid target course id
     * @param int $userid restoring user id
     * @return string tempdir basename for restore_controller staging
     * @throws \moodle_exception on copy failure or zero-byte result
     */
    public static function stage_file(string $sourcepath, int $courseid, int $userid): string {
        \simple_restore_utils::includes();

        $tempbasename = \restore_controller::get_tempdir_name($courseid, $userid);
        $backuptempdir = make_backup_temp_directory('');
        $destpath = $backuptempdir . '/' . $tempbasename;

        if (!@copy($sourcepath, $destpath)) {
            throw new \moodle_exception('no_restore', 'block_simple_restore', '', 'Unable to stage backup file.');
        }
        if (@filesize($destpath) === 0) {
            @unlink($destpath);
            throw new \moodle_exception('error_backup_empty', 'block_simple_restore', '',
                basename($sourcepath) !== '' ? basename($sourcepath) : $sourcepath);
        }

        return $tempbasename;
    }
}
