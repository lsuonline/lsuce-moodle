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
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Dustin Durrand http://oohoo.biz
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This is the department administration page. You can deal with the administration
 * of each department from here. (Change preamble, standard questions, etc.)
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');


// ----- Parameters ----- //
$dept = optional_param('dept', false, PARAM_TEXT);

unset($_SESSION['list_of_selected']);
unset($_SESSION['list_of_ids']);
// ----- Security ----- //
require_login();

$department_list = get_departments();
$your_administrations = $DB->get_records(
    'department_administrators',
    array('userid' => $USER->id)
);

$your_depts = array();
if (count($department_list) > 0) {
    foreach ($your_administrations as $administration) {
        if (isset($department_list[$administration->department])) {
            $your_depts[$administration->department] = $department_list[$administration->department];
        }
    }
}


// ----- Navigation ----- //
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));
$PAGE->navbar->add(get_string('dept_selection', 'local_evaluations'), new moodle_url('admin.php'));

if ($dept) {
    $PAGE->navbar->add(get_string('nav_course_setings', 'local_evaluations'), new moodle_url('admin.php?dept='.$dept));
}


//If a department was selected the create a link the the department selection page
// if ($dept) {
//     $navlinks[1]['link'] = $CFG->wwwroot . '/local/evaluations/admin.php';

//     $navlinks[] = array(
//         'name' => get_string('nav_admin', 'local_evaluations'),
//         'link' => '',
//         'type' => 'misc'
//     );
// }
// $nav = build_navigation($navlinks);


// ----- Stuff ----- //
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_admin', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_admin', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');
$PAGE->set_pagelayout('standard');

// ----- Output ----- //
echo $OUTPUT->header();


if (isset($dept) && $dept != "") {
    $dept_list = get_departments();
    $this_course = " - ".$dept_list[$dept];
} else {
    $this_course = "";
}

echo printHeaderBar("Course Evaluations", hasAdminAccess());
// echo '<div class="navbar">
//         <div class="navbar-inner">
//             <a class="brand" href="#">Course Evaluations'.$this_course.'</a>
//         </div>
//     </div>';

if (count($your_depts) == 0) {
    echo '
        <div class="alert alert-error">
            You are not an adminstrator for any departments
        </div>';

} else {
    if ($dept !== false && is_dept_admin($dept, $USER)) {
        //If the user is the department admin then generate a list of all department admin options.
        echo '<ul class="list-group">';
        echo '<li class="list-group-item"><a href="' . $CFG->wwwroot . '/local/evaluations/change_preamble.php?dept=' . $dept . '">' . get_string('preamble',
            'local_evaluations') . '</a></li>';
        echo '<li class="list-group-item"><a href="' . $CFG->wwwroot . '/local/evaluations/standard_questions.php?dept=' . $dept . '">' . get_string('nav_st_qe',
            'local_evaluations') . '</a></li>';
        echo '<li class="list-group-item"><a href="' . $CFG->wwwroot . '/local/evaluations/coursecompare.php?dept=' . $dept . '">' . get_string('nav_cs_mx',
            'local_evaluations') . '</a></li>'; //COURSE COMPARE WAS REMOVED LEAVING CODE IN SO IT CAN BE ADDED LATER
        echo '</ul>';
    } else {
        //If no dept was selected then cretae a list of all departments the user has available.
        echo '<ul class="list-group">';
        foreach ($your_depts as $dept_code => $deptartment) {
            echo '<li class="list-group-item"><a href="' . $CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept_code . '"> ' . $deptartment . '</a></li>';
        }
        echo'</ul>';
    }
}

echo $OUTPUT->footer();
