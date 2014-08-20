<?php

$string['pluginname'] = 'Post Grades';

$string['posting_periods'] = 'Posting Periods';
$string['confirm'] = 'Confirm';

$string['notactive'] = 'You are trying to post grades in a an inactive posting period.';
$string['notvalidgroup'] = '{$a} is not a valid posting group';

$string['post_grades:canpost'] = 'User can post grades to the external web service.';
$string['post_grades:canconfigure'] = 'User can create posting periods.';
$string['post_grades:addinstance'] = 'Add block to page';
$string['post_grades:myaddinstance'] = 'Add block to page';
$string['domino_application_url'] = 'Domino post grade application URL';
$string['mylsu_gradesheet_url'] = 'myLSU Gradesheet URL';

$string['https_protocol'] = 'HTTPS?';
$string['https_protocol_desc'] = 'Post grades over `https`. Leave unchecked for `http` posting.';

$string['header_help'] = 'Below are system wide settings for the external web service.

- You can [edit posting periods]({$a->period_url}) on this page.
- You can [reset posting flags]({$a->reset_url}) on this page.';

$string['new_posting'] = 'New posting';
$string['no_posting'] = 'There are no posting periods. Continue to create one.';

$string['message'] = 'You are about to post {$a->post_type} grades for {$a->name} in {$a->fullname}. Please note: you can only post <strong>once</strong> from Moodle for each section in each posting period.';
$string['post_type_grades'] = 'Post {$a->post_type} Grades';
$string['make_changes'] = 'Return to Gradebook';

$string['nopublishing'] = 'Grade publishing has not been actived. Please contact the Moodle admin about this error.';
$string['alreadyposted'] = 'You have already posted {$a->post_type} for {$a->name} from Moodle. If you have confirmed
that there was a problem with the transport, please contact the Moodle Administrator to allow you to post
from Moodle once more.';

$string['nopostings'] = 'No postings were found. Refine your search.';

$string['posted'] = 'Already posted';
$string['not_posted'] = 'Have not posted';

$string['finalgrade_item'] = 'Final grade';
$string['finalgrade_anon'] = 'Anonymous grade';

$string['noitems'] = 'You did not have any grade items within your course. A grade item has been created for you. Continue to edit grades.';
$string['noanonitem'] = 'You did not have a completed anonymous item for your course. If you did not have one in your gradebook, then one was created for you. Continue to begin grading this item.';

$string['course_compliance'] = 'Course compliance';
$string['section_compliance'] = 'Section {$a} compliance';

$string['law_quick_edit_compliance'] = 'QE Compliance';
$string['law_quick_edit_compliance_help'] = 'This setting allows the quick edit screen to display compliance warnings when grading regular grade items.';

// UES People
$string['student_audit'] = 'Auditing';

// Quick Edit Strings
$string['student_incomplete'] = 'Incomplete';

// Form strings
$string['posting_for'] = '{$a->post_type} for {$a->fullname} {$a->course_name} Section {$a->sec_number}';
$string['view_gradsheet'] = 'View Gradesheet';
$string['reset_posting'] = 'Reset Postings';
$string['find_postings'] = 'Find Postings';
$string['semester'] = 'Semester';
$string['posting_period'] = 'Posting Period';
$string['start_time'] = 'Start time';
$string['end_time'] = 'End time';
$string['export_number'] = 'Export Number';
$string['are_you_sure'] = 'Are you sure you want to delete the posting period for {$a}? This action cannot be reversed.';
$string['post_type'] = 'Posting Type';

$string['no_students'] = 'No students for {$a}';

$string['midterm'] = 'Midterms';
$string['final'] = 'Finals';
$string['degree'] = 'Degree Candidates';
$string['test'] = 'PG Test';
$string['law_upper'] = 'LAW Upper Class';
$string['law_first'] = 'LAW First Year';
$string['law_degree'] = 'LAW Degree Candidates';

// LAW return lib
$string['sizeexplain'] = 'Your grades must conform to the {$a->description}
curve compliance. Once your grades meet the standard, then you will be able
to post to the mainframe. If you are able to post now, yet you see still see
red on the graph, then treat this curve compliance graph as a
<strong>recommendation</strong>.';

$string['semexplain'] = 'Your grades must conform to the Seminar guideline.
The median value of your grades must be within {$a->upper} - {$a->lower}.
Your median value is {$a->median}. If you are able to post now, treat the
guideline as a <strong>recommendation</strong>.';

// LAW config page
$string['law_scale'] = 'LAW scale';
$string['law_scale_help'] = 'This scale will be used for LAW courses of the type `LP`.';
$string['mean'] = 'Mean';
$string['median'] = 'Median';
$string['point_range'] = 'Point Range +/-';
$string['required'] = 'Required';
$string['required_help'] = 'If required, then an instructor cannot post grades until his course meets the configured requirement. If not required, then the configured variables are treated as a __recommendation__.';
$string['number_students'] = '# Students >=';
$string['number_students_less'] = '# Students <';
$string['large_courses'] = 'Large and 1L Courses';
$string['mid_courses'] = 'Mid Sized Courses';
$string['small_courses'] = 'Small Courses';
$string['sem_courses'] = 'Seminar Courses';
$string['lower_percent'] = 'Lower percent';
$string['upper_percent'] = 'Upper percent';
$string['high_pass'] = 'High Pass';
$string['high_pass_value'] = 'Average >=';
$string['pass'] = 'Pass';
$string['pass_value'] = 'Average >=';
$string['fail'] = 'Failing';
$string['fail_value'] = 'Average <=';
$string['law_extra'] = 'Additional Course Settings';
$string['law_heading'] = 'LAW Post Grades';
$string['law_domino'] = 'LAW domino URL';
$string['law_mylsu_gradesheet_url'] = 'LAW gradesheet URL';
$string['law_exceptions'] = '1L Exceptions';
$string['law_exceptions_help'] = '1L Exception courses are LAW courses that do
__NOT__ require an anonymous grade item when they otherwise would have. Thus an exception is made for these courses.';
$string['law_legal_writing'] = 'Legal Writing';
$string['law_legal_writing_help'] = 'Courses that are legal writing are manually
determine at the beginning of every LAW semester.';
