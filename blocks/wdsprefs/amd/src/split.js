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
 * @package
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo & David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'],
    function($) {
    'use strict';
    return {
        /**
         * This is the starting function for the splitter.
         */
        init: function() {
            var available;
            available = $("select[name^='before']");
            return $("#id_save").click(function() {
            // $("#id_save").click(function() {
                var value;
                // console.log("SPLIT: clicked the save button BETCH!");
                if (available && $(available).children().length > 0) {
                    $("#split_error").text("You must split all sections.");
                    return false;
                } else if (available) {
                    value = true;
                    $("select[name^='shell_']").each(function(index, select) {
                        value = value && $(select).children().length >= 1;
                        return value;
                    });

                    if (!value) {
                        $("#split_error").text("Each shell must have at least one section.");
                    }
                    return value;
                } else {
                    return true;
                }
            });
        }
    };
});
