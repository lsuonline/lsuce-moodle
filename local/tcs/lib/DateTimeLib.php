<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */
/**
 * Summary.
 *
 * Description.
 *
 * @since x.x.x
 *
 */
class DateTimeLib
{
    public function __construct()
    {
        // global $CFG;
        // $CFG->local_tcs_logging ? error_log("\n Stats -> constructor()") : null;
    }

    /**
     * 
     *
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function epochPretty($time)
    {
        $hour = $time / 3600 % 24;    // to get hours
        $minute = $time / 60 % 60;    // to get minutes
        $second = $time % 60;         // to get seconds

        $final_output = ($hour ? $hour."h " : "") . ($minute ? $minute."m " : "") . ($second ? $second."s" : "");
        // error_log("What is the FINAL Output: ". $final_output);
        return $final_output;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description. Default.
     * @return type Description.
     */
    public function epochTimeDiff($t1, $t2)
    {
        $time_diff = new stdClass();

        $time_diff->totalSeconds = abs($t1-$t2);
        $time_diff->date = getdate($totalSeconds); 
        $time_diff->firstYear = getdate(0);
        $time_diff->years = $date['year']-$firstYear['year'];
        $time_diff->months = $date['mon'];
        $time_diff->days = $date['mday'];
        $time_diff->hours = $date['hour'];
        $time_diff->minutes = $date['minutes'];
        $time_diff->seconds = $date['seconds'];

        // return "$years Years, $months Months, $days Days, $hours Hours, $minutes Minutes & $seconds Seconds.";
        return $time_diff;
    }


    public function getLongestTime()
    {
        global $DB;
    }
    public function getFastestTime()
    {
        global $DB;
    }
    public function placeholder()
    {
        return 80085;
    }
}
