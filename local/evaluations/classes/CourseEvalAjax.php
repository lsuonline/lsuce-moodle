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
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class CourseEvalAJAX
{
    /**
     * Description - The start of the semester will have an empty set of course eval courses, run this function
     *               in the console to auto generate. (only should be run once by an admin).
     * @params - none
     * @return - success or fail.
     */
    public function runPopulateDeptartments($params)
    {
        global $DB;
        
        $no_ajax = isset($params['local']) ? true : false;
        $coursecat = $DB->get_records('course_categories');

        foreach ($coursecat as $cc) {

            error_log("\n Going to insert: ". $cc->name. " <<\n\n");
            $temp = new stdClass();
            $temp->dept_code = "";
            $temp->dept_name = $cc->name;
            $temp->course_cat_id = $cc->id;
            $DB->insert_record('evaluation_departments', $temp);
        }
        error_log("\n\n is no_ajax true: ". $no_ajax. " <<\n\n");
        if ($no_ajax) {
            $depts = $DB->get_records('evaluation_departments');
            return $depts;
        } else {
            die (json_encode(array("success" => "true")));
        }

    }

     /**
     * Add a department to the system.
     * @params array - an array of objects where each object has a course.
     * @return array    Returns an true or false, false will have message.
     */
    public function addDepartment($params)
    {
        global $DB;

        $dept_name = isset($params['dept']) ? $params['dept'] : null;
        $dept_code = isset($params['code']) ? $params['code'] : null;
        $local_call = isset($params['islocal']) ? $params['islocal'] : false;

        $dept = new stdClass();
        $dept->dept_name = $dept_name;
        $dept->dept_code = $dept_code;

        if (debugging()) {
            error_log("\nWhat is the dept name: ". $dept->dept_name);
            error_log("\nWhat is the dept code: ". $dept->dept_code);
        }

        $name_code = "";
        $is_there = $DB->get_records_sql("SELECT * FROM mdl_evaluation_departments WHERE dept_name = '" . $dept_name. "'") ? true : false;
        if (!$is_there) {
            // ok, the name isn't there.........let's check the code
            $is_there = $DB->get_records_sql("SELECT * FROM mdl_evaluation_departments WHERE dept_code = '" . $dept_code. "'") ? true : false;
            if ($is_there) {
                $name_code = "code";
            }
        } else {
            $name_code = "name";
        }

        if (!$is_there) {
            if (!$DB->insert_record('evaluation_departments', $dept)) {
                // error_log("Course Eval -> Insert FAILED for new department: ". $dept_name);
                if ($local_call) {
                    return array("success" => "false", "msg" => "FAIL: The department ". $dept_name . " did not insert into the db.");
                } else {
                    die (json_encode(array("success" => "false", "msg" => "FAIL: The department ". $dept_name . " did not insert into the db.")));
                }

            } else {
                $depts = $this->getDepartments(array('local' => 1));
                $html_return = $this->buildList($depts);
                
                if ($local_call) {
                    return array(
                        "success" => "true",
                        "msg" => "The department ". $dept_name . " has successfully been inserted.",
                        "html" => $html_return
                    );
                } else {
                    die (json_encode(array(
                        "success" => "true",
                        "msg" => "The department ". $dept_name . " has successfully been inserted.",
                        "html" => $html_return
                    )));
                }
            }
        } else {
            if ($local_call) {
                return array("success" => "false", "msg" => "FAIL: This departments ". $name_code ." for: ". $dept_name . " already exists.");
            } else {
                die (json_encode(array("success" => "false", "msg" => "FAIL: This departments ". $name_code ." for: ". $dept_name . " already exists.")));
            }
        }
    }

    /**
     * Redraw the list of departments
     * @params array - an array of departments
     * @return array - the html output
     */
    public function buildList($depts)
    {

        $return_html = "";
        foreach ($depts as $code => $dept) {
            $return_html .= '<tr><td><a href="administration.php?dept=' . $code . '">' . $dept . '</a></td>
                <td id="local_eval_admin_code_'.$code.'"><button class="btn btn-danger pull-right" id="local_eval_admin_code_delete_btn"><i class="fa fa-trash"></i></button></td></tr>';
        }
        return $return_html;
    }

    /**
     * Delete a department from the system.
     * @params array - an array of objects where each object has a course.
     * @return array    Returns an true or false, false will have message.
     */
    public function deleteDepartment($params)
    {
        global $DB;

        $dept_code = isset($params['code']) ? $params['code'] : null;
        $is_there = $DB->get_records_sql("SELECT * FROM mdl_evaluation_departments WHERE dept_code = '" . $dept_code. "'");

        // now, reset the array and then get id and delete.
        $is_there = array_values($is_there);

        if ($is_there && count($is_there) == 1) {
            $DB->delete_records('evaluation_departments', array('id' => $is_there[0]->id));
            die (json_encode(array("success" => "true", "msg" => "This department ". $dept_code . " has been removed.")));
        } else {
            die (json_encode(array("success" => "false", "msg" => "FAIL: This department ". $dept_code . " wasn't found so it wasn't removed.")));
        }
    }
    /**
     * Generates a list of departments that are in the system.
     *
     * @return array    Returns an associative array with the a unique department code
     *      as the key and the the department name as the value.
     */
    // public function get_departments() {
    public function getDepartments($params)
    {
        global $DB;

        $no_ajax = isset($params['local']) ? true : false;

        $sql = "SELECT *
            FROM mdl_evaluation_departments
            ORDER BY dept_name";

        $depts = $DB->get_records_sql($sql);

        if (count($depts) == 0 || $depts == null) {
            $depts = $this->runPopulateDeptartments(array('local' => true));
        }
        // error_log("\n");
        // error_log("\nlocallib.php -> get_departments() -> what is the query: ". print_r($dept, 1));
        // error_log("\n");

        // to play nice with the existing code let's make the array of objects into an associative array
        $depts = array_values($depts);
        $dept = array();
        foreach ($depts as $this_dept) {
            $dept[$this_dept->course_cat_id] = $this_dept->dept_name;
        }
        if ($no_ajax) {
            return $dept;
        } else {
            die (json_encode(array("success" => "true", "data" => $dept)));
        }
    }
    /**
     * Description
     * @param type $params
     * @return type
     */
    public function getAllGroups($params)
    {
        // error_log("Inside CourseEvalAjax.php in the getAllGroups");
        global $DB;
        require_once('ComparisonReports.php');

        $dept = $params['dept'];
        $reports = new ComparisonReports($dept, null, null);
        // error_log("CourseEvalAjax.php -> getAllGroups() reports has been created, now calling func.");

        die (json_encode(array("success" => "true", "html" => $reports->getAllGroupReportsHTML())));

    }

    public function removeThisComparison($params)
    {
        // error_log("CourseEvalAjax.php -> removeThisComparison() -> START");
        global $DB;

        $this_compare_report = $params['remove_comparison'];

        // error_log("CourseEvalAjax.php -> removeThisComparison() -> what is the param: ". $this_compare_report);

        $result = $DB->delete_records('evaluation_compare', array('date' => $this_compare_report));

        // error_log("CourseEvalAjax.php -> removeThisComparison() -> what is the result: ". print_r($result, 1));

        if (!isset($result) || $result == 0) {
            $result = "false";
        }

        // error_log("CourseEvalAjax.php -> removeThisComparison() -> RETURNING");
        die (json_encode(array("success" => $result, "compare_id" => $this_compare_report)));

    }
}
