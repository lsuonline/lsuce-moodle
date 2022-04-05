<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
// require_once($CFG->libdir.'/adminlib.php');
include_once('DeadEnrollments.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$refresh_zombies = optional_param('refresh_dead_zombies', false, PARAM_TEXT);
unset($_POST);

// admin_externalpage_setup('enroltoollmbstatus');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/enrol/lmb/tools/deadview.php');
$PAGE->navbar->add('Dead Enrollments', new moodle_url('deadview.php'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$dead_humanoid = new DeadEnrollments();

if ($refresh_zombies === "zombify") {
    $dead_result = $dead_humanoid->refreshUsers();
} else {
    $dead_result = $dead_humanoid->getUsers();
}

echo '<div class="navbar">
            <div class="navbar-inner">
                <a class="brand" href="#">Dead Enrollments</a>
                <div class="pull-right">
                    <form action="#" method="post">
                        <input type="hidden" name="refresh_dead_zombies" value="zombify"/>
                        <button type="submit" class="btn btn-danger" id="deadview_sync">Refresh List</button>
                    </form>
                </div>
            </div>
        </div>';

$print_zombie = "";

echo '<br>** Note: the only time this list is refreshed is when you push the button **<br>';

if (count($dead_result) > 0) {
    //
    $print_zombie .= '<table class="table">
    <thead><tr>
        <th>Fullname</th>
        <th>UofL ID</th>
        <th>Course ID</th>
        <th>Course Fullname</th>
        <th>Last LMG Action</th>
    </tr></thead>
    <tbody>';


    foreach ($dead_result as $zombie) {
        $print_zombie .= '<tr>' .
                '<td>' . $zombie->fullname . '</td>' .
                '<td>' . $zombie->studentid . '</td>' .
                '<td>' . $zombie->courseid . '</td>' .
                '<td>' . $zombie->coursename . '</td>' .
                '<td>' . date('Y-m-d H:i:s', $zombie->last_modified) . '</td>
            </tr>';
    }
    $print_zombie .= '</tbody></table>';
} else {
    $print_zombie .= '<h3>Is there such a thing as dead zombies??</h3>';
}


echo $print_zombie;
echo $OUTPUT->footer();

exit;
