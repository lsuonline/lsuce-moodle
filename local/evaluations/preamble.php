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
 * Displays a preamble for a report.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Parameters ----- //
$eval_id = required_param('eval_id', PARAM_INT);
$context = context_system::instance();

// ----- Security ----- //
require_login();

// ----- Breadcrumbs ----- //
$PAGE->navbar->add(get_string('nav_ev_mn', 'local_evaluations'), new moodle_url('index.php'));


// ----- Stuff ----- //
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/preamble.php');
$PAGE->set_title(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_pagelayout('standard');

//Display Form
echo $OUTPUT->header();

if (isset($dept) && $dept != "") {
    $dept_list = get_departments();
    $this_course = " - ".$dept_list[$dept];
} else {
    $this_course = "";
}
echo '<div class="navbar">
        <div class="navbar-inner">
            <a class="brand" href="#">Course Evaluations'.$this_course.'</a>
        </div>
    </div>';
    
//display preamble
$eval = $DB->get_record('evaluations', array('id' => $eval_id));

// Pre-pre-amble issued.
echo getPreamble();

//Check if the evaluation has a department preamble.
if ($record = $DB->get_record_select('department_preambles', "department='$eval->department'")) {
    
    //If it does display it format it properly.
    echo '<pre style="padding:20px;
                white-space: pre-wrap;       /* css-3 */
                white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
                white-space: -pre-wrap;      /* Opera 4-6 */
                white-space: -o-pre-wrap;    /* Opera 7 */
                word-wrap: break-word;       /* Internet Explorer 5.5+ */">'
    . $record->preamble . '</pre>';
} else {
    //Otherwise display a default preamble.
    echo "<div style='padding:20px'>Dear Students,<br><br>
Thank you for assisting us by providing feedback to Faculty regarding their teaching.  The feedback is an important part of the evaluative process engaged in by individual instructors and by the Faculty generally.  Copies of all course evaluations are forwarded to the individual instructor and go to the Dean’s office for consideration in program planning and development.  Please be assured that the comments you make are confidential and will be provided to individual instructors only after final grades for the course have been submitted to the Dean’s office and approved.  At that time the instructor will receive a numerical summary of the responses to the questionnaire and a list of all comments provided about the course.  These comments are listed numerically without identifying the writer.
<br><br>
The Faculty appreciates your willingness to participate in this process.
</div>";
}
//display button to continue or not
$href = $CFG->wwwroot . '/local/evaluations/response.php?eval_id=' . $eval_id;
$back = $CFG->wwwroot . '/local/evaluations/evals.php';
echo "<center>";
echo "
	<form method='post' action='$href'>
		<input type='submit' value = 'Continue'>
	</form>
<br>
	<form method='get' action='$back'>
		<input type='submit' value = 'Go Back'>
	</form>	
";
echo "</center>";

echo $OUTPUT->footer();
