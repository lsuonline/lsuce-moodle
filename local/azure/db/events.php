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

defined('MOODLE_INTERNAL') || die();

$events = array('ues_list_provider', 'ues_load_azure_provider');

$mapper = function($event) {
    return array(
        'handlerfile' => '/local/azure/events.php',
        'handlerfunction' => array('azure_enrollment_events', $event),
        'schedule' => 'instant'
    );
};

$handlers = array_combine($events, array_map($mapper, $events));
