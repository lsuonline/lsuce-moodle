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
 * @copyright  2026 onwards Steve Mattsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin stuff.
$string['pluginname'] = 'Workday preferences';
$string['wdsprefs:addinstance'] = 'Add Workday preferences block instance';
$string['wdsprefs:myaddinstance'] = 'Add Workday preferences block instance to my page';
$string['wdsprefs:pluginname'] = 'Workday preferences';

// Basic stuff.
$string['wdsprefs:cancel'] = 'Preference modification cancelled.';
$string['wdsprefs:error'] = 'Error saving preferences!';
$string['wdsprefs:saveprefs'] = 'Save preferences';
$string['wdsprefs:success'] = 'Preferences saved successfully!';

// Link stuff.
$string['wdsprefs:course'] = 'Course preferences';
$string['wdsprefs:coursename'] = 'Course';
$string['wdsprefs:crossenroll'] = 'Cross-enroll';
$string['wdsprefs:crosssplit'] = 'Cross-list & split';
$string['wdsprefs:schedule'] = 'Course schedule';
$string['wdsprefs:teamteach'] = 'Team-teaching';
$string['wdsprefs:unwant'] = 'Unwanted sections';
$string['wdsprefs:user'] = 'User preferences';

$string['help_course_preferences'] = 'Course creation and enrollment preferences.';
$string['help_unwanted_sections'] = 'Unwanted section enrollments.';
$string['help_cross_listing'] = 'Cross-listing and splitting courses and sections into any combination of course shells.';
$string['help_cross_enrollment_help'] = 'Combine sections from multiple academic periods into a single course shell. This is useful for combining sections from different terms into one shell.';
$string['help_course_preferences_help'] = 'Set your course creation and enrollment date preferences.';
$string['help_unwanted_sections_help'] = 'Remove unwanted course sections from your Moodle course shells.';
$string['help_cross_listing_help'] = 'Flexibly combine or separate course sections into any arrangement of course shells - from combining multiple courses into one shell to splitting individual sections into separate shells or any hybrid combination. Enrollments will be kept up to date and grades can be posted for all sections directly from their assigned shells.';

// WDS Page strings.
$string['wdsprefs:format'] = 'Course format';
$string['wdsprefs:cdaysprior'] = 'Create days prior';
$string['wdsprefs:cdaysprior_help'] = 'Number of days prior to the semester starting to <strong>create courses</strong>.';
$string['wdsprefs:cdaysprior_desc'] = 'Number of days prior to the semester starting to <strong>create courses</strong>.';
$string['wdsprefs:edaysprior'] = 'Enroll days prior';
$string['wdsprefs:edaysprior_help'] = 'Number of days prior to the semester starting to <strong>enroll students</strong>.';
$string['wdsprefs:edaysprior_desc'] = 'Number of days prior to the semester starting to <strong>enroll students</strong>.';
$string['wdsprefs:courselimit'] = 'Course limit';
$string['wdsprefs:courselimit_help'] = 'Only create courses below this threshold.';
$string['wdsprefs:courselimit_desc'] = 'Only create courses below this threshold.';

// Unwant Page Strings.
$string['wdsprefs:period'] = 'Academic period';

// Schedule view strings.
$string['wdsprefs:courselink'] = 'Course link';
$string['wdsprefs:scheduleview'] = 'Course schedule view';
$string['wdsprefs:courseschedule'] = 'Your course schedule';
$string['wdsprefs:studentschedule'] = 'Your student schedule';
$string['wdsprefs:teachingschedule'] = 'Your teaching schedule';
$string['wdsprefs:courseheading'] = 'Course full name';
$string['wdsprefs:coursenoheading'] = 'Course number';
$string['wdsprefs:sectionheading'] = 'Section';
$string['wdsprefs:statusheading'] = 'Moodle status';
$string['wdsprefs:instructorheading'] = 'Instructor';
$string['wdsprefs:daysheading'] = 'Days';
$string['wdsprefs:timesheading'] = 'Times';
$string['wdsprefs:wdstatusheading'] = 'Section status';
$string['wdsprefs:deliverymodeheading'] = 'Delivery mode';
$string['wdsprefs:nocourses'] = 'You are not registered for courses in Workday.';

// CrossSplit strings.
$string['wdsprefs:crosssplittitle'] = 'Cross-list & split sections';
$string['wdsprefs:crosssplitheading'] = 'Cross-list & split course sections';
$string['wdsprefs:selectcoursesheader'] = 'Step 1: Choose courses and number of shells';
$string['wdsprefs:assignshellsheader'] = 'Step 2: Assign sections to shells';
$string['wdsprefs:selectcourses'] = 'Courses to include';
$string['wdsprefs:shellcount'] = 'Number of course shells';
$string['wdsprefs:shell'] = 'Shell {$a}';
$string['wdsprefs:onetoone'] = 'Splitting one (1) course with one (1) section will not do anything.';
$string['wdsprefs:toomanyshells'] = 'You have selected more course shells: {$a->shellword} ({$a->shell}) than sections: {$a->secword} ({$a->sec}).';
$string['wdsprefs:atleastonesections'] = 'You must select at least one section to enable Cross-listing & Splitting.';
$string['wdsprefs:crosssplitsuccess'] = 'Cross-splitting setup successful.';
$string['wdsprefs:duplicatesection'] = 'Each section can only be assigned to one shell.';
$string['wdsprefs:availablesections'] = 'Available sections';
$string['wdsprefs:availableshells'] = 'Course Shells';
$string['wdsprefs:selectedsections'] = 'Shell {$a} sections';
$string['wdsprefs:selectperiodsheader'] = 'Current / near-future periods';
$string['wdsprefs:selectperiod'] = 'Select a term/period to use';
$string['wdsprefs:periodwithcount'] = '{$a->name} &mdash; {$a->count} available sections';
$string['wdsprefs:crosssplitfail'] = 'Cross-splitting failed';
$string['wdsprefs:shellname'] = 'Shell name';
$string['wdsprefs:shelltaginvalid'] = 'Shell tag may only contain letters, numbers, dashes, underscores and spaces.';
$string['wdsprefs:shelltagunique'] = 'Two or more shells have the same name. Give each shell a different name.';
$string['wdsprefs:shelltagunavailable'] = 'This name is already used by an existing course shell. Choose a different name.';
$string['wdsprefs:datecreated'] = 'Date created';
$string['wdsprefs:actions'] = 'Actions';
$string['wdsprefs:viewcourse'] = 'View course';
$string['wdsprefs:viewsections'] = 'View sections';
$string['wdsprefs:existingcrosssplits'] = 'Your existing cross-listed, split, and cross-enrolled course shells';
$string['wdsprefs:crosssplitsections'] = 'Cross-listed, split, and cross-enrolled sections';
$string['wdsprefs:crosssplitinstructions2'] = '<strong>Cross-listing:</strong> Select the courses to combine, then enter how many shells you need.<br><strong>Splitting:</strong> Choose a course to split into multiple shells, then enter how many shells you need.';
$string['wdsprefs:crosssplitinstructions3'] = '<ol><li>Click on a shell container to select it (highlighted in blue)</li><li>Select any number of sections from the left and click "Add to Shell" to assign them to the highlighted shell.</li></ol><br>You can select sections from any shell and click "Remove" to return them to the available list.<br>You can create up to {$a->shellword} ({$a->shell}) shell(s) from the {$a->secword} ({$a->sec}) available sections.<br>If there are unused shells, they will not be creaed. Any unassigned sections will be left in the original course shell.';
$string['wdsprefs:nocrosssplitperiods'] = 'No courses are eligible for cross-splitting. Cross-splitting requires multiple sections or courses in a common academic period.';
$string['wdsprefs:nocrosssplit'] = 'Cross-listed, split, or cross-enrolled shell not found or you do not have permission to view it.';
$string['wdsprefs:nosections'] = 'No sections have been assigned to this cross-listed, split, or cross-enrolled shell.';
$string['wdsprefs:status'] = 'Status';
$string['wdsprefs:sectionstatus_pending'] = 'Pending';
$string['wdsprefs:sectionstatus_enrolled'] = 'Enrolled';
$string['wdsprefs:sectionstatus_unenrolled'] = 'Unenrolled';
$string['wdsprefs:section'] = 'Section';
$string['wdsprefs:sections'] = 'Sections';
$string['wdsprefs:nosectionsavailable'] = 'Fewer than two (2) sections are available for cross-listing, splitting, or cross-enrolling. You need at least two (2) sections to split a course. You need at least one (1) section in two (2) courses to cross-list or cross-enroll a group of courses. Alternatively, all your courses may already be part of cross-listed, split, or cross-enrolled courses.';

// Blueprint shells related strings.
$string['wdsprefs:blueprint'] = 'Blueprint shells';
$string['wdsprefs:blueprinttitle'] = 'Blueprint shells';
$string['wdsprefs:blueprintheading'] = 'Blueprint shells for courses';
$string['wdsprefs:requestblueprint'] = 'Request a blueprint shell';
$string['wdsprefs:existingblueprints'] = 'Your existing blueprint shells';
$string['wdsprefs:createblueprint'] = 'Create blueprint shell';
$string['wdsprefs:selectcourseforblueprint'] = 'Select course for blueprint';
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

// Undo cross-splitting strings.
$string['wdsprefs:undo'] = 'Undo cross-listing, splitting, and cross-enrollment';
$string['wdsprefs:undoconfirm'] = 'Are you sure you want to undo this cross-listing, splitting, or cross-enrollment? This will reset sections to their original course shells and move enrollments accordingly. This action cannot be undone.';
$string['wdsprefs:undosuccess'] = 'Cross-listing, splitting, and cross-enrollment successfully undone. Sections have been reset to their original course shells.';
$string['wdsprefs:undofailed'] = 'Failed to undo cross-listing, splitting, or cross-enrollment. Please contact support.';

// Cross-enrollment strings.
$string['wdsprefs:crossenrolltitle'] = 'Cross-enrollment';
$string['wdsprefs:crossenrollheading'] = 'Cross-enrollment';
$string['wdsprefs:crossenrollinstructions1'] = 'Select the academic period that will serve as the "primary" or "target" period for the new course shell. This determines the course shell\'s name and dates.';
$string['wdsprefs:crossenrollinstructions2'] = 'Select the sections you wish to combine into a single course shell. You can select sections from multiple academic periods as long as their dates line up.';
$string['wdsprefs:crossenrollsuccess'] = 'Cross-enrollment setup successful.';
$string['wdsprefs:crossenrollfail'] = 'Cross-enrollment failed';
$string['wdsprefs:targetperiod'] = 'Target period';
$string['wdsprefs:selectsections'] = 'Select sections';
$string['wdsprefs:nosectionsselected'] = 'You must select at least one section.';
$string['wdsprefs:nocrossenrollperiods'] = 'No courses are eligible for cross-enrollment. Cross-enrollment requires courses in at least two different academic periods sharing the same start and end dates.';
$string['wdsprefs:alreadycrosssplit'] = 'This section is cross-split or cross-enrolled, please {$a} to cross-enroll this section.';
$string['wdsprefs:undoaction'] = 'undo this action';
$string['wdsprefs:notenoughsectionsforcrossenroll'] = 'You must have at least two sections across multiple academic periods to cross-enroll.';
$string['wdsprefs:mustselectfromtwo'] = 'You must select at least one section from at least two different academic periods to cross-enroll.<br>If you are trying to cross-list courses within the same academic period, please use the {$a} tool.';

// Team Teach strings.
$string['wdsprefs:teamteach'] = 'Team-teaching';
$string['wdsprefs:teamteach_desc'] = 'Team-teaching allows you to invite another instructor to teach one or more sections within your course shell.';
$string['wdsprefs:teamteachheading'] = 'Team-teaching';
$string['wdsprefs:teamteachsections'] = 'Team-teach sections';
$string['wdsprefs:teamteach_expiry_hours'] = 'Request expiration (hours)';
$string['wdsprefs:teamteach_expiry_hours_desc'] = 'Number of hours before a team-teach request expires.';
$string['wdsprefs:teamteach_email_subject'] = 'Email subject';
$string['wdsprefs:teamteach_email_subject_desc'] = 'Subject line for the team-teach invitation email.';
$string['wdsprefs:teamteach_email_subject_default'] = 'Team Teach Request from {$a->requester}';
$string['wdsprefs:teamteach_email_body'] = 'Email body';
$string['wdsprefs:teamteach_email_body_desc'] = 'Body of the team-teach invitation email. Supported placeholders: {requester}, {course}, {sections}, {link}, {expiry}.';
$string['wdsprefs:teamteach_email_body_default'] = '<p>Hello,</p>
<p>{requester} has requested to add your section(s):</p>
<p>{sections}</p>
<p>to their course shell: <strong>{course}</strong>.</p>
<p>Please click the link below to approve or decline this request. This link will expire in {expiry} hours.</p>
<p><a href="{link}">{link}</a></p>';
$string['wdsprefs:teamteach_select_target_course'] = 'Select target course shell';
$string['wdsprefs:teamteach_search_teacher'] = 'Search for instructor';
$string['wdsprefs:teamteach_search_placeholder'] = 'Search by First Name, Last Name, or Email';
$string['wdsprefs:teamteach_select_sections'] = 'Select sections to request';
$string['wdsprefs:teamteach_submit_request'] = 'Send Request';
$string['wdsprefs:teamteach_request_created'] = 'Team-teach request sent successfully.';
$string['wdsprefs:teamteach_request_failed'] = 'Failed to create team-teach request.';
$string['wdsprefs:teamteach_no_sections_found'] = 'No eligible sections found for this instructor in the selected academic period.';
$string['wdsprefs:teamteach_no_teacher_found'] = 'No instructor found matching your search.';
$string['wdsprefs:teamteach_invalid_request'] = 'Invalid request.';
$string['wdsprefs:teamteach_request_not_found'] = 'Request not found.';
$string['wdsprefs:teamteach_request_expired'] = 'This request has expired.';
$string['wdsprefs:teamteach_already_processed'] = 'This request has already been processed.';
$string['wdsprefs:teamteach_approved'] = 'Request approved. Sections have been merged.';
$string['wdsprefs:teamteach_declined'] = 'Request declined.';
$string['wdsprefs:teamteach_approve'] = 'Approve Request';
$string['wdsprefs:teamteach_decline'] = 'Decline Request';
$string['wdsprefs:teamteach_request_details'] = 'Request Details';
$string['wdsprefs:teamteach_requester'] = 'Requester';
$string['wdsprefs:teamteach_target_course'] = 'Target Course';
$string['wdsprefs:teamteach_requested_sections'] = 'Requested Sections';
$string['wdsprefs:teamteach_confirm_approve'] = 'Are you sure you want to approve this request? This will merge your sections into the target course.';
$string['wdsprefs:teamteach_confirm_decline'] = 'Are you sure you want to decline this request?';
$string['wdsprefs:teamteach_self_request'] = 'You cannot send a request to yourself.';
$string['wdsprefs:teamteach_pending'] = 'Pending';
$string['wdsprefs:teamteach_link_sent'] = 'A request has been sent to {$a}.';
$string['wdsprefs:teamteach_revoked_subject'] = 'Team-teach request revoked: {$a->course}';
$string['wdsprefs:teamteach_revoked_body'] = 'Hello {$a->recipient},<br><br>
The team-teaching arrangement for course "{$a->course}" has been {$a->action}.<br><br>
The sections have been moved back to their original course shells.<br><br>
Best regards,<br>{$a->actor}';
$string['wdsprefs:revoked'] = 'revoked';
$string['wdsprefs:undone'] = 'undone';
$string['wdsprefs:teamteach_my_requests'] = 'My team-teach requests';
$string['wdsprefs:teamteach_requests_for_me'] = 'My team-teach invitations';
$string['wdsprefs:teamteach_status'] = 'Status';
$string['wdsprefs:teamteach_action'] = 'Action';
$string['wdsprefs:teamteach_cancel'] = 'Cancel';
$string['wdsprefs:teamteach_undo'] = 'Undo';
$string['wdsprefs:teamteach_revoke'] = 'Revoke';
$string['wdsprefs:teamteach_confirm_cancel'] = 'Are you sure you want to cancel this pending team-teach request?';
$string['wdsprefs:teamteach_confirm_undo'] = 'Are you sure you want to undo this team-teach? External sections will be returned to their original shells.';
$string['wdsprefs:teamteach_confirm_revoke'] = 'Are you sure you want to revoke this team-teach? Your sections will be returned to their original shells if they exist, otherwise new shells will be created and your students will be enrolled.';
$string['wdsprefs:teamteach_cancelled'] = 'Team-teach request cancelled.';
$string['wdsprefs:teamteach_undone_success'] = 'Team-teach request undone successfully.';
$string['wdsprefs:teamteach_revoked_success'] = 'Team-teach request revoked successfully.';
$string['wdsprefs:teamteach_failed_action'] = 'Action failed.';
$string['wdsprefs:teamteach_status_pending'] = 'Pending';
$string['wdsprefs:teamteach_status_approved'] = 'Approved';
$string['wdsprefs:teamteach_status_declined'] = 'Declined';
$string['wdsprefs:teamteach_status_expired'] = 'Expired';
$string['wdsprefs:teamteach_status_cancelled'] = 'Cancelled';
$string['wdsprefs:teamteach_status_revoked'] = 'Revoked';
$string['wdsprefs:section_already_crosslisted'] = 'This section is already cross-listed, split, or cross-enrolled in {$a}';
$string['wdsprefs:section_already_teamtaught'] = 'This section is already being team-taught in {$a}';
