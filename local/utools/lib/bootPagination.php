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
//namespace Utools\Lib

require_once "Pagination.php";

class BootPagination
{

    public $pagenumber;
    public $pagesize;
    public $totalrecords;
    public $showfirst;
    public $showlast;
    public $paginationcss;
    public $paginationstyle;

    public $defaultUrl;
    public $paginationUrl;
    public $alpha_list;

    public function __construct()
    {
            $this->pagenumber = 1;
            $this->pagesize = 20;
            $this->totalrecords = 0;
            $this->showfirst = true;
            $this->showlast = true;
            $this->paginationcss = "pagination-small";
            $this->paginationstyle = 1;  // 1: advance, 0: normal

            $this->defaultUrl = "#"; // in case of ajax pagination
            $this->paginationUrl = "#"; // # incase of ajax pagination e.g index.php?p=[p] -->
            $this->alpha_list = array('start','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
            $this->num_list = array('a' => 0,'b' => 1,'c' => 2,'d' => 3,'e' => 4,'f' => 5,'g' => 6,'h' => 7,'i' => 8,'j' => 9,'k' => 10,'l' => 11,'m' => 12,'n' => 13,'o' => 14,'p' => 15,'q' => 16,'r' => 17,'s' => 18,'t' => 19,'u' => 20,'v' => 21,'w' => 22,'x' => 23,'y' => 25,'z' => 26);
    }

    public function process()
    {
        $paginationlst = "";
        $firstbound =0;
        $lastbound =0;
        $tooltip = "";

        if ($this->totalrecords > $this->pagesize) {
            if ($this->pagesize == 0) {
                $totalpages = 0;
            } else {
                $totalpages = ceil($this->totalrecords / $this->pagesize);
            }

            if ($this->paginationstyle == 2) {
                $totalpages = 26;
            }
            
            if ($this->pagenumber > 1) {
                if ($this->showfirst) {
                    $firstbound = 1;
                    $lastbound = $firstbound + $this->pagesize - 1;
                    $tooltip = "showing " . $firstbound . " - " . $lastbound . " records of " . $this->totalrecords . " records";
                    // First Link
                    if ($this->defaultUrl == "") {
                        $this->defaultUrl = "#";
                    }
                    $paginationlst .= "<li><a id=\"p_1\" href=\"" . $this->defaultUrl . "\" class=\"pagination-css\" data-toggle=\"tooltip\" title=\"" . $tooltip . "\"><i class=\"icon-backward\"></i></a></li>\n";
                }
                $firstbound = (($totalpages - 1) * $this->pagesize);
                $lastbound = $firstbound + $this->pagesize - 1;
                if ($lastbound > $this->totalrecords) {
                    $lastbound = $this->totalrecords;
                }
                $tooltip = "showing " . $firstbound . " - " . $lastbound . " records of " . $this->totalrecords . " records";
                // Previous Link Enabled
                if ($this->paginationUrl == "") {
                    $this->paginationUrl = "#";
                }

                $pid = ($this->pagenumber - 1);
                if ($pid < 1) {
                    $pid = 1;
                }
                $paginationlst .= "<li><a id=\"pp_" . $pid . "\" href=\"" . $this->prepareUrl($pid) . "\" data-toggle=\"tooltip\" class=\"pagination-css\" title=\"" . $tooltip . "\"><i class=\"icon-chevron-left\"></i></a></li>\n";
                // Normal Links
                $paginationlst .= $this->generatePaginationLinks($totalpages, $this->totalrecords, $this->pagenumber, $this->pagesize);

                if ($this->pagenumber < $totalpages) {
                    $paginationlst .= $this->generatePreviousLastLinks($totalpages, $this->totalrecords, $this->pagenumber, $this->pagesize, $this->showlast);
                }
            } else {
                // Normal Links
                $paginationlst .= $this->generatePaginationLinks($totalpages, $this->totalrecords, $this->pagenumber, $this->pagesize);
                // Next Last Links
                $paginationlst .= $this->generatePreviousLastLinks($totalpages, $this->totalrecords, $this->pagenumber, $this->pagesize, $this->showlast);
            }
        }
        return "<ul class=\"pagination " . $this->paginationcss . "\">\n" . $paginationlst . "</ul>\n";
    }

    public function generatePaginationLinks($totalpages, $totalrecords, $pagenumber, $pagesize)
    {
        $script = "";
        $firstbound = 0;
        $lastbound = 0;
        $tooltip = "";

        $lst = new Pagination();
        if ($this->paginationstyle == 1) {
            $arr = $lst->advancePaginationLinks($totalpages, $pagenumber);
        } else {
            $arr = $lst->simplePaginationLinks($totalpages, 15, $pagenumber);
        }

        // error_log("\nbootPagination -> totalpages is: ". $totalpages);
        // error_log("\nbootPagination -> pagenumber is: ". $pagenumber);
        // error_log("\nbootPagination -> arr is: ". print_r($arr, 1));
        // error_log("\nbootPagination -> size of arr is: ". count($arr));

        if (count($arr) > 0) {
            foreach ($arr as $item) {
                $firstbound = (($item - 1) * $pagesize) + 1;
                $lastbound = $firstbound + $pagesize - 1;
                if ($lastbound > $totalrecords) {
                    $lastbound = $totalrecords;
                }
                $tooltip = "showing " . $firstbound . " - " . $lastbound . " records  of " . $totalrecords . " records";
                $css = "";
                if ($item == $pagenumber) {
                    $css = " class=\"active\"";
                }
                $new_item = $item;
                if ($this->paginationstyle == 2) {
                    $new_item = $this->alpha_list[$item];
                }
           
                $script .= "<li" . $css . "><a id=\"pg_" . $item . "\" href=\"" . $this->prepareUrl($item) . "\" class=\"pagination-css\" data-toggle=\"tooltip\" title=\"" . $tooltip . "\">" . strtoupper($new_item) . "</a></li>\n";
            }
        }
        return $script;
    }

    public function generatePreviousLastLinks($totalpages, $totalrecords, $pagenumber, $pagesize, $showlast)
    {
        $script = "";
        $firstbound = (($pagenumber) * $pagesize) + 1;
        $lastbound = $firstbound + $pagesize - 1;
        if ($lastbound > $totalrecords) {
            $lastbound = $totalrecords;
        }

        $tooltip = "showing " . $firstbound . " - " . $lastbound . " records of " . $totalrecords . " records";
        // Next Link
        $pid = ($pagenumber + 1);
        // error_log("bootPagination -> What is pid: ". $pid);
        // error_log("bootPagination -> What is totalpages: ". $totalpages);

        if ($pid > $totalpages) {
            $pid = $totalpages;
        }
        $script .= "<li><a id=\"pn_" . $pid . "\" href=\"" . $this->prepareUrl($pid) . "\" class=\"pagination-css\" data-toggle=\"tooltip\" title=\"" . $tooltip . "\"><i class=\"icon-chevron-right\"></i></a></li>\n";
        
        // error_log("bootPagination -> What is show last: ". $showlast);
        
        if ($showlast) {
            // Last Link
            $firstbound = (($totalpages - 1) * $pagesize) + 1;
            $lastbound = $firstbound + $pagesize - 1;
            if ($lastbound > $totalpages) {
                $lastbound = $totalpages;
            }
            $tooltip = "showing " . $firstbound . " - " . $lastbound . " records of " . $totalrecords . " records";
            $script .= "<li><a id=\"pl_" . $totalpages . "\" href=\"" . $this->prepareUrl($totalpages) . "\" class=\"pagination-css\" data-toggle=\"tooltip\" title=\"" . $tooltip . "\"><i class=\"icon-forward\"></i></a></li>\n";
        }
        return $script;

    }

    public function prepareUrl($pid)
    {
        if ($this->paginationUrl == "") {
            $this->paginationUrl = "#";
        }
        return str_replace("[p]", $pid, $this->paginationUrl);
    }
    
    public function alphaPageNumber($num)
    {
        return $this->alpha_list[$num];
    }

    public function letterToPageNumber($alpha)
    {
        return $this->num_list[$alpha];
    }
}
