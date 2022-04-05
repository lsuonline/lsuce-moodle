<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localwstemplate
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
    'local_tcs_hello_world' => array(
        'classname'   => 'local_tcs_external',
        'methodname'  => 'hello_world',
        'classpath'   => 'local/tcs/externallib.php',
        'description' => 'Stupid Test',
        'type'        => 'write',
        'ajax'        => true

        // capabilities examples:
        // 'capabilities' => 'moodle/grade:manage, moodle/grade:edit',
        // 'capabilities' => 'moodle/grade:view, moodle/grade:viewall, moodle/grade:viewhidden',
        // 'capabilities' => 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail',
    ),
    // 'local_tcs_loadUsers' => array(
    //     'classname'   => 'local_tcs_external',
    //     'methodname'  => 'loadUsers',
    //     'classpath'   => 'local/tcs/externallib.php',
    //     'description' => 'Get all Moodle users for searching',
    //     'type'        => 'write',
    //     'ajax'        => true
    // ),
    'local_tcs_tcsAjax' => array(
        'classname'   => 'local_tcs_external',
        'methodname'  => 'tcsAjax',
        'classpath'   => 'local/tcs/externallib.php',
        'description' => 'Get all Moodle users for searching',
        'type'        => 'write',
        'ajax'        => true
    ),
    // 'local_tcs_StudentListAjax' => array(
    //     'classname'   => 'local_tcs_StudentListAjax',
    //     'methodname'  => 'getCheckedInStudentsTest',
    //     'classpath'   => 'local/tcs/lib/StudentListAjax.php',
    //     'description' => 'Description Get all of the students that are currently checked into the tcs',
    //     'type'        => 'read',
    // )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'TCS Service' => array(
        'functions' => array (
            'local_tcs_hello_world',
            // 'local_tcs_loadUsers',
            'local_tcs_tcsAjax'
        ),
        'restrictedusers' => 0,
        'enabled'=>1,
    )
);

// Request URL: http://u201902/lib/ajax/service.php?sesskey=TodPlQBDpX&info=local_tcs_hello_world
