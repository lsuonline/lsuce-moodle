<?php
/**
 * ************************************************************************
 * *                   Test Centre System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

global $CFG;
    // Link to get back
$string['tcs_link_back_title'] = '<a href="'.$CFG->wwwroot.'/local/tcs/">Back to TCS</a>';


//strings for index.php file
$string['pluginname'] = 'Test Centre System';
$string['plugins'] = 'Plugins';
$string['tcs_title'] = 'Test Centre System';
$string['tcs_app_flow_title'] = 'Flow of the system';
$string['tcs_comp_builder_title'] = 'Component Builder';

// scheduler

$string['run_record_stats_task'] = 'TCS Stats Recorder';

// ======== settings ==========
// seats
$string['local_tcs_seat_size_main_title'] = 'Availability';
$string['local_tcs_seat_size_count'] = 'Max Number of Seats';
// allowed ips
$string['local_tcs_subnet_main_title'] = 'Lab Restrictions';
$string['local_tcs_subnet_list'] = 'Allowed IP Addresses to Use TCS';

// Logging
$string['local_tcs_logging_main_title'] = 'Developer Loggings';
$string['local_tcs_logging'] = 'Set Level';

// Theme
$string['local_tcs_theme_main_title'] = 'Theme transition';
$string['local_tcs_theme_use_old'] = 'Use old theme layout';



$string['local_tcs_quiz_ip_restriction_main_title'] = 'Quiz Restrictions';
$string['local_tcs_quiz_ip_restriction'] = 'Quiz Settings IP Restriction';
$string['local_tcs_subnet_main_ip_title'] = 'Allowed IP Addresses to Use TCS';


// ======== settings ==========
//strings for useradmin_form
$string['nav_tc_admin'] = 'User Administration';
$string['username'] = 'UofL username:';
$string['selectrole'] = 'Select a role:';
$string['add_user'] = 'Add User';
$string['delete_user'] = 'Delete Selected';
//strings for useradmin_form table
$string['id_header'] = 'id';
$string['select_header'] = 'Remove User';
$string['name_header'] = 'Name';
$string['username_header'] = 'Username';
$string['access_level_header'] = 'Access Level';

//strings for tc_pass_admin_form
$string['nav_tc_pass_admin'] = 'Exam Pass Administration';
$string['coursename'] = 'Course:';
$string['examname'] = 'Exam:';
$string['openingdate'] = 'Opening Date(yyyy-mm-dd 10:00):';
$string['closingdate'] = 'Closing Date(yyyy-mm-dd 22:00):';
$string['examname'] = 'Exam:';
$string['password'] = 'Password:';
$string['notes'] = 'Notes:';
$string['delete_record'] = 'Delete';
$string['add_exam'] = 'Add Exam';
$string['add_record'] = 'Add record';
$string['close_exam'] = 'Close record';
$string['open_exam'] = 'Open record';
//tc_pass_admin_form table
$string['course_header'] = 'Course';
$string['exam_pass_id_header'] = 'Course id';
$string['exam_header'] = 'Exam';
$string['openingdate_header'] = 'Opening Date';
$string['closingdate_header'] = 'Closing Date';
$string['password_header'] = 'Password';
$string['notes_header'] = 'Notes';

//tc_comment_search_form table
$string['status_header'] = 'Status';
$string['username_header'] = 'Username';
$string['comment_search_id_header'] = 'Comment ID';
$string['nav_tc_comment_search'] = 'Comment Search Form';
$string['cs_id_type'] = 'Search By ID Type';
$string['cs_date_selectid'] = 'Search By Date:';
$string['cs_date_type'] = 'Search By Date';

$string['nav_tc_print_examlogs'] = 'Print Exam Logs';

$string['nav_tc_print_exam_schedules']='Print Exam Schedules';

//strings for student_list_form
$string['nav_tc_std_list'] = 'Student list';
$string['std_list_username'] = 'Username:';
$string['std_list_useridno'] = 'UofL ID #:';

////strings for ID Type radio
$string['std_list_useridno_radio'] = 'UofL ID #';
$string['std_list_driver_license_radio'] = 'Drivers\'s License';
$string['std_list_passport_radio'] = 'Passport';
$string['std_list_other_radio'] = 'Other\'s(Please Specify in the comments)';

$string['std_list_exams'] = 'Exams:';
$string['std_list_selectid'] = 'ID Type:';
$string['std_list_machineno'] = 'Machine #:';
$string['std_list_comments'] = 'Comments:';
$string['std_list_open_std'] = 'Enter Test Room';
$string['std_list_clear_fields'] = 'Clear Fields';
$string['std_list_image'] = 'Student Image';
$string['std_list_close_record'] = 'Remove selected user(s)';
$string['std_list_remove_button'] = 'Remove User';
//strings for student_list_form table
$string['std_list_id_header'] = 'id';
$string['std_list_username_header'] = 'Username';
$string['std_list_course_header'] = 'Course';
$string['std_list_machine_header'] = 'Machine #';
$string['std_list_signedup_header'] = 'Time Signed in';
$string['std_list_comments_header'] = 'Comments';

//strings for print_pass_admin_form
$string['nav_tc_print_pass_admin'] = 'Print Exam Passwords';


//strings for dashboard
$string['nav_tc_dashboard'] = 'Test Centre Status';

//strings for course stats
$string['nav_tc_course_stat'] = 'Course Statistics';
$string['course_stat_examname'] = 'Name of the exam:';

//strings for name stats
$string['nav_tc_name_stat'] = 'User Statistics';
//$string['name_stat_username'] = 'Username:';

//strings for week stats
$string['nav_tc_week_stat'] = 'Search By Starting Date of a Week';
//$string['name_stat_username'] = 'Username:';


//strings for settings_page
$string['nav_tc_settings'] = 'Test Centre Settings';
$string['seatno'] = 'Total Number of Seat:';
$string['update_settings'] = 'Update';





//strings for assign_proctor
$string['nav_tc_assign_proctor'] = 'Assign Proctor';
$string['asn_proctor_exams'] = 'Exams:';


//strings for user_override_form
$string['nav_tc_usr_override'] = 'User Override';
$string['usr_override_username'] = 'Username:';
$string['usr_override_useridno'] = 'UofL ID #:';


////strings for ID Type radio
$string['usr_override_useridno_radio'] = 'UofL ID #';
$string['usr_override_driver_license_radio'] = 'Drivers\'s License';
$string['usr_override_passport_radio'] = 'Passport';
$string['usr_override_other_radio'] = 'Other\'s(Please Specify in the comments)';

$string['usr_override_exams'] = 'Exams:';
$string['usr_override_selectid'] = 'ID Type:';
$string['usr_override_machineno'] = 'Machine #:';
$string['usr_override_comments'] = 'Comments:';
$string['usr_override_open_std'] = 'Open Student';
$string['usr_override_image'] = 'Student Image';
$string['usr_override_close_record'] = 'Close selected record';


//strings for student_list_form table
$string['usr_override_id_header'] = 'id';
$string['usr_override_username_header'] = 'Username';
$string['usr_override_course_header'] = 'Course';
$string['usr_override_machine_header'] = 'Machine #';
$string['usr_override_signedup_header'] = 'Time Signed in';
$string['usr_override_comments_header'] = 'Comments';

$string['usr_override_fullname_header'] = 'Full Name';
$string['usr_override_studentid_header'] = 'UofL ID';
$string['usr_override_email_header'] = 'Email';

$string['usr_override_timeopen_header'] = 'Time Open';
$string['usr_override_timeclose_header'] = 'Time Close';
$string['usr_override_timelimit_header'] = 'Time Limit';
$string['usr_override_attempts_header'] = 'Attempts';
$string['usr_override_password_header'] = 'Password';
$string['usr_override_subnet_header'] = 'Subnet';

//Calendar
$string['local_tcs_calendar_main_title'] = 'TCS Calendar';
$string['local_tcs_calendar_range'] = 'TCS Calendar Range';

// UI Settings
$string['local_tcs_dashboard_settings_main_title'] = 'Dashboard Settings';
// # of Rooms in the Test Centre
$string['local_tcs_dash_rooms_title'] = 'Number of Rooms Available';
// Refresh rate for stats
$string['local_tcs_dash_refresh_rate_title'] = 'How often to refresh stats? (seconds)';

// Hit enter to complete search?
$string['local_tcs_auto_comp_click_finish_title'] = 'Hit enter to complete search or auto add when only 1 option left (while typing)';

// Random
$string['local_tcs_side_by_side_title'] = 'Run Old and New';

$string['local_tcs_dash_stat_cards_title'] = 'Dashboard Stat Cards';

$string['local_tcs_query_main_title'] = 'Exam Query Options';
$string['local_tcs_query_iprestricted_exams_title'] = 'Show only exams with some IP Restrictions.';
$string['local_tcs_query_closed_exams_title'] = 'Include all exams that are closed/expired.';
