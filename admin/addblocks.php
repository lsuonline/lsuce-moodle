<?php
// add a block to moodle 2.x
require_once('../config.php');
require_once($CFG->libdir.'/blocklib.php');

// This script is being called via the web, so check the password if there is one.
if (!empty($CFG->cronremotepassword)) {
    $pass = optional_param('password', '', PARAM_RAW);
    if ($pass != $CFG->cronremotepassword) {
        // wrong password.
        print_error('cronerrorpassword', 'admin');
        exit; 
    }   
}

$courses = $DB->get_records('course');

foreach ($courses as $course) {
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    // Delete the one that already exists
    $params = array(
        'parentcontextid' => $context->id,
        'blockname' => 'post_grades'
    );

    $post_grades = $DB->get_records('block_instances', $params);

    foreach ($post_grades as $post_grade) {
        $inner_params = array(
            'contextid' => $context->id,
            'blockinstanceid' => $post_grade->id
        );
        blocks_delete_instance($post_grade, true);
        $DB->delete_records('block_positions', $inner_params);
    }
    $DB->delete_records('block_instances', $params);

    $page = new moodle_page();
    $page->set_course($course);
    $page->blocks->add_regions(array(BLOCK_POS_LEFT));
    $page->blocks->add_block('post_grades', BLOCK_POS_LEFT, 0, false, 'course-view-*');
}
