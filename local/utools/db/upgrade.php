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

defined('MOODLE_INTERNAL') || die();

// error_log("utools upgrade.php page was hit");


function xmldb_local_utools_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    
    // 2014031917
    if ($oldversion < 2014072806) {
     // Define table evaluatons_mail_que to be created
        $table = new xmldb_table('utools_selenium_results');

        // Adding fields to table evaluatons_mail_que
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('build_id', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('build_no', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('url', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('result', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '20', null, null, null, 0, null);
        $table->add_field('extra_url', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('build_date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        
        // Adding keys to table evaluatons_mail_que
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for evaluatons_mail_que
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
      
        // evaluations savepoint reached
        //upgrade_plugin_savepoint(true, 2014031914, 'local', 'utools');
    }
    
    
    if ($oldversion < 2014072806) {
        // Define field timecheckstate to be added to quiz_attempts
        $table = new xmldb_table('utools_selenium_results');
        $field = new xmldb_field('extra_url', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'extra_build_url');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->drop_field($table, $field);
            $dbman->add_field($table, $field);
        }
        //upgrade_plugin_savepoint(true, 2014031914, 'local', 'utools');
        
    }
    
    if ($oldversion < 2014072806) {
        // Define field timecheckstate to be added to quiz_attempts
        $table = new xmldb_table('utools_selenium_results');
        $field = new xmldb_field('build_date', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'date_of_build');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->drop_field($table, $field);
            $dbman->add_field($table, $field);
        }
        //upgrade_plugin_savepoint(true, 2014031914, 'local', 'utools');
    }

    // error_log("\n\n");
    // error_log("utools upgrade.php -> now checking to install");
    // error_log("\n\n");

    if ($oldversion < 2015020502) {
        //New table to be created
        // error_log("utools upgrade.php -> YES adding new table");
        $table = new xmldb_table('utools_user_access');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null,
                XMLDB_NOTNULL, null, null);
        $table->add_field('role', XMLDB_TYPE_TEXT, 'small', null,
                XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for this table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    }

     if ($oldversion < 2015091710) {
        //New table to be created
        // error_log("utools course stat.php -> YES adding new table");
        $table = new xmldb_table('utools_course_stat');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('term', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('visible_course', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('invisible_course', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('outcome', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gradebook', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('page_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('book_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('imscp_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('file_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('quiz_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('forum_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('feed_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('wiki_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('glossary_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('chat_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lesson_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('assignment_used', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('date', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);


        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for this table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    }

    if ($oldversion < 2015110619) {
        //New table to be created
        $table = new xmldb_table('utools_developer_suite');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jira_release', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('jira_start_date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('jira_release_date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('func_passed_test', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('func_failed_test', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('func_aborted_test', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('unit_test', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('load_test', XMLDB_TYPE_INTEGER, '20', null, null, null, null);


        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for this table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        } else {
            // old one has bad table values, remove as this is on production but has no data.
            $dbman->drop_table($table);
            // now create with correct attributes.
            $dbman->create_table($table);
        }

    }

    if ($oldversion < 2016100502) {

        $table = new xmldb_table('utools_user_access');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('role', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for this table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        } else {
            // old one has bad table values, remove as this is on production but has no data.
            $dbman->drop_table($table);
            // now create with correct attributes.
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2018110101) {
        $table = new xmldb_table('utools_user_stat');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('semester', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hour', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('uniq_visitors', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visits', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('actions', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('max_actions', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sum_visit_length', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('bounce_count', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visits_converted', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('total_logins', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdate', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for this table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        } else {
            // old one has bad table values, remove as this is on production but has no data.
            $dbman->drop_table($table);
            // now create with correct attributes.
            $dbman->create_table($table);
        }
    }

    return true;
}
