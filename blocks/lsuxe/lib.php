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
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace block_lsuxe\helpers;

defined('MOODLE_INTERNAL') || die();
// require_once('../../config.php');

class lsuxe_helpers {


    // Redirects.
    /**
     * Convenience wrapper for redirecting to moodle URLs
     *
     * @param  string  $url
     * @param  array   $urlparams   array of parameters for the given URL
     * @param  int     $delay        delay, in seconds, before redirecting
     * @return (http redirect header)
     */
    public function redirect_to_url($url, $urlparams = [], $delay = 2) {
        $moodleurl = new \moodle_url($url, $urlparams);

        redirect($moodleurl, '', $delay);
    }
    /**
     * Config Converter - config settings that have multiple lines with 
     * a key value settings will be broken down and converted into an 
     * associative array, for example:
     * Monthly 720,
     * Weekly 168
     * .....etc
     * Becomes (Monthly => 720, Weekly => 168)
     * @param  string  $config setting
     * @return array
     */
    public function config_to_array($config_string) {
        $config_name = get_config('moodle', $config_string);

        // Strip the line breaks
        $break_stripped = preg_replace("/\r|\n/", " ", $config_name);

        // make sure there are not double spaces.
        $break_stripped = str_replace("  ", " ", $break_stripped);

        // now convert to arry and transform to an assoc. array
        $exploded = explode(" ", $break_stripped);
        $exploded_count = count($exploded);
        $final = array();
        for ($i = 0; $i < $exploded_count; $i+=2) {
            $final[$exploded[$i+1]] = $exploded[$i];
        }
        return $final;
    }

    /**
     * Transform any data for viewing purposes here. Intervals, for example,
     * are stored as numbers but the dropdown will need to show words
     * @param  array    $all the form data
     * @param  string   the data for this form 
     * @return array
     *
    public function transform_for_view($data, $this_form) {
        error_log("\n\n");
        error_log(" what is the data for the form: ". print_r($data, 1));
        error_log("\n\n");
        // [id] => 4
        // [courseid] => 99
        // [shortname] => CPSC-1000-A
        // [authmethod] => manual
        // [groupid] => 99
        // [groupname] => groupA
        // [destmoodleid] => 99
        // [destcourseid] => 99
        // [destcourseshortname] => CPSC-A
        // [destgroupprefix] => LSU-
        // [destgroupid] => 99
        // [updateinterval] => 0
        // [starttime] => 
        // [endtime] => 
        // [usercreated] => 1658790914
        // [timecreated] => 1658790914
        // [usermodified] => 2
        // [timemodified] => 1658790914
        // [userdeleted] => 
        // [timedeleted] => 
        // [timeprocessed] => 

        $intervals = $this->config_to_array('block_lsuxe_interval_list');
        // We need to show the correct interval and not the number
        foreach ($data[$this_form] as &$this_record) {
            if (isset($intervals[$this_record['updateinterval']]) && $this_record['updateinterval'] != 0) {
                $this_record['updateinterval'] = $intervals[$this_record['updateinterval']];
            } else {
                $this_record['updateinterval'] = "<i class='fa fa-ban'></i>";
            }
        }
        return $data;
    }
    */
}
