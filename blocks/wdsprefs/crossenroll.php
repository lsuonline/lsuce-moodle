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
 * Cross Enrollment interface for combining multiple course sections into shells across periods.
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
require_once("$CFG->dirroot/blocks/wdsprefs/crossenroll_period_form.php");
require_once("$CFG->dirroot/blocks/wdsprefs/crossenroll_sections_form.php");

// Require user to be logged in.
require_login();

// Get system context for permissions.
$context = context_system::instance();

$url = new moodle_url('/blocks/wdsprefs/crossenroll.php');

// Set up the page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('wdsprefs:crossenrolltitle', 'block_wdsprefs'));
$PAGE->set_heading(get_string('wdsprefs:crossenrollheading', 'block_wdsprefs'));

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
    get_string('wdsprefs:crossenroll', 'block_wdsprefs'),
    new moodle_url('/blocks/wdsprefs/crossenroll.php')
);

// Output the header.
echo $OUTPUT->header();

$periods = wdsprefs::get_crossenroll_periods(true);

// Step 1: Target Period Selection.
if ($step == 'period') {

    if (empty($periods)) {
        echo $OUTPUT->notification(get_string('wdsprefs:nocrossenrollperiods', 'block_wdsprefs'),
        \core\output\notification::NOTIFY_ERROR);
    } else {

        // Check eligibility.
        if (!wdsprefs::check_crossenroll_eligibility($USER->id)) {
             echo $OUTPUT->notification(get_string('wdsprefs:notenoughsectionsforcrossenroll', 'block_wdsprefs'),
             \core\output\notification::NOTIFY_ERROR);
        } else {
            $actionurl = new moodle_url('/blocks/wdsprefs/crossenroll.php', ['step' => 'period']);
            $form1 = new crossenroll_period_form($actionurl, ['periods' => $periods]);

            if ($form1->is_cancelled()) {
                redirect(new moodle_url('/'));
            } else if ($data = $form1->get_data()) {

                // Store selection data in session for next step.
                $SESSION->wdsprefs_target_periodid = $data->periodid;

                // Redirect to step 2.
                redirect(new moodle_url('/blocks/wdsprefs/crossenroll.php',
                    ['step' => 'sections']));
            } else {
                $form1->display();
            }
        }
    }

// Step 2: Sections Selection.
} else if ($step == 'sections') {
    $targetperiodid = $SESSION->wdsprefs_target_periodid;
    if (!$targetperiodid) {
         redirect(new moodle_url('/blocks/wdsprefs/crossenroll.php'));
    }

    // Get ALL sections across periods.
    $sectionsbyperiod = wdsprefs::get_sections_across_periods($targetperiodid);

    // Get target period name for display.
    $targetperiod = wdsprefs::get_period_from_id($targetperiodid);
    $targetperiodname = isset($periods[$targetperiodid]) ? $periods[$targetperiodid] : '';

    $actionurl = new moodle_url('/blocks/wdsprefs/crossenroll.php', ['step' => 'sections']);
    $form2 = new crossenroll_sections_form($actionurl, [
        'sectionsbyperiod' => $sectionsbyperiod,
        'targetperiodname' => $targetperiodname
    ]);

    if ($form2->is_cancelled()) {

        // Clear session.
        unset($SESSION->wdsprefs_target_periodid);
        redirect(new moodle_url('/blocks/wdsprefs/crossenroll.php'));
    } else if ($data = $form2->get_data()) {

        // Filter selected sections.
        $selected = [];
        if (!empty($data->selectedsections) && is_array($data->selectedsections)) {
            $selected = array_filter($data->selectedsections);

            // Re-assign to object property for processing.
            $data->selectedsections = array_values($selected);
        }

        if (empty($selected)) {
            echo $OUTPUT->notification(get_string('wdsprefs:nosectionsselected', 'block_wdsprefs'),
            \core\output\notification::NOTIFY_ERROR);
            $form2->display();
        } else if (!wdsprefs::validate_crossenroll_selection($selected)) {

            $cllink = html_writer::link(
                new moodle_url('/blocks/wdsprefs/crosssplit.php'),
                get_string('wdsprefs:crosssplit', 'block_wdsprefs')
            );

            echo $OUTPUT->notification(get_string('wdsprefs:mustselectfromtwo', 'block_wdsprefs', $cllink),
            \core\output\notification::NOTIFY_ERROR);
            $form2->display();
        } else {
             $teachername = fullname($USER);
             $results = wdsprefs::process_crossenroll_form($data, $targetperiod, $teachername);

             if (!empty($results)) {
                echo $OUTPUT->notification(get_string('wdsprefs:crossenrollsuccess', 'block_wdsprefs'),
                \core\output\notification::NOTIFY_SUCCESS);

                foreach ($results as $shellname => $shelldata) {
                    echo html_writer::tag('h4', $shellname);
                    if (!empty($shelldata['sections'])) {
                         echo html_writer::alist($shelldata['sections']);

                        $crosssplitinfo = wdsprefs::get_crosssplit_info($shelldata['crosssplit_id']);
                        if ($crosssplitinfo && $crosssplitinfo->moodle_course_id) {
                            $courseurl = new moodle_url('/course/view.php', ['id' => $crosssplitinfo->moodle_course_id]);
                            echo html_writer::link($courseurl, get_string('wdsprefs:viewcourse', 'block_wdsprefs'), ['class' => 'btn btn-primary']);
                        }
                    }
                }

                unset($SESSION->wdsprefs_target_periodid);

                echo html_writer::tag('div',
                    html_writer::link(
                        new moodle_url('/blocks/wdsprefs/crossenroll.php'),
                        get_string('wdsprefs:crossenroll', 'block_wdsprefs'),
                        ['class' => 'btn btn-secondary mt-3']
                    ),
                    ['class' => 'mt-4']
                );
             } else {
                 echo $OUTPUT->notification(get_string('wdsprefs:crossenrollfail', 'block_wdsprefs'),
                 \core\output\notification::NOTIFY_ERROR);
                 $form2->display();
             }
        }
    } else {
        $form2->display();
    }
}

// Display existing cross enrollments (reuses existing logic).
if ($step == 'period') {

    // Get existing crosssplited shells for this user.
    $existingcrosssplits = wdsprefs::get_user_crosssplits($USER->id);

    if (!empty($existingcrosssplits)) {
        echo html_writer::tag('h3', get_string('wdsprefs:existingcrosssplits', 'block_wdsprefs'));

        $table = new html_table();
        $table->head = [
            get_string('wdsprefs:shellname', 'block_wdsprefs'),
            get_string('wdsprefs:period', 'block_wdsprefs'),
            get_string('wdsprefs:datecreated', 'block_wdsprefs'),
            get_string('wdsprefs:actions', 'block_wdsprefs')
        ];

        foreach ($existingcrosssplits as $crosssplit) {

            // Get period info.
            $period = wdsprefs::get_period_from_id($crosssplit->academic_period_id);

            // Build out the period name.
            $periodname = wdsprefs::get_current_taught_periods($crosssplit->academic_period_id);

            // It's an array of one, so make it an object.
            $periodname = reset($periodname);

            // Get the Moodle course name if available.
            $displayname = $crosssplit->shell_name;

            $row = [];
            $row[] = $displayname;
            $row[] = $periodname;
            $row[] = userdate($crosssplit->timecreated);

            // Action buttons.
            $actions = '';
            if ($crosssplit->moodle_course_id) {
                $courseurl = new moodle_url('/course/view.php', ['id' => $crosssplit->moodle_course_id]);
                $actions .= html_writer::link(
                    $courseurl,
                    get_string('wdsprefs:viewcourse', 'block_wdsprefs'),
                    ['class' => 'btn btn-sm btn-primary', 'target' => '_blank']
                );

                // Add view sections button.
                $sectionsurl = new moodle_url('/blocks/wdsprefs/crosssplit_sections.php',
                    ['id' => $crosssplit->id]
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
