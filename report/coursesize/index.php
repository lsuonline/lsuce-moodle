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
 * Version information
 *
 * @package    report_coursesize
 * @copyright  2014 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once($CFG->dirroot.'/report/coursesize/locallib.php');
require_once($CFG->dirroot.'/report/coursesize/classes/tables.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/tablelib.php');

admin_externalpage_setup('reportcoursesize');

// $start_time = microtime(true);

$coursecategory = optional_param('category', '', PARAM_INT);
$tsort = optional_param('tsort', '', PARAM_TEXT);
$tdir = optional_param('tdir', '', PARAM_TEXT);
// $download = optional_param('download', '', PARAM_TEXT);
$download2 = optional_param('download', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);
$viewtab = optional_param('view', 'coursesize', PARAM_ALPHA);

$page = optional_param('page', '0', PARAM_INT);
$perpage = (int)optional_param('perpage', 10, PARAM_INT);

$finaloutput = '';
$toprint = '';
// --------------------------------------------------------------------------------------
// Handle the params and prep for URL.
$urlparams = array();
$urlparams['page'] = $page;
$urlparams['perpage'] = $perpage;
if ($download != '') {
    $urlparams['download'] = $download;
}

// Are we sorting?
if ($tsort != '') {
    // $coursecategory will be '' by default and if so let's show the first category rather than all.
}

if ($viewtab == '') {
    $urlparams['view'] = 'coursesize';
} else {
    $urlparams['view'] = $viewtab;
}

if ($coursecategory == '') {
    $coursecategory = 0;
    $urlparams['category'] = 0;
} else {
    $urlparams['category'] = $coursecategory;
}

$urlparams['tsort'] = $tsort;
$urlparams['tdir'] = $tdir;
$urlparams['page'] = $page;

// Set the base URL for sorting links (usually the current page script)
$baseurl = new moodle_url('/report/coursesize/index.php');
$baseurl->params($urlparams);
// -------------------------------------------------------- ------------------------------

// If we should show or hide empty courses.
if (!defined('REPORT_COURSESIZE_SHOWEMPTYCOURSES')) {
    define('REPORT_COURSESIZE_SHOWEMPTYCOURSES', false);
}
$numberofusers = get_config('report_coursesize', 'numberofusers');
// Data for the tabs in the report.
$tabdata = ['coursesize' => '', 'userstopnum' => $numberofusers];
if (!array_key_exists($viewtab, $tabdata)) {
    // For invalid parameter value use 'coursesize'.
    $viewtab = array_keys($tabdata)[0];
}

$tabs = [];
foreach ($tabdata as $tabname => $param) {
    $tabs[] = new tabobject($tabname, new moodle_url($PAGE->url, ['view' => $tabname]),
        get_string($tabname, 'report_coursesize', $param));
}

if (empty($download)) {
    echo $OUTPUT->header();
    echo $OUTPUT->tabtree($tabs, $viewtab);
    // $finaloutput .= $OUTPUT->header();
    // $finaloutput .= $OUTPUT->tabtree($tabs, $viewtab);
}

if ($viewtab == 'userstopnum') {
    
    // if (!empty($usersizes)) {
        // $usertable = new html_table();

    $tobj = new cstables($urlparams, 'admin_usersize_report');
    // $table = new flexible_table('admin_usersize_report');

    $toprint = $tobj->process_user_data($baseurl);

    // unset($users);
    $finaloutput .= $OUTPUT->heading(get_string('userstopnum', 'report_coursesize', $numberofusers));


        /*
        if (!isset($cstable)) {
            $finaloutput .= get_string('nouserfiles', 'report_coursesize');
        } else {
            // print html_writer::table($cstable);
            // Finish export and exit before sending any output.
            if ($cstable->is_downloading($download)) {
                $cstable->finish_output();
                exit;
            }
        }
        */
    // }

} else if ($viewtab == 'coursesize') {

    $tobj = new cstables($urlparams, 'admin_coursesize_report');
    $toprint = $tobj->process_course_data($baseurl);

    // Get the size
    if (!empty($tobj->get_config('filessize')) && !empty($tobj->get_config('filessizeupdated'))) {
            // Total files usage has stored by scheduled task.
            $totalusage = $tobj->get_config('filessize');
            $totaldate = date("Y-m-d H:i", $tobj->get_config('filessizeupdated'));
        } else {
            $totaldate = get_string('never');
            $totalusage = 0;
        }

        $totalusagereadable = display_size((int)$totalusage);
        $systemsize = $systembackupsize = 0;

    $systemsizereadable = display_size($systemsize);
    $systembackupreadable = display_size($systembackupsize);

    if (empty($coursecategory)) {
        
        $updatestring = !empty($tobj->get_config('filessizeupdated')) ? userdate($tobj->get_config('filessizeupdated')) : get_string('never');
        $finaloutput .= $OUTPUT->heading(get_string("sitefilesusage", 'report_coursesize'));
        $finaloutput .= '<strong>' . get_string("totalsitedata", 'report_coursesize', $totalusagereadable) . '</strong> ';
        $finaloutput .= get_string('lastupdate', 'report_coursesize', $updatestring) . "<br/><br/>\n";
        $finaloutput .= get_string('catsystemuse', 'report_coursesize', $systemsizereadable) . "<br/>";
        $finaloutput .= get_string('catsystembackupuse', 'report_coursesize', $systembackupreadable) . "<br/>";
        if (!empty($CFG->filessizelimit)) {
            $finaloutput .= get_string("sizepermitted", 'report_coursesize', number_format($CFG->filessizelimit)) . "<br/>\n";
        }
    }
    $lastupdate = '';
    if (!$tobj->live) {
        if (empty($tobj->get_config('coursesizeupdated'))) {
            $lastupdate = get_string('lastupdatenever', 'report_coursesize');
        } else {
            $lastupdate = get_string('lastupdate', 'report_coursesize', userdate($tobj->get_config('coursesizeupdated')));
        }
        $lastupdate = html_writer::span($lastupdate, 'lastupdate');
    }
    $heading = get_string('coursesize', 'report_coursesize');
    if (!empty($coursecat)) {
        $heading .= " - " . $coursecat->name;
    }
    $finaloutput .= $OUTPUT->heading($heading . ' ' . $lastupdate);

    $desc = get_string('coursesize_desc', 'report_coursesize');

    if (!REPORT_COURSESIZE_SHOWEMPTYCOURSES) {
        $desc .= ' ' . get_string('emptycourseshidden', 'report_coursesize');
    }
    $finaloutput .= $OUTPUT->box($desc);
    $url = '';
    $catlookup = $DB->get_records_sql('select id,name from {course_categories}');
    $options = ['0' => get_string('allcourses', 'report_coursesize')];
    foreach ($catlookup as $cat) {
        $context = context_system::instance();
        $options[$cat->id] = format_string($cat->name, true, ['context' => $context]);
    }
    $filter = $OUTPUT->single_select($url, 'category', $options, $coursecategory, []);
    // $filter .= $OUTPUT->single_button(new moodle_url('index.php', array('download' => 1, 'category' => $this->urlparams->coursecategory)),
    //     get_string('exportcsv', 'report_coursesize'), 'post', ['class' => 'coursesizedownload']);

    $finaloutput .= $OUTPUT->box($filter) . "<br/>";
}

echo $finaloutput;
echo $toprint->tablehtml;
// $cstable->print_html();

if ($tobj->get_config('usepagination')) {
    $perpage = $perpage;
    $results = $tobj->coursessizecount;
    echo $OUTPUT->paging_bar($results, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
