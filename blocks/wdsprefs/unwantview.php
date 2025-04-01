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
$PAGE->set_heading(get_string('wdsprefs:unwant', 'block_wdsprefs'));

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
                sec.course_subject_abbreviation,
                cou.course_number,
                sec.section_listing_id,
                tea.userid,
                sec.section_number
            FROM {enrol_wds_sections} sec
                INNER JOIN {enrol_wds_courses} cou
                    ON cou.course_definition_id = sec.course_definition_id
                INNER JOIN {enrol_wds_periods} per
                    ON per.academic_period_id = sec.academic_period_id
                INNER JOIN {enrol_wds_teacher_enroll} tenr
                    ON sec.section_listing_id = tenr.section_listing_id
                INNER JOIN {enrol_wds_teachers} tea
                    ON tea.universal_id = tenr.universal_id
            WHERE sec.controls_grading = 1
                AND tenr.role = 'Primary'
                AND tea.userid = ?";

        // Fetch all sections for the user on this page.
        $sections = $DB->get_records_sql($ssql, [$userid]);

        // Return them.
        return $sections;
    }

    public function definition() {
        global $CFG, $DB, $USER;

        // Retrieve sections from custom data.
        $sections = $this->_customdata['sections'] ?? [];

        // Instantiate the form.
        $mform = $this->_form;

        // Count 'em up for later.
        $scount = count($sections);

        // If we don't have any sections, redirect home.
        if ($scount < 1) {
            redirect(
                $CFG->wwwroot,
                'You have no Workday Student course sections.',
                null,
                core\output\notification::NOTIFY_WARNING
            );
        }

        // We have some, let's do something with them.
        foreach ($sections as $section) {
            // Define the checkboxname.
            $checkboxname = 'section_' . $section->id;
            // Define the section name for the form.
            $section->name = $section->period_year . ' ' .
                $section->course_subject_abbreviation . ' ' .
                $section->course_number . ' ' .
                $section->section_number;

            // Add the form.
            $mform->addElement('advcheckbox', $checkboxname, $section->name);

            // Build out the parms.
            $parms = [
                'userid' => $section->userid,
                'sectionid' => $section->id,
            ];

            // Check if the user has previously set the section as unwanted.
            $existing = $DB->get_record('block_wdspref_unwants', $parms);

            // If the user has set the section as unwanted, set that as the default.
            if (isset($existing->id) && $existing->unwanted == 1) {
                $mform->setDefault($checkboxname, 1);
            } else if (!isset($existing->id) && self::get_numeric_course_value($section) > 5000) {
                $mform->setDefault($checkboxname, 1);
            }
        }

        // Add the submit button.
        $mform->addElement('submit', 'submitbutton', get_string('wdsprefs:saveprefs', 'block_wdsprefs'));
    }
}

// Fetch sections once.
$sections = section_preferences_form::get_courses($USER->id);

// Instantiate the form and pass sections as custom data.
$form = new section_preferences_form(null, ['sections' => $sections]);

// Process form submission.
if ($form->is_submitted() && $data = $form->get_data()) {

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
