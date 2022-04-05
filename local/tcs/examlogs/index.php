<?php

// error_log("\n\n <<<<<------------------- HIT THE REDIRECT ------------------->>>>>\n\n");
require_once(dirname(dirname(dirname(dirname(__FILE__)))). '/config.php');

// header('Location: http://'.$_SERVER['HTTP_HOST'].'/local/tcs/index.php?page=examlist');

header('Location: '.$CFG->wwwroot.  '/local/tcs/index.php?page=examlogs');

/*
The purpose of this page is only to redirect for the LiveReload plugin.......if you are using it............ 
*/