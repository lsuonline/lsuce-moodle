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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . "/externallib.php");

class block_lsuxe_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function XEAjax_parameters() {
        return new external_function_parameters(
            array(
                'datachunk' => new external_value(
                    PARAM_TEXT,
                    'Encoded Params"'
                )
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function XEAjax($datachunk) {
        $datachunk = json_decode($datachunk);
        $class_obj = isset($datachunk->class) ? $datachunk->class : null;
        $function = isset($datachunk->call) ? $datachunk->call : null;
        $params = isset($datachunk->params) ? $datachunk->params : array("empty" => true);

        if (isset($class_obj)) {
            include_once('classes/controllers/'.$class_obj.'.php');
            $xe_class = new $class_obj();
        } else {
            // TODO: If we want custom logging for this plugin then replace.
            error_log("\nXEAjax => Rejected, no file specified!!!");
            die (json_encode(array("success" => "false")));
        }

        // now let's call the method
        $left_over_data = null;
        if (method_exists($xe_class, $function)) {
            $left_over_data = call_user_func(array($xe_class, $function), $params);
        } else {
            // TODO: If we want custom logging for this plugin then replace.
            error_log("\nXEAjax.php => Rejected, method does not exist!!!");
            die (json_encode(array("success" => "false")));
        }

        return array('data' => json_encode($left_over_data));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function XEAjax_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'JSON encoded goodness')
            )
        );
    }
}
