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
require_once('question_types/question_4_excellent.php');
require_once('question_types/question_5_rate_wc.php');
require_once('question_types/question_5_superior.php');
require_once('question_types/question_comment.php');
require_once('question_types/question_years.php');
//require_once('question_types/question_agree_disagree.php');
require_once('question_types/question_course_pace.php');
require_once('question_types/question_required_hours.php');
require_once('question_types/question_expected_grade.php');
require_once('question_types/question_gpa.php');
require_once('question_types/question_interest_level.php');
require_once('question_types/question_SA_SD.php');
require_once('question_types/question_SA_SD_NA.php');

class anonym_report_PDF extends TCPDF {

    private $course;
    private $teachers;
    private $students;
    private $eval;
    private $std_questions;
    private $nonstd_questions;
    private $comments;
    private $dept;

    function __construct($orientation, $unit, $format, $unicode, $encoding,
            $diskcache, $eval, $course, $dept) {
        global $DB, $CFG;

        $this->eval = $eval;
        $this->course = $course;
        $this->dept = $dept;
        parent::__construct($orientation, $unit, $format, $unicode, $encoding,
                $diskcache);

        //Must get stdquestions before students.
        $this->std_questions = $this->get_eval_std_questions();
        $this->nonstd_questions = $this->get_eval_nonstd_questions();
        $this->teachers = $this->get_course_teachers();
        $this->students = $this->get_course_users();

        $this->comments = $this->get_eval_comments();

        $this->setup();

        $this->AddPage();
        $this->print_general_info();

        $this->SetFont('DejaVuSans', '', 9);
        $this->AddPage();

        $this->build_response_table($this->std_questions, true);

        if($this->nonstd_questions){
            $this->AddPage();
            $this->build_response_table($this->nonstd_questions);
        }
        
        $this->AddPage();

        $this->build_comment_list();
    }

    /**
     * Perform basic TCPDF setup.
     */
    public function setup() {
        // set default header data
        $this->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH,
                PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER + 5);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set font
        $this->SetFont('times', 'B', 12);
    }

    /**
     * Is this evaluation part of a group comparison? if not then don't show it.
     * @return true or false
     */
    private function check_if_group_eval(){
        global $DB;

        $sql = "SELECT * from mdl_evaluation_compare WHERE courseevalid=".$this->eval->id;
        $records = $DB->get_records_sql($sql);

        // $counts = $DB->get_records_sql("SELECT courseevalid from {evaluation_compare}", array('courseevalid' => $this->eval->id));
        if(count($records)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @override
     * 
     * Function to setup header that will be placed on each page.
     * 
     * @global moodle_database $DB
     */
    public function Header() {
        global $DB;

        // Logo
        $this->SetFont('helvetica', 'B', 14);
        // Title
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        // $this->SetTextColor(73, 115, 183);
        // $this->SetFillColor(255, 255, 255);
        
        $this->SetFont('helvetica', 'B', 14);
        $category = $DB->get_record('course_categories', array('id' => $this->course->category));
        
        // $this->MultiCell(70, 30, "<img src='http://u201401/local/evaulations/img/logo.png'>", 1, 'L', 1, 1, '', '', true, 1, true, true, 50);
        // $this->MultiCell(134, 5, $this->course->fullname . ' [' . $category->name . ']', 1, 'R', 1, 1, '', '', true, 1, false, true, 10);
        
        $this->MultiCell(0, 5, $this->course->fullname . ' [' . $category->name . ']', 1, 'C', 1, 1, '', '', true, 1, false, true, 10);
        $this->MultiCell(134, 5, $this->eval->name, 1, 'L', 1, 0, '', '', true);

        if (count($this->teachers) >= 1) {
            $name = htmlspecialchars_decode($this->teachers[0]->firstname, ENT_QUOTES) . ' ' . htmlspecialchars_decode($this->teachers[0]->lastname, ENT_QUOTES)." ";
        } else {
            $name = '';
        }

        $this->MultiCell(133, 5, $name, 1, 'R', 1, 0, '', '', true);
    }

    /**
     * @override
     * 
     * Function to setup footer that will be placed on each page. 
     */
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10,
                'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
                0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    /**
     * Print out the general information on the first page. This includes  response
     * rate, number of questions and a list of all the questions.
     * 
     * @global moodle_database $DB
     */
    private function print_general_info() {
        global $DB;

        //Response Rate
        $role = $DB->get_record('role', array('archetype' => 'student', 'shortname' => 'student'));
        $context = context_course::instance($this->course->id);

        $all_students = array_values(get_role_users($role->id, $context));
        $total_students = count($all_students);
        $responses = get_eval_reponses_count($this->eval->id);

        //calculates the percent submitted
        $percent = round((($responses / $total_students) * 100));

        if ($percent > 100) {
            $percent = 100;
        }

        $output = get_string('response_rate', 'local_evaluations') . " $responses / $total_students : $percent%";


        $this->Cell(0, $h = 0, $output, $border = 0, $ln = 1, $align = 'l',
                $fill = false, $link = '', $stretch = 0,
                $ignore_min_height = false, $calign = 'T', $valign = 'M');

        $questions = $DB->get_records('evaluation_questions',
                array('evalid' => $this->eval->id), 'question_order ASC');

        //Question Count
        $output = get_string('question_count', 'local_evaluations') . count($questions);
        $this->Cell(0, $h = 0, $output, $border = 0, $ln = 1, $align = 'l',
                $fill = false, $link = '', $stretch = 0,
                $ignore_min_height = false, $calign = 'T', $valign = 'M');

        //output the questions in this report
        $this->ln();

        if ($questions) {
            $question_i = 1;
            foreach ($questions as $question) {
                $this->SetFont('times', '', 12);
                $txt = "Question $question_i : ";
                $txt .= strip_tags($question->question);
                $this->MultiCell(0, 0, $txt, $border = 0, $align = 'l',
                        $fill = false, $ln = 1, $x = '', $y = '',
                        $reseth = true, $stretch = 0, $ishtml = true,
                        $autopadding = true, $maxh = 0, $valign = 'T',
                        $fitcell = false);
                $question_i++;
            }
        }

        $this->ln();
    }

    /**
     * Builds a response table for the each question passed in. A response table
     * is a table that lists all the students (using anonymous ids) and their 
     * numerical responses to the questions.
     * 
     * @global moodle_database $DB
     * @param question[] $questions A set of question objects that represent the
     *  questions you want responses for in this table.
     * 
     * @param boolean $global_stats Whether or not to use stats from across this
     *  department whehn printing the statistics lines. This defaults to false
     *  and will only output stats for the class as a result.
     */
    private function build_response_table($questions, $global_stats = false) {
        global $DB;


        //Define the html for the table.
        $tbl = "<table class=\"top\" align=\"center\">";
        $tbl .= "
        <style type=\"text/css\">

        table.top td {
            border-width: 1px;
            padding: 1px;
            border-color: black;
            background-color: white;
            -moz-border-radius: ;
        }
        </style>
        ";

        // --- Add in header row --- //
        $tbl .= "<tr>";
        $tbl .= "<th>Stu.</th>";
        foreach ($questions as $question) {
            $q = "Q" . ($question->get_order() + 1);
            $tbl .= "<th align = \"center\" > $q </th>";
        }

        $tbl .= "<th align = \"center\" >x&#772;</th>";
        $tbl .= "<th align = \"center\" > %4/5 </th>";
        $tbl .= "<th align = \"center\" > %1/2 </th>";
        $tbl .= "</tr>";



        // --- Now we fill in the table --- //
        //Create an array to hold all the responses to each question.
        $question_responses = array();

        //Get Output responses for each student and keep track of responses to each question.
        $u_num = 1;
        foreach ($this->students as $student) {

            $student_responses = array();

            //Create new row for this student.
            $tbl .= "<tr>";
            $tbl .= "<td align=\"center\" style=\"border:1px solid black;\"> " . $u_num++ . " </td>";

            foreach ($questions as $question) {

                //If this is the first time this question was answered then create a new array for it.
                if (!array_key_exists($question->get_id(), $question_responses)) {
                    $question_responses[$question->get_id()] = array();
                }

                $response = $DB->get_record('evaluation_response',
                        array('question_id' => $question->get_id(), 'user_id' => $student->id));
                // if ($response) {
                if ($response && isset($response->response)) {
                    $resp_val = $response->response;
                    //Only include responses in stats if they were not left blank.
                    //Add  response value to the student list.
                    $student_responses[] = $resp_val;

                    //Add it to the question list as well.
                    $question_responses[$question->get_id()][] = $resp_val;
                } else {
                    $resp_val = null;
                }

                //Add response value to table.
                $tbl .= "<td align=\"center\"> $resp_val </td>";
            }
            $positive = round($this->get_positive_response_rate($student_responses,
                            $questions));
            $negative = round($this->get_negative_response_rate($student_responses,
                            $questions));
            $average = round($this->get_response_avg($student_responses,
                            $questions), 1);
            //Add stats to table.
            $tbl .="<td align = \"center\" style=\"border-left:2px solid black;\"> $average </td>";
            $tbl .="<td align = \"center\" > $positive </td>";
            $tbl .="<td align = \"center\" style=\"border-right:2px solid black;\"> $negative </td>";
            $tbl .="</tr>";
        }

        //====================================================================================
        //====================================================================================
        //====================================================================================

        // get the class stats
        $all_eval_row_local = "<tr><td colspan=\"100%\"></td></tr><tr><td colspan=\"3\" align=\"center\" style=\"border-top: 0px thin white;\"><b>Class Evaluations</b></td></tr>";
        $x_bar_row_local    = "<tr><td align=\"center\" style=\"border: 1px solid black;\">x&#772;</td>";
        $positive_row_local = "<tr><td align=\"center\" style=\"border: 1px solid black;\">%4/5</td>";
        $negative_row_local = "<tr><td align=\"center\" style=\"border: 1px solid black;\">%1/2</td>";

        foreach ($questions as $question){
            $positive_local = round($this->get_positive_response_rate($question_responses[$question->get_id()], $question));
            $negative_local = round($this->get_negative_response_rate($question_responses[$question->get_id()], $question));
            $average_local = round($this->get_response_avg($question_responses[$question->get_id()], $question), 1);
            $average_local_mean += $this->get_response_avg($question_responses[$question->get_id()], $question);

            //Fill in stats for each question.
            $positive_row_local .= "<td align=\"center\" style=\"border:2px solid black;\"> $positive_local</td>";
            $negative_row_local .= "<td align=\"center\" style=\"border:2px solid black;\"> $negative_local</td>";
            $x_bar_row_local    .= "<td align=\"center\" style=\"border:2px solid black;\"> $average_local</td>";
        }

        // get the mean of the class evaluations (end box for first row (x))
        $x_bar_row_local .= "<td align=\"center\" style=\"border:2px solid black;\">".round($average_local_mean / count($questions), 2)."</td>";
        // $positive_row_local .= "<td align=\"center\" style=\"border:2px solid black;\">&nbsp;</td>";
        // $negative_row_local .= "<td align=\"center\" style=\"border:2px solid black;\">&nbsp;</td>";

        $positive_row_local .= "</tr>";
        $negative_row_local .= "</tr>";
        $x_bar_row_local .= "</tr>";

        $tbl.= $all_eval_row_local . $x_bar_row_local . $positive_row_local . $negative_row_local;


        //====================================================================================
        //====================================================================================
        //====================================================================================

        // STANDARD QUESTIONS ONLY, non standard will by pass this 
        if ($global_stats) {
            //If we're looking at all evaluations then the responses we already got are uesless so let's start with an empty array.
            $question_responses = array();

            foreach ($questions as $question) {
                //If this is the first time this question was answered then create a new array for it.
                if (!array_key_exists($question->get_id(), $question_responses)) {
                    $question_responses[$question->get_id()] = array();
                }
                //-----
                //We assume that standard questions show up in the same order on each page.
                //Should standard questions change at any point then the evaluation statistics will
                //become corrupt unless course compare page is changed.
                //-----
                //Get list of all evaluations that are in the same department as this one.
                // $evaluation_list = $DB->get_records_select('evaluations',
                //         'department = \'' . $this->eval->department . '\'');

                // $evaluation_list = $DB->get_records_select('evaluations',
                //         'department = \'' . $this->eval->department . '\'');

                $sql = "
                    SELECT ev.id, ev.course, ev.start_time, ev.end_time, ev.name, ev.deleted, ev.email_students, ev.complete, ev.department, evc.date
                    FROM mdl_evaluations ev
                    JOIN mdl_evaluation_compare evc ON ev.id=evc.courseevalid
                    WHERE  evc.date = '".$this->eval->compare_date."'
                ";

                $evaluation_list = $DB->get_records_sql($sql);
                //Now get all questions that have the same order as this one.
                $question_list = array();
                // die;
                foreach ($evaluation_list as $evaluation) {
                    
                    //  if ($DB->get_record('evaluation_compare', array('courseevalid' => $evaluation->id))) {
                    $temp = $DB->get_records('evaluation_questions', array('question_order' => $question->get_order(), 'evalid' => $evaluation->id));
                    $question_list = array_merge($question_list, $DB->get_records('evaluation_questions', array('question_order' => $question->get_order(), 'evalid' => $evaluation->id)));
                    //}
                }

                $response_list = array();
                //Now get responses for all these questions.
                foreach ($question_list as $aQuestion) {
                    $response_list = array_merge($response_list,
                            $DB->get_records('evaluation_response',
                                    array('question_id' => $aQuestion->id)));
                }

                foreach ($response_list as $response) {
                    $question_responses[$question->get_id()][] = $response->response;
                }
            }
        
            // check if this course is part of a group calculation
            if($this->check_if_group_eval()){

                //These three rows are the bottom statistic rows for each question.
                $all_eval_row = "<tr><td colspan=\"100%\"></td></tr><tr><td colspan=\"3\" align=\"center\" style=\"border-top: 0px thin white;\"><b>Faculty Comparison</b></td></tr>";
                $x_bar_row    = "<tr><td align=\"center\" style=\"border: 1px solid black;\">x&#772;</td>";
                $positive_row = "<tr><td align=\"center\" style=\"border: 1px solid black;\">%4/5</td>";
                $negative_row = "<tr><td align=\"center\" style=\"border: 1px solid black;\">%1/2</td>";

                foreach ($questions as $question) {
                    $positive = round($this->get_positive_response_rate($question_responses[$question->get_id()],$question));
                    $negative = round($this->get_negative_response_rate($question_responses[$question->get_id()],$question));
                    $average = round($this->get_response_avg($question_responses[$question->get_id()],$question), 1);
                    $average_mean += $this->get_response_avg($question_responses[$question->get_id()],$question);

                    //Fill in stats for each question.
                    $positive_row .= "<td align=\"center\" style=\"border:2px solid black;\"> $positive</td>";
                    $negative_row .= "<td align=\"center\" style=\"border:2px solid black;\"> $negative</td>";
                    $x_bar_row    .= "<td align=\"center\" style=\"border:2px solid black;\"> $average</td>";
                }

                // get the mean of the faculty comparison
                $x_bar_row .= "<td align=\"center\" style=\"border:2px solid black;\">".round($average_mean / count($questions), 2)."</td>";

                $positive_row .= "</tr>";
                $negative_row .= "</tr>";
                $x_bar_row .= "</tr>";
            }
            $tbl.= $all_eval_row . $x_bar_row . $positive_row . $negative_row;
        }
        
        $tbl .= "</table>";

        $this->writeHTML($tbl, true, false, false, false, '');
    }

    /**
     * Creates a list of responses to all the comment questions.
     * 
     * @global moodle_database $DB
     */
    private function build_comment_list() {
        global $DB;
        $comment_info = '';

        $comment_array = array();
        foreach ($this->comments as $comment) {
            $comment_info .= '<strong>' . $comment->get_question() . "</strong>";
            $comment_info .= '<pre>';
            $comment_info .= '<hr/><ol>';
            foreach ($this->students as $student) {
                $response = $DB->get_record('evaluation_response',
                        array('question_id' => $comment->get_id(), 'user_id' => $student->id));
                $comment_info .= '<li>' . $response->question_comment . '</li>';
            }
            $comment_info .= '</ol><br/><br/>';
            $comment_info .= '</pre>';
        }

        //writing the question comment table
        $this->writeHTML($comment_info, true, false, false, false, '');
    }

    /**
     * Gets the average of all the responses
     * 
     * @param int[] $responses   A list of all the numerical responses for each 
     *  question
     * @param stdClass[] $questions   A list of all all the questions that are 
     *  associated with the responses. Or a single question that all the reponses are for.
     *  There will always be the same number of responses as questions.
     * 
     * @return float The average of all the responses.
     */
    private function get_response_avg($responses, $questions) {
        if (count($responses) == 0) {
            return;
        }
        if (is_array($questions)) {
            $sum = 0;
            foreach ($responses as $response) {
                $sum += $response;
            }
            return $sum / count($responses);
        } else {
            return $questions->average($responses);
        }
    }

    /**
     * Gets the number of positive responses as a percentage of the number of 
     * responses.
     * 
     * @param int[] $responses   A list of all the numerical responses for each 
     *  question
     * @param stdClass() $questions   A list of all all the questions that are 
     *  associated with the responses. Or a single question that all the reponses are for.
     *  There will always be the same number of responses as questions.
     * 
     * @return float    A percent comparison between the number of positive 
     *  responses and the number of responses.
     */
    private function get_positive_response_rate($responses, $questions) {
        if (count($responses) == 0) {
            return;
        }
        if (is_array($questions)) {
            $positive = 0;
            for ($i = 0; $i < count($responses); $i++) {
                if ($questions[$i]->isPositive($responses[$i])) {
                    $positive++;
                }
            }
            return $positive / count($questions) * 100.0;
        } else {
            $positive = 0;
            for ($i = 0; $i < count($responses); $i++) {
                if ($questions->isPositive($responses[$i])) {
                    $positive++;
                }
            }
            return $positive / count($responses) * 100.0;
        }
    }

    /**
     * Gets the number of negative responses as a percentage of the number of 
     * responses.
     * 
     * @param int[] $responses   A list of all the numerical responses for each 
     *  question
     * @param stdClass() $questions   A list of all all the questions that are 
     *  associated with the responses. Or a single question that all the reponses are for.
     *  There will always be the same number of responses as questions.
     * 
     * @return float    A percent comparison between the number of negative 
     *  responses and the number of responses.
     */
    private function get_negative_response_rate($responses, $questions) {
        if (count($responses) == 0) {
            return;
        }
        if (is_array($questions)) {
            $negative = 0;
            for ($i = 0; $i < count($responses); $i++) {
                if ($questions[$i]->isNegative($responses[$i])) {
                    $negative++;
                }
            }
            return $negative / count($questions) * 100.0;
        } else {
            $negative = 0;
            for ($i = 0; $i < count($responses); $i++) {
                if ($questions->isNegative($responses[$i])) {
                    $negative++;
                }
            }
            return $negative / count($responses) * 100.0;
        }
    }

    /**
     * Get a list of all students in the course. It Uses roles to determine if a
     * user is a student.
     * @global moodle_database $DB
     * 
     * @return stdClass[] an array of all users in the course. This will only 
     * return students who have responses.(moodle database records)
     */
    private function get_course_users() {
        global $DB;

        $role = $DB->get_record('role', array('archetype' => 'student', 'shortname' => 'student'));
        $context = context_course::instance($this->course->id);

        $students = array_values(get_role_users($role->id, $context));

        //Remove students who did not respond. Note: You cannot partially respond
        //in the current setup. Therefore if he responded he will have responded
        // to all questions.
        foreach ($students as $key => $student) {
            $first_question = $this->std_questions[0];
            $response = $DB->get_record('evaluation_response',
                    array('question_id' => $first_question->get_id(), 'user_id' => $student->id));

            if (!$response) {
                unset($students[$key]);
            }
        }
        //Re index the array.
        $students = array_values($students);

        return $students;
    }

    /**
     * Get a list of all teachers in the course. It Uses roles to determine if a
     * user is a teacher.
     * @global moodle_database $DB
     * 
     * @return stdClass[] an array of all teachers in the course. (moodle database records)
     */
    private function get_course_teachers() {
        global $DB;

        $role = $DB->get_record('role', array('name' => 'Teacher'));
        $context = context_course::instance($this->course->id);
        $teachers = get_role_users($role->id, $context);

        return array_values($teachers);
    }

    /**
     * Gets the list of all standard questions for the evaluation.
     * @global moodle_database $DB
     * 
     * @return stdClass[] an array of all standard questions for the 
     *  evaluation. (moodle database records)
     */
    private function get_eval_std_questions() {
        global $DB;

        $questions = $DB->get_records('evaluation_questions',
                array('evalid' => $this->eval->id, 'isstd' => 1), 'question_order asc');

        $std_questions = array();
        foreach ($questions as $question) {
            $type = $DB->get_record('evaluations_question_types',
                    array('id' => $question->type));
            if ($type) {
                $class = 'question_' . $type->class;
                //Ignore comments
                if ($class::is_numeric()) {
                    $std_questions[] = new $class(true, $question->id, null, $question->question_order);
                }
            }
        }
        return $std_questions;
    }

    /**
     * Gets the list of all non-standard questions for the evaluation.
     * @global moodle_database $DB
     * 
     * @return stdClass[] an array of all non-standard questions for the 
     *  evaluation. (moodle database records)
     */
    private function get_eval_nonstd_questions() {
        global $DB;

        $questions = $DB->get_records('evaluation_questions',
                array('evalid' => $this->eval->id, 'isstd' => 0));

        $nonstd_questions = array();
        foreach ($questions as $question) {
            $type = $DB->get_record('evaluations_question_types',
                    array('id' => $question->type));
            if ($type) {
                $class = 'question_' . $type->class;
                //Ignore comments
                if ($class::is_numeric()) {
                    $nonstd_questions[] = new $class(true, $question->id, null, $question->question_order);
                }
            }
        }
        return $nonstd_questions;
    }

    /**
     * Gets the list of all comment questions in the evaluation.
     * 
     * @return stdClass[] an array of all comment questions for the 
     *  evaluation. (moodle database records)
     */
    private function get_eval_comments() {
        global $DB;

        $questions = $DB->get_records('evaluation_questions',
                array('evalid' => $this->eval->id));

        $comments = array();
        foreach ($questions as $question) {
            $type = $DB->get_record('evaluations_question_types',
                    array('id' => $question->type));
            if ($type) {
                $class = 'question_' . $type->class;
                //Ignore everything but comments
                if (!$class::is_numeric()) {
                    $comments[] = new $class(true, $question->id, null, $question->question_order);
                }
            }
        }
        return $comments;
    }

}
