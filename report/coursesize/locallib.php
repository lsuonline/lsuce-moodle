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
 * Local functions.
 *
 * @package    report_coursesize
 * @copyright  2022 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('report_coursesize_MAX_RECORDS', 5000);
define('report_coursesize_START_OF_WEEK', 6); // Saturday.


/**
 * Get sql snippet for course filesizes.
 * @return string
 */
// BEGIN LSU - Store course size and history.
// Added - $processtime as func param and in return.
function report_coursesize_filesize_sql($processtime = 0) {
// END LSU - Store course size and history.
    $sqlunion = "UNION ALL
                    SELECT c.id, f.filesize
                    FROM {block_instances} bi
                    JOIN {context} cx1 ON cx1.contextlevel = ".CONTEXT_BLOCK. " AND cx1.instanceid = bi.id
                    JOIN {context} cx2 ON cx2.contextlevel = ". CONTEXT_COURSE. " AND cx2.id = bi.parentcontextid
                    JOIN {course} c ON c.id = cx2.instanceid
                    JOIN {files} f ON f.contextid = cx1.id
                UNION ALL
                    SELECT c.id, f.filesize
                    FROM {course_modules} cm
                    JOIN {context} cx ON cx.contextlevel = ".CONTEXT_MODULE." AND cx.instanceid = cm.id
                    JOIN {course} c ON c.id = cm.course
                    JOIN {files} f ON f.contextid = cx.id";

    return "SELECT id AS course, SUM(filesize) AS filesize, $processtime
              FROM (SELECT c.id, f.filesize
                      FROM {course} c
                      JOIN {context} cx ON cx.contextlevel = ".CONTEXT_COURSE." AND cx.instanceid = c.id
                      JOIN {files} f ON f.contextid = cx.id {$sqlunion}) x
                GROUP BY id";
}

/**
 * Get sql snippet for backup filesizes.
 * @return string
 */
function report_coursesize_backupsize_sql() {
    return "SELECT id AS course, SUM(filesize) AS filesize
              FROM (SELECT c.id, f.filesize
                      FROM {course} c
                      JOIN {context} cx ON cx.contextlevel = ".CONTEXT_COURSE." AND cx.instanceid = c.id
                      JOIN {files} f ON f.contextid = cx.id AND f.component = 'backup') x
            GROUP BY id";
}

/**
 * Helper function to return user file sizes.
 *
 * @return void
 */
function report_coursesize_usersize_sql() {
    return "SELECT userid, sum(filesize) totalsize
            FROM {files}
            WHERE userid is not null
        GROUP BY userid ORDER BY totalsize DESC";
}

/**
 * Helper function to return top users who have most data.
 * It also does caching when necessary.
 *
 * @return array with the user data from DB or cache
 */
function report_coursesize_get_usersizes() {
    global $DB;
    $usercache = \cache::make('report_coursesize', 'topuserdata');
    $data = $usercache->get('usersizes');

    if ($data && (time() < $data->expiry)) { // Valid cache data.
        $usersizes = $data->usersizes;
    } else {
        $numberofusers = get_config('report_coursesize', 'numberofusers');
        $usersizes = $DB->get_records_sql(report_coursesize_usersize_sql(), [], 0, $numberofusers);

        if (!empty($usersizes)) {
            $data = new \stdClass();
            // Set expiry period 24 hours.
            $data->expiry = time() + 24 * 60 * 60;
            $data->usersizes = $usersizes;
            $usercache->set('usersizes', $data);
        }
    }
    return $usersizes;
}

class csvtool {
    public $filename;
    public $filehandle;
    
    public function add_csv_row($row = null) {
        if (!$row) {
            return false;
        }

        foreach ($row as $value) {
            $escapeddata[] = $value;
            // $escapeddata[] = '"' . str_replace('"', '""', $value) . '"';
        }
        fwrite($this->filehandle, implode(',', $escapeddata)."\r\n");

    }


    public function add_upload_dir($courseid = 0, $timestamp = 0) {
        global $CFG;

        $path = 'admin_coursesize_report/'.$courseid;
        $datapath = $CFG->dataroot . '/temp/' . $path . '/';

        $success = check_dir_exists($datapath);

        // If doesexist returns false then let's Moodle's other func.
        if (!$success) {
            $success = make_temp_directory($path);
        }

        if (!$success) {
            return false;
        }

        $csvfilename = $CFG->dataroot . '/temp/' . $path . '/' . 
            core_date::strftime('%Y%m%d-%H%M%S', $timestamp) . '.csv';

        if (!file_exists($csvfilename)) {
            $this->filehandle = fopen($csvfilename, 'w');
        } else {
            $this->filehandle = fopen($csvfilename, 'a');
        }
        return true;
    }

    public function close_handle() {

        fclose($this->filehandle);
    }
}
