<?php
//moodle 2.x
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

$courses = get_courses();//can be feed categoryid to just effect one category
foreach($courses as $course) {
   $context = get_context_instance(CONTEXT_COURSE,$course->id);
   blocks_delete_all_for_context($context->id);
   blocks_add_default_course_blocks($course);
} 
?>
