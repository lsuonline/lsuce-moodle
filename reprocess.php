<?php

# Call http://host/reprocess.php?id=COURSEID

/*
 * This script will be needed after a restore, so the idnumber will
 * be lost
 */

require_once 'config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';

ues::require_daos();

if (!is_siteadmin($USER->id)) {
    echo "You don't exist. Go away.";
    exit;
}

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

header("Content-Type: text/plain");

$nonprimary_roleid = get_config('enrol_ues', 'teacher_role');

if (empty($course->idnumber)) {
    echo "{$course->fullname} has lost its idnumber association...\n";
    echo "Trying to find one based on instructor on record...\n";

    $primary_roleid = get_config('enrol_ues', 'editingteacher_role');

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $role = $DB->get_record('role', array('id' => $primary_roleid));

    $primarys = get_users_from_role_on_context($role, $context);

    if (empty($primarys)) {
        echo "No primaries enrolled... Attempting to find non-primaries\n";
        $role = $DB->get_record('role', array('id' => $nonprimary_roleid));

        $nonprimarys = get_users_from_role_on_context($role, $context);

        if (empty($nonprimarys)) {
            echo "ERROR: could not find an instructor on record";
            exit;
        }

        $teacher = current($nonprimarys);
    } else {
        $teacher = current($primarys);
    }

    echo "Found an instructor on record with id: {$teacher->userid}\n";

    $sql = "SELECT g.* FROM {groups} g, {groups_members} gr WHERE gr.userid = :userid AND g.courseid = :courseid AND g.id = gr.groupid";

    $params = array('userid' => $teacher->userid, 'courseid' => $courseid);

    $groups = $DB->get_records_sql($sql, $params);
    if (empty($groups)) {
        echo "ERROR: could not find original group association. It is too dangerous to continue.\n";
        exit;
    }

    $group = current($groups);

    echo "Found original group association: {$group->name}\n";

    list($dept, $number, $section_number) = explode(' ', $group->name);

    $ues_course = ues_course::get(array('department' => $dept, 'cou_number' => $number));

    echo "Found UES course information: {$ues_course->department} {$ues_course->cou_number}\n";

    $names = explode(' ', $course->shortname);

    if (preg_match('/\(\w\)/', $names[2], $matches)) {
        $session = $matches[1];
    } else {
        $session = '';
    }

    $session = empty($names[2]) ? '' : 

    $campus = $ues_course->department == 'LAW' ? 'LAW' : 'LSU';

    $ues_semester = ues_semester::get(array(
        'year' => $names[0],
        'name' => $names[1],
        'campus' => $campus,
        'session_key' => $session
    ));

    $sections = $ues_course->sections($ues_semester);

    echo "Found " . count($sections) . " associated with this course and semester\n";

    foreach ($sections as $section) {
        if ($section->status == ues::PENDING or $section->status == ues::SKIPPED) {
            continue;
        }

        $teachers = $section->teachers();

        foreach ($teachers as $teach) {
            if ($teacher->userid == $teach->userid) {
                $idnumber = $section->idnumber;
                break 2;
            }
        }
    }

    if (empty($idnumber)) {
        echo "ERROR: could not find an idnumber in the database. It needs to be rebuilt from the webservice.\n";
        exit;
    }

    echo "Found idnumber: {$idnumber}\n";
    $course->idnumber = $idnumber;
    $DB->update_record('course', $course);
}

$sections = ues_section::from_course($course);

ues::enroll_users($sections, false);

echo "Done";
