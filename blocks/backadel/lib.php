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
 * @package    block_backadel
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Whitelisted course / category columns for Backadel search
 * (matches {@see \block_backadel\local\table\query_support::ALLOWED_CRITERIA}).
 *
 * @param string $field One of shortname, fullname, idnumber, category.
 * @return string SQL fragment (qualified column expression).
 */
function backadel_search_field_sql(string $field): string {
    static $map = [
        'shortname' => 'co.shortname',
        'fullname' => 'co.fullname',
        'idnumber' => 'co.idnumber',
        'category' => 'cat.name',
    ];
    if (!isset($map[$field])) {
        throw new \coding_exception('Invalid search field');
    }
    return $map[$field];
}

/**
 * Build a safe WHERE fragment and bound parameters for Backadel course search.
 *
 * Each constraint object must have:
 * - field: shortname | fullname | idnumber | category
 * - operator: IN | NOT IN | LIKE | NOT LIKE
 * - search_terms: pipe-separated values (as produced by the index search form).
 *
 * Constraint groups are combined with $query->type ('AND' or 'OR'). Within LIKE / NOT LIKE,
 * multiple terms are OR-combined (legacy UI behaviour).
 *
 * @param stdClass $query Object with string property type: 'AND' or 'OR'.
 * @param array $constraints Array of stdClass constraint objects.
 * @return array{0: string, 1: array} Tuple: SQL WHERE body (no WHERE keyword) and bound parameters.
 */
function backadel_build_where_from_search(stdClass $query, array $constraints): array {
    global $DB;

    $combinator = (isset($query->type) && strtoupper($query->type) === 'OR') ? ' OR ' : ' AND ';
    if ($constraints === []) {
        return ['1=1', []];
    }

    $groups = [];
    $params = [];
    $pidx = 0;

    foreach ($constraints as $c) {
        $field = $c->field ?? '';
        if (!is_string($field) || !in_array($field, ['shortname', 'fullname', 'idnumber', 'category'], true)) {
            throw new \coding_exception('Invalid search constraint field');
        }
        $fieldsql = backadel_search_field_sql($field);
        $operator = $c->operator ?? '';
        $rawterms = $c->search_terms ?? '';
        $terms = array_filter(array_map('trim', explode('|', (string) $rawterms)), static function($t) {
            return $t !== '';
        });

        if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
            $notlike = ($operator === 'NOT LIKE');
            $likesub = [];
            foreach ($terms as $t) {
                $paramname = 'bkw' . $pidx;
                $placeholder = ':' . $paramname;
                $escaped = $DB->sql_like_escape($t);
                $likesub[] = $DB->sql_like($fieldsql, $placeholder, false, false, $notlike);
                $params[$paramname] = '%' . $escaped . '%';
                $pidx++;
            }
            if ($likesub !== []) {
                $groups[] = '(' . implode(' OR ', $likesub) . ')';
            }
            continue;
        }

        if ($operator === 'IN' || $operator === 'NOT IN') {
            if ($terms === []) {
                continue;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($terms, SQL_PARAMS_NAMED, 'in' . $pidx, $operator === 'IN');
            $groups[] = '(' . $fieldsql . ' ' . $insql . ')';
            $params = array_merge($params, $inparams);
            $pidx++;
            continue;
        }

        throw new \coding_exception('Invalid search constraint operator');
    }

    if ($groups === []) {
        return ['1=1', $params];
    }

    return [implode($combinator, $groups), $params];
}

/**
 * Build the full legacy SELECT for course search (safe parameters). Prefer {@see backadel_build_where_from_search()}.
 *
 * @param stdClass $query Object with type AND|OR.
 * @param array $constraints Array of constraint objects (field, operator, search_terms).
 * @return array{0: string, 1: array} Full SQL and parameters.
 */
function build_sql_from_search($query, $constraints): array {
    [$wherebody, $params] = backadel_build_where_from_search($query, $constraints);
    $sql = "SELECT co.id, co.fullname, co.shortname, co.idnumber, cat.name AS category
        FROM {course} co
        JOIN {course_categories} cat ON cat.id = co.category
        WHERE (" . $wherebody . ')';
    return [$sql, $params];
}

/**
 * Build a safe WHERE fragment and parameters for the Backadel course search page filters.
 *
 * @param array $filters Keys: optional string q, optional int category, optional string status
 *     ('' any, 'none' for no status row, or a block_backadel_statuses status code).
 * @return array{0: string, 1: array} SQL WHERE body (no WHERE keyword) and bound parameters.
 */
function backadel_search_to_where(array $filters): array {
    global $DB;

    $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
    $category = isset($filters['category']) ? (int) $filters['category'] : 0;
    $status = isset($filters['status']) ? (string) $filters['status'] : '';

    if ($q === '' && $category <= 0 && $status === '') {
        return ['1=0', []];
    }

    $clauses = [];
    $params = [];
    $pidx = 0;

    if ($q !== '') {
        $escaped = $DB->sql_like_escape($q);
        $pattern = '%' . $escaped . '%';
        $p1 = 'bksq' . $pidx++;
        $p2 = 'bksq' . $pidx++;
        $p3 = 'bksq' . $pidx++;
        $clauses[] = '(' . $DB->sql_like('co.shortname', ':' . $p1, false) . ' OR '
            . $DB->sql_like('co.fullname', ':' . $p2, false) . ' OR '
            . $DB->sql_like('co.idnumber', ':' . $p3, false) . ')';
        $params[$p1] = $pattern;
        $params[$p2] = $pattern;
        $params[$p3] = $pattern;
    }

    if ($category > 0) {
        $pk = 'bkcat' . $pidx++;
        $clauses[] = 'co.category = :' . $pk;
        $params[$pk] = $category;
    }

    if ($status !== '') {
        if ($status === 'none') {
            $clauses[] = 'ba.status IS NULL';
        } else if (in_array($status, ['BACKUP', 'SUCCESS', 'FAIL', 'DELETED'], true)) {
            $pk = 'bkst' . $pidx++;
            $clauses[] = 'ba.status = :' . $pk;
            $params[$pk] = $status;
        }
    }

    if ($clauses === []) {
        return ['1=0', []];
    }

    return [implode(' AND ', $clauses), $params];
}

/**
 * Build a safe WHERE fragment and parameters for the Backadel catalogue page filters.
 *
 * @param array $filters Keys: optional string q; optional int year (0 = all years); optional strings semester,
 *     status ('' any, 'none' => IS NULL, or available|missing|archived), source, pattern.
 * @return array{0: string, 1: array} SQL WHERE body (no WHERE keyword) and bound parameters.
 */
function backadel_catalogue_to_where(array $filters): array {
    global $DB;

    $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
    $year = isset($filters['year']) ? (int) $filters['year'] : 0;
    $semester = isset($filters['semester']) ? (string) $filters['semester'] : '';
    $status = isset($filters['status']) ? (string) $filters['status'] : '';
    $source = isset($filters['source']) ? (string) $filters['source'] : '';
    $pattern = isset($filters['pattern']) ? (string) $filters['pattern'] : '';

    $clauses = [];
    $params = [];
    $pidx = 0;

    if ($q !== '') {
        $escaped = $DB->sql_like_escape($q);
        $likepattern = '%' . $escaped . '%';
        $p1 = 'bcq' . $pidx++;
        $p2 = 'bcq' . $pidx++;
        $p3 = 'bcq' . $pidx++;
        $p4 = 'bcq' . $pidx++;
        $clauses[] = '(' . $DB->sql_like('c.filename', ':' . $p1, false) . ' OR '
            . $DB->sql_like('c.shortname', ':' . $p2, false) . ' OR '
            . $DB->sql_like('c.dept', ':' . $p3, false) . ' OR '
            . $DB->sql_like('c.course_num', ':' . $p4, false) . ')';
        $params[$p1] = $likepattern;
        $params[$p2] = $likepattern;
        $params[$p3] = $likepattern;
        $params[$p4] = $likepattern;
    }

    if ($year > 0) {
        $pk = 'bcy' . $pidx++;
        $clauses[] = 'c.year = :' . $pk;
        $params[$pk] = $year;
    }

    if ($semester !== '') {
        $pk = 'bcs' . $pidx++;
        $clauses[] = 'c.semester = :' . $pk;
        $params[$pk] = $semester;
    }

    if ($status !== '') {
        if ($status === 'none') {
            $clauses[] = 'c.status IS NULL';
        } else if (in_array($status, ['available', 'missing', 'archived'], true)) {
            $pk = 'bcst' . $pidx++;
            $clauses[] = 'c.status = :' . $pk;
            $params[$pk] = $status;
        }
    }

    if ($source !== '' && in_array($source, ['backadel_current', 'legacy_moodleus', 'legacy_openlms'], true)) {
        $pk = 'bcsrc' . $pidx++;
        $clauses[] = 'c.source = :' . $pk;
        $params[$pk] = $source;
    }

    $validpatterns = [
        'semester_legacy',
        'semester_legacy_lc',
        'semester_legacy_clone',
        'storage_course',
        'storagecourse_dept',
        'storage_legacy',
        'backadel_modern',
        'backadel_instructor',
        'moodle_native',
        'unknown',
    ];
    if ($pattern !== '' && in_array($pattern, $validpatterns, true)) {
        $pk = 'bcp' . $pidx++;
        $clauses[] = 'c.pattern = :' . $pk;
        $params[$pk] = $pattern;
    }

    if ($clauses === []) {
        return ['1=1', []];
    }

    return [implode(' AND ', $clauses), $params];
}

/**
 * Delete courses based on supplied courseids
 *
 * @return bool
 */
function backadel_delete_course($courseid) {
    global $DB, $CFG;
    // Get the course object based on the supplied courseid.
    $course = $DB->get_record('course', array('id' => $courseid));
    if (!$course) {
        return false;
    }

    $suffix = generate_suffix($course->id);
    $matchers = array('/\s/', '/\//');
    $safeshort = preg_replace($matchers, '-', $course->shortname);
    $backadelfile = "backadel-{$safeshort}{$suffix}.zip";
    $backadelpath = $CFG->dataroot . get_config('block_backadel', 'path');
    $filepath = $backadelpath . $backadelfile;

    // Delete the course.
    if (delete_course($course, false)) {
        fix_course_sortorder();
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        try {
            $now = time();
            // Primary: exact path match (suffix correct).
            $DB->execute(
                'UPDATE {block_backadel_catalogue} SET status = ?, timemodified = ? WHERE filepath_hash = ? AND source = ?',
                ['missing', $now, sha1($filepath), 'backadel_current']
            );
            // Fallback: any available backadel_current row for this shortname whose file no longer exists
            // (handles the case where instructor suffix differed between backup and delete time).
            $DB->execute(
                'UPDATE {block_backadel_catalogue} SET status = ?, timemodified = ?
                  WHERE source = ? AND shortname = ? AND status = ?',
                ['missing', $now, 'backadel_current', $course->shortname, 'available']
            );
        } catch (\Throwable $e) {
            if (CLI_SCRIPT) {
                mtrace('Backadel catalogue missing update failed: ' . $e->getMessage());
            } else {
                debugging('Backadel catalogue missing update failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return true;
    } else {
        return false;
    }
}

/**
 * Generates the last bit of the backup .zip's filename based on the
 * pattern and roles that the admin chose in config.
 *
 * @return $suffix
 */
function generate_suffix($courseid) {
    $suffix = '';

    // Grab the allowed suffixes.
    $field = get_config('block_backadel', 'suffix');

    // Grab the administratively selected roles.
    $roleids = explode(',', get_config('block_backadel', 'roles'));

    // Grab the course context.
    $context = context_course::instance($courseid);

    // When NOT using fullname (which we might want to avoid anyway).
    if ($field != 'fullname') {
        // Loop through all the administratively selected roles.
        foreach ($roleids as $r) {
            // If the role has any users in the course, return them.
            if ($users = get_role_users($r, $context, false)) {
                // Loop through the users and grab the appropriate suffix.
                foreach ($users as $k => $v) {
                    $suffix .= '_' . $v->$field;
                }
            }
        }
    } else {
        // Loop through all the administratively selected roles.
        foreach ($roleids as $r) {
            // If the role has any users in the course, return them.
            if ($users = get_role_users($r, $context, false)) {
                // Loop through the users and grab the appropriate suffix.
                foreach ($users as $k => $v) {
                    $suffix .= '_' . $v->firstname . $v->lastname;
                }
            }
        }
    }
    return $suffix;
}

/**
 * Instantiate the moodle backup subsystem
 * and backup the course.
 *
 * @return true
 */
function backadel_backup_course($course) {
    global $CFG;

    $buresult = false;

    // Required files for the backups.
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');
    require_once($CFG->dirroot . '/backup/util/helper/backup_cron_helper.class.php');

    // Generate the Filename suffix.
    $suffix = generate_suffix($course->id);
    $matchers = array('/\s/', '/\//');

    // Build the basis for the filename.
    $safeshort = preg_replace($matchers, '-', $course->shortname);

    // Assemble the filename from constituent parts.
    $backadelfile = "backadel-{$safeshort}{$suffix}.zip";

    // Build the path.
    $backadelpath = $CFG->dataroot . get_config('block_backadel', 'path');

    // Resolve semester subfolder for new backups.
    $periodslug = \block_backadel\local\period_resolver::for_course($course);
    if (!empty($periodslug)) {
        $subfolder = rtrim($backadelpath, '/\\') . DIRECTORY_SEPARATOR . $periodslug;
        try {
            if (!is_dir($subfolder)) {
                make_writable_directory($subfolder);
            }
            $backadelpath = $subfolder . DIRECTORY_SEPARATOR;
        } catch (\moodle_exception $e) {
            mtrace('Backadel: could not create period subfolder ' . $subfolder . ' — ' . $e->getMessage());
        }
    }

    // Set the userid.
    $userid = 2;

    // Set up the config for backup.
    $config = get_config('backup');

    // Grab the specified directory for automated backups.
    $dir = $config->backup_auto_destination;

    // The default outcome here is success.
    $outcome = 1;

    // Grab the backup storage location (0: course, 1: specified dir, 2: both).
    $storage = (int)$config->backup_auto_storage;

    // Build the backup controller.
    $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
        backup::MODE_AUTOMATED, $userid);

    // Try some stuff.
    try {

        // Set up the stuff to set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $incfiles = (bool)$config->backup_auto_files;
        $bc->get_plan()->get_setting('filename')->set_value(backup_plan_dbops::get_default_backup_filename($format, $type,
            $id, $users, $anonymised, false, $incfiles));

        // Set the filename PRIOR to completing the backup, we'll do some checking later on.
        $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $course->id, $users, $anonymised,
            !$config->backup_shortname);

        // Set the status for the backup logs.
        $bc->set_status(backup::STATUS_AWAITING);

        // Do the backup.
        $bc->execute_plan();
        $results = $bc->get_results();
        $outcome = outcome_from_results($results);

        // May be empty if file already moved to target location.
        $file = $results['backup_destination'];

        // Get the full path filename for the Moodle backup.
        $mfname = $dir . '/' . $filename;

        // Due to moodle backup not returning filenames and naming them with the minute attached, we have to do stupid stuff.
        if ($storage !== 0 && !file_exists($mfname)) {

            // Print this to the task logs so we see it initially failed.
            mtrace('Moodle backup file does not exist at initial location - ' . $mfname);

            // Grab a secondary filename after the abckup is completed in case the initial location is incorrect..
            $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $course->id, $users, $anonymised,
                !$config->backup_shortname);

            // Set the new location based on the updated filename.
            $mfname = $dir . '/' . $filename;

            // Print the new location so we can see what's going on.
            mtrace('Trying secondary location - ' . $mfname);

            // Some extra sanity checking to make sure the file name has not changed since we updated it and now.
            if (!file_exists($mfname)) {

                // Reset the filename yet again.
                $filename = backup_plan_dbops::get_default_backup_filename(
                    $format, $type, $course->id, $users, $anonymised, !$config->backup_shortname
                );

                // Rebuild the full path based on the new filename... again.
                $mfname = $dir . '/' . $filename;

                // Print the final location and hope we don't fail anymore.
                mtrace('Secondary location does not exist, using tertiary location - ' . $mfname);
            }
        }

        // Get the full path filename for the proposed backadel filename.
        $bdfname = $backadelpath . $backadelfile;

        // Copy the file from the course storage area to backadel and cleanly delete it.
        if ($storage === 0 && !empty($backadelpath) && is_dir($backadelpath) && is_writeable($backadelpath)) {

            // Try to copy the file from the course file-area to the backadel area.
            if ($file->copy_content_to($bdfname)) {

                // Yay! It worked. Log it.
                $bc->log('Backup file copied successfully to the specified backadel folder - ',
                        backup::LOG_INFO, $bdfname);

                // Generate different output if the course has no instructors.
                if (!empty($suffix)) {
                    mtrace('Copy successful from course automated backup area to - ' . $bdfname);
                } else {
                    mtrace('Copy successful for course without instructors - ' . $bdfname);
                }

                // Set the outcome and buresult for later use.
                $outcome = 1;
                $buresult = true;

                // Delete the file from the course backup area on successful save to backadel file area.
                if (!empty($file)) {
                    $file->delete();
                }
            } else {

                // The copy_contents_to failed. Log it accordingly.
                $bc->log('Attempt to copy backup file to the specified backadel failed - ',
                        backup::LOG_ERROR, $bdfname);
                mtrace('Copy failed from course automated backup area to - ' . $bdfname);

                // Set the outcome and buresult for later use.
                $outcome = 0;
                $buresult = false;

                // Delete the file from the course backup area on failed save to backadel file area.
                if (!empty($file)) {
                    $file->delete();
                }
            }

            // We're now working with a specified directory for backup storage.
        } else if ($storage !== 0 && (empty($backadelpath) || !is_dir($backadelpath) || !is_writable($backadelpath))) {

            // The backadel path is either not specified or not a directory or not writeable. Log it accordingly.
            $bc->log('Specified backup directory is not writable - ', backup::LOG_ERROR, $backadelpath);
            mtrace('Backadel failed: Specified backup directory is not writable - ' . $backadelpath);

            // Set the outcome and buresult for later use.
            $outcome = 0;
            $buresult = false;

            // Unlink the Moodle backup.
            @unlink($mfname);

            // Backadel path is set and writable but the Moodle backup is missing.
        } else if ($storage !== 0 && !empty($backadelpath) && is_dir($backadelpath)
                && is_writeable($backadelpath) && !file_exists($mfname)) {

            // The file does not exist, log accordingly.
            $bc->log('Source backup file does not exist - ', backup::LOG_ERROR, $mfname);
            mtrace('Backadel failed: Source backup file does not exist - ' . $mfname);

            // Set the outcome and buresult for later use.
            $outcome = 0;
            $buresult = false;

            // Unlink the Moodle backup.
            @unlink($mfname);

            // Backadel path is set, writable, and Moodle backup exists — proceed with move.
        } else if ($storage !== 0 && !empty($backadelpath) && is_dir($backadelpath)
                && is_writeable($backadelpath) && file_exists($mfname)) {

            // Try to rename the file from the Moodle file location to the backadel file location.
            if (rename($mfname, $bdfname)) {

                // Yay! Everything worked. Let's log accordingly.
                $bc->log('Rename successful - ', backup::LOG_INFO, $backadelpath);

                // Generate different output if the course has no instructors.
                if (!empty($suffix)) {
                    mtrace('Rename successful - ' . $mfname . ' to ' . $bdfname);
                } else {
                    mtrace('Rename successful for course without instructors - ' . $bdfname);
                }

                // Set the outcome and buresult for later use.
                $outcome = 1;
                $buresult = true;
            } else {

                // The rename failed. Log accordingly.
                $bc->log('Rename failed - ', backup::LOG_ERROR, $bdfname);
                mtrace('Rename failed - ' . $mfname . ' to ' . $bdfname);

                // Set the outcome and buresult for later use.
                $outcome = 0;
                $buresult = false;

                // The rename failed, so let's delete this file from the Moodle specified directory for automated backups.
                if ($storage !== 0 && !empty($mfname) && file_exists($mfname)) {
                    @unlink($mfname);
                }
            }
        } else {
            // Super catch-all for something else failing. Better safe than ignorant.
            $bc->log('Something else failed - ', backup::LOG_ERROR, $bdfname);
            mtrace('Something else failed generating ' . $bdfname);

            // Set the outcome and buresult for later use.
            $outcome = 0;
            $buresult = false;
        }

        // Catch and log stuff.
    } catch (moodle_exception $e) {
        $bc->log('backup_auto_failed_on_course', backup::LOG_ERROR, $course->shortname); // Log error header.
        $bc->log('Exception: ' . $e->errorcode, backup::LOG_ERROR, $e->a, 1); // Log original exception problem.
        $bc->log('Debug: ' . $e->debuginfo, backup::LOG_DEBUG, null, 1); // Log original debug information.
        $outcome = 0;
    }

    // Destroy and unset the backup controller.
    $bc->destroy();
    unset($bc);

    if (!empty($buresult) && isset($bdfname)) {
        try {
            (new \block_backadel\local\migrator())->migrate_directory(dirname($bdfname), 'backadel_current');
        } catch (\Throwable $e) {
            if (CLI_SCRIPT) {
                mtrace('Backadel catalogue update failed: ' . $e->getMessage());
            } else {
                debugging('Backadel catalogue update failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    // Return either true or false.
    return $buresult;
}


    /**
     * Returns the backup outcome by analysing its results.
     *
     * @param array $results returned by a backup
     * @return int {@link self::BACKUP_STATUS_OK} and other constants
     */
function outcome_from_results($results) {
    $outcome = 1;
    foreach ($results as $code => $value) {
        // Each possible error and warning code has to be specified in this switch
        // which basically analyses the results to return the correct backup status.
        switch ($code) {
            case 'missing_files_in_pool':
                $outcome = 4;
                break;
        }
        // If we found the highest error level, we exit the loop.
        if ($outcome == 0) {
            break;
        }
    }
    return $outcome;
}


/**
 * Email the admins
 *
 */
function backadel_email_admins($errors) {
    $dellink = new moodle_url('/blocks/backadel/delete.php');

    $subject = get_string('email_subject', 'block_backadel');
    $from = get_string('email_from', 'block_backadel');
    $messagetext = $errors . "\n\n" . get_string('email_body', 'block_backadel') . $dellink;

    foreach (get_admins() as $admin) {
        email_to_user($admin, $from, $subject, $messagetext);
    }
}
