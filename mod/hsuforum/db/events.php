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
 * Meta course enrolment plugin event handler definition.
 *
 * @package mod_hsuforum
 * @category event
 * @copyright 2010 Petr Skoda  {@link http://skodak.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @author Mark Nielsen
 */

/* List of handlers */
$handlers = array (
    'role_assigned' => array (
        'handlerfile'      => '/mod/hsuforum/lib.php',
        'handlerfunction'  => 'hsuforum_user_role_assigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'user_unenrolled' => array (
        'handlerfile'      => '/mod/hsuforum/lib.php',
        'handlerfunction'  => 'hsuforum_user_unenrolled',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);
