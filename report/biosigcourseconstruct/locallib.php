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
 * locallib code for the report_biosigcourseconstruct plugin.
 *
 * @package    report_biosigcourseconstruct
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Function to generate the filter form
 */
function generate_filter_form($category, $showteachers, $showtitles, $showcourses) {
    global $OUTPUT;

    // get list of categories for filter
    $categories = get_category_selection();

    // Print the settings form.
    echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
    echo '<form method="get" action="." id="settingsform"><div>';
    echo $OUTPUT->heading(get_string('reportsettings', 'report_biosigcourseconstruct'));
    echo '<p id="intro">' . get_string('intro', 'report_biosigcourseconstruct') . '</p>';

    $table = new html_table();
    $table->data = array();
    $table->align = array('right', 'left');

    $row = new html_table_row();
    $cell = new html_table_cell(get_string('filtercategory', 'report_biosigcourseconstruct'));
    $cell->style = 'vertical-align: middle;';
    $row->cells[] = $cell;
    $row->cells[] = new html_table_cell(html_writer::select($categories, 'category', $category, false));
    $table->data[] = $row;

    $row = new html_table_row();
    $cell = new html_table_cell(get_string('showteachers', 'report_biosigcourseconstruct'));
    $cell->style = 'vertical-align: middle;';
    $row->cells[] = $cell;
    $row->cells[] = new html_table_cell(html_writer::select_yes_no('showteachers', $showteachers, array()));
    $table->data[] = $row;

    $row = new html_table_row();
    $cell = new html_table_cell(get_string('showtitles', 'report_biosigcourseconstruct'));
    $cell->style = 'vertical-align: middle;';
    $row->cells[] = $cell;
    $row->cells[] = new html_table_cell(html_writer::select_yes_no('showtitles', $showtitles));
    $table->data[] = $row;

    $row = new html_table_row();
    $cell = new html_table_cell(get_string('showcourses', 'report_biosigcourseconstruct'));
    $cell->style = 'vertical-align: middle;';
    $row->cells[] = $cell;
    $row->cells[] = new html_table_cell(html_writer::select(array('All', 'Only BioSig', 'Without BioSig'), 'showcourses', $showcourses, false));
    $table->data[] = $row;

    echo html_writer::table($table);
    echo '<input type="hidden" name="generate" value="1"/>';
    echo '<input type="submit" class="btn btn-secondary" id="settingssubmit" value="' . get_string('generatereport', 'report_biosigcourseconstruct') . '" /></div></form>';
    echo $OUTPUT->box_end();

}

/**
 * Function to get category selection
 * 
 * @return array() Array of category names
 */
function get_category_selection($excludeid = 0, $separator = ' / ') {
    global $DB;

    $sql = "SELECT cc.id, cc.sortorder, cc.name, cc.visible, cc.parent, cc.path
        FROM {course_categories} cc
        JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = :contextcoursecat
        ORDER BY cc.sortorder";
    $rs = $DB->get_recordset_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT));

    $baselist = array();
    $thislist = array();

    foreach ($rs as $record) {
        if (!$record->parent || isset($baselist[$record->parent])) {
            context_helper::preload_from_record($record);
            $context = context_coursecat::instance($record->id);
            $baselist[$record->id] = array(
                'name' => format_string($record->name, true, array('context' => $context)),
                'path' => $record->path
            );
            $thislist[] = $record->id;
        }
    }
    $rs->close();

    $names = array();
    $names[0] = 'All';
    foreach ($thislist as $id) {
        $path = preg_split('|/|', $baselist[$id]['path'], -1, PREG_SPLIT_NO_EMPTY);
        if (!$excludeid || !in_array($excludeid, $path)) {
            $namechunks = array();
            foreach ($path as $parentid) {
                $namechunks[] = $baselist[$parentid]['name'];
            }
            $names[$id] = join($separator, $namechunks);
        }
    }

    return $names;
}

function biosig_course_count($courses) : int {
    $withBioSig = 0;
    foreach($courses as $course) {
        if ($course->tools > 0 || $course->quizzes > 0) {
            $withBioSig++;
        }
    }
	return $withBioSig;
}

function output_summary($courses) {
    global $OUTPUT;

    $withBioSig = 0;
    foreach($courses as $course) {
        if ($course->tools > 0 || $course->quizzes > 0) {
            $withBioSig++;
        }
    }

    $total = count($courses);
    $withoutBioSig = $total - $withBioSig;

    $table = new html_table();
    $table->data = array();

    $table->data[] = array(get_string('summary_with', 'report_biosigcourseconstruct'), $withBioSig);
    $table->data[] = array(get_string('summary_without', 'report_biosigcourseconstruct'), $withoutBioSig);
    $table->data[] = array(get_string('summary_total', 'report_biosigcourseconstruct'), $total);

    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('summary_header', 'report_biosigcourseconstruct'));
    echo html_writer::table($table);
    echo $OUTPUT->box_end();

}

function output_course_table($courses, $showtitles, $showcourses) {
    $table = new html_table();
    $header = array(get_string('column_course', 'report_biosigcourseconstruct'),
        get_string('column_student', 'report_biosigcourseconstruct'),
        get_string('column_tool', 'report_biosigcourseconstruct'),
        get_string('column_quiz', 'report_biosigcourseconstruct')
    );
    if ($showtitles && $showcourses < 2) {
        $header[] = get_string('column_title', 'report_biosigcourseconstruct');
    }
    $table->head = $header;
    $table->data = array();
    $table->class = '';
    $table->id = '';

    foreach ($courses as $course)
    {
        $hasBioSig = $course->tools > 0 || $course->quizzes > 0;

        if ($showcourses == 0 || ($showcourses == 1 && $hasBioSig) || ($showcourses == 2 && !$hasBioSig)) {
            $data = array($course->name, $course->students, $course->tools, $course->quizzes);
            if ($showtitles && $showcourses < 2) {
                $data[] = $course->toolnames;
            }
            $table->data[] = $data;
        }
    }

    echo html_writer::table($table);
}

function get_all_courses($category) {
    global $DB;

    $course_query = "SELECT
            c.id
            , c.fullname
            , COALESCE(t.tool_count, 0) + COALESCE(q.quiz_count, 0) AS total
            , CASE WHEN (s.students IS NULL) THEN 0 ELSE s.students END AS students
            , COALESCE(t.tool_count, 0) AS tool_count
            , t.tools
            , COALESCE(q.quiz_count, 0) AS quiz_count
            , q.quizzes
        FROM
            {course} c
        LEFT JOIN (
            SELECT
                course
                , COUNT(*) AS tool_count
                , GROUP_CONCAT(CONCAT('Tool: ', name) ORDER BY name SEPARATOR '<br />') AS tools
            FROM
                {biosigid}
            GROUP BY
                course
        ) t ON c.id = t.course
        LEFT JOIN (
            SELECT
                course
                , COUNT(*) AS quiz_count
                , GROUP_CONCAT(CONCAT('Quiz: ', name) ORDER BY name SEPARATOR '<br />') AS quizzes
            FROM
                {quiz} q
            JOIN
                {quizaccess_biosigid} qa ON q.id = qa.quizid
            GROUP BY
                course
        ) q ON c.id = q.course
        LEFT JOIN (
            SELECT
                ct.instanceid, COUNT(*) AS students
            FROM
                {context} ct
            LEFT JOIN {role_assignments} ra ON ra.contextid = ct.id
            LEFT JOIN {role} r ON r.id = ra.roleid
            WHERE
                r.shortname = 'student'
            GROUP BY
                ct.instanceid
        ) s ON c.id = s.instanceid
        WHERE
            c.format = 'topics'
            AND (:c1 = '0' OR c.category = :c2)";

    $courselist = $DB->get_recordset_sql($course_query, array('c1' => $category, 'c2' => $category));

    $courses = [];

    foreach ($courselist as $course)
    {
        $c = [
            "name" => $course->fullname,
            "tools" => $course->tool_count,
            "toolnames" => $course->tools,
            "quizzes" => $course->quiz_count,
            "quiznames" => $course->quizzes,
            "students" =>$course->students
        ];
        $courses[$course->id] = (object)$c;
    }

    return $courses;
}

function get_teachers($courses) {
    global $DB;

    $teacher_query = "SELECT
            u.id, u.firstname, u.lastname, t.instanceid
        FROM
            mdl_user u
        JOIN (
            SELECT
                ct.instanceid, ra.userid, r.shortname
            FROM
                {context} ct
            LEFT JOIN {role_assignments} ra ON ra.contextid = ct.id
            LEFT JOIN {role} r ON r.id = ra.roleid
            WHERE
                r.shortname LIKE '%teacher%'
        ) t ON u.id = t.userid";
        
    $teacherlist = $DB->get_recordset_sql($teacher_query);

    $teachers = [];

    foreach ($teacherlist as $teacher) {
        if (array_key_exists($teacher->id, $teachers)) {
            $teachers[$teacher->id]->courses[] = (int)$teacher->instanceid;
        } else {
            $t = [
                "name" => "{$teacher->firstname} {$teacher->lastname}",
                "courses" => [(int)$teacher->instanceid]
            ];
            $teachers[$teacher->id] = (object)$t;
        }
    }

    $courses_with_teachers = [];
    foreach ($teachers as $teacher)
    {
        $courses_with_teachers = array_unique(array_merge($courses_with_teachers, $teacher->courses), SORT_REGULAR);
    }
    $course_keys = array_keys($courses);

    $teachers["-1"] = (object)[
        "name" => "(Unassigned)",
        "courses" => array_diff($course_keys, $courses_with_teachers)
    ];
    return $teachers;
}
