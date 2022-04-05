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
 * Definition of IMS Enterprise enrolment scheduled tasks.
 *
 * @package   enrol_imsenterprise
 * @category  task
 * @copyright 2014 Universite de Montreal
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$tasks = array(
    array(
        'classname' => 'local_utools\task\cron_task',
        'blocking' => 0, //if this is set to 1, no other scheduled task will run at the same time
        //as this task. Do not set this to 1 unless you really need it as it will impact the performance of the task queue.
        // 'minute' => '',
        'hour' => '23', // just need the stats for the day so run at 11:00pm
        'day' => '*', //everyday
        'dayofweek' => '*', // 0 -> sunday, 6-> Saturday
        'month' => '*'
        //This is the default schedule for running the task. The syntax matches the syntax of unix cron.
        //Starting with Moodle 2.8, the minute and hour values accept a special 'R' syntax which causes a random value to be set in the database at install time (useful to avoid overloading web services).
    ),
    array(
        'classname' => 'local_utools\task\userstat_task',
        'blocking' => 0, //if this is set to 1, no other scheduled task will run at the same time
        //as this task. Do not set this to 1 unless you really need it as it will impact the performance of the task queue.
        // 'minute' => '1',
        'hour' => '3', // run at 3:00am
        'day' => '*', //everyday
        'dayofweek' => '*', // 0 -> sunday, 6-> Saturday
        'month' => '*'
        //This is the default schedule for running the task. The syntax matches the syntax of unix cron.
        //Starting with Moodle 2.8, the minute and hour values accept a special 'R' syntax which causes a random value to be set in the database at install time (useful to avoid overloading web services).
    )
);
