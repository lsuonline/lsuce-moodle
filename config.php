<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'lsuonline.cluster-cfeugorgzbik.us-east-2.rds.amazonaws.com';
$CFG->dbname    = 'testrusso_CE39';
$CFG->dbuser    = 'admin';
$CFG->dbpass    = '7raGQ5MLeCq9WQtna51ZaT6QmqakcApF';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->sslproxy  = true;
$CFG->wwwroot   = 'https://testrusso.lsu.edu/ce39';
$CFG->dataroot  = '/var/moodledata/ce39';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
