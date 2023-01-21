<?php
    
    define('CLI_SCRIPT', true);
    use enrol_d1\jenzabar\helpers;

    require_once('helpers.php');

    $sfile = "/Users/davidlowe/Sites/moodle_data/d1debug/importer/student/XStudent_Jan15_Final.csv";
    $sfileh = fopen($sfile, "r");

    $nsfile = "/Users/davidlowe/Sites/moodle_data/d1debug/importer/student/XStudent_Jan15_Final_Updated.csv";
    $nsfileh = fopen($nsfile, "a");
    
    error_log("Going to fack it");
    $array = array();
    $counter = 0;
    if (($sfileh = fopen($sfile, "r")) !== FALSE) {
        while (($rowdata = fgetcsv($sfileh, 5000, ",")) !== FALSE) {

            error_log("\rFacking row: ". $counter);
            $rowdata[13] = helpers::cleanUsername($rowdata[13]);
            fputcsv($nsfileh, $rowdata);
            $counter++;
        }
    }
    fclose($sfileh);
    fclose($nsfileh);
    error_log("-------- Done --------");
