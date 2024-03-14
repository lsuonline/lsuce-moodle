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
 * Displays the preamble form.
 */
require_once("$CFG->libdir/formslib.php");

class preamble_form extends moodleform {

    private $dept;
    function __construct($dept){
        $this->dept = $dept;
        parent::__construct();
    }
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->setType('dept', PARAM_TEXT);
        
        $mform->addElement('header', 'preamble_header', get_string('preamble', 'local_evaluations'));
        // $mform->addElement('textarea', 'preamble', get_string('preamble', 'local_evaluations'), 'rows="10" cols="50"');
        $mform->addElement('editor', 'preamble', get_string('preamble', 'local_evaluations'), null, null);
        $mform->addElement('hidden', 'dept', $this->dept);
        
        //Lets see if it already exists.
        
        if ($record = $DB->get_record_select('department_preambles', "department = '$this->dept'")) {
            // $mform->setDefault('preamble', $record->preamble);
            $mform->setDefault('preamble',  array('text'=>$record->preamble, 'format'=>FORMAT_HTML));
        }
        
        $this->add_action_buttons(false);
    }
}
