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
 * @package    report_coursesize
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace block_lsu_people\output;

defined('MOODLE_INTERNAL') || die();

// use renderable;
// use renderer_base;
// use moodle_url;
// use flexible_table;
// use html_writer;
require_once($CFG->dirroot.'/report/coursesize/locallib.php');
require_once($CFG->libdir.'/tablelib.php');

class cstables {

    protected $download;
    protected $table;
    protected $baseurl;
    protected $reportconfig;
    protected $urlparams;
    public $live;
    public $coursessizecount;
    public $sortdir;

    public function __construct($urlparams, $tablename = '') {

        $this->urlparams = (object)$urlparams;
        if ($this->urlparams->tdir == 3) {
            $this->sortdir = 'ASC';
        } else if ($this->urlparams->tdir == 4) {
            $this->sortdir = 'DESC';
        } else {
            $this->sortdir = '';
        }

        if ($tablename == '') {
            $tablename = "t_".time();
        }
        $this->table = new flexible_table($tablename);

        $this->reportconfig = get_config('report_coursesize');
        $this->reportconfig->usepagination = (int)get_config('report_coursesize', 'paginateresults');
        $this->reportconfig->numberofusers = (int)get_config('report_coursesize', 'numberofusers');
        if ($this->urlparams->perpage == 0) {
            $this->urlparams->perpage = (int)get_config('report_coursesize', 'perpage');
        }

        $this->live = false;

    }
   
    public function get_config($conf) {
        return $this->reportconfig->$conf;
    }

    public function process_user_data($baseurl) {
        global $DB, $CFG;

        $this->table->define_headers(array(get_string('user'), get_string('diskusage', 'report_coursesize')));
        $this->table->define_columns(array('user', 'total'));
        $this->table->define_baseurl($baseurl);
        $this->table->sortable(true, 'total', SORT_ASC);
        // Set the table as downloadable.
        $this->table->is_downloadable(true);

        if (property_exists($this->urlparams, 'download')) {
            $this->table->is_downloading($this->urlparams->download, 'user_size_'.time(), 'LSU User Size Report');
        }

        // Build out the download buttons and options.
        $this->table->show_download_buttons_at([TABLE_P_BOTTOM]);

        $this->table->setup();

        $usersizes = report_coursesize_get_usersizes();

        $usercount = 0;
        foreach ($usersizes as $userid => $size) {
            $usercount++;
            $user = $DB->get_record('user', array('id' => $userid));
            $row = array();
            $row[] = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $userid . '">' . fullname($user) . '</a>';
            $row[] = display_size($size->totalsize);
            $this->table->add_data($row);

            if ($usercount >= $this->reportconfig->numberofusers) {
                break;
            }
        }

        // Finish export and exit before sending any output.
        if ($this->table->is_downloading()) {
            $this->table->finish_output();
            exit;
        }

        // Start the output buffering.
        ob_start();

        // This is a nasty way to avoid the html being littered with str_replace deprecation warnings.
        @$this->table->print_html();

        // Return the data.
        return (object)[
            'tablehtml' => ob_get_clean()
        ];
    }

    public function process_course_data($baseurl) {
        global $DB, $CFG;
        
        $coursesql = 'SELECT cx.id, c.id as courseid ' .
            'FROM {course} c ' .
            ' INNER JOIN {context} cx ON cx.instanceid=c.id AND cx.contextlevel = ' . CONTEXT_COURSE;
        $params = array();
        $courseparams = array();
        $extracoursesql = '';
        $coursecat = 0;

        if (!empty($this->urlparams->category)) {
            $context = context_coursecat::instance($this->urlparams->category);
            $coursecat = core_course_category::get($this->urlparams->category);
            $courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));

            if (!empty($courses)) {
                list($insql, $courseparams) = $DB->get_in_or_equal($courses, SQL_PARAMS_NAMED);
                // $extracoursesql = ' WHERE c.id ' . $insql;
                $extracoursesql = ' WHERE c.category = ' . $this->urlparams->category;
            } else {
                // Don't show any courses if category is selected but category has no courses.
                // This stuff really needs a rewrite!
                $extracoursesql = ' WHERE c.id is null';
            }
        }
        // $coursesql .= $extracoursesql;
        // $params = array_merge($params, $courseparams);
        // $courselookup = $DB->get_records_sql($coursesql, $params);

        $backupsizes = [];
        if (isset($this->reportconfig->calcmethod) && ($this->reportconfig->calcmethod) == 'live') {
            $this->live = true;
        }

        if ($this->live) {
            $filesql = report_coursesize_filesize_sql();
            $sql = "SELECT c.id, c.shortname, c.category, ca.name, rc.filesize
                FROM {course} c
                JOIN ($filesql) rc on rc.course = c.id ";

            // Generate table of backup filesizes too.
            $backupsql = report_coursesize_backupsize_sql();
            $backupsizes = $DB->get_records_sql($backupsql);
            $bytimestamp = "";
        } else {
            $sql = "SELECT c.id, c.shortname, c.category, ca.name, rc.filesize, rc.timestamp, rc.backupsize
              FROM {course} c ";
            $bytimestamp = " JOIN (
                SELECT course, filesize, timestamp, backupsize,
                ROW_NUMBER() OVER (PARTITION BY course ORDER BY timestamp DESC) as rn
                FROM {report_coursesize}
            ) rc ON rc.rn = 1 AND rc.course = c.id ";
        }

        $offset = $this->urlparams->page * $this->urlparams->perpage;

        $sql .= "JOIN {course_categories} ca on c.category = ca.id".
            $bytimestamp.
            $extracoursesql;

        $sarray = array(
            'course' => 'c.shortname',
            'category' => 'c.category',
            'backupsize' => 'rc.backupsize',
            'diskusage' => 'rc.filesize'
        );

        if ($this->urlparams->tsort != '') {
            // $this->urlparams->category will be '' by default and if so let's show the first category rather than all.
            if ($this->urlparams->category == '') {
                $this->urlparams->category = 0;
            }
            $sql .= " ORDER BY ". $sarray[$this->urlparams->tsort]. " ". $this->sortdir;

        } else {
            $sql .= " ORDER BY rc.filesize DESC";
        }

        // IF the download option is on then we want full list.
        if ($this->reportconfig->usepagination && !property_exists($this->urlparams, 'download')) {
            $size = "SELECT COUNT(the_list.id)
                FROM (
                    ".$sql."
                ) AS the_list";
            $this->coursessizecount = $DB->count_records_sql($size);
            $sql .= " LIMIT ".$this->urlparams->perpage." OFFSET $offset";
            $courses = $DB->get_records_sql($sql, $courseparams);
        } else {
            $courses = $DB->get_records_sql($sql, $courseparams);
            $this->coursessizecount = count($courses);
        }

        // $this->table = new flexible_table('admin_coursesize_report');

        $acr_columns = array(
            'course',
            'category',
            'backupsize',
            'diskusage'
        );

        $acr_headers = array(
            'Course Shortname',
            'Course Category',
            'Backup Size',
            'File Size',
        );

        $this->table->define_columns($acr_columns);
        $this->table->define_headers($acr_headers);
        $this->table->define_baseurl($baseurl);
        
        // Enable sorting. The second argument sets the default sort column, 
        // and the third sets the default sort order (e.g., SORT_DESC or SORT_ASC).
        $this->table->sortable(true, 'filesize', SORT_ASC);

        // Set the table as downloadable.
        $this->table->is_downloadable(true);

        if (property_exists($this->urlparams, 'download')) {
            $this->table->is_downloading($this->urlparams->download, 'course_size_'.time(), 'LSU Course Size Report');
        }
        // $this->table->is_downloading('csv');

        // Build out the download buttons and options.
        $this->table->show_download_buttons_at([TABLE_P_BOTTOM]);    
        // Set up the table. This processes the user's sort/page requests.
        $this->table->setup();

        $totalsize = 0;
        $totalbackupsize = 0;
        $downloaddata = array();

        $downloaddata[] = $acr_headers;

        foreach ($courses as $courseid => $course) {
            if ($this->live) {
                if (isset($backupsizes[$course->id])) {
                    $course->backupsize = $backupsizes[$course->id]->filesize;
                } else {
                    $course->backupsize = 0;
                }
            }
            $totalsize = $totalsize + $course->filesize;
            $totalbackupsize = $totalbackupsize + $course->backupsize;
            $coursecontext = context_course::instance($course->id);
            $course->shortname = format_string($course->shortname, true, ['context' => $coursecontext]);
            $course->name = format_string($course->name, true, ['context' => $coursecontext]);
            $row = array();
            $row[] = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . $course->shortname . '</a>';
            $row[] = '<a href="' . $CFG->wwwroot . '/course/index.php?categoryid=' . $course->category . '">' . $course->name . '</a>';

            $readablesize = display_size((int)$course->filesize);
            $a = new stdClass;
            $a->bytes = $course->filesize;
            $a->shortname = $course->shortname;
            $a->backupbytes = $course->backupsize;

            $bytesused = get_string('coursebytes', 'report_coursesize', $a);
            $backupbytesused = get_string('coursebackupbytes', 'report_coursesize', $a);
            $summarylink = new moodle_url('/report/coursesize/course.php', array('id' => $course->id));
            
            if (property_exists($this->urlparams, 'download')) {
                $summary = '';
            } else {
                $summary = html_writer::link($summarylink, ' ' . get_string('coursesummary', 'report_coursesize'));
            }

            
            if ($course->backupsize == "" || $course->backupsize == null) {
                $course->backupsize = 0;
            }
            $row[] = "<span title=\"$backupbytesused\">" . display_size((int)$course->backupsize) . "</span>";
            $row[] = "<span id=\"coursesize_" . $course->shortname . "\" title=\"$bytesused\">$readablesize</span>" . $summary;
            
            $this->table->add_data($row);

            $downloaddata[] = array($course->shortname, $course->name, str_replace(',', '', $readablesize),
                str_replace(',', '', display_size($course->backupsize)));
        }

        // Now add the courses that had no sitedata into the table.
        if (REPORT_COURSESIZE_SHOWEMPTYCOURSES) {
            $a = new stdClass;
            $a->bytes = 0;
            $a->backupbytes = 0;
            foreach ($courses as $cid => $course) {
                $course->shortname = format_string($course->shortname, true, context_course::instance($course->id));
                $a->shortname = $course->shortname;
                $bytesused = get_string('coursebytes', 'report_coursesize', $a);
                $bytesused = get_string('coursebackupbytes', 'report_coursesize', $a);
                $row = array();
                $row[] = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . $course->shortname . '</a>';
                $row[] = "<span title=\"$bytesused\">0</span>";
                $row[] = "<span title=\"$bytesused\">0</span>";
                // $this->table->data[] = $row;
                $this->table->add_data($row);
            }
        }
        // Now add the totals to the bottom of the table.
        // $this->table->data[] = array(); // Add empty row before total.
        $downloaddata[] = array();
        $row = array();
        $row[] = get_string('total');
        $row[] = '';
        $row[] = display_size($totalbackupsize);
        $row[] = display_size($totalsize);
        // $this->table->data[] = $row;
        $this->table->add_data($row);

        $downloaddata[] = [get_string('total'), '', display_size($totalsize), display_size($totalbackupsize)];
        unset($courses);

        
        // Finish export and exit before sending any output.
        // if ($this->table->is_downloading($this->urlparams->download)) {
        if ($this->table->is_downloading()) {
            $this->table->finish_output();
            exit;
        }
        // Add in Course Cat including dropdown to filter.

        // Start the output buffering.
        ob_start();

        // This is a nasty way to avoid the html being littered with str_replace deprecation warnings.
        @$this->table->print_html();
        $wanker = ob_get_clean();
        // Return the data.
        return (object)[
            // 'tablehtml' => ob_get_clean()
            'tablehtml' => $wanker
        ];
        

        // Add in download option. Exports CSV.

        // if ($download == 1) {
        //     $downloadfilename = clean_filename("export_csv");
        //     $csvexport = new csv_export_writer ('commer');
        //     $csvexport->set_filename($downloadfilename);
        //     foreach ($downloaddata as $data) {
        //         $csvexport->add_data($data);
        //     }
        //     $csvexport->download_file();
        //     exit;
        // }

        
    }
}
