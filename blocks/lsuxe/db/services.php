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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
    'block_lsuxe_XEAjax' => array(
        'classname'   => 'block_lsuxe_external',
        'methodname'  => 'XEAjax',
        'classpath'   => 'blocks/lsuxe/externallib.php',
        'description' => 'Entry point for Cross Enrollment Rest Services',
        'type'        => 'write',
        'ajax'        => true
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'LSUXE Service' => array(
        'functions' => array (
            'block_lsuxe_XEAjax'
        ),
        'restrictedusers' => 0,
        'enabled'=>1
    )
);
// Sample Request URL: 
// [$CFG->wwwroot, whatever your url is]/lib/ajax/service.php?sesskey=[sesskey]&info=block_lsuxe_XEAjax
