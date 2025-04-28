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
 * This page provides a two-step interface for crosslisting course sections:
 * 1. Select source courses containing sections to be crosslisted.
 * 2. Assign sections from selected courses into destination course shells.
 *
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required Moodle core.
require('../../config.php');

// Include the form class definitions.
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

// Load required CSS.
$PAGE->requires->css('/blocks/wdsprefs/styles.css');

// Output the header.
echo $OUTPUT->header();

/**
 * TODO: Remove me and get real data.
 */
$sectionsbycourse = [
    '2025 Summer 1 ENGL 1001 for Robert Russo' => [
        101 => 'ENG 1001 001-LEC-SM',
        102 => 'ENG 1001 002-LEC-SM',
    ],
    '2025 Summer 1 MATH 1021 for Robert Russo' => [
        201 => 'MATH 1021 001-LEC-SM',
        202 => 'MATH 1021 002-LEC-SM',
        203 => 'MATH 1021 003-LEC-SM',
    ],
    '2025 Summer 1 BIOL 1201 for Robert Russo' => [
        301 => 'BIOL 1201 001-LEC-SM',
        302 => 'BIOL 1201 002-LEC-SM',
        303 => 'BIOL 1201 003-LEC-SM',
        304 => 'BIOL 1201 004-LEC-SM',
        305 => 'BIOL 1201 005-LEC-SM',
        306 => 'BIOL 1201 006-LEC-SM',
    ]
];

// Step 1: Course selection - displays the first form to select source courses.
if (!optional_param('step', '', PARAM_ALPHA)) {

    // Initialize the first form with course data.
    $form1 = new select_courses_form(null, ['sectionsbycourse' => $sectionsbycourse]);

    // Handle form cancellation.
    if ($form1->is_cancelled()) {
        redirect(new moodle_url('/my'));

    // Process form submission.
    } else if ($data = $form1->get_data()) {

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
            $form1->display();
            echo $OUTPUT->footer();
            exit;
        }

        // Store selection data in session for next step.
        $SESSION->wdsprefs_sectiondata = $sectiondata;
        $SESSION->wdsprefs_shellcount = $data->shellcount;

        // Redirect to step 2.
        redirect(new moodle_url('/blocks/wdsprefs/crosslist.php', 
            ['step' => 'assign']));
    } else {

        // Display the form.
        $form1->display();
    }

// Step 2: Assign sections to shells - displays the second form.
} else {
    
    // Retrieve data from session.
    $sectiondata = $SESSION->wdsprefs_sectiondata ?? [];
    $shellcount = $SESSION->wdsprefs_shellcount ?? 2;

    $actionurl = new moodle_url('/blocks/wdsprefs/crosslist.php', ['step' => 'assign']);

    // Initialize the second form with section data.
    $form2 = new crosslist_form($actionurl, [
        'sectiondata' => $sectiondata,
        'shellcount' => $shellcount,
    ]);

    // Get any submitted data for form2.
    $data = $form2->get_data();
    $cancelled = $form2->is_cancelled();

/*
if (is_null($data)) {
    echo"<pre>";
    var_dump($sectiondata);
    echo"</pre>";
    echo"<pre>";
    var_dump($shellcount);
    echo"</pre>";

    echo"<pre>Form 2 data is null</pre>";
} else {
    echo"<pre>";
    var_dump($data);
    echo"</pre>";
    die();
}
*/

    // Handle form cancellation.
    if ($cancelled) {
    echo"<pre>";
    var_dump($sectiondata);
    echo"</pre>";
    echo"<pre>";
    var_dump($shellcount);
    echo"</pre>";
    die();

        // Clear session data to avoid stale data on restart.
        unset($SESSION->wdsprefs_sectiondata);
        unset($SESSION->wdsprefs_shellcount);

//        redirect(new moodle_url('/blocks/wdsprefs/crosslist.php'));
    }

    // Process form submission.
    if (!is_null($data)) {

        // Prepare array to store results.
        $results = [];

        // TODO: Custom shell names. Process shell assignments.
        for ($i = 1; $i <= $shellcount; $i++) {
            $field = "shell_$i";
            if (isset($data->$field) && is_array($data->$field)) {

                // Collect section names for this shell.
                $shellsections = [];

                // Loop through the data.
                foreach ($data->$field as $sectionid) {
                    if (isset($sectiondata[$sectionid])) {
                        $shellsections[] = $sectiondata[$sectionid];
                    }
                }

                // TODO: Custom shell names. Build out the shell name.
                $results["Shell $i"] = $shellsections;
            } else {

                // TODO: Handle empty shell. Do I really need to do this?
                $results["Shell $i"] = [];
            }
        }

        // Display success message.
        echo $OUTPUT->notification(get_string('wdsprefs:crosslistsuccess', 
            'block_wdsprefs'), 'notifysuccess');

        // Display the results for each shell.
        foreach ($results as $shell => $sections) {
            echo html_writer::tag('h4', $shell);
            if (empty($sections)) {
                echo html_writer::tag('p', 'No sections assigned');
            } else {

                // Display list of sections assigned to this shell.
                echo html_writer::alist($sections);
            }
        }

    // Form 2 has no data submitted yet.
    } else {

        // Display the form.
        $form2->display();
    }
}

// Output the footer.
echo $OUTPUT->footer();
