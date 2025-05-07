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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin stuff.
// $string['pluginname'] = 'Workday Preferences';
$string['wdsprefs:pluginname'] = 'Workday Preferences';
$string['wdsprefs:pluginname_desc'] = 'Course preferences where instructors can split, cross, unwant courses and more.';

// Basic stuff.
$string['wdsprefs:cancel'] = 'Preference modification cancelled.';
$string['wdsprefs:error'] = 'Error saving preferences!';
$string['wdsprefs:saveprefs'] = 'Save Preferences';
$string['wdsprefs:success'] = 'Preferences saved successfully!';

// Link stuff.
$string['wdsprefs:course'] = 'Course preferences';
$string['wdsprefs:crosslist'] = 'Cross listing';
$string['wdsprefs:split'] = 'Split courses';
$string['wdsprefs:schedule'] = 'Course schedule';
$string['wdsprefs:teamteach'] = 'Team teaching';
$string['wdsprefs:unwant'] = 'Unwanted sections';
$string['wdsprefs:user'] = 'User preferences';

$string['wdsprefs:help_course_preferences'] = 'course creation and enrollment preferences.';
$string['wdsprefs:help_unwanted_sections'] = 'unwanted section enrollments.';
$string['wdsprefs:help_split_courses'] = 'splitting a multi-section course into multiple course shells.';
$string['wdsprefs:help_cross_listing'] = 'crosslisting courses and sections into a single course shell.';
$string['wdsprefs:help_team_teaching'] = 'team teaching assignments.';

$string['wdsprefs:help_course_preferences_help'] = 'Set your course creation and enrollment date preferences.';
$string['wdsprefs:help_unwanted_sections_help'] = 'Remove unwanted course sections from your Moodle course shells.';
$string['wdsprefs:help_split_courses_help'] = 'Split courses into multiple course shells with anywhere from one course shell to one section per course shell.';
$string['wdsprefs:help_cross_listing_help'] = 'Merge multiple courses and their sections into one single course shell. Enrollments will be kept up and post grades for all cross listed course sections directly from this shell.';
$string['wdsprefs:help_team_teaching_help'] = 'Invite another professor and their section\'s roster into your course shell. Enrollment will be kept and grade posting permitted from this course shell.';

// WDS Page strings.
$string['wdsprefs:format'] = 'Course Format';
$string['wdsprefs:cdaysprior'] = 'Create Days Prior';
$string['wdsprefs:cdaysprior_help'] = 'Number of days prior to the semester starting to <strong>create courses</strong>.';
$string['wdsprefs:cdaysprior_desc'] = 'Number of days prior to the semester starting to <strong>create courses</strong>.';
$string['wdsprefs:edaysprior'] = 'Enroll Days Prior';
$string['wdsprefs:edaysprior_help'] = 'Number of days prior to the semester starting to <strong>enroll students</strong>.';
$string['wdsprefs:edaysprior_desc'] = 'Number of days prior to the semester starting to <strong>enroll students</strong>.';
$string['wdsprefs:courselimit'] = 'Course Limit';
$string['wdsprefs:courselimit_help'] = 'Only create courses below this threshold.';
$string['wdsprefs:courselimit_desc'] = 'Only create courses below this threshold.';

// Unwant Page Strings.
$string['wdsprefs:period'] = 'Academic Period';

// Schedule view strings.
$string['wdsprefs:courselink'] = 'Course Link';
$string['wdsprefs:scheduleview'] = 'Course Schedule View';
$string['wdsprefs:courseschedule'] = 'Your Course Schedule';
$string['wdsprefs:courseheading'] = 'Course';
$string['wdsprefs:sectionheading'] = 'Section';
$string['wdsprefs:statusheading'] = 'Moodle Status';
$string['wdsprefs:instructorheading'] = 'Instructor';
$string['wdsprefs:daysheading'] = 'Days';
$string['wdsprefs:timesheading'] = 'Times';
$string['wdsprefs:wdstatusheading'] = 'Workday Status';
$string['wdsprefs:deliverymodeheading'] = 'Delivery Mode';
$string['wdsprefs:nocourses'] = 'You are not registered for courses in Workday.';

// Crosslist strings.
$string['wdsprefs:crosslisttitle'] = 'Cross-list Sections';
$string['wdsprefs:crosslistheading'] = 'Cross-list Course Sections';
$string['wdsprefs:selectcoursesheader'] = 'Step 1: Choose Courses and Number of Shells';
$string['wdsprefs:assignshellsheader'] = 'Step 2: Assign Sections to Shells';
$string['wdsprefs:selectcourses'] = 'Courses to include';
$string['wdsprefs:shellcount'] = 'Number of course shells';
$string['wdsprefs:shell'] = 'Shell {$a}';
$string['wdsprefs:atleasttwosections'] = 'You must select at least two sections to enable cross-listing.';
$string['wdsprefs:crosslistsuccess'] = 'Cross-listing setup successful.';
$string['wdsprefs:duplicatesection'] = 'Each section can only be assigned to one shell.';
$string['wdsprefs:availablesections'] = 'Available Sections';
$string['wdsprefs:selectedsections'] = 'Shell {$a} Sections';
$string['wdsprefs:selectperiodsheader'] = 'Current / Near-Future Periods';
$string['wdsprefs:selectperiod'] = 'Select a term/period to use';
$string['wdsprefs:crosslistfail'] = 'Crosslisting Failed';
$string['wdsprefs:crosslistinstructions'] = 'Click on a shell container to select it (highlighted in blue), then select sections from the left and click "Add to Shell" to assign them. You can select sections from any shell and click "Remove" to return them to the available list. You need to create {$a} shell(s).';
$string['wdsprefs:shellname'] = 'Shell Name';
$string['wdsprefs:datecreated'] = 'Date Created';
$string['wdsprefs:actions'] = 'Actions';
$string['wdsprefs:viewcourse'] = 'View Course';
$string['wdsprefs:viewsections'] = 'View Sections';
$string['wdsprefs:existingcrosslists'] = 'Your Existing Crosslisted Shells';
$string['wdsprefs:crosslistsections'] = 'Crosslisted Sections';
$string['wdsprefs:nocrosslist'] = 'Crosslisted shell not found or you do not have permission to view it.';
$string['wdsprefs:nosections'] = 'No sections have been assigned to this crosslisted shell.';
$string['wdsprefs:status'] = 'Status';
$string['wdsprefs:sectionstatus_pending'] = 'Pending';
$string['wdsprefs:sectionstatus_enrolled'] = 'Enrolled';
$string['wdsprefs:sectionstatus_unenrolled'] = 'Unenrolled';
$string['wdsprefs:section'] = 'Section';

// Blueprint shells related strings.
$string['wdsprefs:blueprint'] = 'Blueprint Shells';
$string['wdsprefs:blueprinttitle'] = 'Blueprint Shells';
$string['wdsprefs:blueprintheading'] = 'Blueprint Shells for Courses';
$string['wdsprefs:requestblueprint'] = 'Request a Blueprint Shell';
$string['wdsprefs:existingblueprints'] = 'Your Existing Blueprint Shells';
$string['wdsprefs:createblueprint'] = 'Create Blueprint Shell';
$string['wdsprefs:selectcourseforblueprint'] = 'Select Course for Blueprint';
$string['wdsprefs:blueprintexplanation'] = 'Blueprint shells allow you to create a template course that can be used as a starting point for your future courses. Create a blueprint shell for any course you teach and set it up exactly how you want. You can use this to import materials into in the future.';
$string['wdsprefs:blueprintnotes'] = 'Notes (optional)';
$string['wdsprefs:blueprintnotes_help'] = 'Add any notes or reminders about this blueprint shell for your future reference.';
$string['wdsprefs:blueprintsuccess'] = 'Blueprint shell created successfully!';
$string['wdsprefs:blueprintfailed'] = 'Failed to create blueprint shell. Please try again or contact support.';
$string['wdsprefs:blueprintalreadyexists'] = 'You already have a blueprint shell for this course.';
$string['wdsprefs:noinstructor'] = 'Only instructors can create blueprint shells.';
$string['wdsprefs:nocourses'] = 'You are not teaching any courses in the current or upcoming terms.';
$string['wdsprefs:blueprintstatus_pending'] = 'Pending';
$string['wdsprefs:blueprintstatus_created'] = 'Created';
$string['wdsprefs:blueprintstatus_failed'] = 'Failed';

// Split Strings.
$string['wdsprefs:split'] = 'Splitting';
$string['wdsprefs:split_help'] = 'Splitting allows an instructor to separate online courses with two or more sections into multiple online courses. This is especially useful for separating the gradebook and other activities.';
$string['wdsprefs:next'] = 'Next';
$string['wdsprefs:back'] = 'Back';
$string['wdsprefs:select'] = 'Select a course';
$string['wdsprefs:shells'] = 'Course Shells';
$string['wdsprefs:decide'] = 'Separate Sections';
$string['wdsprefs:confirm'] = 'Review';
$string['wdsprefs:update'] = 'Update';
$string['wdsprefs:loading'] = 'Applying';
$string['wdsprefs:split_how_many'] = 'How many separate course shells would you like to have created?';
$string['wdsprefs:split_how_many_help'] = 'A _course shell_ is a Moodle course that encapsulates one or more sections.
For example: If you were splitting a course with three sections, you may decide to make
two _course shells_, one containing one section, and the other containing two. In most cases,
the number of _course shells_ is limited to the number of sections within a course.';
$string['wdsprefs:split_autopop'] = 'Do you want to automatically assign sections to course shells using generic shell names?';
$string['wdsprefs:split_autopop_help'] = 'When you have the same number of sections and available course shells,
you may choose to automatically assign sections to course shells.  If you do, each section will be assigned
to the next available course shell in turn, and a generic name will be given to each course shell by inserting
\'Course #\' into the original course\'s full name for each section #.  If you don\'t, each course shell will have
a customizable course shell name, and you will get a screen with a box for each course shell which you must use to
choose one section for each shell.';
$string['wdsprefs:split_processed'] = 'Split Courses Processed';
$string['wdsprefs:split_thank_you'] = 'Your split selections have been processed. Continue to head back to the split home screen.';
$string['wdsprefs:chosen'] = 'Please review your selections.';
$string['wdsprefs:available_sections'] = 'Your Sections:';
$string['wdsprefs:move_left'] = '<';
$string['wdsprefs:move_right'] = '>';
$string['wdsprefs:split_option_taken'] = 'Split option taken';
$string['wdsprefs:split_updating'] = 'Updating your split selections';
$string['wdsprefs:split_undo'] = 'Undo these courses?';
$string['wdsprefs:split_reshell'] = 'Reassign the number of shells?';
$string['wdsprefs:split_rearrange'] = 'Rearrange sections?';
$string['wdsprefs:customize_name'] = 'Customize name';
$string['wdsprefs:shortname_desc'] = 'Split course creation uses these defaults.';
$string['wdsprefs:split_shortname'] = '{year} {name}{session} {department} {course_number} {shell_name} for {fullname}';
$string['wdsprefs:please_wait'] = 'Your settings are being applied. Please be patient as the process completes.';
$string['wdsprefs:network_failure'] = 'There was a network error that caused the process to fail. You can either refresh this page or go back to re-apply the settings.';
$string['wdsprefs:application_errors'] = 'The following error occurred while applying the settings: {$a}';
// Error Strings.
$string['wdsprefs:not_enabled'] = 'WDS Setting <strong>{$a}</strong> is not enabled.';
$string['wdsprefs:not_teacher'] = 'You are not enrolled or set to be enrolled in any course. If you believe that you should be, please contact the Moodle administrator for immediate assistance.';
$string['wdsprefs:err_select'] = 'The selected course does not exist.';
$string['wdsprefs:err_split_number'] = 'The selected course does not have two sections.';
$string['wdsprefs:err_select_one'] = 'You must select a course to continue.';
$string['wdsprefs:no_courses'] = 'There were no courses listed for this semester.';
$string['wdsprefs:no_section'] = 'There were no sections found.';


$string['wdsprefs:course_severed'] = 'Delete severed Courses';
$string['wdsprefs:course_severed_desc'] = 'A course is severed if the Moodle course will no longer be handled by the enrollment module, or if enrollment equals zero.';
$string['wdsprefs:course_threshold'] = 'Course Number Threshold';
$string['wdsprefs:course_threshold_desc'] = 'Sections belonging to a course number that is greater than or equal to the specified number will not be initially created. Workday Preferences will create unwanted entries for these sections so the instructor can opted in teaching online.';
$string['wdsprefs:user_field_category'] = 'Profile Category';
$string['wdsprefs:user_field_category_desc'] = 'Workday Preferences will attempt to create Moodle user profile fields associated with the user meta information from WDS.';
$string['wdsprefs:auto_field_desc'] = 'This field was automatically generated through Workday Preferences. Do not change the field settings unless you are absolutely certain of what you are doing.';

$string['wdsprefs:setting'] = 'User preferences';
$string['wdsprefs:setting_help'] = 'Faculty are allowed to change their first name to a preferred name. This change will be permanent until otherwise specified.';
$string['wdsprefs:enabled'] = 'Enabled';
$string['wdsprefs:enabled_desc'] = 'If disabled, the setting will be hidden from the instructor. A Moodle admin who is logged in as the instructor will still be able to see and manipulate the disabled setting.';
$string['wdsprefs:creation'] = 'Creation / Enrollment';
$string['wdsprefs:create_days'] = 'Days before Creation';
$string['wdsprefs:create_days_desc'] = 'The number of days before sections are created.';
$string['wdsprefs:enroll_days'] = 'Days before Enrollment';
$string['wdsprefs:enroll_days_desc'] = 'The number of days before **created** sections are enrolled.';
$string['wdsprefs:material'] = 'Blueprint Course';
$string['wdsprefs:material_help'] = 'A _Blueprint Course_ is a Moodle course designated to store course materials for selected courses. These created courses will __not__ contain student enrollment.';
$string['wdsprefs:material_shortname'] = 'Blueprint Course {department} {course_number} for {fullname}';
$string['wdsprefs:nonprimary'] = 'Allow Non-Primaries';
$string['wdsprefs:nonprimary_desc'] = 'If checked, then Non-Primaries will be able toconfigure the Workday Preferences settings.';

$string['wdsprefs:student_role'] = 'Students';
$string['wdsprefs:student_role_desc'] = 'WDS students will be enrolled in this Moodle role';
$string['wdsprefs:editingteacher_role'] = 'Primary Instructor';
$string['wdsprefs:editingteacher_role_desc'] = 'WDS *primary* teachers will be enrolled in this Moodle role';
$string['wdsprefs:teacher_role'] = 'Non-Primary Instructor';
$string['wdsprefs:teacher_role_desc'] = 'WDS *non-primary* teachers will be enrolled in this Moodle role';
$string['wdsprefs:suspend_enrollment'] = 'Inactivate Enrollment';
$string['wdsprefs:suspend_enrollment_desc'] = 'Inactivate enrollment instead of un-enrolling students.';
$string['wdsprefs:recover_grades'] = 'Recover Grades';
$string['wdsprefs:recover_grades_desc'] = 'Recover grade history grades on enrollment, if grades were present on unenrollment.';

