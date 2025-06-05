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
 * Display student grades
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/sportsgrades/classes/grade_fetcher.php');

// Parameters
$studentid = required_param('studentid', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// Page setup
$PAGE->set_url(new moodle_url('/blocks/sportsgrades/view_grades.php', ['studentid' => $studentid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_sportsgrades'));
$PAGE->set_heading(get_string('pluginname', 'block_sportsgrades'));
$PAGE->set_pagelayout('standard');

// Check access
require_login();
require_capability('block/sportsgrades:viewgrades', context_system::instance());

// Get student and grades
$grade_fetcher = new \block_sportsgrades\grade_fetcher();
$grades = $grade_fetcher->get_course_grades($studentid);

// Get student information

echo"<pre>";
var_dump($studentid);
echo"</pre>";
die();

$student = $DB->get_record('user', ['id' => $studentid], 'id, username, firstname, lastname');

// Start output
echo $OUTPUT->header();

// Display back button
$back_url = new moodle_url('/blocks/sportsgrades/view.php');
echo html_writer::start_div('mb-3');
echo html_writer::link(
    $back_url,
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left']) . ' ' . 
    get_string('grade_back_to_results', 'block_sportsgrades'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

// Display student name
echo html_writer::tag('h4', get_string('grade_title', 'block_sportsgrades', 
    $student->lastname . ', ' . $student->firstname));

// Check if there are courses
if (empty($grades['courses'])) {
    echo html_writer::div(
        get_string('grade_no_courses', 'block_sportsgrades'),
        'alert alert-info'
    );
    echo $OUTPUT->footer();
    exit;
}

// Display course list and grades
echo html_writer::start_div('row mt-4');

// Courses column
echo html_writer::start_div('col-md-4');
echo html_writer::start_div('card');
echo html_writer::div(
    html_writer::tag('h5', get_string('grade_course', 'block_sportsgrades'), ['class' => 'mb-0']), 
    'card-header'
);

echo html_writer::start_tag('ul', ['class' => 'list-group list-group-flush']);

foreach ($grades['courses'] as $i => $course) {
    $course_url = new moodle_url('/blocks/sportsgrades/view_grades.php', 
        ['studentid' => $studentid, 'courseid' => $course['id']]);
    
    $classes = 'list-group-item d-flex justify-content-between align-items-center';
    if (!$courseid && $i == 0) {
        $courseid = $course['id']; // Select first course by default
        $classes .= ' active';
    } else if ($courseid == $course['id']) {
        $classes .= ' active';
    }
    
    // Start the list item
    echo html_writer::start_tag('li', ['class' => $classes]);
    
    // Open the anchor tag for the entire list item content
    echo html_writer::start_tag('a', [
        'href' => $course_url->out(false),
        'class' => 'd-flex justify-content-between align-items-center w-100 text-decoration-none',
        'style' => 'color: inherit;'
    ]);
    
    // Course details
    echo html_writer::start_div('flex-grow-1');
    echo html_writer::tag('strong', $course['fullname']);
    echo html_writer::start_div('text-muted small');
    if (!empty($course['term'])) {
        echo $course['term'] . ' &middot; ';
    }
    if (!empty($course['section'])) {
        echo get_string('grade_section', 'block_sportsgrades') . ': ' . $course['section'];
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Grade
    echo html_writer::start_div('text-right');
    echo html_writer::tag('span', $course['letter_grade'], ['class' => 'badge badge-primary']);
    echo html_writer::div($course['final_grade_formatted'], 'small');
    echo html_writer::end_div();
    
    // Close the anchor tag
    echo html_writer::end_tag('a');
    echo html_writer::end_tag('li');
}

echo html_writer::end_tag('ul');
echo html_writer::end_div(); // End card
echo html_writer::end_div(); // End col-md-4

// Grade details column
echo html_writer::start_div('col-md-8');
echo html_writer::start_div('card');
echo html_writer::div(
    html_writer::tag('h5', get_string('grade_details', 'block_sportsgrades'), ['class' => 'mb-0']), 
    'card-header'
);

echo html_writer::start_div('card-body');

// Find the selected course
$selected_course = null;
foreach ($grades['courses'] as $course) {
    if ($course['id'] == $courseid) {
        $selected_course = $course;
        break;
    }
}

if (!$selected_course) {
    echo html_writer::div(
        get_string('grade_no_items', 'block_sportsgrades'),
        'alert alert-info'
    );
} else {
    // Display course header
    echo html_writer::tag('h6', $selected_course['fullname']);
    
    echo html_writer::start_div('d-flex justify-content-between mb-3');
    echo html_writer::start_div();
    if (!empty($selected_course['term'])) {
        echo html_writer::tag('span', $selected_course['term'], ['class' => 'badge badge-secondary mr-2']);
    }
    if (!empty($selected_course['section'])) {
        echo html_writer::tag('span', 
            get_string('grade_section', 'block_sportsgrades') . ': ' . $selected_course['section'], 
            ['class' => 'badge badge-info']
        );
    }
    echo html_writer::end_div();
    
    echo html_writer::start_div();
    echo html_writer::tag('span', 
        get_string('grade_final', 'block_sportsgrades') . ': ' . $selected_course['final_grade_formatted'], 
        ['class' => 'badge badge-success mr-2']
    );
    echo html_writer::tag('span', 
        get_string('grade_letter', 'block_sportsgrades') . ': ' . $selected_course['letter_grade'], 
        ['class' => 'badge badge-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Check if there are grade items
    if (empty($selected_course['grade_items'])) {
        echo html_writer::div(
            get_string('grade_no_items', 'block_sportsgrades'),
            'alert alert-info'
        );
    } else {
        // Display grade items table
        $table = new html_table();
        $table->head = [
            get_string('grade_item', 'block_sportsgrades'),
            get_string('grade_weight', 'block_sportsgrades'),
            get_string('grade_value', 'block_sportsgrades'),
            get_string('grade_percentage', 'block_sportsgrades'),
            get_string('grade_contribution', 'block_sportsgrades')
        ];
        $table->attributes['class'] = 'table table-striped table-hover';
        
        foreach ($selected_course['grade_items'] as $item) {
            $table->data[] = [
                $item['name'],
                $item['weight_formatted'],
                $item['grade_formatted'] . ' / ' . $item['grademax'],
                $item['percentage_formatted'],
                $item['contribution_formatted']
            ];
        }
        
        echo html_writer::table($table);
    }
}

echo html_writer::end_div(); // End card-body
echo html_writer::end_div(); // End card
echo html_writer::end_div(); // End col-md-8

echo html_writer::end_div(); // End row

// End output
echo $OUTPUT->footer();
