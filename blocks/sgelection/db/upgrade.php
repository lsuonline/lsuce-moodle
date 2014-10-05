<?php


function xmldb_block_sgelection_upgrade($oldversion) {
    global $CFG, $DB;

    $result = TRUE;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014100211) {
        require_once $CFG->dirroot.'/blocks/sgelection/classes/vote.php';

        $olddata = $DB->get_records(vote::$tablename);
        // update field values to new type
        foreach($olddata as $od){
            $od->type = $od->type == 'candidate' ? 'C' : 'R';
            $DB->update_record(vote::$tablename, $od);
        }

        // Changing type of field type on table block_sgelection_votes to char.
        $table = new xmldb_table('block_sgelection_votes');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, null, 'typeid');

        // Launch change of type for field type.
        $dbman->change_field_type($table, $field);

        // Sgelection savepoint reached.
        upgrade_block_savepoint(true, 2014100211, 'sgelection');
    }

    if ($oldversion < 2014100212) {


        // Add field election_id to the following tables.
        $addto = array('voter' => 'time');
        foreach($addto as $class => $after){
            $tablename = 'block_sgelection_'.$class.'s';

            // Define field election_id to be added to block_sgelection_voters.
            $table = new xmldb_table($tablename);
            $nullablefield = new xmldb_field('election_id', XMLDB_TYPE_INTEGER, '2', null, null, null, null, $after);

            // Conditionally launch add field election_id.
            if (!$dbman->field_exists($table, $nullablefield)) {
                $dbman->add_field($table, $nullablefield);

                $olddata = $DB->get_records(voter::$tablename);
                foreach($DB->get_records(voter::$tablename) as $row){
                    $row->election_id = 0;
                    $DB->update_record($class::$tablename, $row);
                }

                $field = new xmldb_field('election_id', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', $after);

                // Launch change of nullability for field election_id.
                $dbman->change_field_notnull($table, $field);
            }
        }

        // Sgelection savepoint reached.
        upgrade_block_savepoint(true, 2014100212, 'sgelection');
    }

    return $result;
}
