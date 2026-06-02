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

// Strings for block.
$string['backup_and_delete'] = 'Backup And Delete';
$string['block_index'] = 'Backup';
$string['block_delete'] = 'Delete';
$string['block_pending'] = 'Pending';
$string['block_config'] = 'Config';
$string['block_failed'] = 'Failures';
$string['block_large_backups'] = 'Large Backups';
$string['backing_up'] = 'Backing Up';
$string['backadel_settings'] = 'Backup and Delete Settings';
$string['status_running'] = 'Running: {$a}.';
$string['cron_backup_error'] = 'Error backing up {$a}';
$string['status_not_running'] = 'Not Running';
$string['cron_already_running'] = 'Backadel claims it has been running for {$a} minute(s), but the task manager disagrees.';
$string['backuptask'] = 'Backup job';
$string['task_reresolve_teachers'] = 'Backadel: Re-resolve unresolved teachers';

// Stings shared by pages.
$string['pluginname'] = 'Backup And Delete';
$string['blockname'] = 'Backup And Delete';
$string['need_permission'] = 'You do not have permission to view this page';
$string['toggle_all'] = 'Select All/None';

// Strings for index.php.
$string['build_search'] = 'Build Search';
$string['saved_searches'] = 'Saved Searches';
$string['no_searches'] = 'There are no saved searches at this time';
$string['match'] = 'Match';
$string['of_these_constraints'] = 'of these constraints';
$string['build_search_button'] = 'Build Search Query';
$string['search_name'] = 'Search Name';
$string['created_at'] = 'Created At';
$string['run_query_button'] = 'Run Saved Query';
$string['upload_a_file'] = 'Upload A File';
$string['upload'] = 'Upload';
$string['cancel'] = 'Cancel';
$string['delete_queries_link'] = 'Select Saved Queries for Deletion';
$string['course_id'] = 'Course ID #';
$string['is'] = 'is';
$string['is_not'] = 'is not';
$string['contains'] = 'contains';
$string['does_not_contain'] = 'does not contain';
$string['name_missing'] = 'Please select a name for this query';
$string['term_missing'] = 'Please select at least one search term for this constraint';
$string['search_missing'] = 'Please select a saved search';

// Strings for backup.php.
$string['backup'] = 'Select Backup Courses';
$string['backup_button'] = 'Backup Selected Courses';

// Strings for results.php.
$string['search_results'] = 'Search Results';
$string['search_status_none'] = 'Not yet backed up';
$string['search_instructions'] = 'Use the filters above to search for courses by short / full name, ID number, category, or backup status.';
$string['filter_panel_toggle'] = 'Filters';
$string['filter_panel_active'] = '{$a} active';
$string['filter_semester'] = 'Semester';
$string['save_query'] = 'Save Query';
$string['create_new_query'] = 'Create New Query';

// Strings for CRUD / table UI (MD-2189).
$string['results_status_backup'] = 'Queued';
$string['results_status_success'] = 'Complete';
$string['results_status_failed'] = 'Failed';
$string['results_status_deleted'] = 'Deleted';
$string['results_action_queue'] = 'Queue backup';
$string['results_action_requeue'] = 'Re-queue';
$string['results_action_delete'] = 'Delete archive';
$string['action_confirm_title'] = 'Confirm action';
$string['delete_archive_confirm'] = 'This will permanently delete the course and remove its Backadel status. Continue?';
$string['delete_archive_title'] = 'Delete Archive';
$string['delete_archive_btn'] = 'Delete archive';
$string['delete_archive_course_label'] = 'Course';
$string['delete_archive_warning'] = 'You are about to permanently delete this Moodle course and remove its Backadel backup record. The backup file on disk is not affected, but the course itself and all its content will be gone. This action cannot be undone.';
$string['requeue_title'] = 'Re-queue Backup';
$string['requeue_btn'] = 'Re-queue';
$string['requeue_course_label'] = 'Course';
$string['requeue_info'] = 'This will reset the backup status to BACKUP so the next scheduled run picks up this course again. Any previous failure reason will be cleared.';
$string['restore_action_confirm'] = 'You are about to overwrite this course. Proceed?';
$string['restore_action_confirm_title'] = 'Confirm restore';
$string['table_col_filename'] = 'Filename';
$string['table_col_shortname'] = 'Short name';
$string['table_col_fullname'] = 'Full name';
$string['table_col_category'] = 'Category';
$string['table_col_status'] = 'Status';
$string['table_col_modified'] = 'Last modified';
$string['table_col_filesize'] = 'File size';
$string['table_col_actions'] = 'Actions';
$string['queued_backup'] = 'Backup queued';
$string['requeued_backup'] = 'Backup re-queued';
$string['deleted_archive'] = 'Archive deleted';
$string['error_no_capability'] = 'You do not have permission to perform this action.';

// Strings for delete.php.
$string['delete'] = 'Delete?';
$string['delete_header'] = 'Completed Backups';
$string['deleted'] = 'Successfully deleted {$a}';
$string['delete_error'] = ', but there may have been an error, please check';
$string['none_completed'] = 'There are no completed backups at this time';
$string['delete_button'] = 'Delete Selected Courses';

// Strings for delete_queries.php.
$string['delete_queries_header'] = 'Delete Saved Queries';
$string['delete_queries_button'] = 'Delete Selected Queries';
$string['delete_queries_success'] = '{$a} successful query deletion(s)';

// Strings for send_job.php.
$string['job_sent'] = 'Backup Job Sent';
$string['job_sent_body'] = 'Your backup job will start during the next cron run. ' .
    'You will receive an email when all backups are complete.';
$string['already_successful'] = ' was not scheduled for backup because was ' .
    'already successfully backed up but never deleted.';
$string['already_scheduled'] = ' was not scheduled for backup because it is ' .
    'already scheduled for backup.';
$string['already_failed'] = ' was not scheduled for backup because it is an ' .
    'unresolved failure. Please fix this.';

// String for failed.php.
$string['failed_header'] = 'Failed Backups';
$string['none_failed'] = 'There are no failed backups at this time';
$string['failed_button'] = 'Reschedule Selected Backups';
$string['failed'] = 'Failed?';
$string['statuses_updated'] = 'Selected courses have been rescheduled for backup';

// Strings for settings.php.
$string['config_path'] = 'Storage Path';
$string['config_path_desc'] = 'Relative to {$a}, include the surrounding slashes.
    Ensure that this directory is created and writable.';
$string['config_pattern'] = 'Archive suffix';
$string['config_pattern_desc'] = 'Data that will be appended onto backup names';
$string['config_roles'] = 'Roles';
$string['config_roles_desc'] = 'Roles to include when naming backup files';
$string['config_size_limit'] = 'Size limit before warning';
$string['config_size_limit_desc'] = 'In megabytes';
$string['path_error'] = 'Error: Please ensure that the path you provided is a ' .
    'writable directory';
$string['sched_config'] = 'Access scheduled backup settings as
    (' . $string['pluginname'] . ') uses these settings.';
$string['here'] = 'here';
$string['config_path_not_exists'] = 'The path you have entered does not exists.';
$string['config_path_not_writable'] = 'The path you have entered is not writable.';
$string['config_path_surround'] = 'Surround the path with slashes.';

// Strings for email.
$string['email_subject'] = 'Backup Job Completed';
$string['email_from'] = 'noreply@lsu.edu';
$string['email_body']  = "The Backup And Delete tool has completed the jobs in it's queue.";

// Parse / catalogue (MD-2189).
$string['unknown_period'] = 'Unknown period';
$string['catalogue_title'] = 'Backadel Catalogue';
$string['course_search'] = 'Course Search';
$string['catalogue_filter_apply'] = 'Apply filters';
$string['catalogue_filter_year'] = 'Year';
$string['catalogue_filter_year_all'] = 'All years';
$string['catalogue_filter_semester'] = 'Semester';
$string['catalogue_filter_source'] = 'Source';
$string['catalogue_filter_pattern'] = 'Filename pattern';
$string['catalogue_filter_status_none'] = 'No status row';
$string['catalogue_no_results'] = 'No catalogue rows match the current filters.';
$string['catalogue_results_count'] = '{$a} result(s)';
$string['catalogue_status_available'] = 'Available';
$string['catalogue_status_missing'] = 'Missing';
$string['catalogue_status_archived'] = 'Archived';
$string['catalogue_col_coursetype'] = 'Course type';
$string['catalogue_filter_coursetype'] = 'Course type';
$string['catalogue_filter_instructor'] = 'Instructor username';
$string['catalogue_filter_instructor_placeholder'] = 'e.g. dcastr10';
$string['catalogue_coursetype_undetermined'] = 'Undetermined';
$string['catalogue_col_semester'] = 'Semester';
$string['catalogue_col_dept'] = 'Dept';
$string['catalogue_col_course_num'] = 'Course no.';
$string['catalogue_col_source'] = 'Source';
$string['catalogue_col_backup_date'] = 'Backup date';
$string['catalogue_col_size'] = 'Size';
$string['catalogue_col_pattern'] = 'Pattern';
$string['catalogue_col_actions'] = 'Actions';
$string['catalogue_clear_filters'] = 'Clear filters';
$string['catalogue_action_restore'] = 'Restore';
$string['catalogue_action_restore_disabled'] = 'Restore (unavailable)';
$string['catalogue_restore_disabled_tooltip'] = 'File is not available on disk';
$string['catalogue_action_goto_course'] = 'Go to course';
$string['catalogue_action_instructors'] = 'Instructors';
$string['catalogue_action_download'] = 'Download';
$string['catalogue_instructors_modal_title'] = 'Instructors';
$string['catalogue_instructors_col_username'] = 'Username';
$string['catalogue_instructors_col_fullname'] = 'Full name';
$string['catalogue_instructors_none'] = 'No instructors recorded';
$string['catalogue_override_btn'] = 'Set type';
$string['catalogue_override_modal_title'] = 'Override course type';
$string['catalogue_override_label_type'] = 'Course type';
$string['catalogue_override_label_note'] = 'Note (optional)';
$string['catalogue_override_save'] = 'Save';
$string['catalogue_override_clear'] = 'Clear override / revert to auto-detected type';
$string['catalogue_override_saved'] = 'Course type override saved.';
$string['catalogue_override_cleared'] = 'Override cleared; reverted to auto-detected type.';
$string['catalogue_override_invalid_type'] = 'Invalid course type value.';
$string['catalogue_override_note_too_long'] = 'The note cannot exceed 1024 characters.';
// Restore proxy page (MD-2189 §3.4).
$string['coursebackups_pagetitle'] = 'Course Backups';
$string['coursebackups_heading'] = 'Course Backups';
$string['coursebackups_year'] = 'Year:';
$string['coursebackups_col_filename'] = 'Filename';
$string['coursebackups_col_type'] = 'Type';
$string['coursebackups_col_year'] = 'Year';
$string['coursebackups_col_actions'] = 'Actions';
$string['coursebackups_restore'] = 'Restore';
$string['coursebackups_restore_filemissing'] = 'The selected backup file could not be located on disk. The catalogue row may be stale or the file may have been moved or deleted.';
$string['coursebackups_restore_stagefailed'] = 'The backup file was found on disk but could not be staged for restore. Check filesystem permissions on the Moodle data directory.';
$string['coursebackups_empty'] = 'No backups found for this year.';
$string['catalogue_table_missing'] = 'The backup catalogue table does not exist. Please run the database upgrade first.';
$string['coursetype_teaching'] = 'Teaching';
$string['coursetype_blueprint'] = 'Blueprint';
$string['coursetype_other'] = 'Other';

// Admin settings — Catalogue & Migration (MD-2189 §2.13).
$string['catalogue_migration_heading'] = 'Catalogue &amp; Migration';
$string['blueprint_keywords'] = 'Blueprint keywords';
$string['blueprint_keywords_desc'] = 'One keyword per line. Backups whose name contains any of these keywords are classified as blueprint (template) courses.';
$string['excluded_categories'] = 'Excluded category IDs';
$string['excluded_categories_desc'] = 'Category IDs (one per line) to exclude from migration scans.';
$string['migration_extra_paths'] = 'Extra scan paths';
$string['migration_extra_paths_desc'] = 'Additional absolute directory paths (one per line) to include in the filesystem migration scan.';
$string['catalogue_path_prefix'] = 'Catalogue path prefix rewrite';
$string['catalogue_path_prefix_desc'] = 'Format: old_prefix=new_prefix. Applied when resolving catalogue file paths (e.g. /old/moodledata=/new/moodledata).';
$string['task_migrate_filesystem_adhoc'] = 'Backadel: Migrate filesystem backups to catalogue (on-demand)';
$string['migrate_chunk_timeout_minutes'] = 'Migration chunk timeout (minutes)';
$string['migrate_chunk_timeout_minutes_desc'] = 'Maximum wall-clock minutes a single migration adhoc task instance will run before pausing and queueing a successor task to continue the scan. Default 15. Lower this if cron runs are short; raise it if the queue backlog is a concern.';
$string['instructor_email_domain'] = 'Instructor email domain fallback';
$string['instructor_email_domain_desc'] = 'When a plain username token (e.g. "wjian15") cannot be matched to a Moodle account by username, try looking up the user by email as "username@domain". For example, entering "lsu.edu" means "wjian15" is tried as "wjian15@lsu.edu". Leave empty to disable. Filenames that already embed a full email address (e.g. wjian15@lsu.edu) always use direct email lookup and do not use this setting.';
$string['migrate_pagetitle'] = 'Backadel — Run Migration';
$string['migrate_heading'] = 'Backadel Migration';
$string['migrate_run_button'] = 'Run migration scan now';
$string['migrate_queued'] = 'Migration task queued. You can stay on this page — the counts below will update automatically when it completes.';
$string['migrate_catalogue_count'] = 'Backup files catalogued: ';
$string['migrate_courses_count'] = 'Course records indexed: ';
$string['migrate_what_happens'] = 'Running a migration scan walks the configured backup directories and indexes any new or updated backup files into the catalogue. Existing entries are updated; duplicates are skipped. The counts above will refresh automatically once the task finishes. If the counts do not change, check the cron logs for errors.';
$string['migrate_completed'] = 'Migration complete. The counts above have been updated.';
$string['migrate_warn_no_path'] = 'The backup storage path is not configured. Running a migration scan now will index zero files. <a href="{$a}">Go to Global Settings</a> and set the Storage Path before running a migration.';
$string['migrate_scan_path'] = 'Scan directory:';
$string['task_reclassify_catalogue_adhoc'] = 'Backadel: Re-classify catalogue entries (on-demand)';
$string['reclassify_heading'] = 'Re-classify Catalogue Entries';
$string['reclassify_what_happens'] = 'Re-parses filenames for existing catalogue rows and updates course type, instructor lists, and missing-file status without re-scanning the filesystem. Use this after updating filename patterns or fixing classification bugs.';
$string['reclassify_run_button'] = 'Re-classify selected rows';
$string['reclassify_queued'] = 'Re-classification task queued. The catalogue will be updated in the background.';
$string['reclassify_dryrun_result'] = 'Dry run: {$a} catalogue rows match the selected criteria. No changes were made.';
$string['reclassify_disabled_queued'] = 'A Backadel task is already running. Wait for it to complete before re-classifying.';
$string['reclassify_label_patterns'] = 'Re-classify rows with these filename patterns:';
$string['reclassify_label_null_semester'] = 'Include rows where semester is not set';
$string['reclassify_label_dry_run'] = 'Dry run (count only, no changes)';
$string['reclassify_none_selected'] = 'Select at least one pattern or enable "Null semester" to re-classify.';
$string['reclassify_null_semester_help_title'] = 'Null Semester Rows';

// Admin navigation labels (settings.php admin_externalpage entries).
$string['tab_settings']      = 'Global settings';
$string['tab_search']         = 'Course Search';
$string['tab_migrate']       = 'Run Migration';
$string['tab_coursebackups'] = 'Course Backups';
$string['tab_catalogue']     = 'Catalogue';
$string['tab_failed']        = 'Failed Backups';
$string['tab_delete']        = 'Delete Archives';
$string['nav_settings']      = 'Global settings';
$string['nav_search']         = 'Course Search';
$string['nav_migrate']       = 'Run Migration';
$string['nav_coursebackups'] = 'Course Backups';
$string['nav_catalogue']     = 'Catalogue';
$string['nav_failed']        = 'Failed Backups';
$string['nav_delete']        = 'Delete Archives';

// Help button system (Wave 11).
$string['help_not_found'] = 'Help content not available for this topic.';
$string['help_button_label'] = 'Help';

// Capabilities.
$string['backadel:addinstance'] = 'Add '.$string['pluginname'].' block.';
