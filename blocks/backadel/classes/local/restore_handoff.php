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

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

use context;
use context_user;
use moodle_url;

/**
 * Hands off a catalogue backup file to Moodle's native restore wizard.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_handoff {

    /**
     * Stage a catalogue backup as a stored_file in the current user's backup area
     * and redirect to Moodle's native restore wizard confirm stage.
     *
     * Never returns: always calls redirect() (success or error path).
     *
     * @param int     $catalogueid  block_backadel_catalogue.id
     * @param context $errorcontext Context to send the user back to on failure
     */
    public static function stage_and_redirect(int $catalogueid, context $errorcontext): void {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/blocks/backadel/lib.php');
        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

        $abspath = backadel_resolve_path($catalogueid);

        $listingurl = new moodle_url('/blocks/backadel/restore.php');

        if ($abspath === '' || !is_readable($abspath)) {
            redirect(
                $listingurl,
                get_string('coursebackups_restore_filemissing', 'block_backadel'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        $syscontext = \context_system::instance();
        require_capability('moodle/restore:restorecourse', $syscontext);

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $filename = basename($abspath);

        $filename = self::unique_user_backup_filename($fs, $usercontext->id, $filename);

        $filerecord = (object) [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'backup',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
            'userid'    => $USER->id,
        ];

        try {
            $storedfile = $fs->create_file_from_pathname($filerecord, $abspath);
        } catch (\Throwable $e) {
            redirect(
                $listingurl,
                get_string('coursebackups_restore_stagefailed', 'block_backadel'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        $restoreurl = new moodle_url('/backup/restore.php', [
            'contextid'    => $syscontext->id,
            'pathnamehash' => $storedfile->get_pathnamehash(),
            'contenthash'  => $storedfile->get_contenthash(),
        ]);
        redirect($restoreurl);
    }

    /**
     * Appends " (n)" before the extension if filename already exists in user backup area.
     *
     * @param \file_storage $fs
     * @param int           $usercontextid
     * @param string        $filename
     * @return string
     */
    private static function unique_user_backup_filename(
        \file_storage $fs, int $usercontextid, string $filename
    ): string {
        if (!$fs->file_exists($usercontextid, 'user', 'backup', 0, '/', $filename)) {
            return $filename;
        }
        $info = pathinfo($filename);
        $base = $info['filename'] ?? 'backup';
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        for ($i = 1; $i < 1000; $i++) {
            $candidate = "{$base} ({$i}){$ext}";
            if (!$fs->file_exists($usercontextid, 'user', 'backup', 0, '/', $candidate)) {
                return $candidate;
            }
        }
        return "{$base}-" . time() . $ext;
    }
}
