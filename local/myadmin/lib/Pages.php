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

class Pages
{
    private $pages_list;

    public function __construct()
    {
        $this->pages_list = array(
            "page_dashboard" => false,
            "page_examlist" => false,
            "page_scheduler" => false,
            "page_examreqs" => false,
            "page_useroverride" => false,
            "page_examlogs" => false,
            "page_settings" => false,
            "page_stats" => false,
            "page_printpass" => false,
            "page_useradmin" => false
        );
    }

    public function getPages() {
        // error_log("\n\n**********************************************************************\n");
        // $pages_list = array(
        //     "page_dashboard" => false,
        //     "page_examlist" => false,
        //     "page_scheduler" => false,
        //     "page_examreqs" => false,
        //     "page_useroverride" => false,
        //     "page_examlogs" => false,
        //     "page_settings" => false,
        //     "page_stats" => false,
        //     "page_printpass" => false,
        //     "page_useradmin" => false
        // );
        // error_log("\nPages -> getPages() -> do we have a list: ". print_r($this->pages_list, 1));
        // error_log("\n**********************************************************************\n\n");
        return $this->pages_list;
    }
}
