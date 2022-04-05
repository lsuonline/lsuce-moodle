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

require_once('../../../config.php');
include_once('UtoolsLib.php');

/* object for all uleth functions */
$ulethlib = new UtoolsLib();
// error_log("\n\nWhat is CFG->dirroot: ". $CFG->dirroot);

if ($ulethlib->isLocal() == false || $ulethlib->isLocal() == "0") {
    $cmd = "tail -100 /moodle/logs/enrol/".$ulethlib->getCurrentTerm()."".$ulethlib->getLogInstance().".log";
    // error_log(
    //     "What is the log to view: /moodle/logs/enrol/".
    //     $ulethlib->getCurrentTerm()."".$ulethlib->getLogInstance().
    //     ".log"
    // );
} else {
    $cmd = "tail -100 " . $CFG->dirroot . "/../logs/enrol_".$ulethlib->getCurrentTerm().".log";
    // error_log("Viewing this log: " . $CFG->dirroot . "/../logs/enrol_".$ulethlib->getCurrentTerm().".log");
}

exec("$cmd 2>&1", $output);

foreach ($output as $outputline) {
    echo ("$outputline\n");
}
