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
 * Question type class for the easyomechjs question type.
 *
 * @package    qtype
 * @subpackage easyomechjs
 * @copyright  2014 onwards Carl LeBlond 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/easyomechjs/question.php');
require_once($CFG->dirroot . '/question/type/shortanswer/questiontype.php');

class qtype_easyomechjs extends qtype_shortanswer {
    public function extra_question_fields() {
        return array('question_easyomechjs', 'answers');
    }

    public function questionid_column_name() {
        return 'question';
    }
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        $questiondata->options->usecase = '';
          parent::initialise_question_instance($question, $questiondata);
    }

    /**
     * Save the units and the answers associated with this question.
     */
    public function save_question_options($question) {

        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // this is from core qtype_shortanswer
        global $DB;

        // $oldoptions = array_values($DB->get_records('question_easyomechjs',
        //     array('question' => $question->id),
        //     'answers ASC'
        // ))[0];
        
        $oldoptions = $DB->get_records('question_easyomechjs',
            array('question' => $question->id),
            'answers ASC'
        );

        if (!empty($oldoptions)) {
            $oldoptions = array_values($oldoptions)[0];
        }
        $result = new stdClass();

        // Perform sanity checks on fractional grades.
        $maxfraction = -1;
        foreach ($question->answer as $key => $answerdata) {
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }

        if ($maxfraction != 1) {
            $result->error = get_string('fractionsnomax', 'question', $maxfraction * 100);
            return $result;
        }

        parent::save_question_options($question);

        $this->save_question_answers($question);

        $this->save_hints($question);
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------


        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        
        // ************************************************************
        // SINGLE STRING
        // ************************************************************

        // Get old versions of the objects.
        $newanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $newanswer = array_values($newanswers);
        $current_options1 = $newanswer[0];

        // Insert all the new answers.
        $answer_string = array();

        foreach ($question->answer as $key => $answerdata) {
            // Check for, and ingore, completely blank answer from the form.
            if (trim($answerdata) == '' && $question->fraction[$key] == 0 &&
                    html_is_blank($question->feedback[$key]['text'])) {
                continue;
            }
            $answer_string[] = $newanswer[$key]->id;
        }

        $comma_separated_answers = implode(",", $answer_string);

        $options = new stdClass();
        $options->answers = $comma_separated_answers;
        
        if (isset($oldoptions->id) && $oldoptions->id != null) {
            $options->id = $oldoptions->id;
            $DB->update_record('question_easyomechjs', $options);
        } else {
            $DB->insert_record('question_easyomechjs', $options);
        }

        // ************************************************************
        // MULTIPLE ROWS FOR EACH ANSWERS
        // ************************************************************

        /*$context = $question->context;

        // Get old versions of the objects.
        $newanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $newanswer = array_values($newanswers);

        $oldoptions2 = $DB->get_records('question_easyomechjs',
                array('question' => $question->id), 'answers ASC');

        // Insert all the new answers.
        foreach ($question->answer as $key => $answerdata) {
            // Check for, and ingore, completely blank answer from the form.
            if (trim($answerdata) == '' && $question->fraction[$key] == 0 &&
                    html_is_blank($question->feedback[$key]['text'])) {
                continue;
            }

            if (!$options = array_shift($oldoptions2)) {
                $options = new stdClass();
            }
            
            $options->question = $question->id;
            $options->answers = $newanswer[$key]->id;

            $found_it = $DB->get_record('question_easyomechjs', array('question' => $question->id, 'answers' => $newanswer[$key]->id));
            if (!$found_it) {
                if (isset($options->id)) {
                    $DB->update_record('question_easyomechjs', $options);
                    // error_log("would update, obj is: ". print_r($options, 1));
                } else {
                    $DB->insert_record('question_easyomechjs', $options);
                    // error_log("would insert, obj is: ". print_r($options, 1));
                }
            }
        }



        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // global $DB;
        /*
        $context = $question->context;

        // Get old versions of the objects.
        $newanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $newanswer = array_values($newanswers);

        $oldoptions = $DB->get_records('question_easyomechjs',
                array('question' => $question->id), 'answers ASC');

        // Insert all the new answers.
        foreach ($question->answer as $key => $answerdata) {
            // Check for, and ingore, completely blank answer from the form.
            if (trim($answerdata) == '' && $question->fraction[$key] == 0 &&
                    html_is_blank($question->feedback[$key]['text'])) {
                continue;
            }

            // Update an existing answer if possible.
            // Set up the options object.
            if (!$options = array_shift($oldoptions)) {
                $options = new stdClass();
            }
            
            $options->question = $question->id;
            $options->answers   = $newanswer[$key]->id;

            $found_it = $DB->get_record('question_easyomechjs', array('question' => $question->id, 'answers' => $newanswer[$key]->id));
            if (!$found_it) {
                if (isset($options->id)) {
                    $DB->update_record('question_easyomechjs', $options);
                } else {
                    $DB->insert_record('question_easyomechjs', $options);
                }
            }
        }

        foreach ($oldoptions as $oldoption) {
            $DB->delete_records('question_easyomechjs', array('id' => $oldoption->id));
        }
        */
    }
}
