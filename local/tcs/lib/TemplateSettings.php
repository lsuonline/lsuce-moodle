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
include('Stats.php');


class TemplateSettings extends Stats
{
    public function __construct()
    {
        global $CFG;
        $CFG->local_tcs_logging ? error_log("\n TemplateSettings -> constructor()") : null;
    }

   

}
