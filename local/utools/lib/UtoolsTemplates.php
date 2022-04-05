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
include_once "config.php";
require_once "UtoolsLib.php";

class UtoolsTemplates
{
    private $sidebar_titles = null;
    private $utools_lib = null;

    public function __construct()
    {

        $this->sidebar_titles = config();
        $this->utools_lib = new UtoolsLib();
    }

    public function mainContentStart()
    {
        global $CFG;
        $result = '<span class="utools_debugger_obj" data-utools_debugger="'.$CFG->local_utools_logging.'"></span>'.
            '<div class="row-fluid" id="utools_main_container">'.
            '<!-- uncomment code for absolute positioning tweek see top comment in css -->'.
            '<!-- <div class="absolute-wrapper"> </div> -->'.
            '<!-- Menu -->';
        
        return $result;
    }

    public function mainContent($load_widgets = null)
    {
        global $CFG;

        // '<!-- HEADER -->
        // <header id="header" class="utools_dash_header"></header>
        if ($load_widgets == null) {
            $load_this = '<div class="row">
                        <article class="col-sm-12" id="utools_widget_layout_wide">
                        <br><br>
                        </article>
                    </div>
                    <div class="row">
                        <article class="col-sm-12 col-md-12 col-lg-6">
                            <span id="utools_widget_layout_short_left"></span>
                        </article>
                        <article class="col-sm-12 col-md-12 col-lg-6">
                            <span id="utools_widget_layout_short_right"></span>
                        </article>
                    </div>';
        } else {
            $widget_counter = 0;
            $spot1 = "";
            $spot_left = "";
            $spot_right = "";

            foreach ($load_widgets as $this_widget) {
                if ($widget_counter == 0) {
                    $spot1 = $this_widget;
                } else {
                    if ($widget_counter % 2) {
                        $spot_left .= $this_widget;
                    } else {
                        $spot_right .= $this_widget;
                    }
                }
                $widget_counter++;
            }

            $load_this = '<div class="row">
                <article class="col-sm-12" id="utools_widget_layout_wide">'
                . $spot1 .
                '</article>
            </div>
            <div class="row">
                <article class="col-sm-12 col-md-12 col-lg-6">
                    <span id="utools_widget_layout_short_left">'
                    . $spot_left .
                    '</span>
                </article>
                <article class="col-sm-12 col-md-12 col-lg-6">
                    <span id="utools_widget_layout_short_right">'
                    . $spot_right .
                    '</span>
                </article>
            </div>';
        }


        $result = '<!-- Main Content -->
                <div class="container-fluid" id="utools_mainContent_template">
                    <div class="col-sm-10 side-body" id="utools-side-body-container">
                        <!-- MAIN PANEL -->
                        <div id="main" role="main">
                            <!-- MAIN CONTENT -->
                            <div id="content" class="utools_main_content_zone">
                                <!-- widget grid -->
                                <section id="widget-grid" class="utools_dash_widgetgrid">' .
                                    $load_this .
                                '</section>
                            </div>
                            <!-- END MAIN CONTENT -->
                        </div>
                        <!-- END MAIN PANEL -->
                    </div>
                </div>';
        
        return $result;
    }

    public function mainContentEnd()
    {
        $result = '</div>';
        return $result;
    }
    

    public function printSideBar()
    {
        
        // get the number of sidebar items and create that many random numbers
        $count_rns = sizeof($this->sidebar_titles);
        $random_nums = $this->utools_lib->getRandomNumbers(1, 99, $count_rns, 1);

        $result = ''.
        '<div class="col-sm-2 side-menu">'.
            '<div class="navbar">'.
                '<div class="navbar-inner">'.
                    '<div class="container">'.
                        '<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">'.
                            // '<span class="sr-only">Toggle navigation</span>'.
                            '<span class="icon-bar"></span>'.
                            '<span class="icon-bar"></span>'.
                            '<span class="icon-bar"></span>'.
                        '</a>'.
                        '<a class="brand" href="#">'.
                            'Utools Dashboard'.
                        '</a>'.

                        '<!-- Everything you want hidden at 940px or less, place within here -->'.
                        '<div class="nav-collapse collapse">'.
                        '<!-- .nav, .navbar-search, .navbar-form, etc -->'.
                            
                        '</div>'.
                    '</div>'.
                '</div>'.
                '<!-- Main Menu -->'.
                '<div class="side-menu-container">'.
                    '<ul class="nav navbar-nav">';
                    $loop_count = 0;
                    foreach ($this->sidebar_titles as $parent => $value) {
                        if (sizeof($value) <= 2) {
                            // this parent only has one object so let's just add a direct link
                            // $this_data = array_keys($value);
                            // foreach($value as $subparent => $subvalue){
                            $result .= '<li><a href="'.$value['url'].'"><span class="'.$value['icon'].'"></span> '.$parent.'</a></li>';
                            //}
                        } else {
                            $result .= ''.
                            '<li class="panel panel-default utools_side_panel_item" id="dropdown">'.
                                '<a data-toggle="collapse" href="'.$this->sidebar_titles[$parent]['url'].'" data-target="#utools_drop_'.$loop_count.'">'.
                                    '<span class="'.$this->sidebar_titles[$parent]['icon'].'"></span> '.$parent.' <span class="caret"></span>'.
                                '</a>'.
                                '<!-- Dropdown level 1 -->'.
                                
                                '<div id="utools_drop_'.$loop_count.'" class="panel-collapse collapse">'.
                                    '<div class="panel-body">'.
                                        '<ul class="nav navbar-nav">';
                                            foreach ($value as $name => $details) {
                                                if (gettype($details) === "array") {
                                                    $t_icon = $details['icon'];
                                                    $t_url = $details['url'];
                                                    $t_pagetype = isset($details['pagetype']) ? $details['pagetype'] : "dead";
                                                    $findme   = '.php';
                                                    $pos = strpos($t_url, $findme);

                                                    // if the config.php file has some full address like: http://moodle.uleth.ca/local/utools/reports.php
                                                    // then it's a link, otherwise it's a page that needs to be loaded: utools/reports
                                                    if ($pos === false || $t_pagetype === "external") {
                                                        $result .= '<li class="utools_side_panel_item" id="utools_template_link" data-pagetype="'.$t_pagetype.'" data-template="'.$t_url.'"><a href="javascript:void()"><span class="'.$t_icon.'"></span> '.$name.'</a></li>';
                                                    } else {
                                                        $result .= '<li class="utools_side_panel_item" id="utools_template_link" data-template="'.$t_icon.'"><a href="'.$t_url.'"><span class="'.$t_icon.'"></span> '.$name.'</a></li>';
                                                    }
                                                }
                                            }
                                        $result .= '</ul>'.
                                    '</div>'.
                                '</div>'.
                            '</li>';
                            
                        }
                        $loop_count++;
                    }
                        // '<li class="active"><a href="#"><span class="icon-plane"></span> Active Link</a></li>'.
                        // '<li><a href="#"><span class="icon-cloud"></span> Link</a></li>'.
                        // '<!-- Dropdown-->'.
                        // '<li><a href="#"><span class="icon-signal"></span> Link</a></li>'.
                    $result .= '</ul>'.
                '</div>'.
            '</div>'.
        '</div>';

        return $result;
    }

    public function printSimpleListHeading($spans, $titles)
    {

        $result = '<div class="row-fluid">';
        $count = 0;
        foreach ($spans as $span) {
            $result .= '<div class="'.$span.'">'.
                '<center>';
                    if (isset($titles[$count])) {
                        $result .= '<h2>'.$titles[$count].'<h2>';
                    } else {
                        $result .= '';
                    }
                $result .= '</center>'.
            '</div>';
            $count++;
        }
        $result .= '</div>';
        return $result;
    }
    
    public function printSimpleListBody($spans, $titles, $rows)
    {
        
        $result = '<div class="row-fluid">';

        foreach ($rows as $row) {
            $count = 0;
            foreach ($spans as $span) {
                if ($count == 2) {
                    $result .= '<div class="'.$span.'"><center><button class="btn btn-danger pull-right" id="utools_restore_delete_file" data-fileid="file_'.$row[1].'"><i class="icon-trash"></i></button></center></div>';
                } else {
                    $result .= '<div class="'.$span.'"><center>'.$row[$count].'</center></div>';
                }
                $count++;
            }
        }
        $result .= '</div>';
        return $result;
    }
}
