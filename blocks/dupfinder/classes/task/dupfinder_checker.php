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
 * UES Dupe Finder
 *
 * @package   block_dupfinder
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards Robert Russo, David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dupfinder\task;

/**
 * Extend the Moodle scheduled task class with ours.
 */
class dupfinder_checker extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('df_checker', 'block_dupfinder');
    }

    /**
     * Do the job.
     *
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/dupfinder/helpers.php');
        $df = new \helpers();
        $starttime = microtime(true);

        $data = $df->getdata();
        $xml = $df->objectify($data);
        $dupes = $df->finddupes($xml, false);

        $df->emailduplicates($dupes);
        $elapsedtime = round(microtime(true) - $starttime, 3);
        mtrace(PHP_EOL. "\nThis entire process took " . $elapsedtime . " seconds.". PHP_EOL);
    }
}
