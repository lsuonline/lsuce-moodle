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
 * This file contains all the backup steps that will be used
 * by the backup_biosigid_activity_task
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete assignment structure for backup, with file and id annotations
 */
class backup_biosigid_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $biosigid = new backup_nested_element('biosigid', array('id'), array(
            'name',
            'intro',
            'introformat',
            'timecreated',
            'timemodified',
            )
        );

        // Build the tree
        // (none).

        // Define sources.
        $biosigid->set_source_table('biosigid', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none).

        // Define file annotations.
        $biosigid->annotate_files('mod_biosigid', 'intro', null); // This file area does not have an itemid.

        // Return the root element (biosigid), wrapped into standard activity structure.
        return $this->prepare_activity_structure($biosigid);
    }
}

