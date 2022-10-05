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

namespace block_dupfinder\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

// require_once('../../config.php');
// require_once('../../base.php');

// require_login();

class manual_view implements renderable, templatable {

    /** @var string $dupes Broken users found, send to template. */
    private $dupes = null;

    public function __construct($dupes, $isblock = true) {
        $this->dupes = $dupes;
        $this->isblock = $isblock;
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG;
        // $pname = new mappings();
        // $helpers = new \lsuxe_helpers();

        // $data = $pname->get_all_records("mappings");
        // $updateddata = $pname->transform_for_view($data, $helpers);
        // $updateddata['xeurl'] = $CFG->wwwroot;
        // $updateddata['xeparms'] = "intervals=false&moodleid=0&function=course&courseid=";

        // $df = new helpers();

        // $data = $df->gettestdata();
        // $xml = $df->objectify($data);
        // $dupes = $df->finddupes($xml);
        $templatedata = array();
        // error_log("\n -------------------------------- \n");
        foreach ($this->dupes as $duplist) {
            foreach ($duplist as $dupstudentobj => $dupstudent) {
                $templatedata['dupes'][] = $dupstudent;
                // error_log("\n duplist: \n". print_r($dupstudentobj, 1));
                // error_log("\n fart: \n". print_r($dupstudent, 1));
            }
        }
        // error_log("\n -------------------------------- \n");
        // error_log("\n OUTPUT -> RENDERABLE\n What is the output: ". print_r($this->dupes, 1));

        // $updateddata = array();
        $templatedata['dfurl'] = $CFG->wwwroot;
        $templatedata['isblock'] = $this->isblock;
        return $templatedata;
    }
}
