<?php

require_once '../../config.php';
require_once 'lib.php';
require_once $CFG->libdir . '/gradelib.php';

require_login();

$courseid = required_param('courseid', PARAM_INT);
$groupid = required_param('group', PARAM_INT);
$periodid = required_param('period', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);

$context = context_course::instance($course->id);

require_capability('block/post_grades:canpost', $context);

grade_regrade_final_grades($course->id);

$_s = ues::gen_str('block_post_grades');

$periods = post_grades::active_periods($course);

if (empty($periods) or !isset($periods[$periodid])) {
    print_error('notactive', 'block_post_grades');
}

$period = $periods[$periodid];

$valid_groups = post_grades::valid_groups($course);

if (!isset($valid_groups[$groupid])) {
    print_error('notvalidgroup', 'block_post_grades', '', $group->name);
}

$blockname = $_s('pluginname');
$heading = $_s($period->post_type);

$title = $group->name . ': ' . $heading;

$base_url = new moodle_url('/blocks/post_grades/confirm.php', array(
    'courseid' => $courseid,
    'period' => $periodid,
    'group' => $groupid
));

$PAGE->set_url($base_url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($heading);
$PAGE->navbar->add($group->name);

$output = $PAGE->get_renderer('block_post_grades');

$screen = post_grades::create($period, $course, $group);

echo $output->header();
echo $output->heading($heading);

$return = $screen->get_return_state();

if ($return->is_ready()) {
    // Post grade link
    if ($return instanceof post_grades_delegating_return) {
        // Instructor can use regular re-routing
        echo $output->confirm_return($return, false);
    }
    echo $output->confirm_period($course, $group, $period);
} else {
    echo $output->confirm_return($return);
}

echo $output->box_start();
echo $screen->html();
echo $output->box_end();

echo $output->footer();
