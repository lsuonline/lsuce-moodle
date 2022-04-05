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

// require(dirname(dirname(__FILE__)) . '/config.php');

if (isset($_GET['pageURL'])) {
    if ($_GET['pageURL'] == 1) {
        echo("<script>alert('Page URL is: ".curPageURL()."');</script>");
        //https://moodle-dev.uleth.ca/201302/my/?pageURL=1
    }
}

if (isset($_GET['printLogToPage'])) {
    if ($_GET['printLogToPage'] == 1) {
        print_to_page();
    }
}

function print_to_umoodle_log($string) {
    
    global $CFG;

    $today = date("F j, Y, g:i a");

    $uleth_word = "uleth.ca";

    if (strpos($CFG->wwwroot, $uleth_word) !== false) {
        $fp = fopen('/Users/davidlowe/Sites/logs/umoodle_custom.log', 'a+');
        fwrite($fp, $today.": ".$string." \n");
    } else {
        $fp = fopen('/moodle/dump/umoodle.log', 'a+');
        fwrite($fp, $today.": ".$string." \n");
    }
    
    fclose($fp);
}

function print_to_page() {

    $data = file('/moodle/dump/umoodle.log');
    $lines = implode("\r\n", array_slice($data, count($data)-51, 50));
    $count = count($lines);
    
    echo("<br>What is the count: ".$count);

    for ($i=0; $i<$count; $i++) {
        echo("<br>".$line);
    }
    echo("<br><br><br><br><br><br><br>A nasty clump:<br><br>".$lines);

}


function curPageURL() {
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}
