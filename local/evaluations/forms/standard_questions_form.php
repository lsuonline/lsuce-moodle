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

/**
 * Form for creating standard questions for a department.
 */
require_once("$CFG->libdir/formslib.php");
require_once('classes/standard_question.php');
require_once('classes/standard_question_set.php');
require_once('locallib.php');

class standard_questions_form extends moodleform {

    private $dept;
    
    /**
     * Constructor
     * @param String $dept A department code.
     */
    function __construct($dept) {
        $this->dept = $dept;
        parent::__construct();
    }

    function definition() {
        $mform = & $this->_form;

        $mform->setType('dept', PARAM_TEXT);
        $mform->setType('questionid_x', PARAM_INT);
        $mform->setType('question_std', PARAM_RAW);

        $mform->addElement('header', 'standard_question_header', get_string('nav_st_qe', 'local_evaluations'));
        $mform->addElement('html', '<p>' . get_string('standard_questions_info', 'local_evaluations') . '</p>');
        $mform->addElement('hidden', 'dept', $this->dept);
        $questionSet = new standard_question_set($this->dept);

        $data = new stdClass();


        //Load question data - either exisiting questions or standard questions
        $questionSet->load_creation_form($mform, $this, $data);


        $this->set_data($data);
    }
}
