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
 * @package    block_pu
 * @copyright  2021 onwards LSU Online & Continuing Education
 * @copyright  2021 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class pufile {
    public function check_file_exists($params = null) {
        error_log("\n\n**************************************");
        // error_log("\npufile -> check_file_exists() -> START\n");
        // error_log("\npufile -> what is params coming in: ". print_r($params, true). "\n");
        $mfileid = isset($params->mfileid) ? $params->mfileid : null;
        $pufileid = isset($params->pufileid) ? $params->pufileid : null;
        $fpath = get_config('moodle', "block_pu_copy_file");

        error_log("\npufile -> what is mfileid: ". $mfileid. "   \n");
        error_log("\npufile -> what is pufileid: ". $pufileid. "   \n");
        error_log("\npufile -> what is fpath: ". $fpath. "   \n");
        
        // If file exists then report back to either deny or ask to replace?
        
        if ($mfileid) {
            $fs = get_file_storage();
            // $fs->get_file(...);
            $file = $fs->get_file_by_id($mfileid);
            $fname = $file->get_filename();

            if (file_exists($fpath . $fname)) {
                error_log("\n\n------>>>>>>>>   The file $fname exists");
                return array(
                    "success" => false,
                    "msg" => "Sorry, this file already exists in that location."
                );
            } else {
                error_log("\n\n------>>>>>>>>   The file $fname does NOT exist");
                $file->copy_content_to($fpath. $fname);
                return array(
                    "success" => true,
                    "msg" => "The file was successfully copied over."
                );
            }
        }
    }
}
