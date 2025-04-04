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

class wdsprefs {

    public static function get_instructor($user) : bool {
        global $DB;

        // Get a bool if they exist in this table or not.
        $instructor = $DB->record_exists('enrol_wds_teachers', ['userid' => $user->id]);

        // Return the value.
        return $instructor;
    }

    public static function get_student($user) : bool {
        global $DB;

        // Get a bool if they exist in this table or not.
        $student = $DB->record_exists('enrol_wds_students', ['userid' => $user->id]);

        // Return the value.
        return $student;
    }

}
