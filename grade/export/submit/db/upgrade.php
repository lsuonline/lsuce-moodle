<?php

/**
 * Grade Submit module upgrade
 *
 * gradeexport_submit
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_gradeexport_submit_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011112001) {

        // Define table grade_submit_lmb_submissions to be created
        $table = new xmldb_table('grade_submit_lmb_submissions');

        // Adding fields to table grade_submit_lmb_submissions
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('course_sourcedid', XMLDB_TYPE_CHAR, '200', null, null, null, null);
        $table->add_field('submitter_userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('submitter_sourcedid', XMLDB_TYPE_CHAR, '200', null, null, null, null);
        $table->add_field('timesubmitted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('response_received', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('succeeded', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('error_message', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('raw_xml', XMLDB_TYPE_TEXT, 'big', null, null, null, null);

        // Adding keys to table grade_submit_lmb_submissions
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for grade_submit_lmb_submissions
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // submit savepoint reached
        upgrade_plugin_savepoint(true, XXXXXXXXXX, 'gradeexport', 'submit');
    }


    return true;
}


