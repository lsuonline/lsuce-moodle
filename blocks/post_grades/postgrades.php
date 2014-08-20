<?php

require_once '../../config.php';
require_once 'lib.php';
require_once $CFG->dirroot . '/grade/export/lib.php';

define('EXPIRE_KEY', strtotime('7 days'));

require_login();

$courseid = required_param('courseid', PARAM_INT);
$groupid  = required_param('groupid', PARAM_INT);
$periodid = required_param('periodid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
$period = $DB->get_record('block_post_grades_periods', array('id' => $periodid), '*', MUST_EXIST);

$context = context_course::instance($course->id);

require_capability('block/post_grades:canpost', $context);

$sections = ues_section::from_course($course);
$valid_groups = post_grades::valid_groups($course);

$section = post_grades::find_section($group, $sections);

if (empty($CFG->gradepublishing)) {
    print_error('nopublishing', 'block_post_grades');
}

// Not a valid group
if (!isset($valid_groups[$groupid]) or empty($section)) {
    print_error('notvalidgroup', 'block_post_grades', '', $group->name);
}

// Not a valid posting period
if (!in_array($period, post_grades::active_periods($course))) {
    print_error('notactive', 'block_post_grades');
}

// Need this for LAW types
$ues_course = $section->course()->fill_meta();

$params = array(
    'periodid' => $period->id,
    'sectionid' => $section->id,
    'userid' => $USER->id
);

$_s = ues::gen_str('block_post_grades');

$posting = $DB->get_record('block_post_grades_postings', $params);

// Posted before... complain
if ($posting) {
    $a = new stdClass;
    $a->fullname = $course->fullname;
    $a->name = $group->name;
    $a->post_type = $_s($period->post_type);
    print_error('alreadyposted', 'block_post_grades', '', $a);
}

$posting = (object) $params;

$DB->insert_record('block_post_grades_postings', $posting);

// Data is valid, now process
$key = get_user_key('grade/export', $USER->id, $courseid, '', EXPIRE_KEY);

$course_item = grade_item::fetch(array('itemtype' => 'course', 'courseid' => $courseid));

$export_params = array(
    'id' => $courseid,
    'key' => $key,
    'groupid' => $groupid,
    'itemids' => $course_item->id,
    'export_feedback' => 0,
    'updategradesonly' => 0,
    'decimalpoints' => $course_item->get_decimals(),
    'displaytype' => $period->export_number ?
        GRADE_DISPLAY_TYPE_REAL : GRADE_DISPLAY_TYPE_LETTER
);

if ($ues_course->department == 'LAW') {
    $domino = get_config('block_post_grades', 'law_domino_application_url');
    $export_params['decimalpoints'] = 1;
    $export_params['displaytype'] = GRADE_DISPLAY_TYPE_REAL;

    if (!empty($ues_course->course_first_year)) {
        $course->visible = 0;
        $DB->update_record('course', $course);
    }
} else {
    $domino = get_config('block_post_grades', 'domino_application_url');
}

$export_url = new moodle_url('/grade/export/xml/dump.php', $export_params);

switch($period->post_type) {
    case 'midterm':
        $post_type = 'M'; break;
    case 'final':
    case 'law_first':
    case 'law_upper':
        $post_type = 'F'; break;
    case 'law_degree':
        $post_type = 'D'; break;
    case 'degree':
        $post_type = 'D'; break;
    case 'test':
        $post_type = 'T';
}

$post_params = array(
    'postType' => $post_type,
    'DeptCode' => $ues_course->department,
    'CourseNbr' => $ues_course->cou_number,
    'SectionNbr' => $section->sec_number,
    'MoodleGradeURL' => $ues_course->department == 'LAW' ?
        $export_url->out(false) :
        rawurlencode($export_url->out(false))
);

// We can't be sure about the configured url, so we are required to be safe
$transformed = array();
foreach ($post_params as $key => $value) {
    $transformed[] = "$key=$value";
}

$forward = $domino . implode('&', $transformed);

redirect($forward);
