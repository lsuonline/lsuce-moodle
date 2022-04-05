<?php

/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */
    global $CFG;
    // Link to get back
    $string['utools_link_back_title'] = '<a href="'.$CFG->wwwroot.'/local/utools/">Back to Utools</a>';

    // General Settings
    $string['local_utools_use_js_ajax_title'] = 'Load JS with AJAX';
    $string['local_utools_use_js_ajax_help'] = 'Load all the widgets js files using AJAX?';
    
    $string['utools_semester_title'] = 'Current Semester';
    $string['utools_semester_help'] = 'Current Semester we are on, for ex: 201901';


    
    //Dashboard Refresh
    $string['uofl_webstats'] = 'Dashboard Refresh Rate';
    $string['webstats_autorefreshtime'] = 'Auto refresh time';
    $string['configwebstats_autorefreshtime'] = 'Number of seconds determining the period of time between two consecutive auto refreshes.';


    // scheduler
    $string['course_stat_name'] = 'Course Stat Task';
    $string['user_stat_name'] = 'User Stat Task';

    // New Relic Widget Settings
    $string['local_utools_newrelic_title'] = 'New Relic Settings';
    $string['local_utools_newrelic_interval_time_title'] = 'Refresh Timer for New Relic Stats';
    $string['local_utools_newrelic_enabled_title'] = 'Enable New Relic Widget';
    $string['local_utools_newrelic_enabled_help'] = 'Enable or disable this app.';
    $string['local_utools_newrelic_logging_title'] = 'Developer Logs.';
    $string['local_utools_newrelic_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';

    $string['local_utools_newrelic_auth_token_title'] = 'Token.';
    $string['local_utools_newrelic_auth_token_help'] = 'Auth Token from New Relic';
    $string['local_utools_newrelic_server_ids_title'] = 'Server Ids';
    $string['local_utools_newrelic_server_ids_help'] = 'List of comma seperated id numbers (no spaces, for example: 3351351,1234123';
    $string['local_utools_newrelic_server_names_title'] = 'Server Names';
    $string['local_utools_newrelic_server_names_help'] = 'Name your corresponding list of servers (no spaces, for example: Server1,Server2';
    
    $string['local_utools_newrelic_iframes_title'] = 'Add iframe links to the New Relic widget';
    $string['local_utools_newrelic_iframes_help'] = 'use semicolon (;) for multiple iframes.';

    // Piwik Widget Settings
    $string['local_utools_piwik_title'] = 'Piwik Settings';
    $string['local_utools_piwik_interval_time_title'] = 'Refresh Timer for Piwik Stats';
    $string['local_utools_piwik_enabled_title'] = 'Enable Piwik Widget';
    $string['local_utools_piwik_enabled_help'] = 'Enable or disable this app.';
    $string['local_utools_piwik_logging_title'] = 'Developer Logs.';
    $string['local_utools_piwik_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';
    // JMeter Widget Settings
    $string['local_utools_jmeter_title'] = 'JMeter Settings';
    $string['local_utools_jmeter_enabled_title'] = 'Enable JMeter Widget';
    $string['local_utools_jmeter_enabled_help'] = 'Enable or disable this app.';
    $string['local_utools_jmeter_logging_title'] = 'Developer Logs.';
    $string['local_utools_jmeter_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';

    // Course Stat Widget Settings
    $string['local_utools_coursestat_title'] = 'Course Stat Settings';
    $string['local_utools_coursestat_enabled_title'] = 'Enable Course Stat Widget';
    $string['local_utools_coursestat_enabled_help'] = 'Enable or disable this app.';
    $string['local_utools_coursestat_logging_title'] = 'Developer Logs.';
    $string['local_utools_coursestat_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';

 // Developer Suite Widget Settings
    $string['local_utools_devsuite_title'] = 'Developer Suite Settings';
    $string['local_utools_devsuite_enabled_title'] = 'Enable Developer Suite Widget';
    $string['local_utools_devsuite_enabled_help'] = 'Enable or disable this app.';
    $string['local_utools_devsuite_logging_title'] = 'Developer Logs.';
    $string['local_utools_devsuite_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';

    // TCMS Widget Settings
    $string['local_utools_tcms_title'] = 'Test Centre Management System Settings';
    $string['local_utools_tcms_instance_title'] = 'TCMS Instance';
    $string['local_utools_tcms_instance_help'] = 'For Ex: moodle.uleth.ca/201501/'.
            '<br>This will reach out to the instance that has the TCMS data we want to see.'.
            '<br>Leave this blank to reach the current server.';
    
        // hours of op
    $string['local_utools_tcms_hours_of_op_start_title'] = 'Test Centre Opening Time';
    $string['local_utools_tcms_hours_of_op_start_help'] = 'Use 12 hour format: 9:00am';
    $string['local_utools_tcms_hours_of_op_stop_title'] = 'Test Centre Opening Time';
    $string['local_utools_tcms_hours_of_op_stop_help'] = 'Use 12 hour format: 9:00pm';
        // semester window
    $string['local_utools_tcms_total_exams_start_title'] = 'Total Exams Start Date';
    $string['local_utools_tcms_total_exams_end_title'] = 'Total Exams End Date';
    $string['local_utools_tcms_interval_time_title'] = 'Refresh Timer for TCMS Stats';
    $string['local_utools_tcms_enabled_title'] = 'Enable TCMS Widget';
    $string['local_utools_tcms_enabled_help'] = 'Enable or disable this app.';
    $string['local_utools_tcms_logging_title'] = 'Developer Logs.';
    $string['local_utools_tcms_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';

    

    // LOGGING
    $string['local_utools_logging_main_title'] = 'Developer Logs';
    $string['local_utools_logging'] = 'Set master Level';
    $string['local_utools_logging_help'] = '0 - off'.
        '<br>1 - php console only'.
        '<br>2 - js console only'.
        '<br>3 - both';

    // WEB Services
    $string['utools_web_title'] = 'Web Services';
    $string['utools_web_token'] = 'Web token to use';
    $string['utools_web_func'] = 'Web function to call';
    $string['web_service_token'] = 'Web Token';

    $string['nav_ult_mn'] = 'University of Lethbridge Tools';
    $string['pluginname'] = 'Utools';
    $string['settingsname'] = 'U of L Settings';
    $string['settings'] = 'UofL Tool Settings';

    // Restoring courses
    $string['list'] = 'Edit list of restore backups';
    $string['backupfiles'] = 'Backup files';
    $string['error'] = 'An error occurred';
    $string['viewcourse'] = 'View course (new window)';
    $string['deletecourse'] = 'Delete course (new window)';
    $string['restoreagain'] = 'Restore again';
    $string['restoredone'] = 'Restore completed';
    $string['acronym'] = '(Backup &amp; Restore Operations On Moodle)';

    // JMeter Widget Settings
    $string['local_utools_jmeter_heading'] = 'Edit list of JMeter files';
    $string['local_utools_jmeter_files'] = 'JMeter Files';
    $string['local_utools_jmeter_edit_list'] = 'Edit list of JMeter Files';

    // Reports
    $string['utools_reports'] = 'Analytics and Reports';
    
    // Enrollments & Logging
    
    $string['utools_general_settings_title'] = 'General Settings';
    $string['courseUsersEnrolled'] = 'View Enrolled User Counts in Courses';
    $string['local_enrol_log'] = 'View the LMB log file';
    $string['LMB_refresh_timer'] = 'Frequency to refresh LMB AJAX calls';
    $string['LMB_instance'] = 'Which Instance to view';
    
    // DB Checks
    $string['FDCR'] = 'Fix Duplicate Course Restore';
    $string['pluginname'] = 'Utools';
    // Mass Enroll
    $string['mass_enroll'] = 'Bulk enrolments';

    $string['mass_enroll_info'] = "
    <p>
    With this option you are going to enrol a list of known users from a file with one account per line
    </p>
    <p>
    <b> The firstline </b> the empty lines or unknown accounts will be skipped. </p>
    <p>
    The file may contains one or two columns, separated by a comma, a semi-column or a tabulation.
    <br/>
    <b>The first one must contains a unique account identifier : idnumber (by default) login or email  </b> of the target user. <br/>
    The second <b>if present,</b> contains the group's name in wich you want that user be be added. <br/>
    You may repeat this operation at will without dammages, for example if you forgot or mispelled the target group.
    </p>
    ";

    $string['enroll'] = 'Enrol them to my course';
    $string['mailreport'] = 'Send me a mail report';
    $string['creategroups'] = 'Create group(s) if needed';
    $string['creategroupings'] = 'Create  grouping(s) if needed';
    $string['firstcolumn'] = 'First column contains';
    $string['roleassign'] = 'Role to assign';
    $string['idnumber'] = 'Id number';
    $string['username'] = 'Login';
    $string['mail_enrolment_subject'] = 'Bulk enrolments on {$a}';
    $string['mail_enrolment']='
    Hello,
    You just enroled the following list of users to your course \'{$a->course}\'.
    Here is a report of operations :
    {$a->report}
    Sincerly.
    ';
    $string['email_sent'] = 'email sent to {$a}';
    $string['im:opening_file'] = 'Opening file : {$a} ';
    $string['im:user_unknown'] = '{$a} unknown - skipping line';
    $string['im:already_in'] = '{$a} already enroled ';
    $string['im:enrolled_ok'] = '{$a} enroled ';
    $string['im:error_in'] = 'error enroling {$a}';
    $string['im:error_addg'] = 'error adding group {$a->groupe}  to course {$a->courseid} ';
    $string['im:error_g_unknown'] = 'error unkown group {$a} ';
    $string['im:error_add_grp'] = 'error adding grouping {$a->groupe} to course {$a->courseid}';
    $string['im:error_add_g_grp'] = 'error adding group {$a->groupe} to grouping {$a->groupe}';
    $string['im:and_added_g'] = ' and added to Moodle\'s  group  {$a}';
    $string['im:error_adding_u_g'] = 'error adding to group  {$a}';
    $string['im:already_in_g'] = ' already in group {$a}';
    $string['im:stats_i'] = '{$a} enroled';
    $string['im:stats_g'] = '{$a->nb} group(s) created : {$a->what}';
    $string['im:stats_grp'] = '{$a->nb} grouping(s) created : {$a->what}';
    $string['im:err_opening_file'] = 'error opening file {$a}';


    $string['mass_enroll_help'] = "<h1>Bulk enrolments</h1>
    
    <p>
    With this option you are going to enrol a list of known users from a file with one account per line
    </p>
    <p>
    <b> The firstline </b> the empty lines or unknown accounts will be skipped. </p>

    <p>
    The file may contains one or two columns, separated by a comma, a semi-column or a tabulation.

    You should prepare it from your usual spreadsheet program from official lists of students, for example,
    and add if needed a column with groups to which you want these users to be added. Finally export it as CSV. (*)</p>

    <p>
    <b> The first one must contains a unique account identifier </b>: idnumber (by default) login or email  of the target user. (**). </p>

    <p>
    The second <b>if present,</b> contains the group's name in wich you want that user to be added. </p>

    <p>
    If the group name does not exist, it will be created in your course, together with a grouping of the same name to which the group will be added.
    .<br/>
    This is due to the fact that in Moodle, activities can be restricted to groupings (group of groups), not groups,
     so it will make your life easier. (this requires that groupings are enabled by your site administrator).

    <p>
    You may have in the same file different target groups or no groups for some accounts
    </p>

    <p>
    You may unselect options to create groups and groupings if you are sure that they already exist in the course.
    </p>

    <p>
    By default the users will be enroled as students but you may select other roles that you are allowed to manage (teacher, non editing teacher
    or any custom roles)
    </p>

    <p>
    You may repeat this operation at will without dammages, for example if you forgot or mispelled the target group.
    </p>


    <h2> Sample files </h2>

    Id numbers and a group name to be created in needed in the course (*)
    <pre>
    'idnumber';'group'
    ' 2513110';' 4GEN'
    ' 2512334';' 4GEN'
    ' 2314149';' 4GEN'
    ' 2514854';' 4GEN'
    ' 2734431';' 4GEN'
    ' 2514934';' 4GEN'
    ' 2631955';' 4GEN'
    ' 2512459';' 4GEN'
    ' 2510841';' 4GEN'
    </pre>

    only idnumbers (**)
    <pre>
    idnumber
    2513110
    2512334
    2314149
    2514854
    2734431
    2514934
    2631955
    </pre>

    only emails (**)
    <pre>
    email
    toto@insa-lyon.fr
    titi@]insa-lyon.fr
    tutu@insa-lyon.fr
    </pre>

    usernames and groups, separated by a tab :

    <pre>
    username     group
    ppollet      groupe_de_test              will be in that group
    codet        groupe_de_test              also him
    astorck      autre_groupe                will be in another group
    yjayet                                   no group for this one
                                             empty line skipped
    unknown                                  unknown account skipped
    </pre>

    <p>
    <span <font color='red'>(*) </font></span>: double quotes and spaces, added by some spreadsheet programs will be removed.
    </p>

    <p>
    <span <font color='red'>(**) </font></span>: target account must exit in Moodle ; this is normally the case if Moodle is synchronized with
    some external directory (LDAP...)
    </p>
";
