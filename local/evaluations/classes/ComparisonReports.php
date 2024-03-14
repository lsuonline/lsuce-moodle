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
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Dustin Durrand http://oohoo.biz
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class ComparisonReports {
        
    private $dept; //The department code of the department that the evaluation's course belongs to.
    private $page;
    private $perpage;
    private $notify_user;

    /**
     * Description
     * @param type $dept
     * @param type $page
     * @param type $perpage
     * @return type
     */
    function __construct($dept, $page, $perpage){

        $this->dept = $dept;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->notify_user = null;
    }

    /**
     * Description
     * @return type
     */
    function tableHeader() {
        echo '<tr>';
        echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
        echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
        echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
        echo '<th>' . get_string('status_header', 'local_evaluations') . '</th>';
        echo '<th>' . get_string('response_count', 'local_evaluations') . '</th>';
        echo '<th>' . get_string('tb_t_qok', 'local_evaluations') . '</th>';
        echo '<th>' . get_string('tb_t_adde', 'local_evaluations') . '</th>';
        echo '</tr>';
    }

    /**
     * Description
     * @return type
     */
    function anyNotifications() {
        return ($this->notify_user) ? $this->notify_user : '';
    }

    /**
     * Description
     * @return type
     */
    function purgeNotifications() {
        $this->notify_user = null;
    }

    /**
     * Description
     * @return type
     */
    function purgeAllCompareRecords() {
        global $DB;

        $evaluations = $DB->get_records_select('evaluations','department = \'' . $this->dept . '\'');
        foreach ($evaluations as $evaluation) {
            $DB->delete_records('evaluation_compare',array('evalid' => $evaluation->course));
        }
    }

    /**
     * Description
     * @param type $func_to_call
     * @return type
     */
    function processGetRequest($func_to_call, $action_list) {

        if(method_exists($this, $func_to_call)) {
            $this->$func_to_call($action_list);
        // } else {
            // error_log("The func to call DOES NOT EXISTING so SUCK IT");
        }
    }

    /**
     * Description
     * @param type $dept
     * @param type $page
     * @param type $perpage
     * @return type
     */
    function getCourses($dept, $page, $perpage) {
        global $CFG, $DB;
        $totalcount = $DB->count_records_select('course', "fullname LIKE '%".$dept."%'");

        $url = new moodle_url($CFG->wwwroot . '/local/evaluations/coursecompare.php', array('perpage' => $perpage,'dept' => $dept));

        $search_this = array('fullname' => $dept."-");
        $courses = get_course_eval_search($search_this, $totalcount, "fullname ASC", $page, $perpage);

        return $courses;
    }

    /**
     * Description
     * @param type $dept
     * @param type $page
     * @param type $perpage
     * @return type
     */
    function getCoursesIds($dept, $page, $perpage) {
        global $CFG, $DB;
        $totalcount = $DB->count_records_select('course', "fullname LIKE '%".$dept."%'");

        $url = new moodle_url($CFG->wwwroot . '/local/evaluations/coursecompare.php', array('perpage' => $perpage,'dept' => $dept));

        $search_this = array('fullname' => $dept."-");
        $courses = get_course_eval_search($search_this, $totalcount, "fullname ASC", $page, $perpage);

        foreach ($courses as $course) {
            $courselist[] = $course->id;
        }
        return $courselist;
    }



    /**
     * Description
     * @return type
     */
    function submitComparisonForm() {
        global $CFG, $DB;

        //If search, page, and perpage are all empty then the page was probably submitted with
        //a list of evaluations that we want to compare.
        //-----------------------------------------------
        //Delete the old ones.
        // $evaluations = $DB->get_records_select('evaluations', 'department = \'' . $this->dept . '\'');

        // foreach ($evaluations as $evaluation) {
        //     $DB->delete_records('evaluation_compare',
        //             array('evalid' => $evaluation->course));
        // }

        if(!isset($_SESSION['list_of_selected']) || count($_SESSION['list_of_selected']) < 1) {
            $this->notify_user = '<script>
                $( document ).ready(function() {
                    UofL_Moodle_System.postNotify({
                        title: "Oooops",
                        text: "Sorry, it appears there are no selected courses.",
                        type: "error",
                        opacity: 0.9,
                        animation: "show",
                        icon: "icon-warning"
                    });
                });
            </script>';
            return;
        }

        if(count($_SESSION['list_of_selected']) == 1) {
            $this->notify_user = '<script>
                $( document ).ready(function() {
                    UofL_Moodle_System.postNotify({
                        title: "Oooops",
                        text: "You need more than 1 course to be selected for comparisons.",
                        type: "error",
                        opacity: 0.9,
                        animation: "show",
                        icon: "icon-warning"
                    });
                });
            </script>';
            return;
        }

        if(strlen($CFG->local_eval_current_term) < 1) {
            $this->notify_user = '<script>
                $( document ).ready(function() {
                    UofL_Moodle_System.postNotify({
                        title: "Aborted", 
                        text: "Sorry, please ask the administrator to enter term.",
                        type: "error",
                        opacity: 0.9,
                        animation: "show",
                        icon: "icon-remove"
                    });
                });
            </script>';
            return;
        }

        $date = new DateTime();
        $stampy = $date->getTimestamp();
        //Insert the new ones.
        if(isset($_SESSION['list_of_selected'])) {

            foreach($_SESSION['list_of_selected'] as $courseid) {

                $thee_courseid = explode("-",$courseid);

                $record = new stdClass();
                $record->evalid = $thee_courseid[0];
                $record->courseevalid = $thee_courseid[1];
                $record->dept = $this->dept;
                $record->term = $CFG->local_eval_current_term;
                $record->date = $stampy;

                $DB->insert_record("evaluation_compare", $record);

            }

            $this->notify_user = '<script>
                $( document ).ready(function() {
                    UofL_Moodle_System.postNotify({
                        title: "Sucess",
                        text: "Created the course comparison<br><a href=\''.$CFG->wwwroot.'/local/evaluations/reports.php?dept='.$this->dept.'\'>Click here to view</a>",
                        type: "success",
                        opacity: 0.9,
                        animation: "show",
                        icon: "icon-ok"
                    });
                });
            </script>';

            unset($_SESSION['list_of_selected']);
            $_SESSION['list_of_selected'] = array();
        }
    }

    /**
     * Description
     * @return type
     */
    function updateSelectedList($action_list) {
        global $DB;

        $action = $action_list['index'];

        if($action == "allOnePage") {

            // $action_list['course_ids']
            // $action_list['change_to']
            $list_of_courses = explode(',', $action_list['course_ids']);
            if(!isset($action_list['change_to'])){
                $action_list['change_to'] = "true";
            }

            if($action_list['change_to'] === "true"){
                foreach($list_of_courses as $course){

                    // add to list
                    if (in_array($course, $_SESSION['list_of_selected'])){
                        continue;
                    }else{
                        array_push($_SESSION['list_of_selected'], $course);
                    }
                }
            }else{
                // remove all courses in list
                foreach($list_of_courses as $course){
                    // remove from list
                    if (in_array($course, $_SESSION['list_of_selected'])){
                        $_SESSION['list_of_selected'] = array_diff($_SESSION['list_of_selected'], array($course));
                    }    
                }
            }
            die(json_encode($_SESSION['list_of_selected']));
            // die("Toggled boxes on page");

        }else if($action == "allPages"){
            
            if(!isset($action_list['change_to'])){
                $action_list['change_to'] = "true";
            }
            
            
            $change_to = $action_list['change_to'];
            if($change_to !== "true"){
                // if change all boxes to off then just purge the list.
                unset($_SESSION['list_of_selected']);
                $_SESSION['list_of_selected'] = array();
                die(json_encode($_SESSION['list_of_selected']));
            }
            
            $dept = $action_list['dept'];

            $totalcount = $DB->count_records_select('course', "fullname LIKE '%".$dept."%'");
            $search_this = array('fullname' => $dept."-");
            $courses = $this->get_course_eval_search($search_this, $totalcount, "fullname ASC", 0, 1000);
            
            foreach ($courses as $course) {

                $evals = $DB->get_records('evaluations', array('course' => $course->id, 'deleted' => 0));
                if ($evals == null) {
                    continue;
                } else {
                    foreach ($evals as $eval) {
                        // create the course eval tag
                        $course_eval = $course->id.'-'.$eval->id;
                        // now, if change_to is true then we need to add the course eval to the list
                        if (in_array($course_eval, $_SESSION['list_of_selected'])){
                            // already there
                            continue;
                        }else{
                            array_push($_SESSION['list_of_selected'], $course_eval);
                        }
                    }
                }
            }
            
            die(json_encode($_SESSION['list_of_selected']));

        }else if($action == "printAllLists"){
            die(json_encode($_SESSION['list_of_selected']));
        }else if($action == "purgeList"){
            unset($_SESSION['list_of_selected']);
            $_SESSION['list_of_selected'] = array();
            die(json_encode($_SESSION['list_of_selected']));
        }else if($action == "toggleOne"){
            $cid = $action_list['cid'];

            if(in_array($cid, $_SESSION['list_of_selected'])){
                $_SESSION['list_of_selected'] = array_diff($_SESSION['list_of_selected'], array($cid));
                // unset($_SESSION['list_of_selected'][$action]);
                die("Element ".$cid." has been removed");
            }else{
                array_push($_SESSION['list_of_selected'], $cid );
                die("Element ".$cid." has been added");
            }
        }
    }

    /**
     * Description
     * @param type $searchterms
     * @param type $sort
     * @param type $page
     * @param type $recordsperpage
     * @param type &$totalcount
     * @return type
     */
    function get_course_eval_search($searchterms, &$totalcount, $sort='fullname ASC', $page=0, $recordsperpage=50) {
        global $CFG, $DB;

        if ($DB->sql_regex_supported()) {
            $REGEXP    = $DB->sql_regex(true);
            $NOTREGEXP = $DB->sql_regex(false);
        }

        $searchcond = array();
        $params     = array();
        $i = 0;

        // Thanks Oracle for your non-ansi concat and type limits in coalesce. MDL-29912
        if ($DB->get_dbfamily() == 'oracle') {
            $concat = $DB->sql_concat('c.summary', "' '", 'c.fullname', "' '", 'c.idnumber', "' '", 'c.shortname');
        } else {
            $concat = $DB->sql_concat("COALESCE(c.summary, '')", "' '", 'c.fullname', "' '", 'c.idnumber', "' '", 'c.shortname');
        }

        foreach ($searchterms as $searchterm) {
            $i++;

            $NOT = false; /// Initially we aren't going to perform NOT LIKE searches, only MSSQL and Oracle
                       /// will use it to simulate the "-" operator with LIKE clause

            /// Under Oracle and MSSQL, trim the + and - operators and perform
            /// simpler LIKE (or NOT LIKE) queries
            if (!$DB->sql_regex_supported()) {
                if (substr($searchterm, 0, 1) == '-') {
                    $NOT = true;
                }
                $searchterm = trim($searchterm, '+-');
            }

            // TODO: +- may not work for non latin languages

            if (substr($searchterm,0,1) == '+') {
                $searchterm = trim($searchterm, '+-');
                $searchterm = preg_quote($searchterm, '|');
                $searchcond[] = "$concat $REGEXP :ss$i";
                $params['ss'.$i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";

            } else if (substr($searchterm,0,1) == "-") {
                $searchterm = trim($searchterm, '+-');
                $searchterm = preg_quote($searchterm, '|');
                $searchcond[] = "$concat $NOTREGEXP :ss$i";
                $params['ss'.$i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";

            } else {
                $searchcond[] = $DB->sql_like($concat,":ss$i", false, true, $NOT);
                $params['ss'.$i] = "%$searchterm%";
            }
        }

        if (empty($searchcond)) {
            $totalcount = 0;
            return array();
        }

        $searchcond = implode(" AND ", $searchcond);

        $courses = array();
        $c = 0; // counts how many visible courses we've seen


        // Tiki pagination
        $limitfrom = $page * $recordsperpage;
        $limitto   = $limitfrom + $recordsperpage;

        $ccselect = context_helper::get_preload_record_columns_sql('ctx');
        $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
        $params['contextlevel'] = CONTEXT_COURSE;

        $sql = "SELECT c.*, $ccselect
                  FROM {course} c
               $ccjoin
                 WHERE $searchcond AND c.id <> ".SITEID."
              ORDER BY $sort";

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $course) {

            context_helper::preload_from_record($course);
            $coursecontext = context_course::instance($course->id);
            if ($course->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                // Don't exit this loop till the end
                // we need to count all the visible courses
                // to update $totalcount
                $evals = $DB->get_records(
                    'evaluations',
                    array('course' => $course->id, 'deleted' => 0)
                );

                if ($evals == null) {
                    continue;
                    // $new_courses[] = $course;
                }


                if ($c >= $limitfrom && $c < $limitto) {
                    $courses[$course->id] = $course;
                }
                $c++;
            }
        }
        $rs->close();

        // our caller expects 2 bits of data - our return
        // array, and an updated $totalcount
        $totalcount = $c;
        return $courses;
    }

    function isUserEnvigilator($cid) {
        global $DB, $USER;

        $course_context = context_course::instance($cid);

        $is_envigilator = $DB->get_record_select('department_administrators', "userid=:uid AND department=:dept", array('uid'=>$USER->id, 'dept'=>$this->dept), 'userid,department');
        $am_i_enrolled = is_enrolled($course_context, $USER->id, 'moodle/course:update', true);
        if (!$is_envigilator || $am_i_enrolled) {
            return false;
        } else {
            return true;
        }
    }
    function getAllIndividualReportsHTML() {
        global $DB, $USER, $CFG;

        $perpage = 999;
        $page = 0;


        $totalcount = $DB->count_records_select('course', "fullname LIKE '%".$this->dept."%'");
        $url = new moodle_url($CFG->wwwroot . '/local/evaluations/reports.php', array('perpage' => $perpage,'dept' => $this->dept));
        $search_this = array('fullname' => $this->dept."-");
        $courses = $this->get_course_eval_search($search_this, $totalcount, "fullname ASC", 0, 999);
        // echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
                // <ul class="thumbnails">
        $return_output = '
            <div class="row-fluid" id="course_eval_table">
                <table class="table table-striped">';

        foreach ($courses as $course) {

            // if($this->isUserEnvigilator($course->id)){
            //     return print_error(get_string('restricted', 'local_evaluations'));
            // }
            $course_context = context_course::instance($course->id);
            $is_envigilator = $DB->get_record_select('department_administrators', "userid=:uid AND department=:dept", array('uid'=>$USER->id, 'dept'=>$this->dept), 'userid,department');
            $am_i_enrolled = is_enrolled($course_context, $USER->id, 'moodle/course:update', true);
            if (!$is_envigilator || $am_i_enrolled) {
                continue;
            }

            $current = time();
            //Get all completed evaluations in this course.
            $sql = "SELECT * 
            FROM {evaluations} e

            WHERE e.course = $course->id 
                    AND e.start_time < $current 
                    AND e.end_time < $current AND e.complete = 1 AND e.deleted <> 1";
            $evals = $DB->get_records_sql($sql);

            //If evaluations exist display the evaluation name /w a link to view it and a link to download the pdf.
            if ($evals == null) {
                $return_output .= '<tr><td>

                        <h5>'.$course->fullname.'</h5>
                        <span class="pull-right">
                            No Available Reports
                        </span>
                    </td>
                </tr>';
            } else {
                $return_output .= '<tr><td>';
                $oddeven = 0;
                foreach ($evals as $eval) {

                    $sql = "
                        SELECT ev.id as eval_id, c.id, c.fullname,u.firstname, u.lastname,r.name, ev.name as eval_name
                        FROM mdl_course c
                        JOIN mdl_context ct ON c.id = ct.instanceid
                        JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                        JOIN mdl_user u ON u.id = ra.userid
                        JOIN mdl_role r ON r.id = ra.roleid
                        JOIN mdl_evaluations ev ON ev.course = c.id
                        WHERE  r.id = 10 AND c.id='".$course->id."' AND ev.id='".$eval->id."'
                        GROUP BY eval_id
                    ";

                    // error_log("\n\n");
                    // error_log("ComparisonReports -> getAllIndividualReportsHTML() -> what is the sql: \n". $sql);
                    // error_log("\n\n");

                    $record = $DB->get_records_sql($sql);

                    $href = $CFG->wwwroot . '/local/evaluations/report.php?evalid=' . $eval->id . '&dept=' . $this->dept. '&evcid=' . $eval->id;

                    if ($oddeven++ >= 1) {
                        $return_output .= '<hr>';
                        $oddeven = 0;
                    }

                    foreach ($record as $rec) {

                        $return_output .= '
                            <div class="row-fluid">
                                <div class="col-md-8">
                                    <span class="course_eval_list_title pull-left">'.$rec->eval_name.' - '.$rec->firstname.' '.$rec->lastname.'</span>
                                </div>
                                <div class="col-md-2">
                                    <a href="'.$href.'" class="btn btn-primary btn-block pull-right"><i class="fa fa-eye-open"></i> Preview</a>
                                </div>
                                <div class="col-md-2">
                                    <a href="'.$href.'&force=D" class="btn btn-primary btn-block pull-right"><i class="fa fa-download"> PDF</i></a>
                                </div>
                            </div>';
                    }
                }
                $return_output .= '</td></tr>';
            }
        }
        if (count($courses) < 1) {
            $return_output .= '<h3>There are no reports to show</h3>';
        }
        $return_output .= '</table> </div>';
        return $return_output;
    }

    function getAllGroupReportsHTML() {
        global $DB, $USER, $CFG;

        $counts = $DB->get_records_sql(
            "SELECT date from {evaluation_compare}
            WHERE dept='".$this->dept."'
            GROUP BY date", array('term' => $CFG->local_eval_current_term));

                // <table class="table table-striped">';

        $oddeven = 0;
        $return_output = "";

        foreach ($counts as $key) {

            if ($oddeven % 2) {
                $use_this_css = " eval_comp_grp_row_bck_light";
            } else {
                $use_this_css = " eval_comp_grp_row_bck_dark";
            }

            $return_output .= '<div class="row-fluid'.$use_this_css.'" id="eval_report_id_'.$key->date.'">';
            $return_output .= '<div class="col-md-10">';
            $sql = "
                SELECT evc.id, c.id as cid, c.fullname,u.firstname, u.lastname, r.name, evc.courseevalid, ev.name as eval_name, ev.id as eval_id

                FROM mdl_evaluation_compare evc

                JOIN mdl_evaluations ev ON ev.id=evc.courseevalid
                JOIN mdl_course c ON c.id=evc.evalid
                JOIN mdl_context ct ON c.id = ct.instanceid
                JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                JOIN mdl_user u ON u.id = ra.userid
                JOIN mdl_role r ON r.id = ra.roleid

                WHERE  r.id = 3 AND evc.date = '".$key->date."'
            ";

            $records = $DB->get_records_sql($sql);

            foreach ($records as $record) {
                $href = $CFG->wwwroot . '/local/evaluations/report.php?evalid=' . $record->eval_id . '&dept=' . $this->dept. '&evcid=' . $record->id;

                $return_output .=
                    '<div class="row-fluid" id="eval_comp_grp_spacer">
                        <div class="col-md-8">
                            <span class="course_eval_list_title pull-left">'.$record->eval_name.' - '.$record->firstname.' '.$record->lastname.'</span>
                        </div>

                        <div class="col-md-2">
                            <a href="'.$href.'" class="btn btn-primary btn-block pull-right"><i class="fa fa-eye-open"></i> Preview</a>
                        </div>

                        <div class="col-md-2">
                            <a href="'.$href.'&force=D" class="btn btn-primary btn-block pull-right"><i class="fa fa-download"></i> PDF</a>
                        </div>
                    </div>';
            }

            // close the span of 10
            $return_output .= '</div>
                <div class="col-md-2">
                    <button class="btn btn-danger" id="eval_comp_grp_remove_style" onclick="local_evaluation_funcs.remove_eval_compare('.$key->date.')"><i class="fa fa-remove"></i> Remove Group</button>
                </div>
            </div><hr>';

            $oddeven++;
        }
        return $return_output;
    }
}
