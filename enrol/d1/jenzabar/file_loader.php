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
 *
 * @package    enrol_d1
 * @copyright  2022 onwards Louisiana State University
 * @copyright  2022 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_d1\jenzabar;
// defined('MOODLE_INTERNAL') || die();

class file_loader {
    public $fileobj;
    private $csvobjs;
    private $csvheaders;
    // private $csvheaders;

    public function __construct($file = "") {
        $this->fileobj = $file;
    }

    public function fetchdata() {

        // use this if line endings aren't recognized
        // auto_detect_line_endings 
        // $xml = simplexml_load_file($this->filedata);
        
        $csvobjs = array_map('str_getcsv', file($this->fileobj));
        $this->csvheaders = $csvobjs[0];

        // error_log("\n\n");
        // error_log("\n What is csv: ?? use VCode\n");
        // error_log("\n\n");
        return $csvobjs;
    }

    public function getheaders() {
        $headerrow = true;
        foreach ($this->csvheaders as $title) {
            if ($headerrow) {
                // error_log("\n\n");
                // error_log("\n  -->> skipping the header...... <<--  \n");
                // error_log("\n\n");

                $headerrow = false;
                continue;
            }
            // error_log("\n". $title. "  <<--||");
        }
    }
}
