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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www . gnu . org/copyleft/gpl . html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class crosslist_form extends moodleform {
    public function definition() {
        global $PAGE, $OUTPUT;
        $PAGE->requires->js_call_amd('block_wdsprefs/duallist', 'init');

        // Add the step = assign crap.
        $this->_form->addElement('hidden', 'step', 'assign');
        $this->_form->setType('step', PARAM_TEXT);

        $mform = $this->_form;

        $sectiondata = $this->_customdata['sectiondata'] ?? [];
        $shellcount = $this->_customdata['shellcount'] ?? 2;
        
        $mform->addElement('header', 'assignshellsheader',
            get_string('wdsprefs:assignshellsheader', 'block_wdsprefs'));
            
        // Instructions.
        $mform->addElement('html',
            '<div class="alert alert-info"><p>' .
            get_string('wdsprefs:crosslistinstructions',
                'block_wdsprefs', $shellcount
            ) . '</p></div>');
            
        // Start container.
        $mform->addElement('html', '<div class="duallist-container">');
        
        // Available sections (single box on left).
        $mform->addElement('html', '<div class="duallist-available"><label>' .
            get_string('wdsprefs:availablesections', 'block_wdsprefs') .
            '</label><select class="form-control" name="available_sections" ' .
            'id="available_sections" multiple size="10">');
                
        foreach ($sectiondata as $value => $label) {
            $mform->addElement('html',
                '<option value="' . $value . '">' .
                $label . '</option>');
        }
        
        $mform->addElement('html', '</select></div>');
        
        // Control buttons. TODO: STRINGME!
        $mform->addElement('html', '
            <div class="duallist-controls">
                <button type="button" class="btn btn-secondary mb-2" onclick="moveToShell()">
                    ' . $OUTPUT->pix_icon('t/right', '') . ' Add to Shell</button>
                <button type="button" class="btn btn-secondary" onclick="moveBackToAvailable()">
                    ' . $OUTPUT->pix_icon('t/left', '') . ' Remove</button>
            </div>');
            
        // Shell sections (multiple boxes on right).
        $mform->addElement('html', '<div class="duallist-shells">');
        
        // Create the shell select boxes. TODO: REAL DATA!
        for ($i = 1; $i <= $shellcount; $i++) {
            $mform->addElement('html', '
                <div class="duallist-shell"><label>' .
                get_string('wdsprefs:shell', 'block_wdsprefs', $i) .
                '</label><select class="form-control" name="shell_' .
                $i . '[]" id="shell_' . $i .
                '" multiple size="10"></select></div>');
        }

        $mform->addElement('html', '</div></div>');

        $this->add_action_buttons(true, get_string('submit'));
    }

/* TODO: Real validation?
    public function validation($data, $files) {
        $errors = [];
        $assigned = [];
        
        foreach ($data as $key => $sections) {
            if (strpos($key, 'shell_') === 0 && is_array($sections)) {
                foreach ($sections as $sectionid) {
                    if (in_array($sectionid, $assigned)) {
                        $errors[$key] = get_string('wdsprefs:duplicatesection', 'block_wdsprefs');
                    } else {
                        $assigned[] = $sectionid;
                    }
                }
            }
        }
        
        return $errors;
    }
*/
}
