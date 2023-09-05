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
 * Course Hider Tool
 *
 * @package   block_course_hider
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_course_hider\persistents;
use block_course_hider\models;

class course_hider extends \block_course_hider\persistents\persistent {

    /** Table name for the persistent. */
    const TABLE = 'block_course_hider_sample';
    const PNAME = 'sample';

    /**
     * Return the definition of the properties of this model.
     * NOTE - Must match DB Table
     * @return array
     */
    protected static function define_properties() {
        return [
            'sample1' => [
                'type' => PARAM_INT
            ],
            'sample2' => [
                'type' => PARAM_TEXT
            ],
            'sample3' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
        ];
    }

    /**
     * Define the columns that need to be checked for duplicate records.
     *
     * @return array
     */
    public function column_record_check() {
        return array(
            // DB Column Name => Form Name.
            'sample1' => 'something_here',
            'sample2' => 'something_here'
        );
    }

    /**
     * When saving a new record this matches the form fields to the db columns.
     *
     * @return array
     */
    public function column_form_symetric() {
        return array(
            // DB Column Name => Form Name.
            'coursename' => 'samplecourse',
            'groupname' => 'samplegroup',
            'cities' => 'cities',
            'interval' => 'sample_interval',
        );
    }

    /**
     * The form has limited data and the rest will have to be extracted and/or
     * interpolated. This function is where we do that.
     * @param object This is the current info ready to be saved
     * @param object All form data and tidbits to be extracted and/or interpolated.
     * @return void The object is referenced.
     */
    public function column_form_custom(&$tosave, $data, $update = false) {
        global $DB, $USER;
        // If enabled, let's use Moodle's autocomplete feature.

    }

    /**
     * Transform any custom data from the DB to be used in the form.
     * @param object the data object
     * @param object Helper injection
     * @return void The object is referenced.
     */
    public function transform_for_view($data, $helpers) {
        global $DB;
        
        // If you need to transform the data, do it here.

        return $data;
    }

    /**
     * Persistent hook to redirect user back to the view after the object is saved.
     *
     * @return void
     */
    protected function after_create() {
        global $CFG;
        redirect($CFG->wwwroot . '/blocks/course_hider/sample_view.php',
            get_string('saved_something', 'block_course_hider'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    /**
     * Persistent hook to redirect user back to the view after the object is updated.
     *
     * @return void
     */
    protected function after_update($result) {
        global $CFG;
        // The action should still be stored so let's use that to redirect accordingly.
        $action = optional_param('sentaction', "", PARAM_TEXT);
        if ($action === "recovered") {
            redirect($CFG->wwwroot . '/blocks/course_hider/sample_view.php',
                get_string('recovered_something', 'block_course_hider'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else if ($action === "delete") {
            redirect($CFG->wwwroot . '/blocks/course_hider/sample_view.php',
                get_string('deleted_something', 'block_course_hider'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect($CFG->wwwroot . '/blocks/course_hider/sample_view.php',
                get_string('updated_something', 'block_course_hider'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }
}
