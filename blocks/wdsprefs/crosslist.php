<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Crosslisting interface for combining multiple course sections into shells.
 * 
 * This page provides a three-step interface for crosslisting course sections:
 * 1. Select the semester in which you'd like to crosslist.
 * 2. Select source courses containing sections to be crosslisted.
 * 3. Assign sections from selected courses into destination course shells.
 *
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required Moodle core.
require('../../config.php');

// Get the main wdsprefs class.
require_once("$CFG->dirroot/blocks/wdsprefs/classes/wdsprefs.php");

// Include the form class definitions.
require_once("$CFG->dirroot/blocks/wdsprefs/select_period_form.php");
require_once("$CFG->dirroot/blocks/wdsprefs/select_courses_form.php");
require_once("$CFG->dirroot/blocks/wdsprefs/crosslist_form.php");

// Require user to be logged in.
require_login();

// Get system context for permissions.
$context = context_system::instance();

// TODO: Uncomment me.
//require_capability('block/wdsprefs:manage', $context);

$url = new moodle_url('/blocks/wdsprefs/crosslist.php');

// Set up the page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('wdsprefs:crosslisttitle', 'block_wdsprefs'));
$PAGE->set_heading(get_string('wdsprefs:crosslistheading', 'block_wdsprefs'));

// Get the optional param.
if(!optional_param('step', '', PARAM_ALPHA)) {
    $step = 'period';
} else {
    $step = optional_param('step', '', PARAM_ALPHA);
}

// Load required CSS.
$PAGE->requires->css('/blocks/wdsprefs/styles.css');

// Add breadcrumbs.
$PAGE->navbar->add(
    get_string('home'),
    new moodle_url('/')
);
$PAGE->navbar->add(
    get_string('wdsprefs:crosslist', 'block_wdsprefs'),
    new moodle_url('/blocks/wdsprefs/crosslist.php')
);

// Output the header.
echo $OUTPUT->header();

$periods = wdsprefs::get_current_taught_periods();

// Step 1: Period selection - displays the first form to select a source period.
if ($step == 'period') {
    $actionurl = new moodle_url('/blocks/wdsprefs/crosslist.php', ['step' => 'period']);

    // Instantiate the form1.
    $form1 = new select_period_form($actionurl, ['periods' => $periods]);

    if ($form1->is_cancelled()) {
        redirect(new moodle_url('/my'));
    } else if ($data = $form1->get_data()) {

        $sectionsbycourse = wdsprefs::get_sections_by_course_for_period($data->periodid);

        // Store selection data in session for next step.
        $SESSION->wdsprefs_periodid = $data->periodid;
        $SESSION->wdsprefs_sectionsbycourse = $sectionsbycourse;

        // Redirect to step 2.
        redirect(new moodle_url('/blocks/wdsprefs/crosslist.php',
            ['step' => 'course']));
    } else {
        $form1->display();
    }

// Step 2: Courses selection - displays the second form to select the courses to crosslist.
} else if ($step == 'course') {
    $actionurl = new moodle_url('/blocks/wdsprefs/crosslist.php', ['step' => 'course']);

    // Set this from the session.
    $sectionsbycourse = $SESSION->wdsprefs_sectionsbycourse;
    $periodid =  $SESSION->wdsprefs_periodid;

    // Get the full period info for building shell names.
    $period = wdsprefs::get_period_from_id($periodid);

    // Initialize the first form with course data.
    $form2 = new select_courses_form($actionurl, ['sectionsbycourse' => $sectionsbycourse]);

    // Handle form cancellation.
    if ($form2->is_cancelled()) {
        redirect(new moodle_url('/my'));

    // Process form submission.
    } else if ($data = $form2->get_data()) {

        // Track selected courses.
        $selected = [];

        // Loop through course list and check which ones were selected.
        foreach ($sectionsbycourse as $coursename => $sections) {

            // Sanitize course name for use in form element ID.
            $sanitized = str_replace([' ', '/'], ['_', '-'], $coursename);

            // Check if this course was selected in the form.
            if (!empty($data->{'selectedcourses_' . $sanitized})) {
                $selected[] = $coursename;
            }
        }

        // Collect all sections from selected courses.
        $sectiondata = [];

        // Loop through the form data.
        foreach ($selected as $coursename) {
            if (isset($sectionsbycourse[$coursename])) {
                $sectiondata += $sectionsbycourse[$coursename];
            }
        }

        // Verify at least two sections are selected (required for crosslisting).
        if (count($sectiondata) < 2) {
            echo $OUTPUT->notification(get_string('wdsprefs:atleasttwosections', 
                'block_wdsprefs'), 'notifyproblem');
            $form2->display();
            echo $OUTPUT->footer();
            exit;
        }

        // Store selection data in session for next step.
        $SESSION->wdsprefs_prefixinfo = $sectiondata;
        $SESSION->wdsprefs_suffixinfo = $sectiondata;
        $SESSION->wdsprefs_sectiondata = $sectiondata;
        $SESSION->wdsprefs_shellcount = $data->shellcount;

        // Redirect to step 3.
        redirect(new moodle_url('/blocks/wdsprefs/crosslist.php', 
            ['step' => 'assign']));
    } else {

        // Display the form.
        $form2->display();
    }

// Step 3: Assign sections to shells.
} else if ($step == 'assign') {
    
    // Retrieve data from session.
    $sectiondata = $SESSION->wdsprefs_sectiondata ?? [];
    $shellcount = $SESSION->wdsprefs_shellcount ?? 2;
    $periodid = $SESSION->wdsprefs_periodid ?? '';

    $actionurl = new moodle_url('/blocks/wdsprefs/crosslist.php', ['step' => 'assign']);

    // Get full period info for shell naming
    $period = wdsprefs::get_period_from_id($periodid);
    $periodname = $period->period_year . ' ' . $period->period_type;

    // Get teacher name
    $teachername = fullname($USER);

    // Initialize the second form with section data.
    $form3 = new crosslist_form($actionurl, [
        'period' => $periodname,
        'teacher' => $teachername,
        'sectiondata' => $sectiondata,
        'shellcount' => $shellcount,
    ]);

    // Get any submitted data for form2.
    $data = $form3->get_data();
    $cancelled = $form3->is_cancelled();

    // Handle form cancellation.
    if ($cancelled) {

        // Clear session data to avoid stale data on restart.
        unset($SESSION->wdsprefs_sectiondata);
        unset($SESSION->wdsprefs_shellcount);
        unset($SESSION->wdsprefs_periodid);

        redirect(new moodle_url('/blocks/wdsprefs/crosslist.php'));
    }

    // Process form submission.
    if (!is_null($data)) {
        // Process the crosslisting
        $results = wdsprefs::process_crosslist_form($data, $period, $teachername, $shellcount);

        // Check if we have results
        if (!empty($results)) {
            // Display success message
            echo $OUTPUT->notification(get_string('wdsprefs:crosslistsuccess', 
                'block_wdsprefs'), 'notifysuccess');

            // Display the results for each shell
            foreach ($results as $shellname => $shelldata) {
                echo html_writer::tag('h4', $shellname);
                
                if (empty($shelldata['sections'])) {
                    echo html_writer::tag('p', 'No sections assigned');
                } else {
                    echo html_writer::alist($shelldata['sections']);
                    
                    // Add link to view the created course
                    $crosslistinfo = wdsprefs::get_crosslist_info($shelldata['crosslist_id']);
                    if ($crosslistinfo && $crosslistinfo->moodle_course_id) {
                        $courseurl = new moodle_url('/course/view.php', 
                            ['id' => $crosslistinfo->moodle_course_id]
                        );
                        echo html_writer::link($courseurl, 
                            get_string('wdsprefs:viewcourse', 'block_wdsprefs'),
                            ['class' => 'btn btn-primary']
                        );
                    }
                }
            }
            
            // Clear session data now that we're done
            unset($SESSION->wdsprefs_sectiondata);
            unset($SESSION->wdsprefs_shellcount);
            unset($SESSION->wdsprefs_periodid);
            
            // Add link to view all crosslists
            echo html_writer::tag('div', 
                html_writer::link(
                    new moodle_url('/blocks/wdsprefs/crosslist.php'), 
                    get_string('wdsprefs:crosslist', 'block_wdsprefs'),
                    ['class' => 'btn btn-secondary mt-3']
                ),
                ['class' => 'mt-4']
            );
        } else {
            // Display error message
            echo $OUTPUT->notification(get_string('wdsprefs:crosslistfail', 
                'block_wdsprefs'), 'notifyerror');
                
            // Display the form again
            $form3->display();
        }
    } else {
        $form3->display();
    }
}

// Display existing crosslisted shells if we're on the first step
if ($step == 'period') {
    // Get existing crosslisted shells for this user
    $existingcrosslists = wdsprefs::get_user_crosslists($USER->id);
    
    if (!empty($existingcrosslists)) {
        echo html_writer::tag('h3', get_string('wdsprefs:existingcrosslists', 'block_wdsprefs'));
        
        $table = new html_table();
        $table->head = [
            get_string('wdsprefs:shellname', 'block_wdsprefs'),
            get_string('wdsprefs:period', 'block_wdsprefs'),
            get_string('wdsprefs:datecreated', 'block_wdsprefs'),
            get_string('wdsprefs:actions', 'block_wdsprefs')
        ];
        
        foreach ($existingcrosslists as $crosslist) {
            // Get period info
            $period = wdsprefs::get_period_from_id($crosslist->academic_period_id);
            $periodname = $period ? $period->period_year . ' ' . $period->period_type : '';
            
            $row = [];
            $row[] = $crosslist->shell_name;
            $row[] = $periodname;
            $row[] = userdate($crosslist->timecreated);
            
            // Action buttons
            $actions = '';
            if ($crosslist->moodle_course_id) {
                $courseurl = new moodle_url('/course/view.php', ['id' => $crosslist->moodle_course_id]);
                $actions .= html_writer::link(
                    $courseurl, 
                    get_string('wdsprefs:viewcourse', 'block_wdsprefs'),
                    ['class' => 'btn btn-sm btn-primary', 'target' => '_blank']
                );
                
                // Add view sections button
                $sectionsurl = new moodle_url('/blocks/wdsprefs/crosslist_sections.php', 
                    ['id' => $crosslist->id]
                );
                $actions .= ' ' . html_writer::link(
                    $sectionsurl,
                    get_string('wdsprefs:viewsections', 'block_wdsprefs'),
                    ['class' => 'btn btn-sm btn-secondary']
                );
            }
            
            $row[] = $actions;
            $table->data[] = $row;
        }
        
        echo html_writer::table($table);
    }
}

// Output the footer.
echo $OUTPUT->footer();
