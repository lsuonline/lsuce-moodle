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
 * @copyright  2021 onwards Tim Hunt, Robert Russo, David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_pu\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;
// use block_pu\persistents\mappings;

require_once('../../config.php');
// require_once($CFG->dirroot . '/blocks/pu/lib.php');
require_login();

class files_view implements renderable, templatable {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG, $DB;
        $uploadedfiles = $DB->get_records('block_pu_file');
        $context = \context_system::instance();
        $settingspath = get_config('moodle', "block_pu_copy_file");

        $nonmoodlefilestemp = scandir($settingspath, SCANDIR_SORT_DESCENDING);
        $nonmoodlefiles = array();
        $counter = 0;
        foreach ($nonmoodlefilestemp as $fcheck) {
            if ($fcheck == '.' || $fcheck == '..') {
                continue;
            }
            // error_log("\n\n******************************************************\n");
            // error_log("\nfcheck is: ". $fcheck);
            // error_log("\nfcheck was last modified: " . date ("F d Y H:i:s.", filectime($settingspath.$fcheck)));
            // error_log("\nfilectime(fcheck): " . filectime($settingspath.$fcheck). "\n\n");

            $temp = array(
                "nonmood_filename" => $fcheck,
                "nonmood_modified" => userdate(filectime($settingspath.$fcheck)),
                "nonmood_modifiedstamp" => filectime($settingspath.$fcheck),
                "nonmood_hash" => md5($settingspath.$fcheck.filectime($settingspath.$fcheck)),
                "form_value" => $counter
            );
            $counter++;
            $nonmoodlefiles[] = $temp;
        }

        error_log("\n\nexport_for_template() -> what are the nonmoodlefiles: \n". print_r($nonmoodlefiles, true));
        // $pname = new mappings();
        // $helpers = new \lsuxe_helpers();

        // $data = $pname->get_all_records("mappings");
        // $updateddata = $pname->transform_for_view($data, $helpers);
        $tabledata = array();
        foreach ($uploadedfiles as $ufile) {
            // ------------------------------
            // File Link Example
            // $messagetext = file_rewrite_pluginfile_urls(
            //     // The content of the text stored in the database.
            //     // $messagetext,
            //     "wanker",
            //     // The pluginfile URL which will serve the request.
            //     'pluginfile.php',

            //     // The combination of contextid / component / filearea / itemid
            //     // form the virtual bucket that file are stored in.
            //     $context->id,
            //     'block_pu',
            //     'pu_file',
            //     $ufile->itemid
            // );

            $temp = array(
                "puid" => $ufile->id,
                "fileid" => $ufile->fileid,
                "itemid" => $ufile->itemid,
                "pu_fileurl" => "fix me",
                "pu_filename" => $ufile->filename,
                "pu_filecreated" => userdate($ufile->timecreated),
                "pu_filemodified" => userdate($ufile->timemodified)
            );
            $tabledata[] = $temp;
        }
        // ------------------------------
        $renderdata = array(
            "pu_data" => $tabledata,
            "pu_url" => $CFG->wwwroot,
            "currentpath" => $settingspath,
            "non_mood_files" => $nonmoodlefiles

        );
        return $renderdata;
    }
}
