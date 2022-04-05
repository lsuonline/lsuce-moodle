<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))). '/config.php');
header('Location: '.$CFG->wwwroot.  '/local/tcs/index.php?page=examreqs');
/*
The purpose of this page is only to redirect for the LiveReload plugin.......if you are using it............ 
*/