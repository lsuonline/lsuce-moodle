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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We need the config.
require_once('../../config.php');

// Require login.
require_login();

// Get the main wdsprefs class.
require_once("$CFG->dirroot/blocks/wdsprefs/classes/wdsprefs.php");

// Globals.
global $USER, $OUTPUT, $PAGE;

// Set up the page.
$context = context_system::instance();
$PAGE->set_context($context);

// Build the url.
$PAGE->set_url(new moodle_url('/blocks/wdsprefs/scheduleview.php'));

// Set title (generic).
$PAGE->set_title(get_string('wdsprefs:scheduleview', 'block_wdsprefs'));

// Get current user's ID for later.
$userid = $USER->id;

// Get the user's schedule.
$records = wdsprefs::get_user_course_schedule($userid);

// Separate records.
$studentrecords = [];
$teacherrecords = [];

foreach ($records as $rec) {
    if (isset($rec->role) && $rec->role === 'teacher') {
        $teacherrecords[] = $rec;
    } else {
        $studentrecords[] = $rec;
    }
}

$hasstudent = !empty($studentrecords);
$hasteacher = !empty($teacherrecords);

// Set heading based on roles.
if ($hasstudent && !$hasteacher) {
    $PAGE->set_heading(get_string('wdsprefs:studentschedule', 'block_wdsprefs'));
} elseif (!$hasstudent && $hasteacher) {
    $PAGE->set_heading(get_string('wdsprefs:teachingschedule', 'block_wdsprefs'));
} else {
    $PAGE->set_heading(get_string('wdsprefs:courseschedule', 'block_wdsprefs'));
}

// Output page header.
echo $OUTPUT->header();

// Helper closure to render schedule tables.
$render_schedule = function($schedule_records) {
    // Group records by academic_period_id.
    $grouped = [];

    // loop through records and group them.
    foreach ($schedule_records as $rec) {
        $grouped[$rec->academic_period_id][] = $rec;
    }

    // Loop through the groups.
    foreach ($grouped as $periodid => $periodrecords) {

        // Format the heading: remove underscores.
        $prettyperiod = wdsprefs::titlecase_except_first($periodid);

        // Display the period heading.
        echo html_writer::tag('h3', "{$prettyperiod}");

        // Build the table.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';
        $table->head = [
            get_string('wdsprefs:courseheading','block_wdsprefs'),
            get_string('wdsprefs:coursenoheading','block_wdsprefs'),
            get_string('wdsprefs:sectionheading','block_wdsprefs'),
            get_string('wdsprefs:statusheading','block_wdsprefs'),
            get_string('wdsprefs:instructorheading','block_wdsprefs'),
            get_string('wdsprefs:daysheading','block_wdsprefs'),
            get_string('wdsprefs:timesheading','block_wdsprefs'),
            get_string('wdsprefs:deliverymodeheading','block_wdsprefs')
        ];

        // Define the correct order of the days of the week.
        $dayorder = ['M', 'Tu', 'W', 'Th', 'F', 'Sa', 'Su'];

        // Loop through the records to build the table data.
        foreach ($periodrecords as $record) {

            // We'll use this in case we don't have a course id.
            $courselink = $record->moodlecourse;

            // Simple way to check if we have a course id.
            if (is_numeric($courselink)) {

                // Build the url.
                $url = new moodle_url('/course/view.php', ['id' => $courselink]);

                // Overwrite courselink with the link.
                $courselink = html_writer::link(
                    $url,
                    get_string('wdsprefs:courselink', 'block_wdsprefs')
                );
            }

            // Convert days to array.
            $daysarray = explode('<br>', $record->days);

            // Convert times to array.
            $timesarray = explode('<br>', $record->times);

            // Sort the days based on the correct order with a fancy anonymous function.
            usort($daysarray, function($a, $b) use ($dayorder) {
                return array_search($a, $dayorder) - array_search($b, $dayorder);
            });

            // Handle the case where the times are identical across all days.
            if (count(array_unique($timesarray)) == 1) {

                // If all times are the same, show the time once and then list the days.
                $timesdisplay = $timesarray[0];
                $daysdisplay = implode(', ', $daysarray);
            } else {

                // Otherwise, use the original values (one time per day).
                $timesdisplay = format_text($record->times, FORMAT_HTML);
                $daysdisplay = format_text($record->days, FORMAT_HTML);
            }

            // Build the table.
            $table->data[] = [
                s($record->course),
                s($record->courseno),
                s($record->section),
                $courselink,
                s($record->instructor),

                // Show the cleaned-up days.
                $daysdisplay,

                // Show the cleaned-up times.
                $timesdisplay,
                s($record->delivery),
            ];
        }

        // Output the table.
        echo html_writer::table($table);
    }
};

// Make sure something is here.
if (empty($records)) {
    echo $OUTPUT->notification(get_string('wdsprefs:nocourses', 'block_wdsprefs'));
} else {
    // If we have student records.
    if ($hasstudent) {
        // If we also have teacher records, show a header.
        if ($hasteacher) {
            echo html_writer::tag('h2', get_string('wdsprefs:studentschedule', 'block_wdsprefs'));
        }
        $render_schedule($studentrecords);
    }

    // If we have teacher records.
    if ($hasteacher) {
        // If we also have student records, show a header.
        if ($hasstudent) {
            echo html_writer::tag('h2', get_string('wdsprefs:teachingschedule', 'block_wdsprefs'));
        }
        $render_schedule($teacherrecords);
    }
}

// Output the footer.
echo $OUTPUT->footer();
