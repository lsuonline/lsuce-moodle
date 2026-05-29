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
 * Download a catalogue backup file from disk (raw bytes).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/backadel/lib.php');

require_login();

$fileid = optional_param('fileid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($fileid <= 0) {
    throw new moodle_exception('invalidparameter', 'error');
}

require_sesskey();

$record = $DB->get_record('block_backadel_catalogue', ['id' => $fileid], '*', IGNORE_MISSING);
if (!$record) {
    throw new moodle_exception('filenotfound');
}

// Authorization: site managers via managebackups (system context), OR
// teachers with canrestore in the target course context when the catalogue
// shortname matches the requested course shortname.
$syscontext = context_system::instance();
$ismanager = has_capability('block/backadel:managebackups', $syscontext);

if (!$ismanager) {
    // Teacher path: courseid param required; catalogue row must belong to the
    // teacher (either via exact shortname match to the target course, OR — for
    // blueprint / shared-storage rows whose shortname is a slug like
    // `MaterialsCourse_AAAS_2000_tsimpson` — via the teacher's username
    // appearing in the catalogue row's shortname/filename or instructors JSON).
    if ($courseid <= 0) {
        require_capability('block/backadel:managebackups', $syscontext); // throws.
    }
    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
    if (!$coursecontext) {
        throw new moodle_exception('invalidcourseid', 'error');
    }
    require_capability('block/simple_restore:canrestore', $coursecontext);
    $course = $DB->get_record('course', ['id' => $courseid], 'shortname', MUST_EXIST);

    $allowed = (strtolower((string) $record->shortname) === strtolower((string) $course->shortname));

    if (!$allowed) {
        // Bug-047: blueprint / shared catalogue rows do NOT have a course
        // shortname (they are owned by an instructor, not a course). Accept the
        // download when the current user's username appears in the catalogue
        // row's slug or filename, or is listed in the instructors JSON.
        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');
        $local = simple_restore_utils::username_local_part((string) $USER->username);
        if ($local !== '') {
            $needleslug = '_' . strtolower($local);
            $needlefile = '_' . strtolower($local) . '_';
            $shortlower = strtolower((string) $record->shortname);
            $filelower  = strtolower((string) $record->filename);
            $instr      = (string) ($record->instructors ?? '');
            $instrhit   = strpos($instr, '"' . $local . '"') !== false;
            if (
                $instrhit
                || str_ends_with($shortlower, $needleslug)
                || strpos($filelower, $needlefile) !== false
            ) {
                $allowed = true;
            }
        }
    }

    if (!$allowed) {
        throw new moodle_exception('filenotfound');
    }
}

if (strtolower((string) $record->status) !== 'available') {
    throw new moodle_exception('filenotfound');
}

$path = (string) ($record->filepath_full ?? '');
if ($path === '') {
    throw new moodle_exception('filenotfound');
}

$roots = [];
$pushroot = static function (string $dir) use (&$roots): void {
    $dir = trim($dir);
    if ($dir === '') {
        return;
    }
    $real = realpath($dir);
    if ($real !== false) {
        $roots[$real] = true;
    }
};

$pushroot($CFG->dataroot);

$backupdir = get_config('block_backadel', 'backupdir');
if ($backupdir !== false && trim((string) $backupdir) !== '') {
    $pushroot(trim((string) $backupdir));
}

$relpath = get_config('block_backadel', 'path');
if ($relpath !== false && trim((string) $relpath) !== '') {
    $pushroot($CFG->dataroot . $relpath);
}

$migrationextra = get_config('block_backadel', 'migration_extra_paths');
if (is_string($migrationextra) && $migrationextra !== '') {
    $lines = preg_split('/\R/', $migrationextra);
    foreach ($lines as $line) {
        $pushroot(trim((string) $line));
    }
}

$legacydirs = get_config('block_backadel', 'legacy_dirs');
if (is_string($legacydirs) && $legacydirs !== '') {
    foreach (preg_split('/\R/', $legacydirs) as $line) {
        $pushroot(trim((string) $line));
    }
}

$allowedroots = array_keys($roots);
$realfile = realpath($path);
if ($realfile === false || !is_readable($realfile) || !is_file($realfile)) {
    throw new moodle_exception('filenotfound');
}

$allowed = false;
foreach ($allowedroots as $root) {
    $rootsep = $root . DIRECTORY_SEPARATOR;
    if ($realfile === $root || str_starts_with($realfile, $rootsep)) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    throw new moodle_exception('filenotfound');
}

$filesize = filesize($realfile);
if ($filesize === false) {
    throw new moodle_exception('filenotfound');
}

@ini_set('zlib.output_compression', 'Off');

$basename = basename($realfile);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $basename) . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: private');
header('Pragma: ');

readfile($realfile);
exit;
