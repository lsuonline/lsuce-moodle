<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Test Centre Management System                            **
 * @name        tcms                                                     **
 * @author      David Lowe                                               **
 * @author      Nawshad Farruque                                         **
 * ************************************************************************
 * ********************************************************************* */
/**
 * This file keeps track of upgrades to the newmodule module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 */
defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_newmodule_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_tcs_upgrade($oldversion) {
    
    global $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2014040134) {

        $local_testcentre_exam_pass_table = new xmldb_table('local_tcms_exam_pass');
        $local_testcentre_exam_pass_status = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,  '0');
     
       
        if (! $dbman->field_exists($local_testcentre_exam_pass_table, $local_testcentre_exam_pass_status)) {
            $dbman->add_field($local_testcentre_exam_pass_table, $local_testcentre_exam_pass_status);
            //upgrade_plugin_savepoint(true, 2014040122, 'local', 'tcms');
        }
    
        $local_testcentre_std_entry_table = new xmldb_table('local_tcms_std_entry');
        $local_testcentre_exam_type = new xmldb_field('exam_type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
       
        if (! $dbman->field_exists($local_testcentre_std_entry_table, $local_testcentre_exam_type)) {
            $dbman->add_field($local_testcentre_std_entry_table, $local_testcentre_exam_type);
            //upgrade_plugin_savepoint(true, 2014040122, 'local', 'tcms');
        }
       
       $local_testcentre_exam_status_table = new xmldb_table('local_tcms_exam');
 
       
        $local_testcentre_exam_status_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $local_testcentre_exam_status_table->add_field('examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $local_testcentre_exam_status_table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $local_testcentre_exam_status_table->add_field('notes', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $local_testcentre_exam_status_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
       
      
        if (!$dbman->table_exists($local_testcentre_exam_status_table)) {
            $dbman->create_table($local_testcentre_exam_status_table);
        }

        $local_testcentre_manualexam_std_table = new xmldb_table('local_tcms_manualexam_std');
 
        $local_testcentre_manualexam_std_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $local_testcentre_manualexam_std_table->add_field('manual_examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $local_testcentre_manualexam_std_table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '0');
        $local_testcentre_manualexam_std_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
     
        if (!$dbman->table_exists($local_testcentre_manualexam_std_table)) {
            $dbman->create_table($local_testcentre_manualexam_std_table);
        }

        //upgrade_plugin_savepoint(true, 2014040125, 'local', 'tcms');


    }

    if ($oldversion < 2015041405) {
        $local_testcentre_student_status_table = new xmldb_table('local_tcms_student_stats');
        // $local_testcentre_exam_pass_status = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,  '0');
        $local_testcentre_student_status_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $local_testcentre_student_status_table->add_field('count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $local_testcentre_student_status_table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $local_testcentre_student_status_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
       
        if (!$dbman->table_exists($local_testcentre_student_status_table)) {
            $dbman->create_table($local_testcentre_student_status_table);
        }
    }

    if ($oldversion < 2019050500) {

        // ======================================================
        // mdl_local_tcms_student_entry
        // ======================================================
        error_log("\n\nlocal/tcs -> upgrade.php -> Version: 2019050501 ---->>> STARTING <<<----");
        // need to rename the table from local_tcms_std_entry to mdl_local_tcms_student_entry
        $tcs_std_entry = new xmldb_table('local_tcms_std_entry');
        $dbman->rename_table($tcs_std_entry, 'local_tcms_student_entry');
        
        // changed deleted to finished
        $tcs_student_entry = new xmldb_table('local_tcms_student_entry');
        $tcs_stud_ent_deleted = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,  '0');
        $dbman->rename_field($tcs_student_entry, $tcs_stud_ent_deleted, 'finished');

        // last_changed column added (big int, 0 as def)
        // $local_testcentre_std_entry_table = new xmldb_table('local_tcms_student_entry');
        $local_testcentre_last_changed = new xmldb_field('last_changed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (! $dbman->field_exists('local_tcms_student_entry', 'last_changed')) {
            $dbman->add_field($tcs_student_entry, $local_testcentre_last_changed);
        }
        
        // room column added (char varying, length 5)
        $local_testcentre_room = new xmldb_field('room', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, '1');
        if (! $dbman->field_exists('local_tcms_student_entry', $local_testcentre_room)) {
            $dbman->add_field($tcs_student_entry, $local_testcentre_room);
        }
        // ======================================================
        // mdl_local_tcms_exam_pass
        // ======================================================
        // changed deleted to finished
        $tcs_exam_pass = new xmldb_table('local_tcms_exam_pass');
        if ($dbman->field_exists('local_tcms_exam_pass', 'deleted')) {
            $tcs_exam_pass_deleted = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,  '0');
            $dbman->rename_field($tcs_exam_pass, $tcs_exam_pass_deleted, 'finished');
        }

        // ======================================================
        // local_tcms_exam
        // ======================================================
        if (!$dbman->table_exists('local_tcms_exam')) {
            error_log("\n\n*********************************************************************************");
            error_log("\n\nSupposedly the table does NOT exist, going to create now......\n\n");
            $local_testcentre_exam = new xmldb_table('local_tcms_exam');
            // $local_testcentre_exam_pass_status = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,  '0');
            $local_testcentre_exam->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $local_testcentre_exam->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $local_testcentre_exam->add_field('course_name', XMLDB_TYPE_CHAR, '255', null, null, null, '0');
            $local_testcentre_exam->add_field('exam_id', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $local_testcentre_exam->add_field('exam_name', XMLDB_TYPE_CHAR, '255', null, null, null, '0');
            $local_testcentre_exam->add_field('opening_date', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $local_testcentre_exam->add_field('closing_date', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $local_testcentre_exam->add_field('password', XMLDB_TYPE_CHAR, '100', null, null, null, '0');
            $local_testcentre_exam->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null);
            $local_testcentre_exam->add_field('finished', XMLDB_TYPE_CHAR, '10', null, null, null, 'false');
            $local_testcentre_exam->add_field('visible', XMLDB_TYPE_CHAR, '10', null, null, null, 'true');
            $local_testcentre_exam->add_field('manual', XMLDB_TYPE_CHAR, '10', null, null, null, 'false');
            
            $local_testcentre_exam->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
           
            $dbman->create_table($local_testcentre_exam);
            error_log("*********************************************************************************\n\n");
        } else {
            error_log("\n\n++++++++++++++++++++++++ Table Exists, carry on......++++++++++++++++++++++++\n\n");
        }

        // ======================================================
        // local_settings
        // ======================================================
        $tcs_settings = new xmldb_table('local_tcms_settings');
        if (! $dbman->field_exists('local_tcms_settings', 't_name')) {
            $local_tcs_t_name = new xmldb_field('t_name', XMLDB_TYPE_CHAR, null, null, null, null, '');
            $dbman->add_field($tcs_settings, $local_tcs_t_name);
        }
        if (! $dbman->field_exists('local_tcms_settings', 't_value')) {
            $local_tcs_t_value = new xmldb_field('t_value', XMLDB_TYPE_CHAR, null, null, null, null, '');
            $dbman->add_field($tcs_settings, $local_tcs_t_value);
        }
        // now remove old total_seats
        if ($dbman->field_exists('local_tcms_settings', 'total_seats')) {
            $dbman->drop_field($tcs_settings, 'total_seats');
        }

        // ======================================================
        // mdl_local_tcms_manualexam_std
        // ======================================================
        // need to rename the table from local_tcms_std_entry to mdl_local_tcms_student_entry
        $tcs_manexam_std = new xmldb_table('local_tcms_manualexam_std');
        $dbman->rename_table($tcs_manexam_std, 'local_tcms_manual_exams');
        
        $tcs_manual_exams = new xmldb_table('local_tcms_manual_exams');
        if ($dbman->field_exists('local_tcms_manual_exams', 'manual_examid')) {
            $tcs_man_ex = new xmldb_field('manual_examid', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,  '0');
            $dbman->rename_field($tcs_manual_exams, $tcs_man_ex, 'manual_exam_id');
        }
        
        // ======================================================
        // mdl_local_tcms_user_admin
        // ======================================================
        // need to rename the table from local_tcms_std_entry to mdl_local_tcms_student_entry
        $tcs_user_admin = new xmldb_table('local_tcms_useradmin');
        $dbman->rename_table($tcs_user_admin, 'local_tcms_user_admin');
        
        // if ($dbman->field_exists('local_tcms_user_admin', 'manual_examid')) {
        //     $dbman->rename_field('local_tcms_user_admin', 'manual_examid', 'manual_exam_id');
        // }

    }
    return true;
}
