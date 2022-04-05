<?php

/**
 * ************************************************************************
 * *                             utools                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Custom Tools                                             **
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

// require_once('../../../config.php');


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
        $deptartment = array(
            "AGST" => "Agricultural Studies",
            "ANTH" => "Anthropology",
            "ARKY" => "Archaeology",
            "ART" => "Art",
            "AHMS" => "Art History/Museum Studies",
            "ASCI" => "Arts and Science",
            "ASTR" => "Astronomy",
            "BCHM" => "Biochemistry",
            "BIOL" => "Biology",
            "BKFT" => "Blackfoot",
            "CAAP" => "Campus Alberta",
            "CDEV" => "Career Development",
            "CHEM" => "Chemistry",
            "CPSC" => "Computer Science",
            "DRAM" => "Drama",
            "ECON" => "Economics",
            "EDUC" => "Education",
            "ENGG" => "Engineering",
            "ENGL" => "English",
            "ENVS" => "Environmental Science",
            "FREN" => "French",
            "GEOG" => "Geography",
            "GEOL" => "Geology",
            "GERM" => "German",
            "HLSC" => "Health Sciences",
            "HIST" => "History",
            "IDST" => "Interdisciplinary Studies",
            "JPNS" => "Japanese",
            "KNES" => "Kinesiology",
            "LATI" => "Latin",
            "LBED" => "Liberal Education",
            "LBSC" => "Library Science",
            "LING" => "Linguistics",
            "LOGI" => "Logic",
            "MGT"  => "Management",
            "MATH" => "Mathematics",
            "MUSI" => "Music",
            "MUSE" => "Music Ensemble Activity",
            "NAS" => "Native American Studies",
            "NEUR" => "Neuroscience",
            "NMED" => "New Media",
            "NURS" => "Nursing",
            "PHIL" => "Philosophy",
            "PHAC" => "Physical Activity",
            "PHYS" => "Physics",
            "POLI" => "Political Science",
            "PSYC" => "Psychology",
            "PUBH" => "Public Health",
            "RELS" => "Religious Studies",
            "SSCI" => "Social Sciences",
            "SOCI" => "Sociology",
            "SPAN" => "Spanish",
            "STAT" => "Statistics",
            "WMST" => "Women's Studies",
            "WRIT" => "Writing"
        );

        // error_log("\n");
        foreach ($deptartment as $deptcode => $title) {
            // error_log("\nWhat is deptcode: ". $deptcode. " and title: ". $title);
            $this->addDepartment(array('dept' => $title, 'code' => $deptcode, 'islocal' => true));
        }
        die (json_encode(array("success" => "true")));
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

        // error_log("\n");
        // error_log("\nlocallib.php -> get_departments() -> what is the query: ". print_r($dept, 1));
        // error_log("\n");

        // to play nice with the existing code let's make the array of objects into an associative array
        $dept = array();
        foreach ($depts as $code => $this_dept) {
            $dept[$this_dept->dept_code] = $this_dept->dept_name;
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
