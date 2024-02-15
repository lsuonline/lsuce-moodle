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
 * Course breakdown.
 *
 * @package    report_coursesize
 * @copyright  2017 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/theme/lsu.php');

$courseid = required_param('id', PARAM_INT);

$isspeshul = lsu_snippets::role_check_course_size($courseid);

// Check to see if we are allowing special access to this page.
if (!$isspeshul['found']) {
    admin_externalpage_setup('reportcoursesize');
} else {
    
    // Getting the following warnings as they are done in admin_externalpage_setup()

    // - $PAGE->context was not set. 
    // - You may have forgotten to call require_login() or $PAGE->set_context().
    // - This page did not call $PAGE->set_url(...). Using http://lsu/report/coursesize/course.php?id=38581
}

$course = $DB->get_record('course', array('id' => $courseid));

$context = context_course::instance($course->id);
$contextcheck = $context->path . '/%';

// Old query.
// $sizesql = "SELECT a.component, a.filearea, SUM(a.filesize) as filesize
//               FROM (SELECT DISTINCT f.contenthash, f.component, f.filesize, f.filearea
//                     FROM {files} f
//                     JOIN {context} ctx ON f.contextid = ctx.id
//                     WHERE ".$DB->sql_concat('ctx.path', "'/'")." LIKE ?
//                        AND f.filename != '.') a
//              GROUP BY a.component, a.filearea";

$sizesql = "SELECT mcs.section, mcs.name, mm.name as modname, ff.filename,
        ff.filesize, ff.filearea AS filearea, ff.component AS filecomp
    FROM (
        SELECT ctx.id, ctx.instanceid, f.filename, f.filesize, f.filearea, f.component
        FROM {files} f
        JOIN {context} ctx ON f.contextid = ctx.id
        WHERE ".$DB->sql_concat('ctx.path', "'/'")." LIKE ? AND f.filename != '.'
    ) AS ff

    LEFT JOIN {course_modules} mcm ON mcm.id = ff.instanceid AND mcm.course=?
    LEFT JOIN {modules} mm ON mm.id = mcm.module
    LEFT JOIN {course_sections} mcs ON mcs.id = mcm.section
    LEFT JOIN {course} mc ON mc.id = mcm.course

    ORDER BY mcs.section";

$cxsizes = $DB->get_recordset_sql($sizesql, array($contextcheck, $courseid));

$coursetable = new html_table();
$coursetable->attributes['class'] = 'table';
$coursetable->responsive = true;

$fackisthis = new html_table_cell('Section Name');

$headerlist = array(
    new html_table_cell('Section Name'),
    new html_table_cell('Activity Type'),
    new html_table_cell('File name'),
    new html_table_cell(get_string('size'))
);
$tableheader = new html_table_row($headerlist);

$tableheader->header = true;
$tableheader->attributes['class'] = 'table-primary bold';
$coursetable->data[] = $tableheader;

$sectionstart = true;
$currentsection = 0;
$sizetotal = 0;

foreach ($cxsizes as $cxdata) {
    $activitytype = $cxdata->modname;
    
    if ($cxdata->section == null) {
        $activitytype = $cxdata->filearea;
        $sectionlink = '';
    } else {
        // Each time the loop hits a new section let's reset and then create a header.
        if ($currentsection != $cxdata->section) {
            $sectionstart = true;
            $currentsection = $cxdata->section;
        }
        
        $sectionlink = '#section-'.$cxdata->section;
        if ($sectionstart) {
            // Make the rest of the rows for the course section regular.
            $header = new html_table_cell(html_writer::tag('span', "Section ".$cxdata->section, array('id'=>'coursesize_header')));
            $header->header = true;
            $header->colspan = count($headerlist);
            $header->colclasses = array ('centeralign'); 
            $header = new html_table_row(array($header));
            $header->attributes['class'] = 'table-info';

            $sectionstart = false;
            $coursetable->data[] = $header;
        }
    }

    $row = array();
    $row[] = $cxdata->name;
    $row[] = $activitytype;
    $row[] = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid. $sectionlink.'">'.$cxdata->filename.'</a>';
    $row[] = display_size($cxdata->filesize);

    $coursetable->data[] = $row;

    $sizetotal += $cxdata->filesize;
}

// Now the final total row.
$footertitle = new html_table_cell(html_writer::tag('span', "Total Size: ", array()));
$footersize = new html_table_cell(html_writer::tag('span', display_size($sizetotal), array()));
$footertitle->colspan = count($headerlist) - 1;
$footer = new html_table_row(array(
    $footertitle,
    $footersize
));
$footer->cells[0]->style = 'text-align: right;';
// $footer->cells[1]->style = 'text-align: right;';
$footer->attributes['class'] = 'table-primary bold';
$coursetable->data[] = $footer;

$cxsizes->close();

// Calculate filesize shared with other courses.
$sizesql = "SELECT SUM(filesize) FROM (SELECT DISTINCT contenthash, filesize
            FROM {files} f
            JOIN {context} ctx ON f.contextid = ctx.id
            WHERE ".$DB->sql_concat('ctx.path', "'/'")." NOT LIKE ?
                AND f.contenthash IN (SELECT DISTINCT f.contenthash
                                      FROM {files} f
                                      JOIN {context} ctx ON f.contextid = ctx.id
                                     WHERE ".$DB->sql_concat('ctx.path', "'/'")." LIKE ?
                                       AND f.filename != '.')) b";
$size = $DB->get_field_sql($sizesql, array($contextcheck, $contextcheck));
if (!empty($size)) {
    $size = display_size($size);
}


// All the processing done, the rest is just output stuff.

print $OUTPUT->header();

print $OUTPUT->heading(get_string('coursesize', 'report_coursesize'). " - ". format_string($course->fullname));
print $OUTPUT->box(get_string('coursereport', 'report_coursesize'));
if (!empty($size)) {
    print $OUTPUT->box(get_string('sharedusagecourse', 'report_coursesize', $size));
}

print html_writer::table($coursetable);
print $OUTPUT->footer();
