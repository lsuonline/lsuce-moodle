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
 * @package    block_simple_restore
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

// Semester backup shortcut: copy Backadel file into restore temp, then continue as usual.
$precourseid = optional_param('id', 0, PARAM_INT);
$prefile = optional_param('file', '', PARAM_RAW);
if ($precourseid > 0 && $prefile !== '') {
    $precourse = get_course($precourseid);
    require_login($precourse);
    $precontext = context_course::instance($precourseid);
    $prerestoreto = optional_param('restore_to', 0, PARAM_INT);
    $archiveprecheck = get_config('simple_restore', 'is_archive_server') && $prerestoreto == 2 && $precourseid == SITEID;
    if ($archiveprecheck) {
        require_capability('block/simple_restore:canrestorearchive', context_system::instance());
    } else {
        require_capability('block/simple_restore:canrestore', $precontext);
    }
    $prefile = basename(str_replace('\\', '/', $prefile));
    $tempfilename = simple_restore_utils::prep_restore($prefile, 'backadel', $precourseid);
    redirect(new moodle_url('/blocks/simple_restore/restore.php', [
        'contextid' => $precontext->id,
        'filename' => $tempfilename,
        'restore_to' => $prerestoreto,
    ]));
}

$contextid = required_param('contextid', PARAM_INT);
$filename = required_param('filename', PARAM_FILE);
$restoreto = optional_param('restore_to', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$loading = optional_param('loading', 0, PARAM_INT);

$archivemode = get_config('simple_restore', 'is_archive_server') == 1 && $restoreto == 2;
list($context, $course, $cm) = get_context_info_array($contextid);

$blockname = get_string('pluginname', 'block_simple_restore');
$restoreheading = simple_restore_utils::heading($restoreto);

$PAGE->set_url(new moodle_url('/blocks/simple_restore/restore.php', array(
    'contextid' => $contextid)
));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->navbar->add($restoreheading);
$PAGE->set_title($blockname . ': ' . $restoreheading);
$PAGE->set_heading($blockname . ': ' . $restoreheading);

// Check requirements according to restore mode.
if ($archivemode) {
    require_login();
    require_capability('block/simple_restore:canrestorearchive', $context);
} else {
    require_login($course, null, $cm);
    require_capability('block/simple_restore:canrestore', $context);
}

$restore = new simple_restore($course, $filename, $restoreto);
$header = $course->fullname;

if (!$confirm) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($header);
    $confirmstr = simple_restore_utils::_s('confirm_message',
        '<strong>'.$restoreheading.'</strong>'
    );

    $confirmurl = new moodle_url('restore.php', array(
        'contextid' => $contextid,
        'restore_to' => $restoreto,
        'confirm' => 1,
        'filename' => $filename
    ));

    $cancelurl = new moodle_url('list.php', array(
        'id' => $course->id,
        'restore_to' => $restoreto
    ));

    echo $OUTPUT->confirm($confirmstr, $confirmurl, $cancelurl);
    echo $OUTPUT->footer();
}

$useasync = (bool)get_config('simple_restore', 'async_toggle');

// This conditional returns html content for the ajax reponse.
// Bug-054: async mode redirects here via GET (confirm=1); data_submitted() is
// false on GET, so we also fire when async is active and confirm is set.
if ($confirm and ($useasync or data_submitted())) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($header);
    echo '<span class="restore_template_progress_hider">';
    try {
        // Defense-in-depth: validate the staged backup file is non-empty before
        // handing it to the restore engine. A 0-byte backup can otherwise
        // produce a broken course shell that crashes the Snap renderer with
        // "Call to a member function get_filename() on null".
        $tempdir = isset($CFG->backuptempdir) ? $CFG->backuptempdir : $CFG->tempdir;
        $tempdir = substr($tempdir, -1) === '/' ? $tempdir : $tempdir . '/';
        $stagedpath = $tempdir . $filename;
        if (file_exists($stagedpath) && @filesize($stagedpath) === 0) {
            @unlink($stagedpath);
            throw new moodle_exception(
                'error_backup_empty',
                'block_simple_restore',
                '',
                $filename
            );
        }

        $restore->execute();
        if (!$useasync) {
            echo $OUTPUT->notification(
                get_string('restoreexecutionsuccess', 'backup'), 'notifysuccess'
            );
        }
    } catch (Exception $e) {
        // Log the full exception (message + stack trace) so admins can
        // diagnose restore failures from the Moodle debug log. Teachers
        // see only the friendly notification below.
        debugging(
            'block_simple_restore: restore failed for filename "' . $filename . '": '
            . $e->getMessage() . "\n" . $e->getTraceAsString(),
            DEBUG_DEVELOPER
        );

        // Show a friendlier notification that names the backup and points the
        // user to their administrator. The raw exception message is included
        // in parentheses for context, but framed by helpful copy.
        $a = (object) [
            'filename' => $filename,
            'message'  => $e->getMessage(),
        ];
        echo $OUTPUT->notification(
            simple_restore_utils::_s('no_restore_friendly', $a)
        );

        // In case of an aborted archive restore, the 'new' course will have been deleted.
        $course->id = $archivemode == 1 ? 1 : $course->id;
    }
    if (!$useasync) {
        echo $OUTPUT->continue_button(
            new moodle_url('/course/view.php', array('id' => $course->id))
        );
    }
    echo '</span>';
    echo $OUTPUT->footer();
}
