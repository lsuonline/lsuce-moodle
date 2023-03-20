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
 * ************************************************************************
 *                            QuickMail
 * ************************************************************************
 * @package    block - Quickmail
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Update by David Lowe
 */

defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . "/externallib.php");

class block_quickmail_external extends external_api {
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function qmAjax_parameters() {
        return new external_function_parameters(
            array(
                'datachunk' => new external_value(
                    PARAM_TEXT,
                    'Encoded Params'
                )
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function qmAjax($datachunk) {
        global $CFG, $USER;

        $datachunk = json_decode($datachunk);

        $class_obj = isset($datachunk->class) ? $datachunk->class : null;
        $function = isset($datachunk->call) ? $datachunk->call : null;
        $params = isset($datachunk->params) ? $datachunk->params : null;
        $path = isset($datachunk->path) ? $datachunk->path : null;
        
        if (!isset($params)) {
            $params = array("empty" => "true");
        }

        // it could be either GET or POST, let's check......
        if (isset($class_obj)) {
            $this_file = $CFG->dirroot. '/blocks/quickmail/'. $path. $class_obj. '.php';
            include_once($this_file);
            $qmajax = new $class_obj();
        }

        // now let's call the method
        $ret_obj_data = null;
        if (method_exists($qmajax, $function)) {
            $ret_obj_data = call_user_func(array($qmajax, $function), $params);
        }

        $ret_json_data = [
            'data' => json_encode($ret_obj_data)
        ];
        return $ret_json_data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function qmAjax_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'JSON encoded goodness')
            )
        );
    }
    
}
