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

/*
    This is the menu for Utools, the clicks will be handled in js/utools_end.js
*/

function config()
{
    global $CFG;

    $sidebar_titles = array(
        // 'Dashboard' => array(
        //     'url' => $CFG->wwwroot . '/local/utools/reports.php',
        //     'icon' => 'icon-warning-sign'
        //     // 'Home' => array(
        //     //     'url' => $CFG->wwwroot . '/local/utools/index.php',
        //     //     'icon' => 'icon-wrench'
        //     // ),
        // ),
        
        'Reporting' => array(
            'url' => $CFG->wwwroot . '/local/utools/index.php',
            'icon' => 'icon-dashboard',

            'Dashboard' => array(
                'url' => $CFG->wwwroot . '/local/utools/index.php',
                'icon' => 'icon-dashboard'
            ),
            'Test Centre' => array(
                'url' => 'tcms',
                'icon' => 'icon-beaker',
                'pagetype' => 'widget'
            ),
            'Piwik' => array(
                'url' => 'piwik',
                'icon' => 'icon-bar-chart',
                'pagetype' => 'widget'
            ),
            'Course Stats' => array(
                'url' => 'coursestat',
                'icon' => 'icon-bar-chart',
                'pagetype' => 'widget'
            ),
            'Load' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/',
                'url' => 'jmeter',
                'icon' => 'icon-beaker',
                'pagetype' => 'widget'
            ),
            'New Relic' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/',
                'url' => 'newrelic',
                'icon' => 'icon-bar-chart',
                'pagetype' => 'widget'
            ),
            'Functional' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/',
                'url' => 'http://parmenion.netsrv.uleth.ca:8080/jenkins/view/Moodle/',
                'icon' => 'icon-th',
                'pagetype' => 'widget'
            ),
            'Unit' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/',
                'url' => 'http://parmenion.netsrv.uleth.ca:8080/jenkins/view/Moodle/',
                'icon' => 'icon-th',
                'pagetype' => 'widget'
            ),
        ),
        
        'Test Centre' => array(
            'url' => $CFG->wwwroot.'/local/tcms/',
            'icon' => 'icon-tumblr',

            'TCMS Home' => array(
                // 'url' =>$CFG->wwwroot . '/local/utools/',
                'url' => $CFG->wwwroot.'/local/tcms/',
                'icon' => 'icon-home',
                'pagetype' => 'external'
            ),
            'Reports' => array(
                // 'url' =>$CFG->wwwroot . '/local/utools/',
                'url' => 'tcms',
                'icon' => 'icon-dashboard',
                'pagetype' => 'widget'
            ),
        ),
        
        'DB Tools' => array(
            'url' => '#',
            'icon' => 'icon-archive',

            'Find Missing IDs' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/db_checks.php',
                'url' => 'db_checks',
                'icon' => 'icon-user',
                'pagetype' => 'single'
            ),
            'Course Restore' => array(
                'url' => 'restore',
                'icon' => 'icon-mail-reply',
                'pagetype' => 'single'
            ),
            'Mass Enroll' => array(
                'url' =>$CFG->wwwroot . '/local/utools/massEnroll.php',
                'icon' => 'icon-group',
                'pagetype' => 'external'
            ),
            'See all users in a courses' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/courseUsersEnrolled.php',
                'url' => 'course_users_enrolled',
                'icon' => 'icon-group',
                'pagetype' => 'single'
            )
        ),

        'LMB' => array(
            'url' => '#',
            'icon' => 'icon-exchange',

            'Live LMB Log' => array(
                // 'url' => $CFG->wwwroot . '/local/utools/enrollmentLog.php',
                'url' => 'enrollment_log',
                'icon' => 'icon-file-text-alt',
                'pagetype' => 'single'
            ),
            'Dead Enrollments' => array(
                'url' => 'deadview',
                'icon' => 'icon-ambulance',
                'pagetype' => 'single'
            ),
            'Unenroll A User' => array(
                'url' => 'unenroll_user',
                'icon' => 'icon-trash',
                'pagetype' => 'single'
            ),
            'Banner Compare Tool' => array(
                'url' => 'course_compare',
                'icon' => 'icon-exchange',
                'pagetype' => 'single'
            )
        ),
        
        'Settings' => array(
            'url' => '#',
            'icon' => 'icon-cogs',

            'Utool Settings' => array(
                'url' => $CFG->wwwroot . '/admin/settings.php?section=local_utools',
                'icon' => 'icon-cog',
                'pagetype' => 'external'
            ),
            'Admin Users' => array(
                // 'url' => $CFG->wwwroot . '',
                // 'url' => $CFG->wwwroot . '/local/utools/administration.php',
                'url' => 'administration',
                'icon' => 'icon-group',
                'pagetype' => 'single'
            ),
        ),

        'My Moodle' => array(
            'url' => '#',
            'icon' => 'icon-home',
                
            'Home' => array(
                'url' => $CFG->wwwroot,
                'icon' => 'icon-home',
                'pagetype' => 'external'
            )
        )
    );

    return $sidebar_titles;
}
