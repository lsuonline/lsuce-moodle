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
 * @package    block_wdsprefs
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class crossenroll_sections_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // Add the step parameter.
        $this->_form->addElement('hidden', 'step', 'sections');
        $this->_form->setType('step', PARAM_TEXT);

        // Get the sections data.
        $sectionsbyperiod = $this->_customdata['sectionsbyperiod'] ?? [];

        // Target period info (optional display)
        $targetperiodname = $this->_customdata['targetperiodname'] ?? '';

        // Add header
        $mform->addElement('header',
            'wdsprefs:selectsections',
            get_string('wdsprefs:selectsections', 'block_wdsprefs'));

        // Instructions
        $mform->addElement('html',
            '<div class="alert alert-info"><p>' .
            get_string('wdsprefs:crossenrollinstructions2', 'block_wdsprefs') .
            '</p></div>'
        );

        if ($targetperiodname) {
             $mform->addElement('html',
                '<p><strong>' . get_string('wdsprefs:targetperiod', 'block_wdsprefs') . ': ' . $targetperiodname . '</strong></p>'
            );
        }

        // Loop through periods
        foreach ($sectionsbyperiod as $periodname => $courses) {

            $mform->addElement('html', '<h4 class="mt-3">' . $periodname . '</h4>');
            $mform->addElement('html', '<div class="card-row">');

            foreach ($courses as $coursename => $sections) {

                $mform->addElement('html', '<div class="card"><div class="card-body">');
                $mform->addElement('html', '<h5 class="card-title">' . $coursename . '</h5>');

                foreach ($sections as $sectionid => $sectiondata) {

                    // Check if it's an object (new format) or just a string (fallback).
                    if (is_object($sectiondata)) {
                        $sectionname = $sectiondata->name;
                        $crosssplitid = $sectiondata->crosssplit_id;
                    } else {
                        $sectionname = $sectiondata;
                        $crosssplitid = null;
                    }

                    if ($crosssplitid) {
                         // Build the link to undo the action.
                         $undourl = new moodle_url('/blocks/wdsprefs/crosssplit_sections.php', ['id' => $crosssplitid]);
                         $undolink = html_writer::link($undourl, get_string('wdsprefs:undoaction', 'block_wdsprefs'));

                         $message = get_string('wdsprefs:alreadycrosssplit', 'block_wdsprefs', $undolink);

                         $mform->addElement('html', html_writer::span($sectionname . ' - ' . $message, 'placeholder'));

                    } else {
                         $mform->addElement('advcheckbox',
                            'selectedsections['.$sectionid.']',
                            null,
                            $sectionname,
                            null,
                            [0, $sectionid]
                        );
                    }
                }

                $mform->addElement('html', '</div></div>');
            }
                $mform->addElement('html', '</div>');
        }

        // Add the action buttons.
        $this->add_action_buttons(true, get_string('submit'));
    }
}
