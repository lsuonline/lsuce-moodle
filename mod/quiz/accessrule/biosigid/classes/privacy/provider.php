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
 * Privacy module
 *
 * @package    quizaccess_biosigid
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_biosigid\privacy;
   
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
   
defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider  {

    public static function get_metadata(collection $collection)  : collection {

        $collection->add_external_location_link('verifyexpress.com', [
                'userid' => 'privacy:metadata:quizaccess_biosigid:userid',
                'biometricdata' => 'privacy:metadata:quizaccess_biosigid:biometricdata',
                'email' => 'privacy:metadata:mod_bioquizaccess_biosigidsigid:email',
                'firstname' => 'privacy:metadata:quizaccess_biosigid:firstname',
                'lastname' => 'privacy:metadata:quizaccess_biosigid:lastname'
            ], 'privacy:metadata:quizaccess_biosigid:externalpurpose');

            return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        return new contextlist();   
    }

    public static function export_user_data(approved_contextlist $contextlist) {
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }
}