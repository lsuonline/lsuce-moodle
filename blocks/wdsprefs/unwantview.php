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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

// Ensure user is logged in.
require_login();

// Get the system context.
$context = context_system::instance();

// Define the url for the page.
$url = new moodle_url('/blocks/wdsprefs/unwantview.php');
// Page setup.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('wdsprefs:unwant', 'block_wdsprefs'));
// $PAGE->set_heading(get_string('wdsprefs:unwant', 'block_wdsprefs'));

// Add breadcrumbs.
$PAGE->navbar->add(
    get_string('home'),
    new moodle_url('/')
);
$PAGE->navbar->add(
    get_string('wdsprefs:unwant', 'block_wdsprefs'),
    new moodle_url('/blocks/wdsprefs/unwantview.php')
);

// Set page layout.
$PAGE->set_pagelayout('base');

class section_preferences_form extends moodleform {
    public static function get_numeric_course_value($section) {

        // Extract the numeric portion from the start of the string.
        preg_match('/^\d+/', $section->course_number, $matches);

        // If we have a match, set it in the $section object..
        if (!empty($matches)) {
            $numerical_value = (int)$matches[0];

            // Return the numerical value.
            return $numerical_value;
        }

        // Return nothing.
        return null;
    }

    public static function get_courses($userid) {
        global $DB;

        // Define the SQL.
        $ssql = "SELECT sec.id,
                per.period_year,
                per.period_type,
                per.academic_period_id,
                sec.course_subject_abbreviation,
                cou.course_number,
                sec.section_listing_id,
                tea.userid,
                sec.section_number,
                COALESCE(c.id, 'Pending') AS moodle_courseid
            FROM {enrol_wds_sections} sec
                INNER JOIN {enrol_wds_courses} cou
                    ON cou.course_definition_id = sec.course_definition_id
                INNER JOIN {enrol_wds_periods} per
                    ON per.academic_period_id = sec.academic_period_id
                INNER JOIN {enrol_wds_teacher_enroll} tenr
                    ON sec.section_listing_id = tenr.section_listing_id
                INNER JOIN {enrol_wds_teachers} tea
                    ON tea.universal_id = tenr.universal_id
                LEFT JOIN {course} c ON c.id = sec.moodle_status
            WHERE sec.controls_grading = 1
                AND tenr.role = 'Primary'
                AND tea.userid = ?
            ORDER BY per.start_date ASC,
                sec.course_subject_abbreviation ASC,
                cou.course_number ASC,
                sec.section_number ASC";

        // Fetch all sections for the user on this page.
        $sections = $DB->get_records_sql($ssql, [$userid]);

        // Group sections by academic_period_id.
        $gsections = [];
        foreach ($sections as $section) {
            $gsections[$section->academic_period_id][] = $section;
        }

        // Return them.
        return $gsections;
    }

    public function definition() {
        global $CFG, $DB, $USER;

        // Retrieve grouped sections from custom data.
        $gsections = $this->_customdata['gsections'] ?? [];

        // Instantiate the form.
        $mform = $this->_form;

        // If no sections exist, redirect home.
        if (empty($gsections)) {
            redirect(
                $CFG->wwwroot,
                'You have no Workday Student course sections.',
                null,
                core\output\notification::NOTIFY_WARNING
            );
        }

        // Iterate over academic periods.
        foreach ($gsections as $academic_period_id => $sections) {

            // Add a header for each academic period.
            $mform->addElement('header', "academic_period_$academic_period_id", 
                    $sections[0]->period_year .
                    " " .
                    $sections[0]->period_type
            );

            // Iterate through sections in the academic period.
            foreach ($sections as $section) {
                // Define the checkbox name.
                $checkboxname = 'section_' . $section->id;

                // Define the section name for the form.
                $section->name = $section->course_subject_abbreviation . ' ' .
                    $section->course_number . ' ' .
                    $section->section_number;

                // Add the form checkbox.
                $checkbox = $mform->addElement(
                    'advcheckbox',
                    $checkboxname,
                    $section->name
                );

                // Check if the user has previously set the section as unwanted.
                $parms = ['userid' => $section->userid, 'sectionid' => $section->id];
                $existing = $DB->get_record('block_wdspref_unwants', $parms);

                // Set the default values.
                if (isset($existing->id) && $existing->unwanted == 1) {
                    $mform->setDefault($checkboxname, 1);
                } else if (!isset($existing->id) && self::get_numeric_course_value($section) > 5000) {
                    $mform->setDefault($checkboxname, 1);
                }
            }
        }

        // Add the submit button.
        $this->add_action_buttons(
            false,
            get_string('wdsprefs:saveprefs', 'block_wdsprefs')
        );
    }
}

// Fetch sections once and group them by academic_period_id.
$gsections = section_preferences_form::get_courses($USER->id);

// Instantiate the form and pass grouped sections as custom data.
$form = new section_preferences_form('', ['gsections' => $gsections]);

// Get the workdaystudent lib for later.
$wdslib = $CFG->dirroot . '/enrol/workdaystudent/classes/workdaystudent.php';

// Process form submission.
if ($form->is_submitted() && $data = $form->get_data()) {

    // Just to be safe.
    if (file_exists($wdslib)) {
        require_once($wdslib);
        $usingwds = true;
    }

    // Loop through grouped sections by academic period.
    foreach ($gsections as $academic_period_id => $sections) {

        // Loop through the sections.
        foreach ($sections as $section) {

            // Define the key names.
            $key = 'section_' . $section->id;

            // Ignore unset checkboxes to avoid overriding existing preferences.
            if (!isset($data->$key)) {
                continue;
            }

            // Build these out for later.
            $sectionid = $section->id;
            $unwanted = $data->$key ? 1 : 0;

            // Check if this record already exists.
            $existing = $DB->get_record(
                'block_wdspref_unwants',
                ['userid' => $USER->id, 'sectionid' => $sectionid]
            );

            // If we have a record in the DB for this user in this section.
            if ($existing) {

                // Update only if the value actually changed.
                if ($existing->unwanted != $unwanted) {
                    $existing->unwanted = $unwanted;
                    $existing->lastupdated = time();

                    // Update the record.
                    $DB->update_record('block_wdspref_unwants', $existing);
                }

            // Insert a new record only if checked.
            } else if ($unwanted == 1 ||
                section_preferences_form::get_numeric_course_value($section) > 5000
            )  {

                // Build the new record object.
                $newrecord = new stdClass();
                $newrecord->userid = $USER->id;
                $newrecord->sectionid = $sectionid;
                $newrecord->unwanted = $unwanted;
                $newrecord->lastupdated = time();

                // Insert the record.
                $DB->insert_record('block_wdspref_unwants', $newrecord);
            }
        }
    }

    // Redirect here once saved.
    redirect($url,
        get_string('wdsprefs:success', 'block_wdsprefs'),
        null,
        core\output\notification::NOTIFY_SUCCESS
    );
}

// Render the page.
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
