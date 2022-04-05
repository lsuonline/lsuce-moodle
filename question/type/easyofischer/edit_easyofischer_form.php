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
 * Defines the editing form for the easyofischer question type.
 *
 * @package    qtype
 * @subpackage easyofischer
 * @copyright  2014 onwards Carl LeBlond
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/shortanswer/edit_shortanswer_form.php');
class qtype_easyofischer_edit_form extends qtype_shortanswer_edit_form {
    protected function definition_inner($mform) {
        global $PAGE, $CFG, $question, $DB, $numofstereo;
        $PAGE->requires->js('/question/type/easyofischer/easyofischer_script.js');
        $PAGE->requires->css('/question/type/easyofischer/styles.css');
        if (isset($question->id)) {
            $record      = $DB->get_record('question_easyofischer', array(
                'question' => $question->id
            ));
            $numofstereo = $record->numofstereo;
        } else {
            $numofstereo = 1;
        }
        $mform->addElement('static', 'answersinstruct',
            get_string('correctanswers', 'qtype_easyofischer'), get_string('filloutoneanswer', 'qtype_easyofischer'));
        $mform->closeHeaderBefore('answersinstruct');
        $menu = array(
            '0' => 'False',
            '1' => 'True'
        );
        $mform->addElement('html', '<strong>'.get_string('rotationmore', 'qtype_easyofischer').'</strong>');
        $mform->addElement('select', 'strictfischer', get_string('rotationallowed', 'qtype_easyofischer'), $menu);
        $menu = array(
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4'
        );
        $mform->addElement('html', '<strong>'.get_string('numofstereomore', 'qtype_easyofischer').'</strong>');
        $mform->addElement('select', 'numofstereo', get_string('numofstereo', 'qtype_easyofischer'), $menu);
        $mform->addElement('html', '<strong>'.get_string('fischerinstruct', 'qtype_easyofischer').'</strong>');

        $easyofischerbuildstring = '<div id="fischer_template" style="overflow: hidden; padding: 10px; ">';
        $easyofischerbuildstring .= file_get_contents('type/easyofischer/edit_fischer' . $numofstereo . '.html');
        //maybe add end div in here
        ///////////////
        $easyofischerbuildstring .= file_get_contents('type/easyofischer/fischer_dragable.html');


        // UofL Fix ------------------
        // error_log("\n\nFISCHER -> edit_easyofischer_form");

        $easyofischerbuildstring = str_replace("question/type/easyofischer/pix", $CFG->wwwroot. "/question/type/easyofischer/pix", $easyofischerbuildstring);
        $easyofischerbuildstring .= html_writer::end_tag('div');  // End divnew!
        $mform->addElement('html', $easyofischerbuildstring);
        $jsmodule = array(
            'name' => 'qtype_easyofischer',
            'fullpath' => '/question/type/easyofischer/easyofischer_script.js',
            'requires' => array(),
            'strings' => array(
                array(
                    'enablejava',
                    'qtype_easyofischer'
                )
            )
        );
        $htmlid   = 1;
        $module   = array(
            'name' => 'easyofischer',
            'fullpath' => '/question/type/easyofischer/module.js',
            'requires' => array(
                'yui2-treeview'
            )
        );
        $url      = $CFG->wwwroot . '/question/type/easyofischer/template_update.php?numofstereo=';
        $PAGE->requires->js_init_call('M.qtype_easyofischer.init_reload', array(
            $url,
            $htmlid
        ), true, $jsmodule);
        $PAGE->requires->js_init_call('M.qtype_easyofischer.insert_structure_into_applet', array(
            $numofstereo
        ), true, $jsmodule);

        $PAGE->requires->js_init_call('M.qtype_easyofischer.dragndrop', array('1'), true, $jsmodule);
        
        // UofL needed to jsmodule param, wasn't loading
        $PAGE->requires->js_init_call('M.qtype_easyofischer.init_getanswerstring', array($numofstereo), false, $jsmodule);
        
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_easyofischer', '{no}'),
            question_bank::fraction_options());
        $this->add_interactive_settings();
    }
    protected function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        global $numofstereo;
        $repeated      = parent::get_per_answer_fields($mform, $label, $gradeoptions, $repeatedoptions, $answersoption);
        $scriptattrs = 'class = id_insert';
        $insertbutton = $mform->createElement('button', 'insert', get_string('insertfromeditor',
        'qtype_easyofischer'), $scriptattrs);
        array_splice($repeated, 2, 0, array($insertbutton));
        return $repeated;
    }
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        return $question;
    }
    public function qtype() {
        return 'easyofischer';
    }
}
