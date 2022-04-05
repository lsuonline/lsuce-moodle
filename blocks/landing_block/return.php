<?php

require(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/assignment/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/local/utools/umoodle.php');
require_once($CFG->dirroot . '/blocks/landing_block/lib/LBTemplates.php');

/* Print the course name */
function crdc_print_local_course($context, $course) {
    global $OUTPUT;

    /* output the course name as a link to the course page */
    $fullname = format_string($course->fullname, true, array('context' => $context));
    $attributes = array('title' => s($fullname));
    if (empty($course->visible)) {
        $attributes['class'] = 'dimmed';
    }

    echo $OUTPUT->heading(html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $fullname, $attributes), 3);
}

/* Print all a courses instructors */
function crdc_print_local_teachers($context, $course) {
    global $CFG, $DB;

    if (!empty($CFG->coursecontact)) {
        /* find all the names of instructors, etc. and the Moodle string
        that defines their role in the course */
        $names = array();
        $roles = explode(',', $CFG->coursecontact);
        $view_fullnames = has_capability('moodle/site:viewfullnames', $context);
        foreach ($roles as $roleid) {
            $role = $DB->get_record('role', array('id' => $roleid));
            if ($teachers = get_role_users((int) $roleid, $context, true)) {
                foreach ($teachers as $teacher) {
                    $fullname = fullname($teacher, $view_fullnames);
                    $names[] = format_string(role_get_name($role, $context)).': '.
                    html_writer::link(new moodle_url('/user/view.php', array('id' => $teacher->id, 'course' => SITEID)), $fullname);
                }
            }
        }

        /* if we found anything above, print it to the summary screen */
        if (!empty($names)) {
            echo html_writer::start_tag('ul', array('class' => 'teachers'));
            foreach ($names as $name) {
                echo html_writer::tag('li', $name);
            }
            echo html_writer::end_tag('ul');
        }
    }
}

/* Print overview information for a course */
function crdc_print_overview_array($course, $htmlarray) {
  
    /* the hard work was already done for us in crdc_print_overview(),
     we just need to loop over the mod's preview results */

    if (array_key_exists($course->id, $htmlarray)) {
        foreach ($htmlarray[$course->id] as $name => $html) {
            echo $html;
        }
    }
}

/* Print a custom course overview as per the CRDC expectations. */
function crdc_print_overview($courses) {
    global $OUTPUT;

    /* gather all the assignment information on courses */
    $assignment_status = array();

    assignment_print_overview($courses, $assignment_status);

    /* gather all the assignment information on quizes */
    $quiz_status = array();
    
    // called from mod/quiz/lib.php (required above)
    quiz_print_overview($courses, $quiz_status);

  /* for each course, print the title, assignment status and the
     instructors */


    $context = context_system::instance();
    foreach ($courses as $course) {
        // $PAGE->set_context($context);

        echo $OUTPUT->box_start('coursebox');
        
        crdc_print_local_course($context, $course);
        crdc_print_local_teachers($context, $course);
        crdc_print_overview_array($course, $assignment_status);
        crdc_print_overview_array($course, $quiz_status);
        echo $OUTPUT->box_end();
    }
}

/**
* required key from server
* @type string
*/
//defined('MOODLE_INTERNAL') || die();
global $DB, $CFG, $USER;

$key = $CFG->block_landing_block_secret;
// print_to_umoodle_log("Landing_Block -> key from  block_landing_block_secret is: ".$key);
$template = new LBTemplates();

$username = null;
$username2 = $_POST['username']; // optional_param('username', '', PARAM_TEXT);

if (isset($username2)) {
    $username = $username2;
} elseif (isset($USER->username)) {
    $username = $USER->username;
}

$secret2 = $_POST['secret']; // optional_param('username', '', PARAM_TEXT);
if (isset($secret2)) {
    $secret = $secret2;
    $used_this = "post";
} else {
    $secret = sha1($CFG->block_landing_block_secret);
    $used_this = "CFG";

}

if (!isset($key)){// || $secret != $key) {
    // echo "Please Login";
    die();
}

if ($DB->record_exists('user', array('username' => $username))) {
  
    /* get the id of the username provided */
    $uid = $DB->get_field('user', 'id', array('username' => $username));

    /* get full user object so that when we query the DB from now on, we
     are doing it as the user.  This means we'll only see things the
     user can, but also that we'll get the status of their courses,
     not some non-existant user. */
    $USER = get_complete_user_data('id', $uid);
    // print_to_umoodle_log("Landing_Block -> User found: $USER->username");

    /* grab the course data using the id of the student */
    try {
        $courses = enrol_get_users_courses($uid, true, 'id, shortname, modinfo', 'visible DESC, sortorder ASC');
        
    } catch (Exception $ex) {
        die("no courses found");
    }
    // print_to_umoodle_log("Landing_Block -> how many courses: ".count($courses));

    /* print a quick overview of the course */
    // crdc_print_overview($courses);
    $template->lb_print_overview($courses);

    $refresh_date = new DateTime();
    // echo("<div style='visibility:hidden;'>".$refresh_date->getTimestamp()."</div>");
    // echo("<div style='visibility:hidden;'>username is: ".$username."</div>");
    // echo("<div style='visibility:hidden;'>username2 is: ".$username2."</div>");
    // echo("<div style='visibility:hidden;'>used this for secret: ".$used_this."</div>");
    // print_to_umoodle_log("Landing_Block -> Should have printed courses, returning now.");

} else {
    /* used in block to skip printing of course information */
    // echo "no user found";
    // echo("<div style='visibility:hidden;'>".$refresh_date->getTimestamp()."</div>");
}
