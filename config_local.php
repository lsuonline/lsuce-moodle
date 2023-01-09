<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

//=========================================================================
// 1. DATABASE SETUP
//=========================================================================
// First, you need to configure the database where all Moodle data       //
// will be stored.  This database must already have been created         //
// and a username/password created to access it.

$CFG->dbtype    = 'mysqli';
$CFG->dbuser    = 'root';
$CFG->dbpass    = '3Goodgirls';


// $CFG->dbtype    = 'pgsql';
// $CFG->dbuser    = 'davidlowe';
// $CFG->dbpass    = '123456';

$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';

// $CFG->dbname    = 'lsu_test'; // based on LSU_Base_Apr8
// $CFG->dbname    = 'LSU_Test_Dest'; // based on LSU_Base_Apr8
$CFG->dbname    = 'fourOneVanilla'; // based on LSU_Base_Apr8
// $CFG->dbname    = 'lsu_vanilla2';

$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => false,
  // 'dbport' => '5432',
  'dbsocket' => '',
  // 'dbhandlesoptions' => false,// On PostgreSQL poolers like pgbouncer don't
                                // support advanced options on connection.
                                // If you set those in the database then
                                // the advanced settings will not be sent.

  // 'fetchbuffersize' => 100000, // On PostgreSQL, this option sets a limit
                                // on the number of rows that are fetched into
                                // memory when doing a large recordset query
                                // (e.g. search indexing). Default is 100000.
                                // Uncomment and set to a value to change it,
                                // or zero to turn off the limit. You need to
                                // set to zero if you are using pg_bouncer in
                                // 'transaction' mode (it is fine in 'session'
                                // mode).
);

$CFG->disablelogintoken = true;

$CFG->passwordsalt = 'c7378a709a7fd5225d51c65c77c40247';

// $CFG->allowthemechangeonurl = true;

// DEBUGGING
// --------------------------
// $bugger = false;
$bugger = true;

// MAILHOG
// --------------------------
// $mailhog = true;
$mailhog = false;

// $CFG->sessioncookie='local_201903_';

// $CFG->session_memcached_save_path = '127.0.0.1:22122';
// $CFG->session_memcached_save_path = '142.66.6.234:11223,142.66.6.235:11223';

// $CFG->session_handler_class = '\core\session\memcached';
// $CFG->session_memcached_save_path = "142.66.184.241:11211";
// $CFG->session_memcached_prefix = 'DALO_session_201903_';
// $CFG->session_memcached_acquire_lock_timeout = 120;
// $CFG->session_memcached_lock_expire = 7200;

// $CFG->tool_generator_users_password = "fart";

// node issues:
// https://moodlerooms.github.io/moodle-plugin-ci/CHANGELOG.html

// ALTER DATABASE moodle SET client_encoding = UTF8;
// ALTER DATABASE moodle_201703_uat SET standard_conforming_strings = on;
// ALTER DATABASE moodle SET search_path = 'moodle,public';  -- Optional, if you wish to use a custom schema.


//=========================================================================
// 2. WEB SITE LOCATION
//=========================================================================
// Now you need to tell Moodle where it is located. Specify the full
// web address to where moodle has been installed.  If your web site
// is accessible via multiple URLs then choose the most natural one
// that your students would use.  Do not include a trailing slash
//
// If you need both intranet and Internet access please read
// http://docs.moodle.org/en/masquerading
// error_log("\n\nconfig.php -> WTF1 is this: http://".$_SERVER['SERVER_NAME']."\n\n");
// error_log("\n\nconfig.php -> WTF2 is this: http://".$_SERVER['HTTP_HOST']."\n\n");

$CFG->wwwroot   = 'http://lsut';
// $CFG->wwwroot   = 'http://lowed-crd17.local';
// $CFG->wwwroot   = 'http://'.$_SERVER["HTTP_HOST"];

//=========================================================================
// 3. DATA FILES LOCATION
//=========================================================================
// Now you need a place where Moodle can save uploaded files.  This
// directory should be readable AND WRITEABLE by the web server user
// (usually 'nobody' or 'apache'), but it should not be accessible
// directly via the web.
//
// - On hosting systems you might need to make sure that your "group" has
//   no permissions at all, but that "others" have full permissions.
//
// - On Windows systems you might specify something like 'c:\moodledata'
$CFG->dataroot  = '/Users/davidlowe/Sites/moodle_data/lsut';
// $CFG->dataroot  = '/Users/davidlowe/Sites/moodle_data/lmc_temp';

//=========================================================================
// 4. DATA FILES PERMISSIONS
//=========================================================================
// The following parameter sets the permissions of new directories
// created by Moodle within the data directory.  The format is in
// octal format (as used by the Unix utility chmod, for example).
// The default is usually OK, but you may want to change it to 0750
// if you are concerned about world-access to the files (you will need
// to make sure the web server process (eg Apache) can access the files.
// NOTE: the prefixed 0 is important, and don't use quotes.
$CFG->directorypermissions = 0777;

//=========================================================================
// 5. DIRECTORY LOCATION  (most people can just ignore this setting)
//=========================================================================
// A very few webhosts use /admin as a special URL for you to access a
// control panel or something.  Unfortunately this conflicts with the
// standard location for the Moodle admin pages.  You can work around this
// by renaming the admin directory in your installation, and putting that
// new name here.  eg "moodleadmin".  This should fix all admin links in Moodle.
// After any change you need to visit your new admin directory
// and purge all caches.
$CFG->admin = 'admin';

//=========================================================================
// 6. OTHER MISCELLANEOUS SETTINGS (ignore these for new installations)
//=========================================================================


//=========================================================================
// 7. SETTINGS FOR DEVELOPMENT SERVERS - not intended for production use!!!
//=========================================================================
// Force a debugging mode regardless the settings in the site administration
// @error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
// @ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
// $CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
// $CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!

// You can specify a comma separated list of user ids that that always see
// debug messages, this overrides the debug flag in $CFG->debug and $CFG->debugdisplay
// for these users only.
// $CFG->debugusers = '2';

if ($bugger == true) {
    // error_log("\n===========>>>>>>>> DEBUGGER IS ON <<<<<<<<===========\n");
    // Prevent JS caching
    $CFG->cachejs = false;

    // Prevent theme caching
    $CFG->themedesignermode = true; // NOT FOR PRODUCTION SERVERS!

    // Prevent Template caching
    $CFG->cachetemplates = false; // NOT FOR PRODUCTION SERVERS!

    // Prevent core_string_manager application caching
    $CFG->langstringcache = false; // NOT FOR PRODUCTION SERVERS!
}

// When working with production data on test servers, no emails or other messages
// should ever be send to real users
$CFG->noemailever = true;    // NOT FOR PRODUCTION SERVERS!
//
// Divert all outgoing emails to this address to test and debug emailing features
// $CFG->divertallemailsto = 'root@localhost.local'; // NOT FOR PRODUCTION SERVERS!
$CFG->divertallemailsto = 'dlowe6@lsu.edu'; // NOT FOR PRODUCTION SERVERS!

if ($mailhog == true) {

    // When working with production data on test servers, no emails or other messages
    // should ever be send to real users
    // error_log("\n");
    // error_log("\nMailHog flag is on and will be sending out EMAILS.\n");

    $CFG->noemailever = false;    // NOT FOR PRODUCTION SERVERS!
    //
    // Divert all outgoing emails to this address to test and debug emailing features
    // $CFG->divertallemailsto = 'root@localhost.local'; // NOT FOR PRODUCTION SERVERS!
    // $CFG->divertallemailsto = 'dlowe6@lsu.edu'; // NOT FOR PRODUCTION SERVERS!
}

// Except for certain email addresses you want to let through for testing. Accepts
// a comma separated list of regexes.
// $CFG->divertallemailsexcept = 'tester@dev.com, fred(\+.*)?@example.com'; // NOT FOR PRODUCTION SERVERS!
//
// Uncomment if you want to allow empty comments when modifying install.xml files.
// $CFG->xmldbdisablecommentchecking = true;    // NOT FOR PRODUCTION SERVERS!
//
// Since 2.0 sql queries are not shown during upgrade by default.
// Please note that this setting may produce very long upgrade page on large sites.
// $CFG->upgradeshowsql = true; // NOT FOR PRODUCTION SERVERS!
//
// Add SQL queries to the output of cron, just before their execution
// $CFG->showcronsql = true;
//
// Force developer level debug and add debug info to the output of cron
// $CFG->showcrondebugging = true;

//=========================================================================
// ALL DONE!  To continue installation, visit your main page with a browser
//=========================================================================
require_once(__DIR__ . '/lib/setup.php'); // Do not edit
// require_once($CFG->dirroot.'/admin/tool/userdebug/lib.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
