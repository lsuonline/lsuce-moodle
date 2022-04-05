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
 * Question type class for the easyonewman question type.
 *
 * @package    qtype
 * @subpackage easyonewman
 * @copyright  2014 onwards Carl LeBlond
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/easyonewman/question.php');
require_once($CFG->dirroot . '/question/type/shortanswer/questiontype.php');

class qtype_easyonewman extends qtype_shortanswer {
    public function extra_question_fields() {
        return array('question_easyonewman', 'answers', 'conformimportant', 'orientimportant', 'stagoreclip');
    }

    public function questionid_column_name() {
        return 'question';
    }

    /**
     * Save the units and the answers associated with this question.
     */
    public function save_question_options($question) {

        global $DB;

        $oldoptions = $DB->get_records('question_easyonewman',
            array('question' => $question->id),
            'answers ASC'
        );

        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // this is from core qtype_shortanswer
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
        
        // global $DB;
        // Attempt 1
        // $context = $question->context;

        // Get old versions of the objects.
        $newanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $newanswer = array_values($newanswers);
        $current_options1 = array_values($oldoptions)[0];

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

        $options->id = $current_options1->id;
        $options->answers = $comma_separated_answers;
        $options->conformimportant = $question->conformimportant;
        $options->orientimportant = $question->orientimportant;
        $options->stagoreclip = $question->stagoreclip;

        $DB->update_record('question_easyonewman', $options);

        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        // ----------------------------------------------------------------------
        /*$context = $question->context;

        // Get old versions of the objects.
        $newanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $newanswer = array_values($newanswers);

        $oldoptions2 = $DB->get_records('question_easyonewman',
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

            $options->conformimportant = $question->conformimportant;
            $options->orientimportant = $question->orientimportant;
            $options->stagoreclip = $question->stagoreclip;

            $found_it = $DB->get_record('question_easyonewman', array('question' => $question->id, 'answers' => $newanswer[$key]->id));
            if (!$found_it) {
                if (isset($options->id)) {
                    $DB->update_record('question_easyonewman', $options);
                    // error_log("would update, obj is: ". print_r($options, 1));
                } else {
                    $DB->insert_record('question_easyonewman', $options);
                    // error_log("would insert, obj is: ". print_r($options, 1));
                }
            }
        }
        */
    }
}
