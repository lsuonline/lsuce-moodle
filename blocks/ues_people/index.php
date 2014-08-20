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
 *
 * @package    block_ues_people
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

require_once 'lib.php';

if (!defined('DEFAULT_PAGE_SIZE')) {
    define('DEFAULT_PAGE_SIZE', 20);
}

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$from_request = optional_param('perpage', ues_people::get_perpage($course), PARAM_INT);

$page = optional_param('page', 0, PARAM_INT);
$perpage = ues_people::set_perpage($course, $from_request);
$roleid = optional_param('roleid', 0, PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$meta = optional_param('meta', 'lastname', PARAM_TEXT);
$sortdir = optional_param('dir', 'ASC', PARAM_TEXT);

$silast = optional_param('silast', 'all', PARAM_TEXT);
$sifirst = optional_param('sifirst', 'all', PARAM_TEXT);

$agree_ferpa = optional_param('FERPA',null,PARAM_INT);
$disagree = optional_param('disagree',null,PARAM_INT);

$export_params = array(
    'roleid' => $roleid,
    'group' => $groupid,
    'id' => $id,
    'silast' => $silast,
    'sifirst' => $sifirst,
    'meta' => $meta,
    'dir' => $sortdir
);

$PAGE->set_url('/blocks/ues_people/index.php', array(
    'id' => $id,
    'roleid' => $roleid,
    'group' => $groupid,
    'page' => $page,
    'perpage' => $perpage
));

$PAGE->set_pagelayout('incourse');
$PAGE->add_body_class('ues_people');

$all_sections = ues_section::from_course($course);

if (empty($all_sections)) {
    print_error('only_ues', 'block_ues_people');
}

require_login($course);

$context = context_course::instance($id);

require_capability('moodle/course:viewparticipants', $context);

$can_view = (
    has_capability('moodle/site:accessallgroups', $context) or
    has_capability('block/ues_people:viewmeta', $context) or
    ues_user::is_teacher_in($all_sections)
);

if (!$can_view) {
    redirect(new moodle_url('/course/view.php', array('id' => $id)));
}

$_s = ues::gen_str('block_ues_people');

$user = ues_user::get(array('id' => $USER->id));

$allroles = get_all_roles();
$roles = ues_people::ues_roles();

$allrolenames = array();
$rolenames = array(0 => get_string('allparticipants'));
foreach ($allroles as $role) {
    $allrolenames[$role->id] = strip_tags(role_get_name($role, $context));
    if (isset($roles[$role->id])) {
        $rolenames[$role->id] = $allrolenames[$role->id];
    }
}

if (empty($rolenames[$roleid])) {
    print_error('noparticipants');
}

$groupmode = groups_get_course_groupmode($course);
$currentgroup = groups_get_course_group($course, true);

if (empty($currentgroup)) {
    $currentgroup = null;
}

$isseparategroups = (
    $course->groupmode == SEPARATEGROUPS and
    !has_capability('moodle/site:accessallgroups', $context)
);

$event = \block_ues_people\event\all_ues_logs_viewed::create(array(
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->trigger();

$meta_names = ues_people::outputs($course);

$using_meta_sort = $using_ues_sort = false;

if (($meta == 'sec_number' or $meta == 'credit_hours') and isset($meta_names[$meta])) {
    $using_ues_sort = true;
} else if (isset($meta_names[$meta])) {
    $using_meta_sort = true;
}

$PAGE->set_title("$course->shortname: " . get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$module = array(
    'name' => 'block_ues_people',
    'fullpath' => '/blocks/ues_people/js/module.js',
    'requires' => array('base', 'dom')
);

$PAGE->requires->js_init_call('M.block_ues_people.init', array(), false, $module);

if ($isseparategroups and (!$currentgroup)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('notingroup'));
    echo $OUTPUT->footer();
    exit;
}

if ($currentgroup) {
    $group = groups_get_group($currentgroup);
}

$upicfields = user_picture::fields('u');

$select = sprintf('SELECT %s, u.email, ues.sec_number, u.deleted,
                  u.username, u.idnumber, u.picture, u.imagealt, u.lang, u.timezone,
                  ues.credit_hours, ues.student_audit', $upicfields);
$joins = array('FROM {user} u');

list($esql, $params) = get_enrolled_sql($context, NULL, $currentgroup, true);
$joins[] = "JOIN ($esql) e ON e.id = u.id";

// See comments in deprecatedlib.php for context_instance_preload_sql().
$ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
$ccjoin   = sprintf("LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = %s)", CONTEXT_USER);


$select .= $ccselect;
$joins[] = $ccjoin;

$unions = array();

$selects = array(
    't' =>
    "SELECT DISTINCT(t.userid), '' AS sec_number, 0 AS credit_hours, 0 AS student_audit FROM ".
    ues_teacher::tablename('t') . ' WHERE ',
    'stu' =>
    'SELECT DISTINCT(stu.userid), sec.sec_number, stu.credit_hours, stum.value AS student_audit FROM '.
    ues_student::tablename('stu') . ' JOIN ' .
    ues_section::tablename('sec') . ' ON (sec.id = stu.sectionid) LEFT JOIN ' .
    ues_student::metatablename('stum') . " ON (stu.id = stum.studentid
        AND stum.name = 'student_audit') WHERE "
);

$sectionids = array_keys($all_sections);

foreach ($selects as $key => $union) {
    $union_where = ues::where()
        ->sectionid->in($sectionids)
        ->status->in(ues::ENROLLED, ues::PROCESSED);

    $unions[$key] = '(' . $union . $union_where->sql(function($k) use ($key) {
        return $key . '.' . $k;
    }) . ')';
}

$joins[] = 'LEFT JOIN ('. implode(' UNION ', $unions) . ') ues ON ues.userid = u.id';

if ($using_meta_sort) {
    $meta_table = ues_user::metatablename('um');
    $joins[] = 'LEFT JOIN ' . $meta_table.
        ' ON (um.userid = u.id AND um.name = :metakey)';
    $params['metakey'] = $meta;
}

$from = implode("\n", $joins);

$wheres = ues::where();

if ($sifirst != 'all') {
    $wheres->firstname->starts_with($sifirst);
}

if ($silast != 'all') {
    $wheres->lastname->starts_with($silast);
}

if ($roleid) {
    $params['roleid'] = $roleid;

    $contextids  = $context->get_parent_context_ids(true);
    $contextlist = sprintf("IN (%s)", implode(',',$contextids));
    $sub = 'SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid ' . $contextlist;

    $wheres->id->raw("IN ($sub)");
}

$where = $wheres->is_empty() ? '' : 'WHERE ' . $wheres->sql(function($k) {
    switch ($k) {
        case 'sectionid': return 'ues.' . $k;
        default: return 'u.' . $k;
    }
});

if ($using_meta_sort) {
    $sort = 'ORDER BY um.value ' . $sortdir;
} else if ($using_ues_sort) {
    $sort = 'ORDER BY ues.' . $meta . ' ' . $sortdir;
} else {
    $sort = 'ORDER BY u.' . $meta . ' ' . $sortdir;
}

// @TODO This query assumes that no user will exist in more than one section of the same course.
$sql = "$select $from $where $sort";

if ($data = data_submitted()) {

    if(!$agree_ferpa){        
        redirect(new moodle_url($PAGE->url, array('disagree' => 1)));
    }

    $controls = ues_people::control_elements($meta_names);

    if (isset($data->export)) {
        $filename = $course->idnumber . '.csv';

        header('Context-Type: text/csv');
        header('Content-Disposition: attachment; fileName=' . $filename);

        $to_csv = function ($user) use ($data, $controls) {
            $user->fill_meta();

            $line = array();

            foreach ($controls as $meta => $output) {
                if (!isset($data->$meta)) {
                    continue;
                }

                if ($meta == 'fullname') {
                    if (isset($user->alternatename)) {
                        $line[] = '"' . $user->alternatename . ' (' . $user->firstname . ')' . '"';
                        $line[] = '"' . $user->lastname . '"';
                    } else {
                        $line[] = '"' . $user->firstname . '"';
                        $line[] = '"' . $user->lastname . '"';
                    }
                } else {
                    $line[] = '"' . strip_tags($output->format($user)) . '"';
                }
            }

            return implode(',', $line);
        };

        $lines = ues_user::by_sql($sql, $params, 0, 0, $to_csv);

        echo implode("\n", $lines);
        exit;
    }

    if (isset($data->save)) {
        $prefix = 'block_ues_people_filter_';
        foreach ($controls as $meta => $output) {
            ues_people::set_filter($meta, isset($data->$meta));
        }
    }
}

$count = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);

$base_url = new moodle_url('/blocks/ues_people/index.php', array(
    'id' => $id,
    'perpage' => $perpage
));

$base_with_params = function($params) use ($base_url) {
    $url = $base_url->out() . '&amp;';

    $mapper = function ($key, $value) { return "$key=$value"; };

    $parms = array_map($mapper, array_keys($params), array_values($params));

    return $url . implode('&amp;', $parms);
};

$paging_bar = $OUTPUT->paging_bar($count, $page, $perpage, $base_with_params(array(
    'roleid' => $roleid,
    'group' => $groupid,
    'silast' => $silast,
    'sifirst' => $sifirst,
    'meta' => $meta,
    'dir' => $sortdir
)));

echo $OUTPUT->header();

if (count($rolenames) > 1) {
    $cr = get_string('currentrole', 'role');

    $rolesnameurl = $base_with_params(array(
        'group' => $groupid, 'meta' => $meta, 'dir' => $sortdir
    ));

    echo html_writer::start_tag('div', array('class' => 'rolesform'));
    echo html_writer::tag('label', $cr . '&nbsp;', array('for' => 'rolesform_jump'));
    echo $OUTPUT->single_select($rolesnameurl, 'roleid', $rolenames, $roleid, null, 'rolesform');
    echo html_writer::end_tag('div');
}

$groups_url = $base_with_params(array(
    'roleid' => $roleid, 'meta' => $meta, 'dir' => $sortdir
));
echo groups_print_course_menu($course, $groups_url);

if ($roleid > 0) {
    $a = new stdClass();
    $a->number = $count;
    $a->role = $rolenames[$roleid];

    $heading = format_string(get_string('xuserswiththerole', 'role', $a));

    if ($currentgroup and $group) {
        $a->group = $group->name;
        $heading .= ' ' . format_string(get_string('ingroup', 'role', $a));
    }

    $heading .= ": $a->number";
    echo $OUTPUT->heading($heading, 3);
} else {
    $strall = get_string('allparticipants');
    $sep = get_string('labelsep', 'langconfig');
    echo $OUTPUT->heading($strall . $sep . $count, 3);
}

$table = new html_table();

$sort_url = $base_with_params(array(
    'roleid' => $roleid,
    'group' => $groupid,
    'silast' => $silast,
    'sifirst' => $sifirst,
    'page' => $page
));

$user_fields = array(
    'username' => new ues_people_element_output('username', get_string('username')),
    'idnumber' => new ues_people_element_output('idnumber', get_string('idnumber'))
);


$meta_names = array_merge($user_fields, $meta_names);

$name = new html_table_cell(
    ues_people::sortable($sort_url, get_string('firstname'), 'firstname') .
    ' (' . get_string('alternatename') . ') ' .
    ues_people::sortable($sort_url, get_string('lastname'), 'lastname')
);
$name->attributes['class'] = 'fullname';
$name->colspan = 2;
if (ues_people::is_filtered('fullname')) {
    $name->style = 'display: none;';
}

$headers = array($name);

foreach ($meta_names as $output) {
    $cell = new html_table_cell(
        ues_people::sortable($sort_url, $output->name, $output->field)
    );
    $cell->attributes['class'] = $output->field;
    if (ues_people::is_filtered($output->field)) {
        $cell->style = 'display: none;';
    }
    $headers[] = $cell;
}

// Transform function to optimize table formatting
$to_row = function ($user) use ($OUTPUT, $meta_names, $id) {

    // Needed for user_picture
    $underlying = new stdClass;
    foreach (get_object_vars($user) as $field => $value) {
        $underlying->$field = $value;
    }

    // Needed for user meta
    $user->fill_meta();

    $user_url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $id));

    $line = array();
    $pic = new html_table_cell($OUTPUT->user_picture($underlying, array('course' => $id)));
    $pic->attributes['class'] = 'fullname';

    if (isset($user->alternatename)) { 
        $cell= new html_table_cell(html_writer::link($user_url, $user->alternatename . ' (' . $user->firstname . ') ' . $user->lastname));
    } else {
        $cell= new html_table_cell(html_writer::link($user_url, fullname($user)));
    }
    $cell->attributes['class'] = 'fullname';

    if (ues_people::is_filtered('fullname')) {
        $pic->style = $cell->style = 'display: none;';
    }

    $line[] = $pic;
    $line[] = $cell;

    foreach ($meta_names as $output) {
        $cell = new html_table_cell($output->format($user));
        $cell->attributes['class'] = $output->field;
        if (ues_people::is_filtered($output->field)) {
            $cell->style = 'display: none;';
        }
        $line[] = $cell;
    }

    return new html_table_row($line);
};

$table->head = $headers;

$offset = $perpage * $page;

$table->data = ues_user::by_sql($sql, $params, $offset, $perpage, $to_row);

$default_params = array(
    'roleid' => $roleid, 'group' => $groupid, 'meta' => $meta, 'dir' => $sortdir
);

$firstinitial = $base_with_params($default_params + array('silast' => $silast));
echo ues_people::initial_bars(get_string('firstname'), 'sifirst', $firstinitial);

$lastinitial = $base_with_params($default_params + array('sifirst' => $sifirst));
echo ues_people::initial_bars(get_string('lastname'), 'silast', $lastinitial);

echo ues_people::show_links($export_params, $count, $perpage);

echo $paging_bar;

echo html_writer::start_tag('div', array('class' => 'no-overflow'));
echo html_writer::table($table);
echo html_writer::end_tag('div');

echo $paging_bar;

echo ues_people::show_links($export_params, $count, $perpage);
echo ues_people::controls($export_params, $meta_names, $disagree);

echo $OUTPUT->footer();
